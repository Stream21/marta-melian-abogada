#!/usr/bin/env bash
# Deploy a staging + limpia clientes/expedientes de prueba.
# Uso (WSL): bash scripts/deploy-and-purge-staging.sh
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

SSH_KEY="${STAGING_SSH_KEY:-$HOME/.ssh/hetzner-bufete}"
if [[ ! -f "$SSH_KEY" && -f /mnt/c/Users/pivit/.ssh/hetzner-bufete ]]; then
  mkdir -p "$HOME/.ssh"
  cp /mnt/c/Users/pivit/.ssh/hetzner-bufete "$HOME/.ssh/hetzner-bufete"
  chmod 600 "$HOME/.ssh/hetzner-bufete"
  SSH_KEY="$HOME/.ssh/hetzner-bufete"
fi
export STAGING_SSH_KEY="$SSH_KEY"

SSH_HOST="${STAGING_SSH_HOST:-root@178.104.183.66}"
REMOTE_DIR="${STAGING_REMOTE_DIR:-/opt/bufete-app}"
COMPOSE='docker compose -f docker-compose.yml -f docker-compose.staging.yml'

echo "==> 1/4 Deploy"
bash scripts/remote-deploy-staging.sh

echo "==> 2/4 HOLDED_ENV_PREFIX=STG"
ssh -i "$SSH_KEY" -o StrictHostKeyChecking=accept-new "$SSH_HOST" bash -s <<EOF
set -euo pipefail
cd "$REMOTE_DIR"
if grep -q '^HOLDED_ENV_PREFIX=' .env; then
  sed -i 's/^HOLDED_ENV_PREFIX=.*/HOLDED_ENV_PREFIX=STG/' .env
else
  echo 'HOLDED_ENV_PREFIX=STG' >> .env
fi
$COMPOSE up -d php
$COMPOSE exec -T php php bin/console cache:clear --env=prod
EOF

echo "==> 3/4 Purge clientes + expedientes (pruebas)"
# Usa el SQL local (incluye tablas Mercurio nuevas) por si el pull aún no lo trajo.
ssh -i "$SSH_KEY" -o StrictHostKeyChecking=accept-new "$SSH_HOST" \
  "cd $REMOTE_DIR && $COMPOSE exec -T postgres psql -U bufete -d bufete" \
  < scripts/reset-expedientes-clientes.sql

ssh -i "$SSH_KEY" -o StrictHostKeyChecking=accept-new "$SSH_HOST" bash -s <<EOF
set -euo pipefail
cd "$REMOTE_DIR"
$COMPOSE exec -T -u root php sh -c '
  rm -rf /app/var/clientes/* /app/var/expedientes/* 2>/dev/null || true
  mkdir -p /app/var/clientes /app/var/expedientes
  chown -R www-data:www-data /app/var/clientes /app/var/expedientes
'
echo "Conteos:"
$COMPOSE exec -T postgres psql -U bufete -d bufete -c "
  SELECT 'cliente' AS tabla, COUNT(*)::text AS n FROM cliente
  UNION ALL SELECT 'expediente', COUNT(*)::text FROM expediente;
"
EOF

echo "==> 4/4 Health"
curl -sS https://app.martamelianguerraabogados.com/health || true
echo
echo "==> Listo"
