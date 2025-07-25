# Monaco IDE Backend API Documentation

## Overview
Das Backend für deinen Web IDE Editor besteht aus drei Hauptkomponenten:

1. **File API** (`file_api.php`) - Dateiverwaltung im geschützten `/data/` Verzeichnis
2. **Git API** (`monaco_git_api.php`) - Git-Operations für Change Detection und Commits
3. **Integration** - Anbindung an die bestehende GitHub und Vercel Integration

## File API (`file_api.php`)

### Datenspeicherung
- Alle Projektdateien werden in `/data/projects/{userID}/{projectName}/` gespeichert
- Das `/data/` Verzeichnis ist durch `.htaccess` geschützt
- Automatische Git-Repository Initialisierung für jedes Projekt

### Endpoints

#### GET Requests
```
GET /backend/file_api.php?project={PROJECT}&action=list
```
Listet alle Dateien im Projekt auf.

```
GET /backend/file_api.php?project={PROJECT}&action=read&file={FILEPATH}
```
Liest eine spezifische Datei.

```
GET /backend/file_api.php?project={PROJECT}&action=changes
```
Zeigt Git-Änderungen an.

```
GET /backend/file_api.php?project={PROJECT}&action=commits
```
Zeigt Git-Commit History an.

#### POST Requests
```
POST /backend/file_api.php?project={PROJECT}
Content-Type: application/json

{
  "action": "create_file",
  "path": "filename.js",
  "content": "console.log('Hello');"
}
```
Erstellt eine neue Datei.

```
POST /backend/file_api.php?project={PROJECT}
Content-Type: application/json

{
  "action": "commit",
  "message": "Commit message",
  "files": ["file1.js", "file2.css"]
}
```
Erstellt einen Git-Commit.

#### PUT Requests
```
PUT /backend/file_api.php?project={PROJECT}
Content-Type: application/json

{
  "file": "filename.js",
  "content": "new content"
}
```
Aktualisiert eine existierende Datei.

#### DELETE Requests
```
DELETE /backend/file_api.php?project={PROJECT}
Content-Type: application/json

{
  "file": "filename.js"
}
```
Löscht eine Datei.

## Git API (`monaco_git_api.php`)

### Erweiterte Git-Funktionalität
- Echte Git-Status Detection
- Staging/Unstaging von Dateien
- Commit-Erstellung
- Change Tracking
- Diff-Anzeige

### Endpoints

#### GET Requests
```
GET /backend/monaco_git_api.php?project={PROJECT}&action=status
```
Git-Status mit detaillierten Änderungen.

```
GET /backend/monaco_git_api.php?project={PROJECT}&action=changes
```
Strukturierte Change-Liste (staged, unstaged, untracked).

```
GET /backend/monaco_git_api.php?project={PROJECT}&action=commits
```
Commit-History mit Metadaten.

```
GET /backend/monaco_git_api.php?project={PROJECT}&action=diff&file={FILEPATH}
```
Diff für eine spezifische Datei.

```
GET /backend/monaco_git_api.php?project={PROJECT}&action=branches
```
Liste aller Git-Branches.

#### POST Requests
```
POST /backend/monaco_git_api.php?project={PROJECT}
Content-Type: application/json

{
  "action": "stage",
  "file": "filename.js"
}
```
Staged eine Datei für den nächsten Commit.

```
POST /backend/monaco_git_api.php?project={PROJECT}
Content-Type: application/json

{
  "action": "unstage",
  "file": "filename.js"
}
```
Unstaged eine Datei.

```
POST /backend/monaco_git_api.php?project={PROJECT}
Content-Type: application/json

{
  "action": "commit",
  "message": "Commit message",
  "files": ["file1.js"]
}
```
Erstellt einen Git-Commit.

```
POST /backend/monaco_git_api.php?project={PROJECT}
Content-Type: application/json

{
  "action": "discard",
  "file": "filename.js"
}
```
Verwirft Änderungen an einer Datei.

## Frontend Integration

### MonacoSidebar.vue Erweiterungen

#### File Explorer
- Echte Dateiliste aus dem Backend
- Datei-Icons basierend auf Dateityp
- Create/Delete Funktionalität
- Klick zum Öffnen von Dateien

#### Git Changes Section
- Echte Git-Status Detection
- Stage/Unstage Buttons
- Discard Changes Funktionalität
- Commit mit echten Git-Operations

### Modul1View.vue Erweiterungen

#### Auto-Save
- Automatisches Speichern nach 1 Sekunde Inaktivität
- Integration mit File API

#### File Management
- Laden von Dateien aus dem Backend
- Event-basierte Kommunikation mit Sidebar
- Refresh-Funktionalität

## Sicherheit

### Datenschutz
- `/data/` Verzeichnis durch `.htaccess` geschützt
- User-spezifische Projektverzeichnisse
- Keine direkten Dateizugriffe über URL möglich

### Git-Sicherheit
- Lokale Git-Repositories
- Keine automatischen Remote-Pushes
- Validierung aller Git-Operationen

## Installation & Setup

1. **Verzeichnisse erstellen:**
   ```bash
   mkdir -p /path/to/control-center/data/projects
   chmod 755 /path/to/control-center/data
   ```

2. **Git verfügbar machen:**
   - Git muss auf dem Server installiert sein
   - PHP `exec()` Funktion muss aktiviert sein

3. **Test ausführen:**
   ```
   http://localhost/backend/test_monaco_setup.php
   ```

4. **Web Server Konfiguration:**
   - Apache: `.htaccess` wird automatisch verwendet
   - Nginx: Separate Konfiguration für `/data/` Directory nötig

## Beispiel-Usage im Frontend

```javascript
// Datei laden
const loadFile = async (filename) => {
  const response = await axios.get(`/backend/file_api.php?project=${project}&action=read&file=${filename}`)
  return response.data.content
}

// Datei speichern
const saveFile = async (filename, content) => {
  await axios.put(`/backend/file_api.php?project=${project}`, {
    file: filename,
    content: content
  })
}

// Git-Änderungen laden
const loadChanges = async () => {
  const response = await axios.get(`/backend/monaco_git_api.php?project=${project}&action=changes`)
  return response.data.changes
}

// Commit erstellen
const commit = async (message) => {
  await axios.post(`/backend/monaco_git_api.php?project=${project}`, {
    action: 'commit',
    message: message
  })
}
```

## Debugging

### Log-Files
- PHP-Errors werden in den Standard PHP-Logs aufgezeichnet
- Git-Operationen loggen Errors in der Console

### Common Issues
1. **"Not a git repository"** - Git-Repository nicht initialisiert
2. **"Permission denied"** - Verzeichnis-Permissions prüfen
3. **"exec() disabled"** - PHP `exec()` Funktion aktivieren
4. **CORS-Errors** - API-Headers sind bereits gesetzt

## Erweiterungen

### GitHub Integration
Die bestehende `github_api.php` kann erweitert werden um:
- Push-Operations zu GitHub
- Pull-Requests erstellen
- Branch-Management

### Vercel Integration
Die bestehende `vercel_api.php` funktioniert weiterhin:
- Automatische Deployments
- Preview-URLs
- Build-Status

Diese Backend-Implementierung gibt dir eine solide Basis für deinen Web IDE Editor mit echter Datenpersistierung und Git-Funktionalität!
