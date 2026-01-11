#!/bin/bash

###############################################################################
# Web Builder Project Setup Script
# Dieses Script erstellt die notwendigen Verzeichnisse und Symlinks
# für ein Web Builder Projekt
#
# Usage: ./setup_web_builder_project.sh <project_slug> <domain>
# Example: ./setup_web_builder_project.sh myproject myproject.sites.control-center.eu
###############################################################################

set -e  # Exit on error

# Farben für Output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Funktion für Log-Ausgaben
log() {
    echo -e "${GREEN}[$(date +'%Y-%m-%d %H:%M:%S')]${NC} $1"
}

error() {
    echo -e "${RED}[ERROR]${NC} $1" >&2
}

warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

# Check if script is run as root
if [ "$EUID" -ne 0 ]; then 
    error "Bitte als root ausführen (sudo)"
    exit 1
fi

# Parameter prüfen
if [ $# -lt 2 ]; then
    error "Usage: $0 <project_slug> <domain>"
    error "Example: $0 myproject myproject.sites.control-center.eu"
    exit 1
fi

PROJECT_SLUG="$1"
DOMAIN="$2"

# Validiere Project Slug
if ! [[ "$PROJECT_SLUG" =~ ^[a-z0-9-]+$ ]]; then
    error "Project slug darf nur Kleinbuchstaben, Zahlen und Bindestriche enthalten"
    exit 1
fi

log "Setting up Web Builder project: $PROJECT_SLUG"
log "Domain: $DOMAIN"

# Web Root Verzeichnis
WEB_ROOT="/home/ftpuser/$PROJECT_SLUG/wb"
NGINX_CONFIG="/etc/nginx/sites-available/web-builder-$PROJECT_SLUG"
NGINX_ENABLED="/etc/nginx/sites-enabled/web-builder-$PROJECT_SLUG"

# 1. Web Root erstellen
log "Creating web root: $WEB_ROOT"
if [ ! -d "$WEB_ROOT" ]; then
    mkdir -p "$WEB_ROOT"
    chown -R ftpuser:ftpuser "$WEB_ROOT"
    chmod 775 "$WEB_ROOT"  # Group-writable für www-data (Publish-Webhook)
    log "✓ Web root created"
    
    # Erstelle eine einfache index.html als Platzhalter
    cat > "$WEB_ROOT/index.html" <<EOF
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>$PROJECT_SLUG - Web Builder</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .container {
            text-align: center;
            padding: 40px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            backdrop-filter: blur(10px);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }
        h1 { margin: 0 0 20px 0; font-size: 2.5em; }
        p { margin: 10px 0; font-size: 1.2em; opacity: 0.9; }
        .project { 
            font-family: monospace; 
            background: rgba(255, 255, 255, 0.2);
            padding: 10px 20px;
            border-radius: 10px;
            display: inline-block;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 Web Builder Project</h1>
        <div class="project">$PROJECT_SLUG</div>
        <p>Dein Web Builder Projekt ist bereit!</p>
        <p style="font-size: 0.9em; opacity: 0.7;">
            Lade deine Dateien über FTP hoch oder bearbeite sie im Web Builder.
        </p>
    </div>
</body>
</html>
EOF
    chown ftpuser:ftpuser "$WEB_ROOT/index.html"
    chmod 664 "$WEB_ROOT/index.html"  # Group-writable für www-data (Publish-Webhook)
    log "✓ Created placeholder index.html"
else
    warning "Web root already exists"
fi

# 2. Nginx Konfiguration erstellen (HTTP first, Certbot adds SSL)
# Pure static/Vue SPA - no PHP needed (API runs on CC server)
log "Creating Nginx configuration for Vue SPA"
cat > "$NGINX_CONFIG" <<EOF
server {
    listen 80;
    listen [::]:80;
    server_name $DOMAIN;

    root $WEB_ROOT;
    index index.html;

    # Disable directory listing
    autoindex off;

    # Vue Router SPA - fallback to index.html for client-side routing
    location / {
        try_files \$uri \$uri/ /index.html;
    }

    # ACME Challenge für Certbot
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

    # Gzip compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_types text/plain text/css text/xml text/javascript application/javascript application/xml+rss application/json;

    access_log /var/log/nginx/web-builder-$PROJECT_SLUG.access.log;
    error_log /var/log/nginx/web-builder-$PROJECT_SLUG.error.log;
}
EOF
log "✓ Nginx config created for Vue SPA"

# 3. Nginx Site aktivieren
log "Enabling Nginx site"
if [ ! -L "$NGINX_ENABLED" ]; then
    ln -s "$NGINX_CONFIG" "$NGINX_ENABLED"
    log "✓ Site enabled"
else
    warning "Site already enabled"
fi

# 4. Nginx Konfiguration testen
log "Testing Nginx configuration"
if nginx -t 2>&1 | grep -q "successful"; then
    log "✓ Nginx configuration is valid"
else
    error "Nginx configuration test failed"
    nginx -t
    exit 1
fi

# 5. Nginx neu laden
log "Reloading Nginx"
systemctl reload nginx
log "✓ Nginx reloaded"

# 6. SSL-Zertifikat mit Certbot (automatisch)
log "Creating SSL certificate with Certbot"
if certbot --nginx -d "$DOMAIN" --non-interactive --agree-tos --email admin@control-center.eu --redirect 2>&1; then
    log "✓ SSL certificate created successfully"
else
    warning "SSL certificate creation failed - DNS may not be propagated yet"
    warning "Try running manually: certbot --nginx -d $DOMAIN"
fi

# Erfolgsmeldung
echo ""
log "════════════════════════════════════════════════════════════"
log "✓ Web Builder Project erfolgreich eingerichtet!"
log "════════════════════════════════════════════════════════════"
echo ""
echo "Project:        $PROJECT_SLUG"
echo "Domain:         $DOMAIN"
echo "Web Root:       $WEB_ROOT"
echo "Nginx Config:   $NGINX_CONFIG"
echo ""
echo "FTP Upload:"
echo "  Host:         control-center.eu"
echo "  User:         ftpuser"
echo "  Path:         /home/ftpuser/$PROJECT_SLUG/wb/"
echo ""
echo "Test URL:       http://$DOMAIN"
echo ""
log "════════════════════════════════════════════════════════════"
