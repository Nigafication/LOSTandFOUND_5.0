#!/usr/bin/env bash
set -Eeuo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
BACKEND_DIR="$ROOT_DIR/backend"
FRONTEND_DIR="$ROOT_DIR/frontend"

cleanup() {
  trap - EXIT INT TERM
  kill "${BACKEND_PID:-}" "${FRONTEND_PID:-}" 2>/dev/null || true
  wait "${BACKEND_PID:-}" "${FRONTEND_PID:-}" 2>/dev/null || true
}
trap cleanup EXIT INT TERM

if [[ ! -f "$BACKEND_DIR/.env" ]]; then
  cp "$BACKEND_DIR/.env.example" "$BACKEND_DIR/.env"
fi

if [[ ! -f "$BACKEND_DIR/database/database.sqlite" ]]; then
  touch "$BACKEND_DIR/database/database.sqlite"
fi

(cd "$BACKEND_DIR" && php artisan migrate --seed --force && php artisan serve --host=0.0.0.0 --port=8000) &
BACKEND_PID=$!

(cd "$FRONTEND_DIR" && npm run dev -- --hostname 0.0.0.0 --port 3000) &
FRONTEND_PID=$!

echo "Laravel API: http://localhost:8000"
echo "Next.js app: http://localhost:3000"
echo "Press Ctrl+C to stop both servers."
wait
