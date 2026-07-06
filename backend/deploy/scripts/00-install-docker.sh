#!/bin/bash
set -e

echo "[docker] installing docker.io"
sudo DEBIAN_FRONTEND=noninteractive apt-get install -y docker.io
sudo systemctl enable --now docker
docker --version

echo "[docker] creating isolated networks"
docker network inspect fringelo-build >/dev/null 2>&1 || \
  docker network create --subnet 172.31.240.0/24 fringelo-build
docker network inspect fringelo-apps >/dev/null 2>&1 || \
  docker network create --subnet 172.31.241.0/24 fringelo-apps

echo "[docker] done"
