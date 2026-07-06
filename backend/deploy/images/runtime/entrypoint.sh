#!/bin/sh
set -e

cd /app

if [ -z "$START_CMD" ]; then
  echo "[runtime] ERROR: no START_CMD provided"
  exit 2
fi

echo "[runtime] starting on port ${PORT} via: $START_CMD"
exec sh -c "$START_CMD"
