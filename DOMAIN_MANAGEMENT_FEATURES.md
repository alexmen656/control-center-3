# Domain Management Features - Super Admin & Main Domain

## Overview
Diese Dokumentation beschreibt die erweiterten Domain-Management-Funktionen für Super Admin Benutzer (userID == 152) und die Main Domain Nutzung in Web Builder und Codespaces.

---

## 1. Super Admin Features

### Super Admin Rechte
**Benutzer mit userID == 152** haben erweiterte Rechte im Domain-Management:

#### A) Custom Domain Auswahl
- In **Project Info** und **Web Builder** können Super Admins JEDE Domain aus dem Domain Management System auswählen
- Normale Benutzer sehen nur ihre eigenen Domains
- Super Admins können Domains für beliebige Projekte konfigurieren

#### B) Subdomain Erstellung von Custom Domains
- Super Admins können Subdomains von JEDER Domain im Domain Management erstellen
- Format: `subdomain.custom-domain.com`
- Beispiel: Wenn `myapp.com` im Domain Management existiert, kann Super Admin `api.myapp.com` für ein Projekt erstellen

#### C) Verfügbarkeit
Super Admin Features sind verfügbar in:
1. **Project Info** ([src/views/ProjectInfo.vue](src/views/ProjectInfo.vue))
   - Custom Domain Dropdown zeigt ALLE Domains
   - Subdomain kann optional hinzugefügt werden
   
2. **Web Builder** ([src/views/WebBuilderView.vue](src/views/WebBuilderView.vue))
   - Custom Domain Auswahl für Web Builder Projekte
   - Subdomain-Erstellung möglich

### Backend Implementation
- **[backend/domains.php](backend/domains.php)**: `list_available` Endpoint
  - Liefert `is_super_admin` Flag für userID 152
  - Enthält `features` Array mit Super Admin Capabilities
  
- **[backend/project_domain.php](backend/project_domain.php)**: Domain Connection
  - Unterstützt `domain_type` Parameter (`subdomain`, `custom`, `main`)
  - `custom_base_domain` Parameter für Subdomain-Erstellung
  - Super Admin Check für Custom Domains

---

## 2. Main Domain Exclusive Usage

### Konzept
Die **Main Domain** (konfiguriert in Project Info) kann NUR von EINEM System gleichzeitig verwendet werden:
- **Web Builder** ODER
- **Codespace**

### Conflict Detection
Bidirektionale Prüfung verhindert gleichzeitige Nutzung:

#### A) Web Builder → Codespace Check
**Backend**: [backend/web_builder_domains.php](backend/web_builder_domains.php) (Zeile ~140)
```php
$codespaceCheck = query("
    SELECT cd.id FROM codespace_domains cd
    JOIN project_codespaces pc ON cd.codespace_id = pc.id
    WHERE pc.project_id = '$projectID' AND cd.is_main = 1
");

if (mysqli_num_rows($codespaceCheck) > 0) {
    echo json_encode(['error' => 'Die Main Domain wird bereits von einem Codespace verwendet.']);
}
```

#### B) Codespace → Web Builder Check
**Backend**: [backend/codespace_connections.php](backend/codespace_connections.php) (Zeile ~520)
```php
$webBuilderCheck = query("
    SELECT id FROM web_builder_domains 
    WHERE projectID = '$project_link' AND domain = '$base_domain'
");

if (mysqli_num_rows($webBuilderCheck) > 0) {
    echo json_encode(['error' => 'Die Main Domain wird bereits vom Web Builder verwendet.']);
}
```

### Frontend Implementation

#### Web Builder
**Datei**: [src/views/WebBuilderView.vue](src/views/WebBuilderView.vue)

**UI Features**:
- Radio Button Auswahl: "Subdomain" oder "Main Domain verwenden"
- Warning Nachricht: "Die Main Domain kann nur von einem System (Web Builder ODER Codespace) gleichzeitig genutzt werden"
- Conditional Rendering basierend auf `useMainDomain` Auswahl

**Data Properties**:
```typescript
const useMainDomain = ref(false)  // Main Domain usage toggle
const mainDomain = ref('')        // Main Domain value from Project Info
```

**Save Function**:
```typescript
const saveWebBuilderDomain = async () => {
  const response = await axios.post('web_builder_domains.php', qs.stringify({
    action: 'save',
    project: projectName.value,
    subdomain: webBuilderDomain.value.subdomain,
    main_domain: mainDomain.value,
    is_enabled: webBuilderDomain.value.is_enabled ? 'true' : 'false',
    use_main_domain: useMainDomain.value ? 'true' : 'false'  // NEW
  }))
}
```

#### Codespaces
**Datei**: [src/views/ManageCodespaces.vue](src/views/ManageCodespaces.vue)

**UI Features**:
- Radio Button Auswahl: "Subdomain" oder "Haupt-Domain"
- Disabled State wenn Main Domain bereits vergeben
- Info Nachricht: "Die Main Domain kann nur von einem System (Codespace ODER Web Builder) gleichzeitig genutzt werden"
- Zeigt an welcher Codespace die Main Domain verwendet

**Data Properties**:
```typescript
const domainType = ref('subdomain')  // 'subdomain' or 'main'
const domainInfo = ref({
  base_domain: '',
  main_domain_taken: false,
  main_domain_codespace: ''
})
```

**Connect Function**:
```typescript
const connectDomain = async () => {
  const data = {
    action: 'connect_domain',
    codespace_id: selectedCodespace.value.id,
    user_id: user.userID,
    is_main: domainType.value === 'main' ? 'true' : 'false'  // NEW
  }
  
  if (domainType.value === 'subdomain') {
    data.subdomain = domainInput.value
  }
}
```

---

## 3. Database Schema

### web_builder_domains
```sql
CREATE TABLE web_builder_domains (
  id INT PRIMARY KEY AUTO_INCREMENT,
  projectID VARCHAR(255),
  subdomain VARCHAR(255),
  domain VARCHAR(255),  -- Kann Main Domain sein wenn use_main_domain = 1
  main_domain VARCHAR(255),
  is_enabled TINYINT(1),
  ssl_status VARCHAR(50),
  cloudflare_record_id VARCHAR(255)
)
```

### codespace_domains
```sql
CREATE TABLE codespace_domains (
  id INT PRIMARY KEY AUTO_INCREMENT,
  codespace_id INT,
  domain VARCHAR(255),
  is_main TINYINT(1),  -- 1 = Main Domain, 0 = Subdomain
  user_id INT,
  cloudflare_record_id VARCHAR(255)
)
```

### domains (Domain Management)
```sql
CREATE TABLE domains (
  id INT PRIMARY KEY AUTO_INCREMENT,
  user_id INT,
  domain VARCHAR(255),
  registrar VARCHAR(255),
  buy_date DATE,
  expiry_date DATE,
  cloudflare_zone_id VARCHAR(255),
  auto_renew TINYINT(1),
  notes TEXT
)
```

---

## 4. API Endpoints

### backend/domains.php
**Action**: `list_available`
- Liefert alle verfügbaren Domains für Domain-Auswahl
- Super Admin erhält alle Domains
- Normale Benutzer nur eigene Domains
- Response enthält `is_super_admin` Flag und `features` Array

### backend/web_builder_domains.php
**Action**: `save`
- **Parameters**:
  - `project`: Projektname
  - `subdomain`: Subdomain (optional wenn use_main_domain = true)
  - `main_domain`: Main Domain von Project Info
  - `is_enabled`: true/false
  - `use_main_domain`: true/false (NEW)
- **Conflict Check**: Prüft ob Codespace Main Domain verwendet
- **Response**: success/error mit Fehlermeldung bei Konflikt

### backend/codespace_connections.php
**Action**: `connect_domain`
- **Parameters**:
  - `codespace_id`: Codespace ID
  - `user_id`: User ID
  - `is_main`: true/false (NEW)
  - `subdomain`: Subdomain (nur wenn is_main = false)
- **Conflict Check**: Prüft ob Web Builder Main Domain verwendet
- **Response**: success/error mit Fehlermeldung bei Konflikt

### backend/project_domain.php
**Action**: `connect`
- **Parameters**:
  - `project`: Projektname
  - `domain_type`: 'subdomain' | 'custom' | 'main'
  - `custom_base_domain`: Custom Domain für Subdomain (nur Super Admin)
  - `subdomain`: Optional Subdomain
- **Super Admin Check**: Custom Domains nur für userID 152

---

## 5. User Flow Examples

### Beispiel 1: Web Builder mit Main Domain
1. User konfiguriert Main Domain in Project Info: `example.com`
2. User öffnet Web Builder für Projekt
3. User wählt "Main Domain verwenden" Radio Button
4. System prüft: Wird Main Domain von Codespace verwendet?
   - **JA** → Error: "Die Main Domain wird bereits von einem Codespace verwendet"
   - **NEIN** → Main Domain wird für Web Builder gespeichert
5. Web Builder ist nun unter `example.com` erreichbar

### Beispiel 2: Codespace mit Main Domain (Konflikt)
1. Web Builder verwendet bereits Main Domain `example.com`
2. User versucht Main Domain für Codespace zu verbinden
3. System zeigt: "Haupt-Domain (vergeben)" - Radio Button disabled
4. Info: "Die Main Domain wird bereits vom Web Builder verwendet"
5. User muss zuerst Web Builder Main Domain entfernen

### Beispiel 3: Super Admin Custom Subdomain
1. Super Admin erstellt Domain `custom-client.com` in Domain Management
2. Super Admin öffnet Project Info für beliebiges Projekt
3. Super Admin wählt Domain Type: "Custom Domain"
4. Dropdown zeigt `custom-client.com` (obwohl nicht eigene Domain)
5. Super Admin gibt Subdomain ein: `api`
6. Domain wird verbunden: `api.custom-client.com`

---

## 6. Error Messages

### Web Builder Errors
- `"Bitte zuerst eine Main Domain in Project Info konfigurieren"` - Keine Main Domain konfiguriert
- `"Die Main Domain wird bereits von einem Codespace verwendet. Bitte zuerst dort die Main Domain entfernen."` - Konflikt mit Codespace
- `"Subdomain muss mindestens 3 Zeichen lang sein"` - Validation Error
- `"Diese Subdomain ist bereits vergeben"` - Subdomain Konflikt

### Codespace Errors
- `"Kein Vercel-Projekt verbunden. Bitte zuerst ein Vercel-Projekt verbinden."` - Vercel Connection fehlt
- `"Die Main Domain wird bereits vom Web Builder verwendet. Bitte zuerst dort die Main Domain entfernen."` - Konflikt mit Web Builder
- `"Ein anderer Codespace verwendet bereits die Haupt-Domain"` - Codespace Konflikt
- `"Domain bereits vergeben"` - Domain schon in Nutzung

---

## 7. Testing Checklist

### Super Admin Features
- [ ] Login als Super Admin (userID 152)
- [ ] Prüfen ob alle Domains in Dropdown sichtbar
- [ ] Custom Domain für fremdes Projekt verbinden
- [ ] Subdomain von Custom Domain erstellen
- [ ] Prüfen ob normale User nur eigene Domains sehen

### Main Domain Web Builder
- [ ] Main Domain in Project Info konfigurieren
- [ ] Web Builder: "Main Domain verwenden" auswählen
- [ ] Speichern und prüfen ob Domain funktioniert
- [ ] Versuchen Main Domain in Codespace zu verbinden → Error erwartet
- [ ] Web Builder Main Domain entfernen
- [ ] Nun Codespace Main Domain verbinden sollte funktionieren

### Main Domain Codespace
- [ ] Main Domain für Codespace verbinden
- [ ] Prüfen ob Codespace unter Main Domain erreichbar
- [ ] Versuchen Web Builder Main Domain zu aktivieren → Error erwartet
- [ ] Codespace Domain trennen
- [ ] Nun Web Builder Main Domain sollte funktionieren

### Conflict Resolution
- [ ] Web Builder verwendet Main Domain
- [ ] Codespace UI zeigt "Haupt-Domain (vergeben)"
- [ ] Radio Button disabled
- [ ] Info zeigt welcher Codespace/Web Builder verwendet
- [ ] Nach Entfernen ist Option wieder verfügbar

---

## 8. MCP Tools Documentation

### MCP Tool: domains.php
Die MCP Tool Description ist im Backend File dokumentiert:
**Datei**: [backend/domains.php](backend/domains.php) (Zeilen 1-30)

**Beschreibung**:
```php
/*
 * MCP Tool: Domain Management
 * 
 * Super Admin Features (userID == 152):
 * - Can select ANY domain from Domain Management in Project Info and Web Builder
 * - Can create subdomains from any custom domain
 * - Has access to all domains via list_available endpoint
 * 
 * Main Domain Exclusive Usage:
 * - Main Domain can only be used by ONE system at a time
 * - Either Web Builder OR Codespace can use the Main Domain
 * - Bidirectional conflict detection prevents simultaneous usage
 * 
 * Endpoints:
 * - list: Get all domains for current user (or all for Super Admin)
 * - list_available: Get available domains for project/webbuilder configuration
 * - add: Add new domain to management
 * - update: Update domain information
 * - delete: Remove domain from management
 */
```

---

## 9. Future Enhancements

### Mögliche Erweiterungen
1. **Multi-Domain Support**: Erlauben mehrerer Domains pro Web Builder/Codespace
2. **Domain Transfer**: Einfaches Übertragen von Main Domain zwischen Systemen ohne manuelles Entfernen
3. **Domain Analytics**: Tracking welche Domains am meisten verwendet werden
4. **Auto-SSL**: Automatisches SSL-Zertifikat Management für alle Domains
5. **Domain Monitoring**: Uptime und Performance Monitoring für verbundene Domains
6. **Bulk Operations**: Mehrere Domains gleichzeitig verbinden/trennen

---

## 10. Support & Troubleshooting

### Häufige Probleme

**Problem**: Main Domain Option nicht sichtbar in Web Builder
- **Lösung**: Main Domain muss zuerst in Project Info konfiguriert werden

**Problem**: "Main Domain (vergeben)" aber kein System verwendet sie
- **Lösung**: Datenbank Inkonsistenz, prüfen:
  ```sql
  SELECT * FROM web_builder_domains WHERE domain = 'example.com';
  SELECT * FROM codespace_domains WHERE is_main = 1;
  ```

**Problem**: Super Admin sieht nicht alle Domains
- **Lösung**: Prüfen ob userID == 152 in Session, Backend-Logs prüfen

**Problem**: Conflict Error aber Domain wurde entfernt
- **Lösung**: Browser Cache leeren, Domain Info neu laden

### Debug Commands
```bash
# Check Web Builder Main Domain Usage
mysql> SELECT wb.*, p.project FROM web_builder_domains wb 
       JOIN projects p ON wb.projectID = p.project 
       WHERE wb.domain = (SELECT domain FROM control_center_project_domains WHERE project = 'YOUR_PROJECT');

# Check Codespace Main Domain Usage
mysql> SELECT cd.*, pc.project_id FROM codespace_domains cd 
       JOIN project_codespaces pc ON cd.codespace_id = pc.id 
       WHERE cd.is_main = 1 AND pc.project_id = 'YOUR_PROJECT';

# Check Super Admin Domains
mysql> SELECT * FROM domains WHERE user_id = 152 OR 152 = 152;
```

---

## Changelog

### Version 1.0 (Current)
- ✅ Super Admin Custom Domain Auswahl
- ✅ Subdomain Erstellung von Custom Domains
- ✅ Main Domain Exclusive Usage für Web Builder
- ✅ Main Domain Exclusive Usage für Codespaces
- ✅ Bidirektionale Conflict Detection
- ✅ MCP Tools Documentation in backend/domains.php
- ✅ UI Warning Messages für Konflikte
- ✅ Frontend Validation und Error Handling

---

**Erstellt**: 2024
**Letzte Aktualisierung**: 2024
**Version**: 1.0
**Autor**: GitHub Copilot
