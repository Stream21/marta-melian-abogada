#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

COMPOSE=(docker compose -f docker-compose.yml -f docker-compose.staging.yml)

if [[ ! -f .env ]]; then
  echo "Falta .env. Copia .env.example y configura secretos:"
  echo "  cp .env.example .env && nano .env"
  exit 1
fi

echo "==> Build frontend"
cd frontend
npm ci
VITE_API_BASE_URL= npm run build
cd "$ROOT_DIR"

echo "==> Build PHP image"
"${COMPOSE[@]}" build php

echo "==> Start services"
"${COMPOSE[@]}" up -d postgres mailpit mercure php nginx

echo "==> Composer + Symfony"
"${COMPOSE[@]}" exec -T php composer install --no-dev --optimize-autoloader
if [[ ! -f config/jwt/private.pem ]]; then
  "${COMPOSE[@]}" exec -T php php bin/console lexik:jwt:generate-keypair
fi
"${COMPOSE[@]}" exec -T php php bin/console doctrine:migrations:migrate --no-interaction
"${COMPOSE[@]}" exec -T php php bin/console cache:clear --env=prod

echo "==> Done"
echo "App:    http://$(hostname -I | awk '{print $1}')"
echo "Mailpit: http://$(hostname -I | awk '{print $1}'):8025"
