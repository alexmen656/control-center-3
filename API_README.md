# CMS API Management System

## Überblick

Das CMS API Management System ermöglicht es Ihrem CMS, **eigene APIs bereitzustellen**, die von Projekten genutzt werden können. Projekte können sich für APIs anmelden und erhalten API-Schlüssel für den Zugriff.

## Konzept

**CMS stellt APIs bereit** → **Projekte abonnieren APIs** → **Nutzen APIs in Web Builder/Monaco Editor**

### Beispiel-APIs die das CMS bereitstellt:
- **User Management API** - Benutzer verwalten, Login/Registration
- **File Storage API** - Dateien hochladen, verwalten, herunterladen
- **Database API** - Datenbankoperationen, CRUD
- **Notification API** - Push-Benachrichtigungen, E-Mails
- **Analytics API** - Event-Tracking, Statistiken

## Architektur

### Backend-Struktur

#### Datenbank-Schema
- **`cms_apis`** - Verfügbare APIs des CMS
- **`cms_api_endpoints`** - Endpunkte jeder API
- **`project_api_subscriptions`** - Projekt-API-Abonnements
- **`api_usage_logs`** - Nutzungsstatistiken

#### API-Handler
- **`backend/apis.php`** - Verwaltung der API-Abonnements
- **`backend/api/v1/users.php`** - Beispiel User Management API
- **`backend/api/v1/files.php`** - File Storage API (TODO)
- **`backend/api/v1/database.php`** - Database API (TODO)

### Frontend-Struktur

#### Komponenten
- **`ManageApis.vue`** - Hauptverwaltung mit 3 Tabs:
  - **Available APIs** - Alle verfügbaren CMS-APIs
  - **My APIs** - Abonnierte APIs des Projekts  
  - **Usage & Stats** - Nutzungsstatistiken

#### Features
- ✅ API-Browser mit Kategorien und Suche
- ✅ One-Click-Abonnement
- ✅ API-Schlüssel-Verwaltung
- ✅ Rate-Limiting pro Projekt
- ✅ Nutzungsstatistiken
- ✅ Detailansicht mit Endpoints

## API-Beispiel: User Management

### Endpoints
- `GET /api/v1/users` - Alle Benutzer abrufen
- `GET /api/v1/users/{id}` - Spezifischen Benutzer abrufen
- `POST /api/v1/users` - Neuen Benutzer erstellen
- `PUT /api/v1/users/{id}` - Benutzer aktualisieren
- `DELETE /api/v1/users/{id}` - Benutzer löschen

### Authentifizierung
```bash
Authorization: Bearer cms_a1b2c3d4e5f6g7h8_123
```

### Beispiel-Request
```javascript
// GET /api/v1/users?page=1&limit=10
fetch('/api/v1/users?page=1&limit=10', {
  headers: {
    'Authorization': 'Bearer cms_a1b2c3d4e5f6g7h8_123',
    'Content-Type': 'application/json'
  }
})
.then(response => response.json())
.then(data => console.log(data));
```

### Beispiel-Response
```json
{
  "users": [
    {
      "userID": "1",
      "firstName": "John",
      "lastName": "Doe",
      "email": "john@example.com",
      "accountStatus": "active"
    }
  ],
  "total": 25,
  "page": 1,
  "limit": 10,
  "pages": 3
}
```

## Workflow für Projekte

### 1. API entdecken
- Projekt öffnet `/project/{project}/manage/apis`
- Durchsucht verfügbare APIs nach Kategorie
- Liest API-Dokumentation und Endpoints

### 2. API abonnieren
- Klick auf "Subscribe" bei gewünschter API
- System generiert einzigartigen API-Schlüssel
- API ist sofort verfügbar

### 3. API verwenden
```javascript
// Im Web Builder oder Monaco Editor
const apiKey = 'cms_a1b2c3d4e5f6g7h8_123';

// Benutzer abrufen
const users = await fetch('/api/v1/users', {
  headers: { 'Authorization': `Bearer ${apiKey}` }
}).then(r => r.json());

// Datei hochladen
const formData = new FormData();
formData.append('file', fileInput.files[0]);

const uploadResult = await fetch('/api/v1/files/upload', {
  method: 'POST',
  headers: { 'Authorization': `Bearer ${apiKey}` },
  body: formData
}).then(r => r.json());
```

### 4. Überwachung
- Nutzungsstatistiken im "Usage & Stats" Tab
- Rate-Limit-Überwachung
- Fehlerprotokollierung

## Features für Entwickler

### Rate Limiting
- Automatisches Rate Limiting pro API-Schlüssel
- Konfigurierbar pro Projekt
- 429-Fehler bei Überschreitung

### Logging & Analytics
- Alle API-Calls werden geloggt
- Response-Zeiten gemessen
- Erfolgsraten berechnet
- IP-Tracking

### Security
- API-Schlüssel-Validation
- CORS-Unterstützung
- Input-Sanitization
- Error-Handling

## Installation & Setup

### 1. Datenbank-Migration
```bash
cd /home/alex/control-center-3_2/backend
php run_project_apis_migration.php
```

### 2. API-Endpunkte konfigurieren
- APIs werden automatisch aus der Datenbank geladen
- Neue APIs können über SQL-Inserts hinzugefügt werden
- Endpoint-Dokumentation in `cms_api_endpoints`

### 3. Webserver-Konfiguration
```apache
# .htaccess für API-Routing
RewriteEngine On
RewriteRule ^api/v1/users(/.*)?$ backend/api/v1/users.php [L,QSA]
RewriteRule ^api/v1/files(/.*)?$ backend/api/v1/files.php [L,QSA]
```

## Zukünftige Erweiterungen

### Neue APIs
- **Database API** - Direkte DB-Queries für Web Builder
- **File Storage API** - Datei-Management
- **Analytics API** - Event-Tracking
- **Email API** - E-Mail-Versand
- **Webhook API** - Real-time Benachrichtigungen

### Erweiterte Features
- **API-Versionierung** - Multiple API-Versionen
- **Webhook-Support** - Push-Benachrichtigungen
- **GraphQL-Endpoints** - Flexible Datenabfragen
- **WebSocket-APIs** - Real-time Kommunikation
- **API-Playground** - Interaktive API-Tests

### Integration mit Web Builder
```javascript
// Beispiel: Benutzer-Dropdown im Web Builder
const UserDropdown = {
  async loadUsers() {
    const response = await CMS.api.users.getAll();
    return response.users.map(u => ({
      value: u.userID,
      label: `${u.firstName} ${u.lastName}`
    }));
  }
};
```

### Integration mit Monaco Editor
```javascript
// Auto-completion für CMS APIs
monaco.languages.registerCompletionItemProvider('javascript', {
  provideCompletionItems: () => {
    return {
      suggestions: [
        {
          label: 'CMS.api.users.getAll()',
          kind: monaco.languages.CompletionItemKind.Function,
          documentation: 'Get all users from CMS'
        }
      ]
    };
  }
});
```

## Fazit

Dieses System verwandelt Ihr CMS in eine **API-Plattform**, die Projekte nutzen können. Statt externe APIs zu integrieren, stellen Sie Ihre eigenen bereit und haben volle Kontrolle über Daten und Funktionalität.
