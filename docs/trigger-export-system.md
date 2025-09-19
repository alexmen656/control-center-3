# Trigger & Export System - Dokumentation

## Übersicht

Das neue **Trigger & Export System** erweitert das Control Center um zwei wichtige Features:

1. **Trigger-System**: Automatische Benachrichtigungen bei Datenbank-Operationen
2. **CSV/Excel Export**: Export von Formulardaten

## Features

### 🔔 Trigger-System

Das Trigger-System überwacht Formulardaten und sendet automatisch Benachrichtigungen bei:
- **INSERT**: Neue Einträge werden hinzugefügt
- **UPDATE**: Bestehende Einträge werden bearbeitet  
- **DELETE**: Einträge werden gelöscht

#### Unterstützte Benachrichtigungskanäle:
- **📧 Email**: Standard-Mail-Versand
- **💬 Telegram**: Bot-Nachrichten über Telegram API
- **🎮 Discord**: Webhook-Nachrichten 
- **📱 SMS**: SMS-Versand (erweiterbar)

#### Platzhalter in Nachrichten:
```
Verwende {feldname} für dynamische Werte:
- {id} - Eintrag-ID
- {table} - Tabellenname  
- {beliebiges_feld} - Wert eines Formularfelds
```

### 📊 CSV Export

- **Ein-Klick Export** aller Formulardaten
- **Automatische Dateinamens-Generierung**: `projekt_formular_datum.csv`
- **Vollständiger Datenexport** inklusive aller Spalten
- **Excel-kompatibel**

## Installation & Setup

### Backend-Dateien:
- `backend/triggers.php` - Hauptlogik für Trigger und Export
- `backend/form.php` - Erweitert um Trigger-Hooks

### Frontend-Komponenten:
- `src/views/FormDisplay.vue` - Erweitert um Export & Trigger-Buttons
- `src/components/TriggerManager.vue` - Trigger-Verwaltung

### Datenbank:
```sql
CREATE TABLE form_triggers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project VARCHAR(255) NOT NULL,
    form_name VARCHAR(255) NOT NULL,
    trigger_event ENUM('insert', 'update', 'delete') NOT NULL,
    notification_type ENUM('email', 'telegram', 'discord', 'sms') NOT NULL,
    notification_target TEXT NOT NULL,
    message_template TEXT NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

## Verwendung

### 1. Trigger erstellen

1. Öffne ein Formular in der FormDisplay-Ansicht
2. Klicke auf **"Manage Triggers"**
3. Wähle Event-Typ (Insert/Update/Delete)
4. Wähle Benachrichtigungskanal
5. Konfiguriere Ziel (Email, Telegram Token:ChatID, Discord Webhook, etc.)
6. Erstelle Message-Template mit Platzhaltern
7. Speichere den Trigger

### 2. CSV Export

1. Öffne ein Formular in der FormDisplay-Ansicht
2. Klicke auf **"Export CSV"**
3. Datei wird automatisch heruntergeladen

## Konfiguration

### Telegram Setup:
```
Format: bot_token:chat_id
Beispiel: 1234567890:ABCDEF:123456789
```

### Discord Setup:
```
Webhook URL von Discord-Server Channel Settings
Beispiel: https://discord.com/api/webhooks/123/abc...
```

### Email Setup:
```
Standard PHP mail() Funktion
Stelle sicher, dass der Server Mail-Versand unterstützt
```

## API Endpoints

### Trigger Management:
```php
POST triggers.php
- create_trigger: Neuen Trigger erstellen
- get_triggers: Trigger für Formular abrufen  
- delete_trigger: Trigger löschen
- toggle_trigger: Trigger aktivieren/deaktivieren
```

### Export:
```php
POST triggers.php
- export_csv: CSV-Export starten
```

## Beispiel Message Templates

### Neuer Eintrag:
```
🆕 Neuer Eintrag in {table}!
ID: {id}
Name: {name}
Email: {email}
Erstellt: {created_at}
```

### Eintrag bearbeitet:
```
✏️ Eintrag {id} wurde bearbeitet
Tabelle: {table}
Neuer Status: {status}
```

### Eintrag gelöscht:
```
🗑️ Eintrag {id} wurde gelöscht
Tabelle: {table}
```

## Sicherheit

- Alle Eingaben werden über `escape_string()` bereinigt
- Trigger-Ziele werden validiert
- SQL-Injection-Schutz implementiert
- CSRF-Schutz durch bestehende Session-Verwaltung

## Erweiterungen

### SMS Provider hinzufügen:
```php
// In triggers.php - sendSMS() Methode
private function sendSMS($phoneNumber, $message) {
    // Twilio, Nexmo, oder anderen SMS-Provider integrieren
}
```

### Weitere Notification-Kanäle:
- Slack Webhooks
- Microsoft Teams
- Push Notifications
- WhatsApp Business API

## Troubleshooting

### Trigger funktionieren nicht:
1. Prüfe Datenbankverbindung
2. Überprüfe include-Pfade in form.php
3. Teste Notification-Ziele manuell

### Export schlägt fehl:
1. Prüfe Dateirechte
2. Überprüfe Tabellennamen-Konvertierung
3. Prüfe ob Tabelle existiert

### Telegram/Discord Nachrichten kommen nicht an:
1. Validiere Bot-Token/Webhook-URL
2. Prüfe Netzwerk-Verbindung vom Server
3. Teste API-Endpunkte manuell
