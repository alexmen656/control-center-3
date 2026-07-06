#!/bin/bash
set -e

GW="$(cd "$(dirname "$0")" && pwd)"

echo "[gateway] building image"
sudo docker build -t fringelo/gateway "$GW"

echo "[gateway] (re)starting container"
sudo docker rm -f fringelo-gateway 2>/dev/null || true
sudo docker run -d --restart unless-stopped --name fringelo-gateway --network host \
  -e GW_PORT=8790 \
  -e DB_HOST=127.0.0.1 -e DB_USER=cc_user -e DB_PASS="$DB_PASS" -e DB_NAME=control_center \
  -e DEPLOY_PORT_BASE=21000 -e APPS_DOMAIN=apps.fringelo.com \
  fringelo/gateway

echo "[gateway] enabling nginx vhost"
sudo ln -sf "$GW/gw.conf" /etc/nginx/sites-enabled/gw.fringelo.com
sudo nginx -t && sudo systemctl reload nginx

echo "[gateway] done: https://gw.fringelo.com/health"
