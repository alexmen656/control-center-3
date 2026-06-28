# Email System - Setup Anleitung

## Übersicht

Dieses Modul ermöglicht das Senden und Empfangen von E-Mails über **Resend**.
E-Mails werden über Webhooks empfangen und in der Datenbank gespeichert.

**Provider:** [Resend](https://resend.com) (einfacher als AWS SES, kein Production Approval nötig)

## Architektur

```
SENDEN:
App → ResendMailer → Resend API → Empfänger

EMPFANGEN:
Absender → Resend → Webhook → resend_incoming.php → ResendReceiver → DB
```

## Dateien

| Datei | Beschreibung |
|-------|-------------|
| `backend/services/ResendMailer.php` | Service-Klasse für E-Mail-Versand |
| `backend/services/ResendReceiver.php` | Service-Klasse für E-Mail-Empfang |
| `backend/webhooks/resend_incoming.php` | Webhook-Endpoint für eingehende E-Mails |
| `backend/emails.php` | REST API für E-Mail-Verwaltung |
| `src/views/IncomingEmailsView.vue` | Vue-Komponente für E-Mail-Darstellung |

## Resend Setup

### 1. Account erstellen

1. Gehe zu [resend.com](https://resend.com) und erstelle einen Account
2. Verifiziere deine E-Mail-Adresse

### 2. API Key erstellen

1. Gehe zu [API Keys](https://resend.com/api-keys)
2. Klicke auf "Create API Key"
3. Kopiere den Key (beginnt mit `re_`)
4. Trage ihn in `backend/creds.php` ein:

```php
$resend_api_key = 're_xxxxxxxxx';
```

### 3. Domain verifizieren (optional, aber empfohlen)

Ohne eigene Domain kannst du nur an deine verifizierte E-Mail senden.

1. Gehe zu [Domains](https://resend.com/domains)
2. Klicke auf "Add Domain"
3. Füge die angezeigten DNS Records hinzu:
   - **TXT** Record für Verifizierung
   - **TXT** Record für SPF
   - **CNAME** Records für DKIM

### 4. E-Mail-Empfang einrichten

#### Option A: Resend-Subdomain (schnell zum Testen)

1. Gehe zu [Emails → Receiving](https://resend.com/emails/receiving)
2. Deine Resend-Domain ist: `<id>.resend.app`
3. E-Mails an `anything@<id>.resend.app` werden empfangen

#### Option B: Eigene Domain

1. Gehe zu [Domains](https://resend.com/domains)
2. Füge einen **MX Record** hinzu:
   ```
   Type: MX
   Name: @ (oder Subdomain wie "mail")
   Value: feedback-smtp.resend.com
   Priority: 10
   ```

### 5. Webhook einrichten

1. Gehe zu [Webhooks](https://resend.com/webhooks)
2. Klicke auf "Add Webhook"
3. Konfiguration:
   - **Endpoint URL:** `https://your-domain.com/backend/webhooks/resend_incoming.php`
   - **Events:** 
     - ✅ `email.received` (wichtig für Empfang!)
     - ✅ `email.delivered` (optional)
     - ✅ `email.bounced` (optional)
     - ✅ `email.complained` (optional)

4. Optional: **Signing Secret** kopieren und in `creds.php` eintragen:
   ```php
   $resend_webhook_secret = 'whsec_xxxxxxxxx';
   ```

## Datenbank Tabellen

Die Tabellen werden automatisch erstellt beim ersten Aufruf:

### incoming_emails
```sql
CREATE TABLE incoming_emails (
    id INT AUTO_INCREMENT PRIMARY KEY,
    message_id VARCHAR(255) UNIQUE,
    from_email VARCHAR(255) NOT NULL,
    from_name VARCHAR(255),
    to_email VARCHAR(255) NOT NULL,
    cc TEXT,
    bcc TEXT,
    subject VARCHAR(500),
    body_text LONGTEXT,
    body_html LONGTEXT,
    headers JSON,
    raw_email LONGTEXT,
    spam_verdict VARCHAR(50),
    virus_verdict VARCHAR(50),
    spf_verdict VARCHAR(50),
    dkim_verdict VARCHAR(50),
    dmarc_verdict VARCHAR(50),
    is_read TINYINT(1) DEFAULT 0,
    is_starred TINYINT(1) DEFAULT 0,
    is_archived TINYINT(1) DEFAULT 0,
    is_deleted TINYINT(1) DEFAULT 0,
    folder VARCHAR(50) DEFAULT 'inbox',
    labels JSON,
    s3_bucket VARCHAR(255),
    s3_key VARCHAR(500),
    received_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### email_attachments
```sql
CREATE TABLE email_attachments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email_id INT NOT NULL,
    filename VARCHAR(255) NOT NULL,
    content_type VARCHAR(100),
    content_id VARCHAR(255),
    size INT,
    content LONGBLOB,
    s3_bucket VARCHAR(255),
    s3_key VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (email_id) REFERENCES incoming_emails(id) ON DELETE CASCADE
);
```

## API Endpunkte

### Liste E-Mails
```
GET /backend/emails.php?action=list&folder=inbox&limit=50&offset=0&search=
```

### Einzelne E-Mail
```
GET /backend/emails.php?action=get&id=123&mark_read=true
```

### Anhang herunterladen
```
GET /backend/emails.php?action=get_attachment&id=123&download=true
```

### Als gelesen markieren
```
POST /backend/emails.php?action=mark_read
Body: { "id": 123, "read": true }
```

### E-Mail verschieben
```
POST /backend/emails.php?action=move
Body: { "id": 123, "folder": "archive" }
```

### E-Mail löschen
```
POST /backend/emails.php?action=delete
Body: { "id": 123, "permanent": false }
```

### Bulk-Aktionen
```
POST /backend/emails.php?action=bulk_action
Body: { "ids": [1,2,3], "action": "mark_read" }
```

### Folder-Statistiken
```
GET /backend/emails.php?action=stats
```

## Frontend

Die E-Mail-Ansicht ist unter `/emails` erreichbar:

```
https://your-domain.com/emails
```

Features:
- Ordner-Navigation (Inbox, Starred, Archive, Spam, Trash)
- Suche
- Bulk-Aktionen
- E-Mail-Detail mit HTML-Rendering
- Anhänge herunterladen
- Raw-Email Ansicht (für Debugging)

## E-Mails senden

```php
require_once 'services/ResendMailer.php';

$mailer = new \ControlCenter\ResendMailer();

// Einfache E-Mail
$result = $mailer->send(
    'empfaenger@example.com',
    'Betreff',
    '<h1>Hallo!</h1><p>Das ist eine Test-E-Mail.</p>'
);

// Mit Optionen
$result = $mailer->send(
    'empfaenger@example.com',
    'Betreff',
    '<h1>Hallo!</h1>',
    [
        'from' => 'sender@yourdomain.com',
        'from_name' => 'Mein Name',
        'reply_to' => 'reply@example.com',
        'cc' => 'cc@example.com',
        'text_body' => 'Plain-Text Version',
        'attachments' => [
            [
                'filename' => 'dokument.pdf',
                'content' => file_get_contents('path/to/file.pdf'),
                'content_type' => 'application/pdf'
            ]
        ]
    ]
);

if ($result['success']) {
    echo "E-Mail gesendet! ID: " . $result['email_id'];
} else {
    echo "Fehler: " . $result['error'];
}
```

## Umgebungsvariablen (optional)

Alternativ zu `creds.php` können Umgebungsvariablen verwendet werden:

```env
RESEND_API_KEY=re_xxxxxxxxx
RESEND_FROM_EMAIL=noreply@yourdomain.com
RESEND_FROM_NAME=Your App Name
RESEND_WEBHOOK_SECRET=whsec_xxxxxxxxx
```

## Testing

### Webhook testen
```bash
curl -X POST https://your-domain.com/backend/webhooks/resend_incoming.php \
  -H "Content-Type: application/json" \
  -d '{
    "type": "email.received",
    "created_at": "2024-01-01T12:00:00.000Z",
    "data": {
      "email_id": "test123",
      "from": "Test Sender <sender@example.com>",
      "to": ["recipient@yourdomain.com"],
      "subject": "Test Email",
      "message_id": "<test123@example.com>",
      "attachments": []
    }
  }'
```

### E-Mail senden testen
```php
require_once 'services/ResendMailer.php';

$mailer = new \ControlCenter\ResendMailer();
$result = $mailer->send(
    'your-email@example.com',
    'Test von Fringelo',
    '<h1>Es funktioniert!</h1>'
);
print_r($result);
```

## Logs

Webhook-Logs werden gespeichert in:
```
backend/logs/resend_incoming_YYYY-MM-DD.log
```

## Troubleshooting

### E-Mails werden nicht empfangen
1. Prüfe ob Webhook in Resend korrekt konfiguriert ist
2. Prüfe ob `email.received` Event aktiviert ist
3. Prüfe die Logs unter `backend/logs/`
4. Teste den Webhook manuell mit cURL

### E-Mails werden nicht gesendet
1. Prüfe API Key in `creds.php`
2. Prüfe ob Domain verifiziert ist
3. Ohne eigene Domain: Nur an verifizierte Adressen möglich

### Anhänge fehlen
- Resend sendet Anhänge nicht im Webhook
- Sie werden on-demand über die API geladen
- Prüfe ob API Key Leserechte hat

## Migration von AWS SES

Falls du vorher AWS SES verwendet hast:

1. Bestehende `incoming_emails` Tabelle bleibt kompatibel
2. Neue Spalte `resend_email_id` wird automatisch hinzugefügt
3. AWS SES Services können parallel bestehen bleiben
4. Webhook-URL auf Resend ändern

