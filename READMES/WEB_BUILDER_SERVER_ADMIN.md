# Web Builder Server - Quick Reference

Schnell-Referenz für die Administration der Web Builder Domains auf Server 92.5.112.145

## Verzeichnisstruktur

```
/home/ftpuser/<project>/wb/           # FTP Upload, Source-Dateien
/var/www/web-builder/<project>/       # Symlink zum Source (Nginx Root)
/etc/nginx/sites-available/web-builder-<project>   # Nginx Config
/etc/nginx/sites-enabled/web-builder-<project>     # Symlink zur Config
/var/log/nginx/web-builder-<project>.access.log    # Access Log
/var/log/nginx/web-builder-<project>.error.log     # Error Log
```

## Häufige Befehle

### Neues Projekt erstellen
```bash
sudo setup_web_builder_project.sh <project> <domain>
```

### Projekt löschen
```bash
sudo cleanup_web_builder_project.sh <project>
```

### Symlink manuell erstellen
```bash
sudo ln -s /home/ftpuser/<project>/wb /var/www/web-builder/<project>
```

### Nginx testen & neu laden
```bash
sudo nginx -t
sudo systemctl reload nginx
```

### SSL-Zertifikat erstellen
```bash
sudo certbot --nginx -d <domain>
```

### SSL-Zertifikat erneuern
```bash
sudo certbot renew
```

### Logs ansehen
```bash
# Webhook Log
sudo tail -f /var/log/web_builder_webhook.log

# Nginx Error Log
sudo tail -f /var/log/nginx/web-builder-<project>.error.log

# Nginx Access Log
sudo tail -f /var/log/nginx/web-builder-<project>.access.log

# FTP Log
sudo tail -f /var/log/vsftpd.log
```

### Berechtigungen setzen
```bash
# Source-Verzeichnis (FTP User)
sudo chown -R ftpuser:ftpuser /home/ftpuser/<project>/wb
sudo chmod -R 755 /home/ftpuser/<project>/wb

# Web-Verzeichnis (www-data)
sudo chown -R www-data:www-data /var/www/web-builder
```

### Alle Web Builder Projekte anzeigen
```bash
# Alle Symlinks
ls -la /var/www/web-builder/

# Alle Nginx Configs
ls -la /etc/nginx/sites-available/ | grep web-builder

# Alle Source-Verzeichnisse
ls -la /home/ftpuser/
```

## Nginx Konfiguration Template

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name <domain>;
    
    root /var/www/web-builder/<project>;
    index index.html index.htm;
    
    autoindex off;
    
    location / {
        try_files $uri $uri/ /index.html;
    }
    
    location /.well-known/acme-challenge/ {
        root /var/www/certbot;
    }
    
    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    
    # Cache static assets
    location ~* \.(css|js|jpg|jpeg|png|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
    
    access_log /var/log/nginx/web-builder-<project>.access.log;
    error_log /var/log/nginx/web-builder-<project>.error.log;
}
```

## FTP-Zugang

### Verbindungsdaten
```
Host:     control-center.eu
Port:     21
User:     ftpuser
Path:     /home/ftpuser/<project>/wb/
```

### FTP-Service verwalten
```bash
# Status prüfen
sudo systemctl status vsftpd

# Neustarten
sudo systemctl restart vsftpd

# Log ansehen
sudo tail -f /var/log/vsftpd.log
```

## Webhook

### Webhook testen
```bash
curl -X POST https://webhook.control-center.eu/web_builder_webhook.php \
  -H "Content-Type: application/json" \
  -d '{
    "domain": "test.sites.control-center.eu",
    "project": "test",
    "type": "web_builder",
    "secret": "cc_web_builder_webhook_secret_2025",
    "timestamp": 1735305600
  }'
```

### Webhook Log
```bash
sudo tail -f /var/log/web_builder_webhook.log
```

## Troubleshooting Checklists

### ❌ Website zeigt 404
1. Prüfe ob Symlink existiert: `ls -la /var/www/web-builder/<project>`
2. Prüfe ob Source existiert: `ls -la /home/ftpuser/<project>/wb/`
3. Prüfe ob index.html existiert: `ls -la /home/ftpuser/<project>/wb/index.html`
4. Prüfe Nginx Error Log: `sudo tail /var/log/nginx/web-builder-<project>.error.log`

### ❌ SSL funktioniert nicht
1. Prüfe Zertifikat: `sudo certbot certificates | grep <project>`
2. Teste Domain: `curl -I https://<domain>`
3. Erstelle neu: `sudo certbot --nginx -d <domain>`
4. Prüfe Certbot Log: `sudo tail /var/log/letsencrypt/letsencrypt.log`

### ❌ FTP Upload funktioniert nicht
1. Prüfe vsftpd: `sudo systemctl status vsftpd`
2. Prüfe Berechtigungen: `ls -la /home/ftpuser/<project>/wb/`
3. Setze Berechtigungen: `sudo chown -R ftpuser:ftpuser /home/ftpuser/<project>/wb`
4. Prüfe FTP Log: `sudo tail -f /var/log/vsftpd.log`

### ❌ Nginx startet nicht
1. Teste Config: `sudo nginx -t`
2. Finde Fehler: `sudo nginx -t 2>&1 | grep error`
3. Prüfe Syntax in Config: `sudo nano /etc/nginx/sites-available/web-builder-<project>`
4. Deaktiviere Site: `sudo rm /etc/nginx/sites-enabled/web-builder-<project>`

## Monitoring

### Disk Space
```bash
# Gesamter Space
df -h

# Pro Projekt
du -sh /home/ftpuser/*
```

### Active Connections
```bash
# Nginx Connections
sudo netstat -tnlp | grep nginx

# FTP Connections
sudo netstat -tnlp | grep vsftpd
```

### SSL Expiry
```bash
# Alle Zertifikate
sudo certbot certificates

# Spezifisches Zertifikat
sudo certbot certificates | grep -A 10 web-builder-<project>
```

## Backup & Restore

### Projekt sichern
```bash
# Archiv erstellen
sudo tar -czf /backup/web-builder-<project>-$(date +%Y%m%d).tar.gz \
  /home/ftpuser/<project>/wb \
  /etc/nginx/sites-available/web-builder-<project>

# Liste der Backups
ls -lh /backup/web-builder-*
```

### Projekt wiederherstellen
```bash
# Archiv extrahieren
sudo tar -xzf /backup/web-builder-<project>-<date>.tar.gz -C /

# Symlink neu erstellen
sudo ln -s /home/ftpuser/<project>/wb /var/www/web-builder/<project>

# Nginx neu laden
sudo systemctl reload nginx
```

## Security

### Firewall (ufw)
```bash
# Status
sudo ufw status

# Ports öffnen
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw allow 21/tcp
```

### Fail2ban für FTP
```bash
# Status
sudo fail2ban-client status vsftpd

# Logs
sudo tail -f /var/log/fail2ban.log
```

### File Permissions Check
```bash
# Alle Dateien prüfen
sudo find /home/ftpuser -type f -not -perm 644 -ls
sudo find /home/ftpuser -type d -not -perm 755 -ls
```

## Performance

### Nginx Status
```bash
# Verbindungen
sudo nginx -V 2>&1 | grep -o with-http_stub_status_module

# Stats (falls aktiviert)
curl http://localhost/nginx_status
```

### Caching
```bash
# Cache löschen
sudo rm -rf /var/cache/nginx/*
sudo systemctl restart nginx
```

## Maintenance Mode

### Maintenance-Seite erstellen
```bash
cat > /tmp/maintenance.html <<EOF
<!DOCTYPE html>
<html>
<head><title>Wartung</title></head>
<body>
  <h1>Wartungsarbeiten</h1>
  <p>Wir sind bald zurück!</p>
</body>
</html>
EOF

# Zu allen Projekten kopieren
for project in /home/ftpuser/*/wb; do
  sudo cp /tmp/maintenance.html "$project/maintenance.html"
done
```

### Maintenance aktivieren (in Nginx Config)
```nginx
location / {
    if (-f $document_root/maintenance.html) {
        return 503;
    }
    try_files $uri $uri/ /index.html;
}

error_page 503 @maintenance;
location @maintenance {
    rewrite ^(.*)$ /maintenance.html break;
}
```
