# Codespace Domain Management

Diese Erweiterung fügt Domain-Management für Codespaces hinzu, ähnlich wie bei Projekten, aber mit spezifischen Beschränkungen für Codespace-Domains.

## Features

### 1. Codespace Domain-Typen

**Subdomain:**
- Format: `<codespace_subdomain>.<project_domain>.sites.control-center.eu`
- Beispiel: `api.myproject.sites.control-center.eu`
- Mehrere Subdomains pro Projekt möglich

**Haupt-Domain:**
- Format: `<project_domain>.sites.control-center.eu`
- Beispiel: `myproject.sites.control-center.eu`
- Nur ein Codespace pro Projekt kann die Haupt-Domain verwenden

### 2. Datenbankstruktur

**Neue Tabelle: `codespace_domains`**
```sql
CREATE TABLE codespace_domains (
  id int(11) AUTO_INCREMENT PRIMARY KEY,
  codespace_id int(11) NOT NULL,
  domain varchar(255) NOT NULL UNIQUE,
  is_main tinyint(1) DEFAULT 0,
  user_id int(11) NOT NULL,
  created_at timestamp DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (codespace_id) REFERENCES project_codespaces(id) ON DELETE CASCADE
);
```

### 3. Backend API

**Neue Endpoints in `codespace_connections.php`:**

- `connect_domain` - Verbindet eine Domain mit einem Codespace
- `disconnect_domain` - Trennt Domain-Verbindung
- `get_domain` - Ruft Domain-Info für einen Codespace ab
- `get_project_domain_info` - Ruft Projekt-Domain-Info ab

### 4. Frontend Integration

**Neues Settings Modal in ManageCodespaces.vue:**
- Domain-Section mit Radio-Button-Auswahl
- Subdomain-Input für benutzerdefinierte Subdomains
- Haupt-Domain-Option (wenn verfügbar)
- Automatische Vercel und Cloudflare Integration

### 5. Validierung und Beschränkungen

- Projekt muss eine konfigurierte Domain haben
- Vercel-Projekt muss verbunden sein
- Nur eine Haupt-Domain pro Projekt
- Subdomain-Format: nur Kleinbuchstaben, Zahlen und Bindestriche

### 6. Integration mit bestehenden Services

**Vercel:**
- Automatische Domain-Konfiguration
- CNAME-Target Abfrage

**Cloudflare:**
- DNS-Eintrag Erstellung
- CNAME-Record Management

### 7. Verwendung

1. **Projekt-Domain einrichten** (in ProjectInfo.vue)
2. **Codespace erstellen** mit GitHub/Vercel Verbindung
3. **Domain konfigurieren** über Settings-Button
4. **Domain-Typ wählen** (Subdomain oder Haupt-Domain)
5. **Automatische DNS-Konfiguration**

### 8. Migration

Die Datenbankstrukturen werden automatisch erstellt durch:
```bash
php run_codespace_connections_migration.php
```

### 9. Fehlerbehandlung

- Rollback bei Vercel/Cloudflare API-Fehlern
- Validierung vor Domain-Erstellung
- Benutzerfreundliche Fehlermeldungen

### 10. Testing

Test-Script verfügbar unter `test_codespace_domain.php` zur Überprüfung der Funktionalität.
