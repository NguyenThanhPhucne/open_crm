#!/usr/bin/env bash

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
CHAT_ROOT="$ROOT_DIR/new/Moji-Drupal"
BACKEND_DIR="$CHAT_ROOT/backend"
FRONTEND_DIR="$CHAT_ROOT/frontend"

if [[ ! -d "$BACKEND_DIR" || ! -d "$FRONTEND_DIR" ]]; then
  echo "Khong tim thay thu muc backend/frontend trong new/Moji-Drupal"
  exit 1
fi

echo "[1/2] Khoi dong Node.js chat backend (port 5001)..."
(
  cd "$BACKEND_DIR"
  npm install
  npm run dev
) &
BACKEND_PID=$!

echo "[2/2] Khoi dong React chat frontend (port 5173)..."
(
  cd "$FRONTEND_DIR"
  npm install
  npm run dev
) &
FRONTEND_PID=$!

echo "Chat stack dang chay. Backend PID=$BACKEND_PID, Frontend PID=$FRONTEND_PID"
echo "Nhan Ctrl+C de dung."

cleanup() {
  kill "$BACKEND_PID" "$FRONTEND_PID" 2>/dev/null || true
}
trap cleanup EXIT
wait
