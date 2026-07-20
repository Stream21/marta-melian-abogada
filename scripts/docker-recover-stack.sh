#!/usr/bin/env bash
# Recupera el stack cuando docker-compose v1 falla con KeyError: 'ContainerConfig'.
# Elimina contenedores huérfanos del proyecto y los recrea. NO borra volúmenes de BD.
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

echo "==> Comprobando Docker Compose"
if docker compose version >/dev/null 2>&1; then
  docker compose version
else
  echo "AVISO: solo tienes docker-compose v1. Si vuelve a fallar, ejecuta:"
  echo "  bash scripts/install-docker-compose-plugin.sh"
  docker-compose version || true
fi

echo ""
echo "==> Parando servicios del proyecto (sin -v)"
bash scripts/compose.sh down --remove-orphans 2>/dev/null || docker-compose down --remove-orphans 2>/dev/null || true

echo ""
echo "==> Eliminando contenedores huérfanos bufete-app-marta"
ids=$(docker ps -a --format '{{.ID}} {{.Names}}' | grep -E 'bufete-app-marta' | awk '{print $1}' || true)
if [ -n "${ids}" ]; then
  # shellcheck disable=SC2086
  docker rm -f ${ids}
else
  echo "    (ninguno)"
fi

echo ""
echo "==> Levantando stack completo"
bash scripts/compose.sh up -d

echo ""
echo "==> Levantando ngrok (perfil opcional)"
bash scripts/compose.sh --profile ngrok up -d ngrok 2>/dev/null || echo "    ngrok omitido o ya activo"

echo ""
echo "==> Estado"
docker ps --format 'table {{.Names}}\t{{.Status}}\t{{.Ports}}' | grep -E 'bufete|NAMES' || docker ps

echo ""
echo "Listo. Inspector ngrok: http://localhost:4040"
echo "App local: http://localhost:5173"
