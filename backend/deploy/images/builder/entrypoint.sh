#!/bin/sh
set -e

echo "[build] preparing workspace"
mkdir -p /build /out
cp -a /src/. /build/ 2>/dev/null || true
rm -rf /build/.git
cd /build

if [ -n "$INSTALL_CMD" ]; then
  echo "[build] install: $INSTALL_CMD"
  sh -c "$INSTALL_CMD"
fi

if [ -n "$BUILD_CMD" ]; then
  echo "[build] build: $BUILD_CMD"
  sh -c "$BUILD_CMD"
fi

if [ "$RUNTIME" = "node" ]; then
  echo "[build] pruning dev dependencies"
  npm prune --omit=dev 2>/dev/null || true
  echo "[build] exporting app to /out"
  cp -a /build/. /out/
  rm -rf /out/.git
else
  OUT="${OUTPUT_DIR:-.}"
  if [ ! -d "/build/$OUT" ]; then
    echo "[build] ERROR: output directory '$OUT' not found"
    exit 3
  fi
  echo "[build] exporting /$OUT to /out"
  cp -a "/build/$OUT/." /out/
fi

echo "[build] done"
