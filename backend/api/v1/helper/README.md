# BaseAPI v2 - Dokumentation

## Übersicht

Der neue BaseAPI ermöglicht es, API-Keys mit spezifischen Services (app_id) zu verknüpfen und zu validieren. Jede API muss ihre `app_id` (slug aus der `cms_apis` Tabelle) an den BaseAPI übergeben.

## Datenbankstruktur

### cms_apis
```sql
- id: Eindeutige API-ID
- slug: Eindeutiger Identifier für die API (z.B. 'user-management', 'file-storage')
- name: Anzeigename der API
- is_active: Ob die API aktiv ist
```

### project_api_subscriptions
```sql
- id: Eindeutige Subscription-ID
- projectID: Projekt-ID (VARCHAR)
- api_id: Referenz auf cms_apis.id
- api_key: Eindeutiger API-Key für diese Projekt-API-Kombination
- is_enabled: Ob die Subscription aktiv ist
- rate_limit: Anfragen pro Stunde
- usage_count: Aktuelle Nutzungsanzahl
- last_used: Letzter Nutzungszeitpunkt
```

## Verwendung

### 1. BaseAPI erweitern

```php
<?php
require_once 'BaseAPI.php';

class YourAPI extends BaseAPI
{
    public function __construct()
    {
        // app_id muss der slug aus cms_apis entsprechen
        parent::__construct('your-api-slug');
        
        // Ab hier ist die Authentifizierung bereits erfolgt
        $this->handleRequest();
    }
    
    private function handleRequest()
    {
        // Rate Limiting prüfen
        $this->checkRateLimit();
        
        // Ihre API-Logik hier
        // $this->projectID und $this->userID sind verfügbar
    }
}

new YourAPI();
```

### 2. Authentifizierung

Der BaseAPI erwartet einen `Authorization: Bearer {api-key}` Header. Der API-Key wird gegen die `project_api_subscriptions` Tabelle validiert und muss für die angegebene `app_id` (slug) gültig sein.

### 3. Verfügbare Methoden

#### `authenticate($appId = null)`
- Authentifiziert den API-Key für eine bestimmte App
- Wird automatisch im Constructor aufgerufen

#### `checkRateLimit()`
- Prüft das Rate Limiting für die aktuelle API
- Sollte vor API-Operationen aufgerufen werden

#### `checkProjectAccess($projectID = null)`
- Prüft ob der Benutzer Zugriff auf das Projekt hat

#### `logApiCall($endpoint, $method, $responseCode = 200)`
- Loggt API-Aufrufe für Monitoring

#### `sendSuccess($data = null, $message = 'Success')`
- Sendet eine erfolgreiche JSON-Antwort

#### `sendError($message = 'Error', $code = 400)`
- Sendet eine Fehler-JSON-Antwort

#### `validateRequired($data, $required)`
- Validiert erforderliche Parameter

#### `getJsonInput()`
- Holt JSON-Daten aus dem Request Body

#### `sanitize($data)`
- Sanitisiert Eingabedaten

## Beispiel API-Aufruf

```bash
curl -X GET "https://your-domain.com/backend/api/v1/your_api.php" \
     -H "Authorization: Bearer your-api-key-here" \
     -H "Content-Type: application/json"
```

## API-Key Setup

1. API in `cms_apis` registrieren mit eindeutigem `slug`
2. Projekt-API-Subscription in `project_api_subscriptions` erstellen
3. API-Key generieren und in der Subscription speichern
4. API mit dem entsprechenden `slug` implementieren

## Sicherheitsfeatures

- ✅ API-Key Validierung pro Service (app_id)
- ✅ Rate Limiting pro Projekt-API-Kombination
- ✅ Automatisches Usage Tracking
- ✅ CORS-Unterstützung
- ✅ Input Sanitization
- ✅ SQL Injection Schutz
- ✅ API-Call Logging

## Migration von alter BaseAPI

Alte APIs müssen angepasst werden:

1. `parent::__construct()` → `parent::__construct('your-app-slug')`
2. Neue `app_id` Parameter in cms_apis registrieren
3. Rate Limiting Aufrufe hinzufügen: `$this->checkRateLimit()`
