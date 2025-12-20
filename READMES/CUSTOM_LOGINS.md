# Custom Login Domains - Setup Dokumentation

## Übersicht

Custom Login Domains ermöglichen es Projekten, eine eigene Domain für die Login-Seite zu verwenden, mit eigenem Logo und Branding.

### Zwei Modi

1. **Interne Domains (*.control-center.eu)** → 100% automatisch
   - Cloudflare DNS wird automatisch konfiguriert
   - Nginx wird automatisch eingerichtet
   - SSL wird automatisch via Cloudflare bereitgestellt

2. **Externe Domains** → Manuelles Setup
   - User muss DNS selbst konfigurieren
   - User muss Nginx/SSL selbst einrichten
   - System zeigt benötigte Konfiguration an

## Architektur

```
┌──────────────────┐     ┌──────────────────┐     ┌──────────────────┐
│   Frontend       │     │   Backend        │     │  Frontend Server │
│   (Vue.js)       │────>│   (PHP API)      │────>│  (Nginx/SSL)     │
│   ProjectInfo    │     │   Cloudflare     │     │  92.5.112.145    │
└──────────────────┘     └──────────────────┘     └──────────────────┘
                                │
                                │ nur für *.control-center.eu
                                ▼
                         ┌──────────────────┐
                         │   Cloudflare     │
                         │   DNS + SSL      │
                         └──────────────────┘
```

## Dateien

### Backend
- `backend/custom_login_domains.php` - Haupt-API für Custom Login Verwaltung
- `backend/custom_login_config.php` - Öffentlicher Endpoint für Login Config (ohne Auth)
- `backend/migrations/create_custom_login_domains_table.sql` - DB Schema

### Frontend
- `src/views/ProjectInfo.vue` - UI zum Konfigurieren der Custom Login Domain
- `src/views/LogIn.vue` - Angepasste Login-Seite mit Custom Branding Support

### Server Scripts (auf Frontend Server)
- `scripts/setup_custom_login_domain.sh` - Nginx & SSL Setup Script
- `scripts/custom_login_webhook.php` - Webhook Endpoint für automatisches Setup

## Datenbank

```sql
CREATE TABLE IF NOT EXISTS custom_login_domains (
    id INT AUTO_INCREMENT PRIMARY KEY,
    projectID VARCHAR(255) NOT NULL,
    domain VARCHAR(255) NOT NULL UNIQUE,
    domain_type ENUM('internal', 'external') DEFAULT 'internal',
    is_enabled BOOLEAN DEFAULT FALSE,
    primary_color VARCHAR(20) DEFAULT '#e53e3e',
    logo_url VARCHAR(500) DEFAULT NULL,
    company_name VARCHAR(255) DEFAULT NULL,
    cloudflare_record_id VARCHAR(100) DEFAULT NULL,
    ssl_status ENUM('pending', 'active', 'failed', 'manual') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

## Ablauf

### Interne Domain (z.B. meinefirma.control-center.eu)

1. **Admin wählt in ProjectInfo.vue:**
   - Domain-Typ: "Subdomain von control-center.eu"
   - Subdomain eingeben (z.B. `meinefirma`)
   - Logo URL, Farbe, Firmenname konfigurieren
   - Aktivieren & Speichern

2. **Backend (automatisch):**
   - Erstellt Cloudflare A-Record auf 92.5.112.145
   - Speichert in DB mit `domain_type='internal'`
   - Triggert Nginx Webhook

3. **Frontend Server (automatisch):**
   - Erstellt Nginx Konfiguration
   - SSL via Cloudflare Proxy (kein Certbot nötig)

### Externe Domain (z.B. login.meinefirma.de)

1. **Admin wählt in ProjectInfo.vue:**
   - Domain-Typ: "Externe Domain"
   - Vollständige Domain eingeben
   - Logo URL, Farbe, Firmenname konfigurieren
   - Speichern

2. **Backend:**
   - Speichert in DB mit `domain_type='external'`
   - **Kein** Cloudflare oder Nginx Setup

3. **User muss manuell:**
   - DNS A-Record erstellen → 92.5.112.145
   - Nginx auf eigenem Server konfigurieren
   - SSL einrichten (Certbot, Cloudflare, etc.)

## Setup auf dem Frontend Server (92.5.112.145)

### 1. Scripts kopieren

```bash
# Scripts nach /var/www/control-center/scripts/ kopieren
cp scripts/setup_custom_login_domain.sh /var/www/control-center/scripts/
cp scripts/custom_login_webhook.php /var/www/control-center/webhook/

# Ausführbar machen
chmod +x /var/www/control-center/scripts/setup_custom_login_domain.sh
```

### 2. Sudoers konfigurieren

```bash
# Erlaube www-data das Script ohne Passwort auszuführen
echo "www-data ALL=(ALL) NOPASSWD: /var/www/control-center/scripts/setup_custom_login_domain.sh" | sudo tee /etc/sudoers.d/custom-login
```

### 3. Certbot installieren

```bash
sudo apt install certbot python3-certbot-nginx
```

### 4. Nginx Konfiguration für Webhook

```nginx
# In der Haupt-Nginx-Config oder als separater Server Block
location /webhook/ {
    alias /var/www/control-center/webhook/;
    index index.php;
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $request_filename;
        include fastcgi_params;
    }
}
```

### 5. Log-Verzeichnisse erstellen

```bash
sudo touch /var/log/custom-login-setup.log
sudo touch /var/log/custom-login-webhook.log
sudo chown www-data:www-data /var/log/custom-login-*.log
```

## Cloudflare Konfiguration

Da Cloudflare Proxy (`proxied: true`) verwendet wird, übernimmt Cloudflare automatisch:
- SSL Termination (Full oder Full Strict)
- DDoS Schutz
- CDN/Caching

**Wichtig:** In den Cloudflare SSL/TLS Einstellungen "Full (strict)" verwenden.

## Sicherheit

- Webhook ist durch Secret Token geschützt (`WEBHOOK_SECRET`)
- Öffentlicher Config-Endpoint gibt nur aktivierte Domains zurück
- Domain-Validierung gegen SQL Injection und Path Traversal

## Troubleshooting

### Domain funktioniert nicht
1. Cloudflare DNS prüfen (A-Record auf 92.5.112.145)
2. Nginx Config prüfen: `nginx -t`
3. SSL Status in DB prüfen
4. Logs checken: `/var/log/custom-login-*.log`

### SSL Fehler
1. Certbot manuell ausführen: `certbot --nginx -d domain.de`
2. Cloudflare SSL Mode auf "Full" setzen

### Webhook Fehler
1. Webhook Secret überprüfen
2. PHP Error Log checken
3. Sudoers Konfiguration verifizieren
