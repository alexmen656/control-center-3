# Fringelo MCP Server

Model Context Protocol (MCP) Server für das Fringelo CMS. Ermöglicht AI Agents Zugriff auf alle CMS-Funktionen über HTTP oder STDIO.

## Features

- 🔐 JWT-basierte Authentifizierung (Fringelo Login-Token)
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
CMS_BACKEND_URL=https://api.fringelo.com/backend

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
| `file_upload_to_filesystem` | Datei ins Filesystem hochladen |
| `file_create_folder_in_filesystem` | Ordner im Filesystem erstellen |
| `file_get_signed_url` | Signierte URL für eine Datei generieren |
| `file_get_bulk_signed_urls` | Signierte URLs für mehrere Dateien generieren |

### Signed URLs für Filesystem-Bilder in Web Builder

Mit Signed URLs können Bilder aus dem Fringelo Filesystem sicher in Web Builder Komponenten verwendet werden.

**Workflow:**
1. Bild hochladen mit `file_upload_to_filesystem`
2. Signierte URL generieren mit `file_get_signed_url`
3. URL in Web Builder HTML verwenden: `<img data-image src="SIGNED_URL" alt="...">`

**Wichtig:** Das `data-image` Attribut ist erforderlich, damit das Bild im Web Builder Page Editor bearbeitbar ist.

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

### Web Builder

| Tool | Beschreibung |
|------|-------------|
| `webbuilder_project_list` | Alle Web Builder Projekte auflisten |
| `webbuilder_project_create` | Neues Web Builder Projekt erstellen |
| `webbuilder_project_get` | Projekt-Details abrufen |
| `webbuilder_project_update` | Projekt aktualisieren |
| `webbuilder_project_delete` | Projekt löschen |
| `webbuilder_page_list` | Seiten eines Projekts auflisten |
| `webbuilder_page_create` | Neue Seite erstellen |
| `webbuilder_page_get` | Seiten-Details |
| `webbuilder_page_update` | Seite aktualisieren |
| `webbuilder_page_delete` | Seite löschen |
| `webbuilder_components_get` | Komponenten einer Seite |
| `webbuilder_component_add` | Komponente hinzufügen |
| `webbuilder_component_update` | Komponente aktualisieren |
| `webbuilder_component_delete` | Komponente löschen |
| `webbuilder_components_replace_all` | Alle Komponenten ersetzen |
| `webbuilder_domain_get` | Web Builder Domain abrufen |
| `webbuilder_domain_configure` | Web Builder Domain konfigurieren (Subdomain oder Main Domain) |
| `webbuilder_domain_delete` | Domain löschen |
| `webbuilder_domains_list` | Alle Domains auflisten |
| `webbuilder_create_landing_page` | Landing Page mit Template erstellen |

**Hinweis**: Für Project Main Domain Konfiguration verwende `domain_connect_to_project` aus dem Domain Management Bereich.

### Domain Management (NEU)

Vollständiges Domain-Management mit Cloudflare-Integration und Super Admin Features.

**Super Admin Features (userID 152):**
- Kann JEDE Domain aus dem Domain Management für JEDES Projekt auswählen
- Kann Subdomains von Custom Domains erstellen (z.B. `api.custom-domain.com`)
- Hat Zugriff auf alle Domains in `domain_list_available`

**Main Domain Exclusive Usage:**
- Die Main Domain kann nur von EINEM System gleichzeitig genutzt werden
- Entweder Web Builder ODER Codespace kann die Main Domain verwenden
- Automatische Konflikt-Erkennung verhindert gleichzeitige Nutzung

| Tool | Beschreibung |
|------|-------------|
| `domain_list` | Alle verwalteten Domains auflisten (Super Admin: alle, User: nur eigene) |
| `domain_list_available` | Verfügbare Domains für Projekt-Konfiguration (Super Admin: alle, User: nur eigene) |
| `domain_add` | Neue Domain zum Management hinzufügen |
| `domain_update` | Domain-Informationen aktualisieren (Registrar, Daten, etc.) |
| `domain_delete` | Domain aus Management entfernen |
| `domain_fetch_cloudflare` | Alle Domains von Cloudflare importieren |
| `domain_connect_to_project` | Domain mit Projekt verbinden (Super Admin: custom domains, User: subdomains) |
| `domain_get_project` | Aktuelle Domain-Konfiguration eines Projekts abrufen |
| `domain_codespace_connect` | Domain mit Codespace verbinden (Subdomain oder Main Domain) |

**Hinweis**: Für Web Builder Domain-Konfiguration verwende die `webbuilder_domain_*` Tools im Web Builder Bereich.

### App Store Metadata (NEU)

Vollständige Kontrolle über App Store Connect Metadaten für AI-gesteuerte Lokalisierung.

| Tool | Beschreibung |
|------|-------------|
| `appstore_list_apps` | Alle Apps eines Projekts auflisten |
| `appstore_get_app` | App-Details mit allen Lokalisierungen |
| `appstore_browse_apps` | Apps aus App Store Connect Account durchsuchen |
| `appstore_connect_app` | Bestehende App Store App verbinden |
| `appstore_list_versions` | Alle Versionen einer App |
| `appstore_get_version` | Version mit Lokalisierungen und Screenshots |
| `appstore_create_version` | Neue App-Version erstellen |
| `appstore_list_app_localizations` | App-Level Lokalisierungen (Name, Subtitle) |
| `appstore_create_app_localization` | Neue Sprache zur App hinzufügen |
| `appstore_update_app_localization` | App-Lokalisierung aktualisieren |
| `appstore_list_version_localizations` | Version-Lokalisierungen (Description, Keywords) |
| `appstore_create_version_localization` | Neue Sprache zu Version hinzufügen |
| `appstore_update_version_localization` | Version-Lokalisierung aktualisieren |
| `appstore_bulk_update_localizations` | Mehrere Lokalisierungen auf einmal aktualisieren |
| `appstore_sync_pull` | Metadaten von App Store Connect abrufen |
| `appstore_sync_push` | Lokale Änderungen zu App Store Connect pushen |
| `appstore_get_credentials` | API Credentials Status prüfen |
| `appstore_set_credentials` | App Store Connect API Credentials setzen |
| `appstore_list_locales` | Alle unterstützten Locales auflisten |
| `appstore_dashboard` | Dashboard mit Übersicht |

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

### Web Builder Projekt erstellen

```javascript
// 1. Web Builder Projekt erstellen (verknüpft mit CC Projekt)
const response = await fetch('http://localhost:3001/mcp/tools/webbuilder_project_create', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${jwtToken}`,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    name: 'Meine Website',
    description: 'Eine moderne Landingpage',
    ccProjectId: 'my-project-id'  // Fringelo Projekt ID
  })
});
```

### App Store Automatisierung (AI Agent Beispiel)

```javascript
// AI Agent kann App Store Metadaten vollautomatisch lokalisieren

// 1. Apps aus Account abrufen
const apps = await fetch('http://localhost:3001/mcp/tools/appstore_browse_apps', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${jwtToken}`,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({ project: 'my-app-project' })
});

// 2. App verbinden
await fetch('http://localhost:3001/mcp/tools/appstore_connect_app', {
  method: 'POST',
  headers: { 'Authorization': `Bearer ${jwtToken}`, 'Content-Type': 'application/json' },
  body: JSON.stringify({
    project: 'my-app-project',
    appleAppId: '1234567890',
    bundleId: 'com.company.myapp',
    name: 'My Awesome App'
  })
});

// 3. Metadaten von Apple pullen
await fetch('http://localhost:3001/mcp/tools/appstore_sync_pull', {
  method: 'POST',
  headers: { 'Authorization': `Bearer ${jwtToken}`, 'Content-Type': 'application/json' },
  body: JSON.stringify({ project: 'my-app-project', appId: 1 })
});

// 4. Lokalisierungen in mehreren Sprachen aktualisieren (Bulk)
await fetch('http://localhost:3001/mcp/tools/appstore_bulk_update_localizations', {
  method: 'POST',
  headers: { 'Authorization': `Bearer ${jwtToken}`, 'Content-Type': 'application/json' },
  body: JSON.stringify({
    project: 'my-app-project',
    versionId: 1,
    localizations: [
      {
        locale: 'de-DE',
        description: 'Die beste App für...',
        keywords: 'produktivität,organisation,todo',
        whatsNew: 'Neue Funktionen in Version 2.0'
      },
      {
        locale: 'fr-FR',
        description: 'La meilleure application pour...',
        keywords: 'productivité,organisation,todo',
        whatsNew: 'Nouvelles fonctionnalités dans la version 2.0'
      },
      {
        locale: 'es-ES',
        description: 'La mejor aplicación para...',
        keywords: 'productividad,organización,todo',
        whatsNew: 'Nuevas funciones en la versión 2.0'
      }
    ]
  })
});

// 5. Änderungen zu Apple pushen
await fetch('http://localhost:3001/mcp/tools/appstore_sync_push', {
  method: 'POST',
  headers: { 'Authorization': `Bearer ${jwtToken}`, 'Content-Type': 'application/json' },
  body: JSON.stringify({ project: 'my-app-project', appId: 1 })
});
```

### Landing Page mit Template erstellen

```javascript
// Quick Action: Landing Page mit Hero, Features und CTA
const response = await fetch('http://localhost:3001/mcp/tools/webbuilder_create_landing_page', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${jwtToken}`,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    projectId: 42,
    pageName: 'Product Launch',
    headline: 'Revolutionieren Sie Ihr Business',
    subheadline: 'Mit unserer innovativen Lösung sparen Sie Zeit und Geld',
    ctaText: 'Jetzt starten',
    ctaLink: '#signup',
    features: [
      { title: 'Schnell', description: 'Blitzschnelle Performance', icon: '⚡' },
      { title: 'Sicher', description: 'Enterprise-Grade Security', icon: '🔒' },
      { title: 'Einfach', description: 'Intuitive Bedienung', icon: '✨' }
    ]
  })
});
```

### Domain konfigurieren

```javascript
// Schritt 1: Prüfen ob Main Domain existiert
const mainDomainCheck = await fetch('http://localhost:3001/mcp/tools/webbuilder_main_domain_get', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${jwtToken}`,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    ccProject: 'my-project'
  })
});

// Schritt 2: Falls keine Main Domain, erst diese erstellen
// Main Domain wird: myproject.sites.control-center.eu
const mainDomainResponse = await fetch('http://localhost:3001/mcp/tools/webbuilder_main_domain_configure', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${jwtToken}`,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    ccProject: 'my-project',
    subdomain: 'myproject',  // -> myproject.sites.control-center.eu
    userId: 123
  })
});

// Schritt 3: Web Builder Subdomain einrichten
// Wird: blog.myproject.sites.control-center.eu
const response = await fetch('http://localhost:3001/mcp/tools/webbuilder_domain_configure', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${jwtToken}`,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    ccProject: 'my-project',
    subdomain: 'blog',  // -> blog.myproject.sites.control-center.eu
    enabled: true
  })
});
// SSL-Zertifikat wird automatisch erstellt!
```

### HTML-Komponenten hinzufügen

```javascript
// Custom HTML Komponente zu einer Seite hinzufügen
const response = await fetch('http://localhost:3001/mcp/tools/webbuilder_component_add', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${jwtToken}`,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    pageId: 123,
    componentId: 'custom-hero-1',
    htmlCode: `
      <section class="hero" style="padding: 100px 20px; background: #667eea; color: white;">
        <h1>Willkommen</h1>
        <p>Dies ist meine benutzerdefinierte Hero-Sektion</p>
      </section>
    `
  })
});
```

### Filesystem-Bilder in Web Builder verwenden (Signed URLs)

```javascript
// Vollständiger Workflow: Bild hochladen und in Web Builder verwenden

// 1. Bild ins Filesystem hochladen (Base64-encoded)
const uploadResponse = await fetch('http://localhost:3001/mcp/tools/file_upload_to_filesystem', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${jwtToken}`,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    name: 'hero-background.jpg',
    content: 'BASE64_ENCODED_IMAGE_DATA',
    directory: 'Images',
    project: 'my-project',  // Optional: für Projekt-Filesystem
    isBase64: true
  })
});
// Ergebnis: { success: true, path: "Images/hero-background.jpg" }

// 2. Signierte URL für das Bild generieren
const signedUrlResponse = await fetch('http://localhost:3001/mcp/tools/file_get_signed_url', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${jwtToken}`,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    path: 'Images/hero-background.jpg',
    project: 'my-project',  // Optional: für Projekt-Filesystem
    validitySeconds: 3600   // 1 Stunde gültig (default)
  })
});
// Ergebnis: {
//   success: true,
//   url: "https://domain.com/backend/secure_file_provider.php?path=Images/hero-background.jpg&expires=1234567890&signature=abc123...",
//   expires: 1234567890,
//   expiresIn: 3600
// }

// 3. Signierte URL in Web Builder Komponente verwenden
const signedUrl = signedUrlResponse.url;
const componentResponse = await fetch('http://localhost:3001/mcp/tools/webbuilder_component_add', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${jwtToken}`,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    pageId: 123,
    componentId: 'hero-with-image',
    htmlCode: `
      <section data-componentid="hero-with-image" style="min-height: 60vh; background-image: url('${signedUrl}'); background-size: cover; display: flex; align-items: center; justify-content: center;">
        <div style="background: rgba(0,0,0,0.5); padding: 40px; border-radius: 10px; text-align: center; color: white;">
          <h1>Willkommen</h1>
          <p>Mit Hintergrundbild aus dem Filesystem</p>
        </div>
      </section>
    `
  })
});

// Für mehrere Bilder gleichzeitig: Bulk Signed URLs
const bulkResponse = await fetch('http://localhost:3001/mcp/tools/file_get_bulk_signed_urls', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${jwtToken}`,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    files: [
      { path: 'Images/photo1.jpg', project: 'my-project' },
      { path: 'Images/photo2.jpg', project: 'my-project' },
      { path: 'Images/photo3.jpg' }  // Ohne project = globales Filesystem
    ],
    validitySeconds: 7200  // 2 Stunden gültig
  })
});
// Ergebnis: {
//   success: true,
//   count: 3,
//   urls: [
//     { originalPath: 'Images/photo1.jpg', signedUrl: '...', project: 'my-project', expires: ... },
//     { originalPath: 'Images/photo2.jpg', signedUrl: '...', project: 'my-project', expires: ... },
//     { originalPath: 'Images/photo3.jpg', signedUrl: '...', project: null, expires: ... }
//   ]
// }
```

**Hinweis zu Signed URLs:**
- URLs sind standardmäßig 1 Stunde gültig (max. 24 Stunden)
- Die Rendering Engine von Web Builder cached die Bilder automatisch
- Für editierbare Bilder im Page Builder das `data-image` Attribut verwenden: `<img data-image src="SIGNED_URL" alt="...">`
- Unterstützte Dateitypen: JPEG, PNG, GIF, WebP, SVG, PDF, Text, CSV, JSON, MP4, WebM, MP3, WAV

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
        "CMS_BACKEND_URL": "https://api.fringelo.com/backend"
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

1. Login im Fringelo
2. Token aus localStorage: `localStorage.getItem('authToken')`
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
