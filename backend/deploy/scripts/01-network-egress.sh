#!/bin/bash
set -e

SUBNETS="172.31.240.0/24 172.31.241.0/24"
BLOCK="169.254.169.254/32 10.0.0.0/8 172.16.0.0/12 192.168.0.0/16"

echo "[egress] applying DOCKER-USER isolation rules"

for SUBNET in $SUBNETS; do
  for DEST in $BLOCK; do
    if ! sudo iptables -C DOCKER-USER -s "$SUBNET" -d "$DEST" -j DROP 2>/dev/null; then
      sudo iptables -I DOCKER-USER -s "$SUBNET" -d "$DEST" -j DROP
      echo "[egress] drop $SUBNET -> $DEST"
    fi
  done
done

echo "[egress] persisting rules"
sudo apt-get install -y iptables-persistent >/dev/null 2>&1 || true
sudo netfilter-persistent save 2>/dev/null || sudo sh -c 'iptables-save > /etc/iptables/rules.v4' 2>/dev/null || true

echo "[egress] done (build containers use --dns 1.1.1.1 so DNS does not need the host)"
