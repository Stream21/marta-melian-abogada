#!/usr/bin/env bash
# Recupera Mercure cuando falla el recreate del contenedor.
# NO borra volúmenes ni postgres data.
set -euo pipefail

cd "$(dirname "$0")/.."

echo "==> Contenedores mercure (incluidos huérfanos con prefijo hash)"
docker ps -a --format '{{.ID}} {{.Names}} {{.Status}}' | grep -i mercure || echo "(ninguno)"

echo "==> Eliminando TODOS los contenedores cuyo nombre contiene 'mercure'"
ids=$(docker ps -a --format '{{.ID}} {{.Names}}' | grep -i mercure | awk '{print $1}' || true)
if [ -n "${ids}" ]; then
  # shellcheck disable=SC2086
  docker rm -f ${ids}
else
  echo "    No había contenedores mercure."
fi

# Cargar secreto JWT del .env si existe
if [ -f .env ]; then
  # shellcheck disable=SC1091
  set -a
  source .env
  set +a
fi
SECRET="${MERCURE_JWT_SECRET:-ChangeThisMercureHubJWTSecretKeyForDev}"

echo "==> Levantando mercure"
bash scripts/compose.sh up -d mercure

echo ""
echo "==> Estado final"
docker ps --format 'table {{.Names}}\t{{.Status}}\t{{.Ports}}' | grep -E 'mercure|NAMES' || true
echo ""
echo "Prueba: curl -sI http://localhost:3000/.well-known/mercure | head -3"
