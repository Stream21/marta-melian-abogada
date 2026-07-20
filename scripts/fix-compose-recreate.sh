#!/usr/bin/env bash
# Recuperación del KeyError 'ContainerConfig' de docker-compose v1 al recrear contenedores.
# Uso: bash scripts/fix-compose-recreate.sh [servicio...]
# Ejemplo: bash scripts/fix-compose-recreate.sh php nginx
set -euo pipefail

cd "$(dirname "$0")/.."

if ! docker compose version >/dev/null 2>&1; then
  echo "AVISO: No tienes Docker Compose v2. Instálalo para evitar este error:"
  echo "  bash scripts/install-docker-compose-plugin.sh"
  echo ""
fi

SERVICES=("$@")
if [ ${#SERVICES[@]} -eq 0 ]; then
  SERVICES=(php)
fi

for service in "${SERVICES[@]}"; do
  ids=$(docker ps -a --format '{{.ID}} {{.Names}} {{.Label "com.docker.compose.service"}}' \
    | awk -v svc="$service" '$3 == svc {print $1}' || true)
  if [ -z "${ids}" ]; then
    ids=$(docker ps -a --format '{{.ID}} {{.Names}}' | awk -v svc="_${service}_" '$2 ~ svc {print $1}' || true)
  fi
  if [ -n "${ids}" ]; then
    echo "==> Eliminando contenedor(es) de ${service}:"
    # shellcheck disable=SC2086
    docker rm -f ${ids}
  else
    echo "==> No hay contenedor previo para ${service}."
  fi
done

echo "==> Levantando servicios..."
bash scripts/compose.sh up -d "${SERVICES[@]}"
bash scripts/compose.sh ps
