#!/bin/bash

# Custom Login Domain Setup Script
# Dieses Script muss auf dem Frontend-Server (92.5.112.145) liegen
# und wird von einem Webhook oder Cron Job aufgerufen

# Konfiguration
NGINX_SITES_AVAILABLE="/etc/nginx/sites-available"
NGINX_SITES_ENABLED="/etc/nginx/sites-enabled"
WEBROOT="/home/ftpuser/control-center-app"  # Pfad zum Frontend Build
BACKEND_URL="https://alex.polan.sk/control-center"
LOG_FILE="/var/log/custom-login-setup.log"

# Logging Funktion
log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" >> "$LOG_FILE"
    echo "$1"
}

# SSL mit Certbot einrichten
setup_ssl() {
    local domain=$1
    
    log "Setting up SSL for $domain..."
    
    # Certbot für SSL
    if command -v certbot &> /dev/null; then
        certbot --nginx -d "$domain" --non-interactive --agree-tos --email admin@control-center.eu --redirect
        
        if [ $? -eq 0 ]; then
            log "SSL successfully configured for $domain"
            # SSL Status im Backend aktualisieren
            curl -s -X POST "${BACKEND_URL}/custom_login_domains.php" \
                -d "action=updateSslStatus&domain=${domain}&status=active"
            return 0
        else
            log "SSL configuration failed for $domain"
            curl -s -X POST "${BACKEND_URL}/custom_login_domains.php" \
                -d "action=updateSslStatus&domain=${domain}&status=failed"
            return 1
        fi
    else
        log "Certbot not installed. Please install certbot first."
        return 1
    fi
}

# Nginx Konfiguration erstellen
create_nginx_config() {
    local domain=$1
    local config_file="${NGINX_SITES_AVAILABLE}/${domain}"
    
    log "Creating Nginx config for $domain..."
    
    # Nginx Config Template
    cat > "$config_file" << EOF
server {
    listen 80;
    listen [::]:80;
    server_name ${domain};
    
    root ${WEBROOT};
    index index.html;
    
    # Gzip Compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_proxied any;
    gzip_types text/plain text/css text/xml text/javascript application/x-javascript application/xml application/javascript;
    gzip_disable "MSIE [1-6]\.";
    
    # Security Headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    
    # Backend Proxy (für API Calls)
    location /backend/ {
        proxy_pass https://alex.polan.sk/control-center/backend/;
        proxy_http_version 1.1;
        proxy_set_header Upgrade \$http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host alex.polan.sk;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;
        proxy_cache_bypass \$http_upgrade;
    }
    
    # Static Assets mit Caching
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        try_files \$uri =404;
    }
    
    # Vue Router - Alle anderen Requests an index.html weiterleiten
    location / {
        try_files \$uri \$uri/ /index.html;
    }
    
    # Health Check Endpoint
    location /health {
        access_log off;
        return 200 "OK";
        add_header Content-Type text/plain;
    }
}
EOF
    
    # Symlink erstellen
    ln -sf "$config_file" "${NGINX_SITES_ENABLED}/${domain}"
    
    # Nginx Konfiguration testen
    nginx -t
    
    if [ $? -eq 0 ]; then
        log "Nginx config valid for $domain"
        return 0
    else
        log "Nginx config invalid for $domain"
        rm -f "$config_file" "${NGINX_SITES_ENABLED}/${domain}"
        return 1
    fi
}

# Domain hinzufügen
add_domain() {
    local domain=$1
    
    if [ -z "$domain" ]; then
        log "Error: No domain provided"
        exit 1
    fi
    
    log "Adding custom login domain: $domain"
    
    # Prüfen ob bereits existiert
    if [ -f "${NGINX_SITES_AVAILABLE}/${domain}" ]; then
        log "Domain $domain already configured"
        return 0
    fi
    
    # Nginx Config erstellen
    create_nginx_config "$domain"
    
    if [ $? -ne 0 ]; then
        log "Failed to create Nginx config for $domain"
        exit 1
    fi
    
    # Nginx reload
    systemctl reload nginx
    
    # SSL einrichten
    setup_ssl "$domain"
    
    # Nginx reload nach SSL
    systemctl reload nginx
    
    log "Successfully added domain: $domain"
}

# Domain entfernen
remove_domain() {
    local domain=$1
    
    if [ -z "$domain" ]; then
        log "Error: No domain provided"
        exit 1
    fi
    
    log "Removing custom login domain: $domain"
    
    # Nginx Konfiguration entfernen
    rm -f "${NGINX_SITES_AVAILABLE}/${domain}"
    rm -f "${NGINX_SITES_ENABLED}/${domain}"
    
    # Nginx reload
    nginx -t && systemctl reload nginx
    
    log "Successfully removed domain: $domain"
}

# Alle Domains synchronisieren (aus DB)
sync_domains() {
    log "Syncing all custom login domains..."
    
    # Domains aus Backend holen
    # Dies erfordert einen speziellen Admin-Endpoint
    local domains=$(curl -s "${BACKEND_URL}/custom_login_config.php?action=listAllDomains")
    
    # TODO: Implementierung der Sync-Logik
    log "Sync completed"
}

# Usage
usage() {
    echo "Usage: $0 {add|remove|sync} [domain]"
    echo ""
    echo "Commands:"
    echo "  add <domain>    - Add a new custom login domain"
    echo "  remove <domain> - Remove a custom login domain"
    echo "  sync            - Sync all domains from database"
    echo ""
    echo "Examples:"
    echo "  $0 add login.example.com"
    echo "  $0 remove login.example.com"
    echo "  $0 sync"
}

# Main
case "$1" in
    add)
        add_domain "$2"
        ;;
    remove)
        remove_domain "$2"
        ;;
    sync)
        sync_domains
        ;;
    *)
        usage
        exit 1
        ;;
esac
