#!/bin/bash
set -e

CF_TOKEN="$1"
EMAIL="${2:-admin@fringelo.com}"

if [ -z "$CF_TOKEN" ]; then
  echo "usage: 02-wildcard-cert.sh <cloudflare_api_token> [email]"
  exit 1
fi

echo "[cert] installing certbot cloudflare plugin"
sudo DEBIAN_FRONTEND=noninteractive apt-get install -y python3-certbot-dns-cloudflare

echo "[cert] writing cloudflare credentials"
sudo sh -c "umask 077; printf 'dns_cloudflare_api_token = %s\n' '$CF_TOKEN' > /etc/letsencrypt/cloudflare.ini"

echo "[cert] requesting wildcard cert for apps.fringelo.com + *.apps.fringelo.com"
sudo certbot certonly --dns-cloudflare \
  --dns-cloudflare-credentials /etc/letsencrypt/cloudflare.ini \
  --dns-cloudflare-propagation-seconds 30 \
  -d apps.fringelo.com -d '*.apps.fringelo.com' \
  --cert-name apps.fringelo.com --expand \
  --non-interactive --agree-tos -m "$EMAIL"

echo "[cert] enabling wildcard vhost + reload"
sudo ln -sf /var/www/api.fringelo.com/public_html/deploy/nginx/apps-wildcard.conf /etc/nginx/sites-enabled/apps-wildcard.conf
sudo nginx -t && sudo systemctl reload nginx

echo "[cert] done"
