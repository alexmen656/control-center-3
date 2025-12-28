#!/bin/bash

###############################################################################
# Web Builder Project Cleanup Script
# Dieses Script entfernt ein Web Builder Projekt vollständig
#
# Usage: ./cleanup_web_builder_project.sh <project_slug>
# Example: ./cleanup_web_builder_project.sh myproject
###############################################################################

set -e  # Exit on error

# Farben für Output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

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
if [ $# -lt 1 ]; then
    error "Usage: $0 <project_slug>"
    error "Example: $0 myproject"
    exit 1
fi

PROJECT_SLUG="$1"

# Verzeichnisse definieren
WEB_ROOT="/home/ftpuser/$PROJECT_SLUG/wb"
NGINX_CONFIG="/etc/nginx/sites-available/web-builder-$PROJECT_SLUG"
NGINX_ENABLED="/etc/nginx/sites-enabled/web-builder-$PROJECT_SLUG"

# Warnung & Bestätigung
echo ""
warning "ACHTUNG: Dies wird folgende Elemente löschen:"
echo "  - Nginx Config: $NGINX_CONFIG"
echo "  - Nginx Enabled: $NGINX_ENABLED"
echo ""
warning "Optional: Web Root: $WEB_ROOT"
echo ""
read -p "Fortfahren? (j/N) " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Jj]$ ]]; then
    log "Abgebrochen"
    exit 0
fi

log "Cleaning up Web Builder project: $PROJECT_SLUG"

# 1. Nginx Site deaktivieren
if [ -L "$NGINX_ENABLED" ]; then
    log "Removing Nginx enabled link"
    rm "$NGINX_ENABLED"
    log "✓ Nginx site disabled"
fi

# 2. Nginx Konfiguration löschen
if [ -f "$NGINX_CONFIG" ]; then
    log "Removing Nginx configuration"
    rm "$NGINX_CONFIG"
    log "✓ Nginx config removed"
fi

# 3. Nginx neu laden
log "Reloading Nginx"
if nginx -t 2>&1 | grep -q "successful"; then
    systemctl reload nginx
    log "✓ Nginx reloaded"
else
    error "Nginx configuration test failed after cleanup"
fi

# 4. SSL-Zertifikat entfernen (optional)
echo ""
read -p "SSL-Zertifikat auch löschen? (j/N) " -n 1 -r
echo
if [[ $REPLY =~ ^[Jj]$ ]]; then
    log "Looking for certificates to delete..."
    # Finde alle Domains die mit diesem Projekt verknüpft sind
    CERTS=$(certbot certificates 2>/dev/null | grep -A 2 "web-builder-$PROJECT_SLUG" || echo "")
    if [ -n "$CERTS" ]; then
        certbot delete --cert-name "web-builder-$PROJECT_SLUG" --non-interactive || warning "Konnte Zertifikat nicht löschen"
        log "✓ SSL certificate removed"
    else
        warning "Kein Zertifikat gefunden"
    fi
fi

# Erfolgsmeldung
echo ""
log "════════════════════════════════════════════════════════════"
log "✓ Web Builder Project erfolgreich entfernt!"
log "════════════════════════════════════════════════════════════"
echo ""
echo "Project:        $PROJECT_SLUG"
echo ""
warning "Web Root noch vorhanden: $WEB_ROOT"
echo ""
read -p "Web Root auch löschen? (j/N) " -n 1 -r
echo
if [[ $REPLY =~ ^[Jj]$ ]]; then
    if [ -d "$WEB_ROOT" ]; then
        rm -rf "$WEB_ROOT"
        log "✓ Web root removed"
    fi
fi

echo ""
log "Cleanup abgeschlossen"
log "════════════════════════════════════════════════════════════"
