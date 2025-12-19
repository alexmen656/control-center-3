# Newsletter Module - Dokumentation

## Übersicht

Das Newsletter-Modul ermöglicht das Versenden von E-Mail-Newslettern an Abonnenten mit umfassendem Tracking und Analytics.

## Features

- ✅ Newsletter erstellen und versenden
- ✅ HTML-Unterstützung im E-Mail-Editor
- ✅ Empfängerverwaltung
- ✅ Test-E-Mail-Funktion
- ✅ Öffnungs- und Klick-Tracking
- ✅ Dashboard-Widgets für Analytics
- ✅ SMTP-Konfiguration
- ✅ Rate-Limiting
- ✅ Abmelde-Link Integration
- ✅ Newsletter-Verlauf
- ✅ Modernes responsive Design

## Dateistruktur

```
src/modules/newsletter/
├── index.ts                          # Modul-Registrierung
├── routes.ts                         # Route-Definitionen
├── dashboard.provider.ts             # Dashboard-Widgets
└── components/
    ├── NewsletterView.vue           # Haupt-Ansicht
    └── ConfigView.vue                # Einstellungen

backend/
└── newsletter.php                    # API-Endpunkt
```

## Routen

- `/project/:project/newsletter` - Newsletter-Hauptansicht
- `/project/:project/newsletter/config` - Einstellungen

## Dashboard Widgets

### 1. Statistik-Widgets

- **Gesendete Newsletter** (`newsletter-total-sent`)
  - Zeigt Gesamtzahl gesendeter Newsletter
  - Format: Nummer
  - Farbe: Primary

- **Abonnenten** (`newsletter-total-subscribers`)
  - Anzahl aktiver Abonnenten
  - Format: Nummer
  - Farbe: Success

- **Öffnungsrate** (`newsletter-open-rate`)
  - Prozentuale Öffnungsrate
  - Format: Prozent
  - Farbe: Info

- **Klickrate** (`newsletter-click-rate`)
  - Prozentuale Klickrate
  - Format: Prozent
  - Farbe: Warning

### 2. Tabellen-Widget

- **Letzte Newsletter** (`newsletter-recent-campaigns`)
  - Zeigt die letzten gesendeten Newsletter
  - Spalten: Betreff, Empfänger, Status, Gesendet am

### 3. Chart-Widget

- **Newsletter Performance** (`newsletter-performance-chart`)
  - Liniendiagramm mit Versand/Öffnungs/Klick-Daten
  - Zeitraum: 30 Tage (konfigurierbar)

## API Endpunkte

### Newsletter senden
```
POST /backend/newsletter.php
{
  "action": "send",
  "project": "mein-projekt",
  "subject": "Newsletter Betreff",
  "email": "<html>Newsletter Inhalt</html>",
  "recipients": "user1@example.com,user2@example.com",
  "test_mode": false
}
```

### Statistiken abrufen
```
POST /backend/newsletter.php
{
  "action": "get_stats",
  "project": "mein-projekt"
}
```

### Letzte Newsletter abrufen
```
POST /backend/newsletter.php
{
  "action": "get_recent",
  "project": "mein-projekt",
  "limit": 10,
  "offset": 0
}
```

### Performance-Daten abrufen
```
POST /backend/newsletter.php
{
  "action": "get_performance",
  "project": "mein-projekt",
  "period": "30d"
}
```

### Einstellungen speichern
```
POST /backend/newsletter.php
{
  "action": "save_settings",
  "project": "mein-projekt",
  "settings": "{...}"
}
```

### SMTP-Einstellungen speichern
```
POST /backend/newsletter.php
{
  "action": "save_smtp",
  "project": "mein-projekt",
  "smtp": "{...}"
}
```

### SMTP-Verbindung testen
```
POST /backend/newsletter.php
{
  "action": "test_smtp",
  "project": "mein-projekt",
  "smtp": "{...}"
}
```

## Datenbank-Tabellen

Das Modul erstellt automatisch folgende Tabellen:

### `{project}_newsletters`
- Newsletter-Daten
- Betreff, Inhalt, Empfänger
- Status, Versanddatum

### `{project}_newsletter_tracking`
- Tracking-Daten
- Öffnungen, Klicks
- Zeitstempel

### `{project}_newsletter_settings`
- Modul-Einstellungen
- SMTP-Konfiguration

### `{project}_newsletter_subscribers`
- Abonnenten-Verwaltung
- E-Mail, Name, Status

## Verwendung im Dashboard

1. Modul installieren (automatisch verfügbar)
2. Dashboard öffnen
3. **+** Button klicken
4. **"Module Widget"** auswählen
5. **"Newsletter"** als Modul wählen
6. Gewünschtes Widget auswählen
7. **Confirm** klicken

## Konfiguration

### Allgemeine Einstellungen

- **Absender Name**: Name des Newsletter-Absenders
- **Absender E-Mail**: E-Mail-Adresse des Absenders
- **Antwort-E-Mail**: E-Mail für Antworten (optional)
- **E-Mail Vorlage**: Design-Template wählen
- **Tracking**: Öffnungen/Klicks verfolgen
- **Abmelde-Link**: Automatisch einfügen
- **Versandlimit**: E-Mails pro Minute (Rate-Limiting)

### SMTP-Einstellungen

- **Host**: SMTP-Server-Adresse
- **Port**: SMTP-Port (587 für TLS)
- **Benutzername**: SMTP-Login
- **Passwort**: SMTP-Passwort
- **Verschlüsselung**: TLS/SSL/Keine

## Zukünftige Features (TODO)

- [ ] PHPMailer-Integration für echten E-Mail-Versand
- [ ] Drag-and-Drop E-Mail-Editor
- [ ] Newsletter-Templates
- [ ] A/B-Testing
- [ ] Automatische Kampagnen
- [ ] Segment-Verwaltung
- [ ] Import/Export von Abonnenten
- [ ] Detaillierte Analytics
- [ ] Responsive E-Mail-Vorschau
- [ ] Spam-Score-Checker

## Design-System

Das Modul folgt dem modernen Design-System wie `FormDisplay.vue`:

- CSS-Variablen für einfaches Theming
- Responsive Design (Mobile-First)
- Dark-Mode-Unterstützung
- Konsistente Spacing/Typography
- Ionicons für Icons
- Moderne Transitions/Animations

## Beispiel-Nutzung

```javascript
// Widget im Dashboard hinzufügen
{
  chart_type: "module_widget",
  module: "newsletter",
  widget: "newsletter-total-sent"
}
```

## Support

Bei Fragen oder Problemen siehe:
- Hauptdokumentation: `/docs/MODULE_DASHBOARD_INTEGRATION.md`
- Dashboard System: `/docs/DASHBOARD_SYSTEM_README.md`
- Quick Reference: `/docs/QUICK_REFERENCE.md`
