#!/bin/bash
set -e

HERE="$(cd "$(dirname "$0")/.." && pwd)"

echo "[images] building fringelo/builder"
docker build -t fringelo/builder "$HERE/images/builder"

echo "[images] building fringelo/runtime"
docker build -t fringelo/runtime "$HERE/images/runtime"

echo "[images] done"
docker images | grep fringelo || true
