# Web Builder Backend - Control Center Project Integration

## Übersicht

Das Web Builder Backend wurde so umgebaut, dass **jedes Web Builder Projekt zwingend mit einem Control Center Projekt verknüpft sein muss**. Dies gewährleistet eine konsistente Rechteverwaltung und Integration in das Control Center Ökosystem.

## Änderungen

### 1. Datenbankschema

**Neue Spalte in `control_center_modul_web_builder_projects`:**
- `project_id` (varchar(255), NOT NULL) - Referenz zu `projects.projectID`
- UNIQUE constraint - Ein Control Center Projekt kann nur ein Web Builder Projekt haben

### 2. Berechtigungssystem

**Zweistufige Validierung:**
1. **User-Berechtigung**: User muss Besitzer des Web Builder Projekts sein (`user_id`)
2. **Projekt-Zugriff**: User muss Zugriff auf das verknüpfte CC Projekt haben (`control_center_user_projects`)

**Validiert in allen Endpoints:**
- `projects.php` - Web Builder Projekt CRUD
- `pages.php` - Seiten CRUD
- `components.php` - Komponenten CRUD

### 3. API Änderungen

#### POST `/backend/web-builder/projects.php` - Projekt erstellen
**Neue Pflichtfelder:**
```json
{
  "name": "Mein Web Builder Projekt",
  "description": "Beschreibung (optional)",
  "project_id": "cc-project-id"  // NEU: Pflichtfeld!
}
```

**Validierung:**
- User muss Zugriff auf das CC Projekt haben
- CC Projekt darf noch kein Web Builder Projekt haben (1:1 Beziehung)

**Response:**
```json
{
  "status": "success",
  "message": "Project created successfully",
  "data": {
    "id": 1,
    "user_id": 123,
    "project_id": "cc-project-id",
    "name": "Mein Web Builder Projekt",
    "control_center_project": {
      "projectID": "cc-project-id",
      "name": "Control Center Projekt",
      "link": "projekt-slug",
      "icon": "icon.png"
    },
    "pages": [...]
  }
}
```

#### GET `/backend/web-builder/projects.php` - Projekte abrufen
**Gefilterte Liste:**
- Zeigt nur Web Builder Projekte an, bei denen der User Zugriff auf das verknüpfte CC Projekt hat
- JOIN mit `control_center_user_projects` für automatische Filterung

**Response enthält CC Projekt Info:**
```json
{
  "status": "success",
  "data": [
    {
      "id": 1,
      "project_id": "cc-project-id",
      "name": "Web Builder Projekt",
      "control_center_project": {
        "projectID": "cc-project-id",
        "name": "CC Projekt Name",
        "link": "projekt-slug"
      },
      "pages": [...]
    }
  ]
}
```

#### GET `/backend/web-builder/available_projects.php` - Verfügbare CC Projekte (NEU)
**Zweck:** Zeigt CC Projekte an, für die noch kein Web Builder Projekt existiert

**Response:**
```json
{
  "status": "success",
  "data": [
    {
      "projectID": "projekt-1",
      "name": "Verfügbares Projekt",
      "link": "projekt-slug",
      "icon": "icon.png"
    }
  ]
}
```

### 4. Neue Helper-Funktionen in `api_base.php`

```php
// Prüft ob User Zugriff auf ein CC Projekt hat
userHasProjectAccess($userId, $projectId): bool

// Holt CC Projekt Details
getControlCenterProject($projectId): array|null

// Holt alle CC Projekte eines Users
getUserControlCenterProjects($userId): array
```

### 5. Fehlerbehandlung

**Neue Fehlercodes:**

- **400**: `project_id` fehlt beim Erstellen
- **403**: User hat keinen Zugriff auf das CC Projekt
- **409**: CC Projekt hat bereits ein Web Builder Projekt

**Beispiele:**
```json
{
  "error": true,
  "message": "Access denied: You do not have access to this Control Center project"
}
```

```json
{
  "error": true,
  "message": "A web builder project already exists for this Control Center project"
}
```

## Migration

### Für neue Installationen:
1. Führe `schema.sql` aus - Tabellen werden mit `project_id` erstellt

### Für bestehende Installationen:
1. Führe `migration_add_project_id.sql` aus (fügt `project_id` Spalte hinzu)
2. Weise jedem bestehenden Web Builder Projekt manuell ein CC Projekt zu:
```sql
UPDATE control_center_modul_web_builder_projects 
SET project_id = 'cc-projekt-id' 
WHERE id = 1;
```
3. Aktiviere den UNIQUE constraint (siehe migration_add_project_id.sql)

### Migrationsscript überprüfen:
```sql
-- Projekte ohne project_id finden:
SELECT id, name, user_id 
FROM control_center_modul_web_builder_projects 
WHERE project_id IS NULL;

-- Verfügbare CC Projekte eines Users finden:
SELECT p.projectID, p.name, p.link 
FROM projects p
INNER JOIN control_center_user_projects up ON p.projectID = up.projectID
WHERE up.userID = 123;  -- User ID hier einsetzen
```

## Frontend Integration

### Projekt erstellen:
```typescript
// 1. Verfügbare CC Projekte laden
const availableProjects = await fetch('/backend/web-builder/available_projects.php', {
  headers: { 'Authorization': `Bearer ${token}` }
});

// 2. User wählt CC Projekt aus Dropdown

// 3. Web Builder Projekt erstellen
await fetch('/backend/web-builder/projects.php', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    name: 'Mein Web Builder',
    project_id: selectedProjectId  // WICHTIG!
  })
});
```

### Projekt bearbeiten:
```typescript
// project_id kann NICHT geändert werden
await fetch('/backend/web-builder/projects.php?id=1', {
  method: 'PUT',
  body: JSON.stringify({
    name: 'Neuer Name',
    description: 'Neue Beschreibung'
    // project_id wird ignoriert/abgelehnt
  })
});
```

## Sicherheit

### Berechtigungs-Flow:

1. **JWT Token Validierung** → User ID
2. **Web Builder Projekt Check** → User ist Besitzer?
3. **CC Projekt Access Check** → User hat Zugriff auf verknüpftes CC Projekt?

Alle drei Checks müssen erfolgreich sein!

### Was passiert wenn User aus CC Projekt entfernt wird?

- User verliert sofort Zugriff auf das Web Builder Projekt
- Andere Team-Mitglieder mit CC Projekt Zugriff behalten Zugriff
- Web Builder Projekt bleibt bestehen

## Testing

### Test-Szenarien:

1. ✅ User erstellt Web Builder Projekt für sein CC Projekt
2. ✅ User hat Zugriff auf Web Builder Projekt (ist im CC Projekt)
3. ❌ User versucht Web Builder Projekt ohne `project_id` zu erstellen
4. ❌ User versucht Web Builder Projekt für fremdes CC Projekt zu erstellen
5. ❌ User versucht zweites Web Builder Projekt für selbes CC Projekt zu erstellen
6. ❌ User wird aus CC Projekt entfernt → Zugriff auf Web Builder verloren

## Datenbankstruktur

```
projects (Control Center)
  └─ projectID (PK)

control_center_user_projects (Control Center)
  ├─ userID (FK → control_center_users)
  └─ projectID (FK → projects)

control_center_modul_web_builder_projects (Web Builder)
  ├─ id (PK)
  ├─ user_id (FK → control_center_users)
  ├─ project_id (FK → projects) ⭐ NEU: UNIQUE, NOT NULL
  └─ ...

control_center_modul_web_builder_pages
  ├─ id (PK)
  ├─ project_id (FK → web_builder_projects)
  └─ ...

control_center_modul_web_builder_components
  ├─ id (PK)
  ├─ page_id (FK → web_builder_pages)
  └─ ...
```

## Zusammenfassung

✅ **Web Builder Projekte sind jetzt fest mit CC Projekten verbunden**
✅ **Doppelte Berechtigung: User + Projekt-Zugriff**
✅ **1:1 Beziehung: Ein CC Projekt = max. ein Web Builder Projekt**
✅ **Automatische Zugriffskontrolle über bestehende CC Strukturen**
✅ **Backward-compatible Migration möglich**
