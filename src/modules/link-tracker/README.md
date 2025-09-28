# Link Tracker Modul

Dieses Modul ermöglicht es, benutzerdefinierte Kurzlinks mit eigener Subdomain für ein Projekt zu erstellen und die Besucher (inkl. Cloudflare-IP, Gerät, Referer) zu tracken.

## Features
- Benutzerdefinierte Subdomain pro Link
- Weiterleitungsziel frei wählbar
- Tracking von IP (Cloudflare), Gerät, Referer, Zeit
- Übersicht aller Links und Statistiken

## Setup
1. SQL-Migration aus `backend/migrations/20250928_create_link_tracker_tables.sql` ausführen
2. `link_tracker_api.php` und `link_tracker_redirect.php` im Backend bereitstellen
3. DNS/Subdomain-Routing für Projekte konfigurieren (Wildcard-Subdomain)
4. Vue-Komponente einbinden

## Sicherheit
- Nur eingeloggte Nutzer können Links für ihr Projekt erstellen und sehen
- Tracking-Daten werden projekt- und linkbasiert gespeichert
