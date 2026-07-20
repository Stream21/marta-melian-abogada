#!/usr/bin/env bash
# Recupera PHP cuando falla el recreate del contenedor.
# NO borra volúmenes ni postgres data.
set -euo pipefail

cd "$(dirname "$0")/.."

echo "==> Contenedores php (incluidos huérfanos con prefijo hash)"
docker ps -a --format '{{.ID}} {{.Names}} {{.Status}}' | grep -E 'php|PHP' || echo "(ninguno)"

echo "==> Eliminando TODOS los contenedores cuyo nombre contiene 'php'"
ids=$(docker ps -a --format '{{.ID}} {{.Names}}' | grep -i php | awk '{print $1}' || true)
if [ -n "${ids}" ]; then
  # shellcheck disable=SC2086
  docker rm -f ${ids}
else
  echo "    No había contenedores php."
fi

echo "==> Levantando php (y dependencias)"
bash scripts/compose.sh up -d mailpit postgres php

echo ""
echo "==> Estado final"
docker ps --format 'table {{.Names}}\t{{.Status}}\t{{.Ports}}' | grep -E 'php|mailpit|postgres|NAMES' || true

echo ""
echo "Si aún falla: bash scripts/docker-recover-stack.sh"
