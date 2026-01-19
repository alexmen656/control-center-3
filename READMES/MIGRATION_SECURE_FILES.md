# Migration Guide: Secure File Authentication

## Quick Start

### 1. Backend ist bereit
Die neuen Dateien sind bereits erstellt:
- ✅ `backend/secure_file_provider.php` - Sichere Dateiauslieferung
- ✅ `backend/signed_url_generator.php` - URL-Generierung

### 2. Frontend ist aktualisiert
Die folgenden Komponenten nutzen jetzt signierte URLs:
- ✅ `src/views/FileSystem.vue`
- ✅ `src/views/ProjectFileSystem.vue`

### 3. Alte Datei (optional)
`backend/file_provider.php` kann für Legacy-Support beibehalten werden oder gelöscht werden.

## Was wurde geändert?

### Backend

#### Neu: secure_file_provider.php
Ersetzt `file_provider.php` mit Sicherheitsfeatures:
```php
// ALT (unsicher):
file_provider.php?path=folder/image.jpg

// NEU (sicher):
secure_file_provider.php?path=folder/image.jpg&expires=1737324000&signature=abc123...
```

#### Neu: signed_url_generator.php
API-Endpoint für Frontend:
```javascript
// POST Request
{
  "path": "folder/image.jpg",
  "projectID": "optional-project-id",
  "validitySeconds": 3600
}

// Response
{
  "success": true,
  "url": "https://...secure_file_provider.php?path=...&signature=...",
  "expires": 1737324000
}
```

### Frontend

#### FileSystem.vue Änderungen:
1. **Neues Data-Property**: `signedUrls: {}`
2. **Neue Methoden**:
   - `generateSignedUrl(filePath, projectID)`
   - `loadSignedUrlsForImages()`
   - `getSignedImageUrl(location)`
3. **Aktualisierte Methoden**:
   - `previewImage()` - async, generiert signed URL
   - `fetchFileSystemData()` - lädt signed URLs nach Daten
4. **Template Updates**:
   - `<img :src="getSignedImageUrl(item.location)" />` statt direkter URL

#### ProjectFileSystem.vue Änderungen:
Identisch zu FileSystem.vue, plus:
- Project-Context wird an URL-Generator übergeben
- Unterstützt `/data/project_filesystems/{projectID}/`

## Testing

### 1. Backend testen
```bash
# Test ohne Signatur (sollte 400 geben)
curl "https://alex.polan.sk/control-center/secure_file_provider.php?path=test.jpg"

# Test mit abgelaufener Signatur (sollte 403 geben)
curl "https://alex.polan.sk/control-center/secure_file_provider.php?path=test.jpg&expires=1&signature=invalid"
```

### 2. Frontend testen
1. Öffne FileSystem-View
2. Developer Console öffnen
3. Prüfe auf Console-Logs:
   - "Raw file system data: ..."
   - "Processed file system data: ..."
   - Keine Fehler bei "Error loading signed URLs"
4. Bilder sollten angezeigt werden
5. Doppelklick auf Bild → Preview-Modal sollte funktionieren

### 3. Project FileSystem testen
1. Öffne ein Projekt
2. Gehe zu Project FileSystem
3. Wiederhole Frontend-Tests
4. Prüfe, dass `?project={id}` Parameter übergeben wird

## Troubleshooting

### Problem: Bilder werden nicht angezeigt
**Lösung 1**: Console-Logs prüfen
```javascript
// In Browser DevTools Console:
console.log(this.signedUrls); // Sollte URLs enthalten
```

**Lösung 2**: Backend-Pfade prüfen
```php
// In filesystem.php prüfen:
private const BASE_PATH = '/data/filesystem';
private const PROJECT_BASE_PATH = '/data/project_filesystems';
```

**Lösung 3**: Secret Key synchronisieren
```php
// Muss in beiden Dateien identisch sein:
// secure_file_provider.php
define('FILE_SIGNATURE_SECRET', 'cc_secure_file_sign_2026_secret_key');

// signed_url_generator.php
define('FILE_SIGNATURE_SECRET', 'cc_secure_file_sign_2026_secret_key');
```

### Problem: 403 Forbidden
**Ursache**: Signatur ungültig oder abgelaufen

**Lösung**: 
1. Gültigkeitsdauer erhöhen (wenn nötig)
2. Server-Zeit prüfen (sollte synchronisiert sein)
3. Secret Key prüfen

### Problem: Langsames initialisieren
**Ursache**: Viele Bilder → viele URL-Generierungen

**Lösung**: 
1. Bulk-Generierung wird bereits verwendet ✅
2. Optional: Gültigkeitsdauer auf 4-8 Stunden erhöhen
3. Optional: Redis-Cache implementieren

## Rollback Plan

Falls Probleme auftreten:

### 1. Frontend Rollback
```vue
// In FileSystem.vue & ProjectFileSystem.vue
// Ersetze:
:src="getSignedImageUrl(item.location)"

// Mit:
:src="'https://alex.polan.sk/control-center/file_provider.php?path=' + item.location"
```

### 2. Alte Datei reaktivieren
```bash
# file_provider.php ist noch vorhanden und kann genutzt werden
# Einfach Frontend auf alte URLs zurücksetzen
```

## Production Checklist

Vor dem Live-Gang:

- [ ] Secret Key ändern (starker, zufälliger String)
- [ ] HTTPS erzwingen
- [ ] Rate Limiting implementieren
- [ ] Logging aktivieren
- [ ] Backup erstellen
- [ ] Tests durchführen
- [ ] Performance messen
- [ ] Dokumentation aktualisieren

## Nächste Schritte

1. **Immediate**: Testen in Development
2. **Short-term**: Production Secret Key setzen
3. **Mid-term**: Monitoring & Logging
4. **Long-term**: CDN-Integration

## Support

Bei Fragen siehe [SECURE_FILE_AUTH.md](./SECURE_FILE_AUTH.md) für Details.
