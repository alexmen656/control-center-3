# Secure File Authentication System

## Übersicht

Dieses System implementiert eine signaturbasierte Authentifizierung für Dateien im Control Center. Alle Bild- und Dateizugriffe werden durch zeitlich begrenzte, signierte URLs geschützt.

## Komponenten

### 1. Backend

#### secure_file_provider.php
- **Zweck**: Liefert Dateien nur mit gültiger Signatur aus
- **Sicherheitsfeatures**:
  - HMAC-SHA256 Signaturvalidierung
  - Zeitbasierte Ablaufzeiten (Standard: 1 Stunde)
  - Directory Traversal Protection
  - MIME-Type Whitelist
  - Separate Pfade für normale und Project-Dateien

#### signed_url_generator.php
- **Zweck**: Generiert signierte URLs für das Frontend
- **Funktionen**:
  - Einzelne URL-Generierung
  - Bulk-URL-Generierung für Performance
  - Unterstützung für Project-spezifische Dateien
  - Konfigurierbare Gültigkeitsdauer

### 2. Frontend

#### FileSystem.vue
- Lädt beim Start alle Bild-URLs als signierte URLs
- Cached URLs in `signedUrls` Object
- Verwendet `getSignedImageUrl()` für Bildanzeige
- Generiert on-demand URLs für Image Preview Modal

#### ProjectFileSystem.vue
- Wie FileSystem.vue, aber mit Project-Kontext
- Übergibt `projectID` an URL-Generator
- Unterstützt Project-spezifische Dateisysteme

## Dateistruktur

```
/data/filesystem/              # Normales Dateisystem
/data/project_filesystems/     # Project-spezifische Dateisysteme
  /{projectID}/                # Pro Project ein Verzeichnis
```

## Sicherheitskonzept

### Signaturerstellung

```
data = path + '|' + expires [+ '|' + projectID]
signature = HMAC-SHA256(data, SECRET_KEY)
```

### URL-Format

```
secure_file_provider.php?path={path}&expires={timestamp}&signature={hmac}&[project={id}]
```

### Validierung

1. Zeitstempel prüfen (nicht abgelaufen?)
2. Signatur neu berechnen
3. Timing-safe Vergleich der Signaturen
4. Dateizugriff nur bei Erfolg

## Verwendung

### Im Frontend - Einzelne URL generieren

```javascript
const signedUrl = await this.generateSignedUrl(file.location);
```

### Im Frontend - Bulk URLs laden

```javascript
await this.loadSignedUrlsForImages();
const url = this.getSignedImageUrl(location);
```

### Mit Project-Kontext

```javascript
const signedUrl = await this.generateSignedUrl(
  file.location,
  this.$route.params.project
);
```

## Konfiguration

### Secret Key
In beiden PHP-Dateien identisch definiert:
```php
define('FILE_SIGNATURE_SECRET', 'cc_secure_file_sign_2026_secret_key');
```

⚠️ **WICHTIG**: In Production durch starken, zufälligen Key ersetzen!

### Gültigkeitsdauer
```php
define('SIGNATURE_VALIDITY', 3600); // 1 Stunde in Sekunden
```

### Erlaubte MIME-Types
In `secure_file_provider.php`:
```php
$allowedMimeTypes = [
    'image/jpeg',
    'image/png',
    // ... weitere Types
];
```

## Migration von altem System

### Altes System (unsicher):
```
file_provider.php?path={path}
```

### Neues System (sicher):
```
secure_file_provider.php?path={path}&expires={ts}&signature={sig}
```

Alle Frontend-Komponenten wurden aktualisiert, um automatisch signierte URLs zu verwenden.

## Performance-Optimierungen

1. **Bulk-Generierung**: Alle Bild-URLs werden beim Laden in einem Request generiert
2. **Caching**: URLs werden im Frontend gecached (`signedUrls` Object)
3. **Lazy Loading**: Preview-URLs werden erst bei Bedarf generiert
4. **Gültigkeitsdauer**: 1 Stunde verhindert zu häufige Neuanfragen

## Fehlerbehandlung

### Backend
- 400: Bad Request (fehlerhafte Parameter)
- 403: Forbidden (ungültige/abgelaufene Signatur)
- 404: File not found

### Frontend
- Bei Fehler: Fallback auf Icon-Anzeige
- Error-Logging in Console
- Image Error State für User-Feedback

## Testing

### Manuelle Tests
1. Bild aufrufen mit gültiger Signatur → Erfolg
2. Bild aufrufen mit ungültiger Signatur → 403
3. Bild aufrufen mit abgelaufener Signatur → 403
4. Bild aufrufen ohne Signatur → 400
5. Directory Traversal versuchen → 400

### Automatische Tests (TODO)
```bash
# Test Suite für secure_file_provider.php
npm run test:security
```

## Bekannte Limitationen

1. URLs laufen nach 1 Stunde ab (Design-Entscheidung)
2. Bei sehr vielen Bildern kann initiales Laden länger dauern
3. Secret Key ist hardcoded (für Production ändern!)

## Zukünftige Verbesserungen

- [ ] Secret Key aus Umgebungsvariable
- [ ] Redis Cache für URL-Generierung
- [ ] WebSocket für URL-Refresh
- [ ] Rate Limiting
- [ ] Audit Logging
- [ ] CDN-Integration mit signed URLs

## Support

Bei Fragen oder Problemen: Siehe Hauptdokumentation oder erstelle ein Issue.
