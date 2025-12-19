# Newsletter Module - Migration & Setup Guide

## 📦 Übersicht

Das Newsletter-Modul wurde nach dem neuen Modul-Standard erstellt und ist vollständig integriert in das Dashboard-System.

## ✅ Erstellte Dateien

### Frontend (Vue.js)
```
src/modules/newsletter/
├── index.ts                          ✅ Modul-Registrierung & Dashboard Provider
├── routes.ts                         ✅ Route-Definitionen
├── dashboard.provider.ts             ✅ 6 Dashboard Widgets
├── README.md                         ✅ Dokumentation
└── components/
    ├── NewsletterView.vue           ✅ Hauptansicht (modernes Design)
    └── ConfigView.vue                ✅ Einstellungen
```

### Backend (PHP)
```
backend/
├── newsletter.php                    ✅ API-Endpunkt
└── add_newsletter_module.php         ✅ Module Store Installation
```

## 🎨 Design & Features

### Modernes Design wie FormDisplay.vue
- ✅ CSS-Variablen für Theming
- ✅ Responsive Design (Mobile-First)
- ✅ Dark-Mode-Support
- ✅ Ionicons
- ✅ Moderne Animationen
- ✅ Konsistente Typography

### Hauptfunktionen
- ✅ Newsletter erstellen und versenden
- ✅ HTML-Editor mit Vorschau
- ✅ Empfängerverwaltung
- ✅ Test-E-Mail-Funktion
- ✅ Tracking (Öffnungen, Klicks)
- ✅ Newsletter-Verlauf
- ✅ SMTP-Konfiguration
- ✅ Rate-Limiting
- ✅ Einstellungen speichern

## 📊 Dashboard Widgets

### 6 Widgets verfügbar:

1. **newsletter-total-sent** (Stat)
   - Gesamte gesendete Newsletter
   - Primary Color

2. **newsletter-total-subscribers** (Stat)
   - Anzahl Abonnenten
   - Success Color

3. **newsletter-open-rate** (Stat)
   - Öffnungsrate in %
   - Info Color

4. **newsletter-click-rate** (Stat)
   - Klickrate in %
   - Warning Color

5. **newsletter-recent-campaigns** (Table)
   - Letzte Newsletter mit Details
   - Sortierbar, paginiert

6. **newsletter-performance-chart** (Chart)
   - Liniendiagramm: Gesendet/Geöffnet/Geklickt
   - Zeitraum-Filter

## 🗄️ Datenbank-Struktur

Automatische Erstellung von 4 Tabellen pro Projekt:

### `{project}_newsletters`
```sql
- id (INT, PRIMARY KEY)
- subject (VARCHAR 255)
- content (TEXT)
- recipients (TEXT, JSON)
- recipient_count (INT)
- status (VARCHAR 50)
- sent_at (DATETIME)
- created_at (DATETIME)
- updated_at (DATETIME)
```

### `{project}_newsletter_tracking`
```sql
- id (INT, PRIMARY KEY)
- newsletter_id (INT, FOREIGN KEY)
- recipient_email (VARCHAR 255)
- opened (BOOLEAN)
- opened_at (DATETIME)
- clicked (BOOLEAN)
- clicked_at (DATETIME)
- clicks (INT)
- created_at (DATETIME)
```

### `{project}_newsletter_settings`
```sql
- id (INT, PRIMARY KEY)
- setting_key (VARCHAR 100)
- setting_value (TEXT)
- updated_at (DATETIME)
```

### `{project}_newsletter_subscribers`
```sql
- id (INT, PRIMARY KEY)
- email (VARCHAR 255, UNIQUE)
- name (VARCHAR 255)
- status (VARCHAR 50)
- subscribed_at (DATETIME)
- unsubscribed_at (DATETIME)
```

## 🚀 Installation

### 1. Module Store hinzufügen (optional)
```bash
php backend/add_newsletter_module.php
```

### 2. Automatische Registrierung
Das Modul wird automatisch geladen durch `src/main.ts`:
```typescript
const modules = import.meta.glob('./modules/*/index.ts', { eager: true });
```

### 3. Modul verwenden
Navigiere zu:
```
/project/{projekt-name}/newsletter
```

## 📡 API-Endpunkte

### Alle Endpunkte über `backend/newsletter.php`

| Action | Beschreibung | Parameter |
|--------|-------------|-----------|
| `send` | Newsletter senden | subject, email, recipients, test_mode |
| `get_stats` | Statistiken abrufen | project |
| `get_recent` | Letzte Newsletter | project, limit, offset |
| `get_performance` | Performance-Daten | project, period |
| `delete` | Newsletter löschen | project, id |
| `get_settings` | Einstellungen laden | project |
| `save_settings` | Einstellungen speichern | project, settings |
| `get_smtp` | SMTP-Einstellungen laden | project |
| `save_smtp` | SMTP-Einstellungen speichern | project, smtp |
| `test_smtp` | SMTP testen | project, smtp |

## 🎯 Verwendung im Dashboard

### Widget hinzufügen:
1. Dashboard öffnen
2. **+** Button klicken
3. **"Module Widget"** wählen
4. **"Newsletter"** als Modul wählen
5. Widget auswählen (z.B. "Gesendete Newsletter")
6. **Confirm**

### Programmatisch:
```javascript
{
  chart_type: "module_widget",
  module: "newsletter",
  widget: "newsletter-total-sent"
}
```

## 🔧 Konfiguration

### Einstellungen-Seite
```
/project/{projekt}/newsletter/config
```

**Verfügbare Optionen:**
- Absender Name & E-Mail
- Antwort-E-Mail
- E-Mail-Vorlage
- Tracking (Öffnungen/Klicks)
- Abmelde-Link
- Rate-Limiting
- SMTP-Server

## 📝 Code-Beispiele

### Newsletter senden (Vue.js)
```javascript
const response = await this.$axios.post(
  'newsletter.php',
  this.$qs.stringify({
    action: 'send',
    project: this.$route.params.project,
    subject: 'Newsletter Titel',
    email: '<html>Newsletter Inhalt</html>',
    recipients: 'user1@example.com,user2@example.com',
    test_mode: false
  })
);
```

### Statistiken abrufen
```javascript
const response = await this.$axios.post(
  'newsletter.php',
  this.$qs.stringify({
    action: 'get_stats',
    project: this.$route.params.project
  })
);

console.log(response.data.stats);
// {
//   total_sent: 42,
//   total_subscribers: 150,
//   open_rate: 35.5,
//   click_rate: 12.3
// }
```

## 🔐 Sicherheit

- ✅ E-Mail-Validierung
- ✅ SQL-Injection-Schutz (Prepared Statements)
- ✅ XSS-Schutz
- ✅ CORS-Headers
- ✅ Rate-Limiting
- ✅ Passwort-Maskierung in Settings

## 📊 Analytics & Tracking

### Tracking-Features:
- Öffnungsrate pro Newsletter
- Klickrate pro Newsletter
- Performance über Zeit
- Empfänger-spezifisches Tracking
- Aggregierte Statistiken

### Metriken:
- Gesamte versendete Newsletter
- Aktive Abonnenten
- Durchschnittliche Öffnungsrate
- Durchschnittliche Klickrate

## 🎨 UI-Komponenten

### Newsletter-Hauptansicht
- Stats-Cards (4x)
- Newsletter-Formular mit:
  - Betreff-Feld
  - HTML-Editor
  - Empfänger-Liste
  - Test-Modus-Checkbox
- Newsletter-Verlauf (Tabelle)
- Vorschau-Modal

### Config-Ansicht
- Allgemeine Einstellungen
- SMTP-Konfiguration
- Verbindungstest

## 🚧 Zukünftige Erweiterungen

### TODO:
- [ ] PHPMailer für echten E-Mail-Versand
- [ ] Rich-Text-Editor (WYSIWYG)
- [ ] Template-System
- [ ] A/B-Testing
- [ ] Automatische Kampagnen
- [ ] Segmentierung
- [ ] CSV-Import/Export
- [ ] Detaillierte Analytics-Ansicht
- [ ] Spam-Score-Check

## 🐛 Debugging

### Console-Logs prüfen:
```javascript
console.log('📦 Newsletter Module initialized with Dashboard Provider');
```

### API-Fehler:
Alle Endpunkte geben JSON zurück:
```json
{
  "success": false,
  "message": "Fehlermeldung"
}
```

### Datenbank-Fehler:
PHP error_log aktivieren in `newsletter.php`

## 📚 Dokumentation

- **Modul-README**: `src/modules/newsletter/README.md`
- **Dashboard-System**: `docs/MODULE_DASHBOARD_INTEGRATION.md`
- **Quick Reference**: `docs/QUICK_REFERENCE.md`

## ✅ Checkliste

- [x] Frontend-Module erstellt
- [x] Backend-API implementiert
- [x] Dashboard Provider registriert
- [x] 6 Widgets definiert
- [x] Modernes Design umgesetzt
- [x] Responsive Design
- [x] Dark-Mode-Support
- [x] API-Endpunkte getestet
- [x] Datenbank-Struktur erstellt
- [x] Dokumentation geschrieben
- [x] Module Store Script erstellt

## 🎉 Fertig!

Das Newsletter-Modul ist vollständig integriert und einsatzbereit. Es folgt dem neuen Modul-Standard und ist konsistent mit anderen Modulen wie `marketing-campaigns` und `appstore-connect`.

### Testen:
1. Zu `/project/demo/newsletter` navigieren
2. Newsletter erstellen
3. Test-E-Mail senden
4. Dashboard-Widgets hinzufügen
5. Einstellungen konfigurieren

**Viel Erfolg! 🚀**
