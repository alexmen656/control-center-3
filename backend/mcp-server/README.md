# Control Center MCP Server

Model Context Protocol (MCP) Server für das Control Center CMS. Ermöglicht AI Agents Zugriff auf alle CMS-Funktionen über HTTP oder STDIO.

## Features

- 🔐 JWT-basierte Authentifizierung (Control Center Login-Token)
- 🛠️ 40+ Tools für CMS-Management
- 📚 Resources für schnellen Datenzugriff
- 🌐 HTTP-Transport für Web-Integration
- 📡 STDIO-Transport für lokale MCP-Clients

## Installation

```bash
cd backend/mcp-server
npm install
```

## Konfiguration

### Umgebungsvariablen

```bash
# HTTP Server Port (default: 3001)
MCP_PORT=3001

# CMS Backend URL
CMS_BACKEND_URL=https://alex.polan.sk/control-center/backend

# Für STDIO: JWT Token
CMS_JWT_TOKEN=your_jwt_token_here
```

## Starten

### HTTP Server

```bash
npm start
# oder für Development mit Auto-Reload
npm run dev
```

Der Server läuft dann auf `http://localhost:3001`

### STDIO Server (für lokale MCP-Clients)

```bash
CMS_JWT_TOKEN=your_token npm run stdio
```

## API Endpoints

### Server Info

```
GET /mcp
```

Gibt Server-Informationen zurück.

### Health Check

```
GET /health
```

### Tools auflisten

```
GET /mcp/tools
Authorization: Bearer <jwt_token>
```

### Tool ausführen

```
POST /mcp/tools/:toolName
Authorization: Bearer <jwt_token>
Content-Type: application/json

{
  "param1": "value1",
  "param2": "value2"
}
```

### Batch-Ausführung

```
POST /mcp/batch
Authorization: Bearer <jwt_token>
Content-Type: application/json

{
  "operations": [
    { "tool": "project_list", "arguments": {} },
    { "tool": "page_list", "arguments": { "project": "my-project" } }
  ]
}
```

### Resources auflisten

```
GET /mcp/resources
Authorization: Bearer <jwt_token>
```

### Resource lesen

```
GET /mcp/resources/projects/my-project
Authorization: Bearer <jwt_token>
```

## Verfügbare Tools

### Projekt-Management

| Tool | Beschreibung |
|------|-------------|
| `project_list` | Alle Projekte auflisten |
| `project_create` | Neues Projekt erstellen |
| `project_get` | Projekt-Details abrufen |
| `project_update` | Projekt aktualisieren |
| `project_delete` | Projekt löschen |
| `project_get_services` | Services/Module eines Projekts |
| `project_get_users` | Projekt-Benutzer auflisten |
| `project_add_user` | Benutzer zum Projekt hinzufügen |
| `project_apply_template` | Projekt aus Template erstellen |
| `project_list_templates` | Verfügbare Templates |

### Seiten-Management

| Tool | Beschreibung |
|------|-------------|
| `page_list` | Seiten eines Projekts auflisten |
| `page_get` | Seiten-Details |
| `page_create` | Neue Seite erstellen |
| `page_update` | Seite aktualisieren |
| `page_delete` | Seite löschen |
| `page_duplicate` | Seite duplizieren |
| `page_get_components` | Komponenten einer Seite |
| `page_update_components` | Komponenten aktualisieren |

### API-Management

| Tool | Beschreibung |
|------|-------------|
| `api_list` | APIs eines Projekts |
| `api_create` | Neue API erstellen |
| `api_get` | API-Details |
| `api_delete` | API löschen |
| `api_endpoint_create` | Endpoint erstellen |
| `api_endpoint_list` | Endpoints auflisten |
| `api_subscribe` | API abonnieren |
| `api_available_list` | Verfügbare APIs |
| `api_generate_key` | API-Key generieren |

### Content-Management

| Tool | Beschreibung |
|------|-------------|
| `content_form_list` | Formulare auflisten |
| `content_form_create` | Formular erstellen |
| `content_form_submissions` | Formular-Einsendungen |
| `content_newsletter_list` | Newsletter-Abonnenten |
| `content_newsletter_send` | Newsletter senden |
| `content_tasks_list` | Tasks auflisten |
| `content_task_create` | Task erstellen |
| `content_task_update` | Task aktualisieren |
| `content_notepad_get` | Notepad lesen |
| `content_notepad_save` | Notepad speichern |

### Datei-Management

| Tool | Beschreibung |
|------|-------------|
| `file_list` | Dateien auflisten |
| `file_read` | Datei lesen |
| `file_create` | Datei erstellen |
| `file_update` | Datei aktualisieren |
| `file_delete` | Datei löschen |
| `file_rename` | Datei umbenennen |
| `file_mkdir` | Verzeichnis erstellen |
| `file_search` | Dateien suchen |
| `file_git_status` | Git Status |
| `file_git_commit` | Git Commit |
| `file_git_push` | Git Push |
| `file_git_pull` | Git Pull |

### User-Management

| Tool | Beschreibung |
|------|-------------|
| `user_profile` | Eigenes Profil |
| `user_update_profile` | Profil aktualisieren |
| `user_list_by_project` | Projekt-Benutzer |
| `user_remove_from_project` | Benutzer entfernen |
| `user_get_notifications` | Benachrichtigungen |
| `user_mark_notification_read` | Als gelesen markieren |
| `user_get_bookmarks` | Lesezeichen |
| `user_add_bookmark` | Lesezeichen hinzufügen |
| `user_delete_bookmark` | Lesezeichen löschen |

## Beispiele

### Projekt erstellen

```javascript
// HTTP Request
const response = await fetch('http://localhost:3001/mcp/tools/project_create', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${jwtToken}`,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    name: 'Mein neues Projekt',
    icon: 'rocket-outline'
  })
});

const result = await response.json();
console.log(result);
```

### Seite erstellen

```javascript
const response = await fetch('http://localhost:3001/mcp/tools/page_create', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${jwtToken}`,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    project: 'mein-neues-projekt',
    name: 'Über uns',
    slug: 'about',
    title: 'Über uns - Mein Projekt',
    metaDescription: 'Erfahren Sie mehr über uns'
  })
});
```

### Batch-Operation

```javascript
const response = await fetch('http://localhost:3001/mcp/batch', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${jwtToken}`,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    operations: [
      {
        tool: 'project_create',
        arguments: { name: 'Neues Projekt' }
      },
      {
        tool: 'page_create',
        arguments: {
          project: 'neues-projekt',
          name: 'Home',
          isHome: true
        }
      }
    ]
  })
});
```

## MCP Client Konfiguration

### VS Code / Claude Desktop

Füge folgendes zu deiner MCP-Konfiguration hinzu:

```json
{
  "mcpServers": {
    "control-center": {
      "command": "node",
      "args": ["/path/to/backend/mcp-server/stdio-server.js"],
      "env": {
        "CMS_JWT_TOKEN": "your_jwt_token",
        "CMS_BACKEND_URL": "https://alex.polan.sk/control-center/backend"
      }
    }
  }
}
```

### HTTP Client

Für HTTP-basierte MCP-Clients:

```json
{
  "mcpServers": {
    "control-center": {
      "transport": "http",
      "url": "http://localhost:3001/mcp",
      "headers": {
        "Authorization": "Bearer your_jwt_token"
      }
    }
  }
}
```

## JWT Token bekommen

1. Login im Control Center
2. Token aus localStorage: `localStorage.getItem('controlCenter_auth_token')`
3. Oder über Login-API:

```javascript
const response = await fetch('/backend/login.php', {
  method: 'POST',
  body: new URLSearchParams({
    login: 'true',
    email: 'your@email.com',
    password: 'your_password'
  })
});
const data = await response.json();
const token = data.token;
```

## Security

- Alle Requests erfordern einen gültigen JWT-Token
- Token-Validierung erfolgt gegen das CMS-Backend
- CORS ist standardmäßig aktiviert
- Projektberechtigungen werden vom Backend geprüft

## Entwicklung

### Neues Tool hinzufügen

1. Tool-Definition in `tools/<category>.js` hinzufügen
2. Handler-Funktion implementieren
3. Im `handleXyzTool` Switch-Case registrieren

```javascript
// In tools/mytools.js
export const myTools = [
  {
    name: 'my_new_tool',
    description: 'Does something cool',
    inputSchema: {
      type: 'object',
      properties: {
        param1: { type: 'string', description: 'First parameter' }
      },
      required: ['param1']
    }
  }
];

export async function handleMyTool(toolName, args, context) {
  switch (toolName) {
    case 'my_new_tool':
      return await myNewToolHandler(args, context);
    // ...
  }
}
```

4. Im Server registrieren (server.js)
