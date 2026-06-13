#!/usr/bin/env bash
# Recupera Mercure cuando docker-compose v1 falla con KeyError: 'ContainerConfig'
# tras un --force-recreate. NO borra volúmenes ni postgres data.
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

echo "==> Intentando docker-compose up (sin recreate)"
if docker-compose up -d --no-recreate mercure 2>/dev/null; then
  echo "==> Mercure levantado con docker-compose."
else
  echo "==> docker-compose falló; levantando con docker run directo..."

  NETWORK=$(docker network ls --format '{{.Name}}' | grep 'bufete-app-marta' | head -1 || true)
  if [ -z "${NETWORK}" ]; then
    NETWORK="bufete-app-marta_default"
    docker network create "${NETWORK}" 2>/dev/null || true
  fi

  docker run -d \
    --name bufete-app-marta_mercure_1 \
    --network "${NETWORK}" \
    -p 3000:80 \
    -e 'SERVER_NAME=:80' \
    -e "MERCURE_PUBLISHER_JWT_KEY=${SECRET}" \
    -e "MERCURE_SUBSCRIBER_JWT_KEY=${SECRET}" \
    -e 'MERCURE_PUBLISHER_JWT_ALG=HS256' \
    -e 'MERCURE_SUBSCRIBER_JWT_ALG=HS256' \
    -e 'MERCURE_EXTRA_DIRECTIVES=cors_origins http://localhost:5173 http://localhost:8080 http://127.0.0.1:5173' \
    --restart unless-stopped \
    dunglas/mercure

  echo "==> Mercure levantado con docker run en red ${NETWORK}."
fi

echo ""
echo "==> Estado final"
docker ps --format 'table {{.Names}}\t{{.Status}}\t{{.Ports}}' | grep -E 'mercure|NAMES' || true
echo ""
echo "Prueba: curl -sI http://localhost:3000/.well-known/mercure | head -3"
