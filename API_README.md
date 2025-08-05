# API Management System

## Überblick

Das API Management System ermöglicht es, APIs pro Projekt zu verwalten, zu konfigurieren und zu testen. Es bietet eine vollständige Lösung für API-Dokumentation, Authentifizierung und Endpoint-Management.

## Struktur

### Backend
- **`apis.php`** - Haupt-API Handler für CRUD-Operationen
- **`create_project_apis_table.sql`** - Datenbank-Schema für APIs
- **`run_project_apis_migration.php`** - Migrations-Script
- **`sidebar.php`** - Erweitert um API-Navigation

### Frontend
- **`src/apis/ManageApis.vue`** - Hauptverwaltungsansicht für APIs
- **`src/apis/ApiView.vue`** - Einzelne API-Ansicht mit Endpoints
- **`src/apis/ApiSettings.vue`** - Einstellungen und Authentifizierung
- **`src/store.ts`** - Vuex Store für API-State Management
- **`src/router/index.ts`** - Routing für API-Seiten

## Datenbank-Schema

### `project_apis`
Haupttabelle für API-Konfigurationen:
- **id** - Eindeutige API-ID
- **projectID** - Referenz zum Projekt
- **name** - API-Name
- **slug** - URL-freundlicher Bezeichner
- **description** - Beschreibung
- **icon** - Ionicon-Name
- **type** - API-Typ (REST, GraphQL, WebSocket, SOAP)
- **base_url** - Basis-URL der API
- **auth_type** - Authentifizierungstyp
- **status** - Status (active, inactive, testing)
- **rate_limit** - Anfragen pro Minute

### `project_api_keys`
Authentifizierungsschlüssel:
- **id** - Schlüssel-ID
- **api_id** - Referenz zur API
- **key_name** - Name des Schlüssels
- **key_value** - Schlüsselwert (verschlüsselbar)
- **is_encrypted** - Verschlüsselungsstatus

### `project_api_endpoints`
API-Endpunkte:
- **id** - Endpoint-ID
- **api_id** - Referenz zur API
- **name** - Endpoint-Name
- **method** - HTTP-Methode
- **endpoint** - Endpunkt-Pfad
- **description** - Beschreibung
- **parameters** - Parameter als JSON
- **headers** - Header als JSON
- **response_example** - Beispiel-Response als JSON

## Features

### API-Verwaltung
- ✅ APIs erstellen, bearbeiten, löschen
- ✅ Verschiedene API-Typen unterstützen
- ✅ Status-Management (aktiv, inaktiv, testing)
- ✅ Rate-Limiting-Konfiguration

### Authentifizierung
- ✅ Verschiedene Auth-Typen (API Key, Bearer, OAuth2, Basic Auth)
- ✅ Verschlüsselte Schlüsselspeicherung
- ✅ Schlüssel-Management

### Endpoints
- ✅ Endpoint-Definition
- ✅ HTTP-Methoden
- ✅ Parameter-Dokumentation
- ✅ Response-Beispiele

### UI/UX
- ✅ Übersichtliche Verwaltungsansicht
- ✅ Detailansicht pro API
- ✅ Einstellungsseite
- ✅ Test-Funktionalität (Mock)
- ✅ Responsive Design

## Installation

1. **Datenbank-Migration ausführen:**
   ```bash
   php backend/run_project_apis_migration.php
   ```

2. **Frontend-Dependencies installieren:**
   ```bash
   npm install
   ```

3. **Development Server starten:**
   ```bash
   npm run dev
   ```

## Verwendung

### API erstellen
1. Gehe zu `/project/{project}/manage/apis`
2. Klicke auf "Add New API"
3. Fülle die erforderlichen Felder aus
4. Speichere die API

### Endpoints hinzufügen
1. Öffne eine API-Detailansicht
2. Klicke auf "Add Endpoint"
3. Definiere Method, Pfad und Parameter
4. Speichere den Endpoint

### Authentifizierung konfigurieren
1. Gehe zu den API-Settings
2. Wähle den Auth-Type
3. Füge benötigte Schlüssel hinzu
4. Speichere die Konfiguration

## Navigation

Die APIs erscheinen automatisch in der Projektsidebar unter dem "APIs" Abschnitt. Jede API zeigt:
- Name und Icon
- Status-Badge
- Typ-Information
- Direkte Links zu Detailansicht und Einstellungen

## Sicherheit

- ✅ JWT-basierte Authentifizierung
- ✅ SQL-Injection-Schutz
- ✅ Verschlüsselte Schlüsselspeicherung
- ✅ Rate-Limiting
- ✅ HTTPS-Enforcement (konfigurierbar)

## Nächste Schritte

### Mögliche Erweiterungen:
1. **API-Testing** - Echte API-Calls von der UI
2. **Webhooks** - Webhook-Management
3. **Monitoring** - API-Performance-Tracking
4. **Import/Export** - OpenAPI/Swagger Import
5. **Collaboration** - Team-Features für API-Dokumentation
6. **Versioning** - API-Versionierung

### Code-Verbesserungen:
1. **Error-Handling** - Verbesserte Fehlerbehandlung
2. **Validation** - Frontend/Backend-Validierung
3. **Caching** - API-Response-Caching
4. **Logs** - Detaillierte API-Logs

## Support

Bei Fragen oder Problemen:
1. Prüfe die Browser-Konsole auf Fehler
2. Kontrolliere die Backend-Logs
3. Stelle sicher, dass die Datenbank-Migration erfolgreich war
