# Web Builder Domain Management

Dieses Feature ermöglicht es Benutzern, eigene Subdomains für ihre Web Builder Projekte zu konfigurieren.

## Übersicht

- **Frontend**: WebBuilderView.vue enthält die UI zur Verwaltung von Subdomains
- **Backend**: web_builder_domains.php verwaltet die Domain-Datenbank
- **Webhook**: web_builder_webhook.php (auf Server 92.5.112.145) für automatisches Nginx/SSL Setup

## Funktionsweise

### 1. Domain-Konfiguration (Frontend)

Benutzer können in der WebBuilderView eine Subdomain eingeben:
- Format: `<subdomain>.sites.control-center.eu`
- Validierung: Nur Kleinbuchstaben, Zahlen und Bindestriche
- Mindestlänge: 3 Zeichen

### 2. Backend-Verarbeitung

Das Backend (`web_builder_domains.php`):
1. Validiert die Subdomain
2. Prüft auf Duplikate
3. Speichert in der `web_builder_domains` Tabelle
4. Triggert einen Webhook für automatisches Setup

### 3. Automatisches Nginx/SSL Setup

Der Webhook (`web_builder_webhook.php`) auf Server 92.5.112.145:
1. Empfängt die Domain-Informationen
2. Erstellt eine Nginx-Konfiguration
3. Testet und lädt Nginx neu
4. Erstellt ein SSL-Zertifikat mit Certbot
5. Meldet den SSL-Status zurück ans Backend

## Datenbank-Schema

```sql
CREATE TABLE web_builder_domains (
    id INT AUTO_INCREMENT PRIMARY KEY,
    projectID VARCHAR(255) NOT NULL,
    domain VARCHAR(255) NOT NULL UNIQUE,
    subdomain VARCHAR(100) NOT NULL,
    is_enabled BOOLEAN DEFAULT TRUE,
    ssl_status ENUM('pending', 'active', 'failed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_domain (domain),
    INDEX idx_projectID (projectID)
);
```

## DNS-Konfiguration

Für jede Subdomain muss ein A-Record erstellt werden:

```
Type: A
Name: <subdomain>.sites.control-center.eu
Value: 92.5.112.145
TTL: 300 (automatisch)
```

## Server-Setup

### Verzeichnisstruktur

Die Web Builder Dateien werden in folgendem Verzeichnis gespeichert:
```
/home/ftpuser/<project_slug>/wb/
```

Das Nginx-Root-Verzeichnis ist ein Symlink zu diesem Ordner:
```
/var/www/web-builder/<project_slug> -> /home/ftpuser/<project_slug>/wb/
```

### Webhook Installation auf 92.5.112.145

1. Erstelle das Webhook-Verzeichnis:
```bash
sudo mkdir -p /var/www/webhook.control-center.eu
```

2. Kopiere die Webhook-Dateien:
```bash
sudo cp backend/webhooks/web_builder_webhook.php /var/www/webhook.control-center.eu/
sudo cp backend/webhooks/setup_web_builder_project.sh /usr/local/bin/
sudo cp backend/webhooks/cleanup_web_builder_project.sh /usr/local/bin/
```

3. Setze die richtigen Berechtigungen:
```bash
sudo chown -R www-data:www-data /var/www/webhook.control-center.eu
sudo chmod 755 /var/www/webhook.control-center.eu
sudo chmod 644 /var/www/webhook.control-center.eu/web_builder_webhook.php

# Scripts ausführbar machen
sudo chmod +x /usr/local/bin/setup_web_builder_project.sh
sudo chmod +x /usr/local/bin/cleanup_web_builder_project.sh
```

4. Erstelle Log-Datei:
```bash
sudo touch /var/log/web_builder_webhook.log
sudo chown www-data:www-data /var/log/web_builder_webhook.log
```

5. Nginx-Konfiguration für Webhook-Domain:
```nginx
server {
    listen 80;
    server_name webhook.control-center.eu;
    
    root /var/www/webhook.control-center.eu;
    index index.php;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
    }
    
    # SSL würde hier von Certbot hinzugefügt
}
```

6. Aktiviere die Konfiguration:
```bash
sudo ln -s /etc/nginx/sites-available/webhook.control-center.eu /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

7. SSL für Webhook-Domain:
```bash
sudo certbot --nginx -d webhook.control-center.eu
```

### Web Builder Basis-Verzeichnisse

Erstelle die Basis-Verzeichnisse:
```bash
# FTP User Verzeichnis
sudo mkdir -p /home/ftpuser
sudo chown ftpuser:ftpuser /home/ftpuser

# Web Builder Symlink-Verzeichnis
sudo mkdir -p /var/www/web-builder
sudo chown -R www-data:www-data /var/www/web-builder
```

### Manuelles Projekt-Setup

Mit dem Setup-Script:
```bash
sudo setup_web_builder_project.sh <project_slug> <domain>

# Beispiel:
sudo setup_web_builder_project.sh myproject myproject.sites.control-center.eu
```

Das Script erstellt:
- Source-Verzeichnis: `/home/ftpuser/<project>/wb/`
- Symlink: `/var/www/web-builder/<project>` -> Source
- Nginx-Konfiguration
- Placeholder index.html
- Optional: SSL-Zertifikat

### Projekt löschen

Mit dem Cleanup-Script:
```bash
sudo cleanup_web_builder_project.sh <project_slug>

# Beispiel:
sudo cleanup_web_builder_project.sh myproject
```

Das Script entfernt:
- Symlink
- Nginx-Konfiguration
- Optional: SSL-Zertifikat
- Optional: Source-Verzeichnis

## FTP-Zugriff

Benutzer können ihre Web Builder Dateien per FTP hochladen:

```
Host:     control-center.eu (oder 92.5.112.145)
Port:     21
User:     ftpuser
Password: <ftpuser_password>
Path:     /home/ftpuser/<project_slug>/wb/
```

### FTP-Server Installation (vsftpd)

Falls noch nicht installiert:
```bash
sudo apt update
sudo apt install vsftpd

# Konfiguration
sudo nano /etc/vsftpd.conf
```

Wichtige Einstellungen:
```
write_enable=YES
local_enable=YES
chroot_local_user=YES
allow_writeable_chroot=YES
```

Restart:
```bash
sudo systemctl restart vsftpd
sudo systemctl enable vsftpd
```

### Webhook Installation auf 92.5.112.145

1. Erstelle das Webhook-Verzeichnis:
```bash
sudo mkdir -p /var/www/webhook.control-center.eu
```

2. Kopiere `web_builder_webhook.php`:
```bash
sudo cp backend/webhooks/web_builder_webhook.php /var/www/webhook.control-center.eu/
```

3. Setze die richtigen Berechtigungen:
```bash
sudo chown -R www-data:www-data /var/www/webhook.control-center.eu
sudo chmod 755 /var/www/webhook.control-center.eu
sudo chmod 644 /var/www/webhook.control-center.eu/web_builder_webhook.php
```

4. Erstelle Log-Datei:
```bash
sudo touch /var/log/web_builder_webhook.log
sudo chown www-data:www-data /var/log/web_builder_webhook.log
```

5. Nginx-Konfiguration für Webhook-Domain:
```nginx
server {
    listen 80;
    server_name webhook.control-center.eu;
    
    root /var/www/webhook.control-center.eu;
    index index.php;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
    }
    
    # SSL würde hier von Certbot hinzugefügt
}
```

6. Aktiviere die Konfiguration:
```bash
sudo ln -s /etc/nginx/sites-available/webhook.control-center.eu /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

7. SSL für Webhook-Domain:
```bash
sudo certbot --nginx -d webhook.control-center.eu
```

### Web Builder Verzeichnis

Erstelle das Basis-Verzeichnis für Web Builder Projekte:
```bash
sudo mkdir -p /var/www/web-builder
sudo chown -R www-data:www-data /var/www/web-builder
```

## API Endpoints

### GET Domain
```
POST web_builder_domains.php
action=get
project=<project_name>
```

### SAVE Domain
```
POST web_builder_domains.php
action=save
project=<project_name>
subdomain=<subdomain>
is_enabled=true/false
```

### DELETE Domain
```
POST web_builder_domains.php
action=delete
project=<project_name>
```

### UPDATE SSL Status (Internal)
```
POST web_builder_domains.php
action=update_ssl_status
project=<project_name>
domain=<full_domain>
ssl_status=pending/active/failed
```

## SSL Status

- **pending**: Zertifikat wird erstellt
- **active**: Zertifikat erfolgreich erstellt
- **failed**: Zertifikat-Erstellung fehlgeschlagen

## Sicherheit

- Webhook verwendet einen geheimen Schlüssel zur Authentifizierung
- Alle Eingaben werden validiert und escaped
- Nginx-Konfigurationen werden vor dem Laden getestet
- Logs werden für Debugging gespeichert

## Troubleshooting

### Symlink-Probleme
- Prüfe ob Symlink existiert: `ls -la /var/www/web-builder/<project>`
- Prüfe ob Source existiert: `ls -la /home/ftpuser/<project>/wb/`
- Erstelle manuell: `sudo ln -s /home/ftpuser/<project>/wb /var/www/web-builder/<project>`

### FTP funktioniert nicht
- Prüfe vsftpd Status: `sudo systemctl status vsftpd`
- Prüfe Berechtigungen: `ls -la /home/ftpuser/`
- Prüfe Logs: `sudo tail -f /var/log/vsftpd.log`

### Dateien werden nicht angezeigt
- Prüfe Nginx Error Log: `sudo tail -f /var/log/nginx/web-builder-<project>.error.log`
- Prüfe ob index.html existiert: `ls -la /home/ftpuser/<project>/wb/index.html`
- Prüfe Berechtigungen: `sudo chmod -R 755 /home/ftpuser/<project>/wb/`

### DNS nicht erreichbar
- Überprüfe A-Record bei DNS-Provider
- Warte auf DNS-Propagierung (bis zu 24h)
- Teste mit: `dig <subdomain>.sites.control-center.eu`

### SSL fehlgeschlagen
- Prüfe Logs: `/var/log/web_builder_webhook.log`
- Stelle sicher, dass Port 80/443 offen sind
- Überprüfe Certbot-Logs: `/var/log/letsencrypt/letsencrypt.log`

### Nginx-Fehler
- Teste Konfiguration: `sudo nginx -t`
- Prüfe Logs: `/var/log/nginx/error.log`
- Überprüfe Berechtigungen der Config-Dateien

## Integration mit bestehenden Features

Diese Domain-Verwaltung ist ähnlich aufgebaut wie:
- Custom Login Domains (in ProjectInfo.vue)
- Project Domains (bereits in der DB)

Alle drei verwenden die gleiche Server-IP (92.5.112.145) und ähnliche Setup-Prozesse.
