#!/usr/bin/env bash
# Wrapper: usa Docker Compose v2 (plugin) si está instalado; si no, docker-compose v1.
set -euo pipefail
if docker compose version >/dev/null 2>&1; then
  exec docker compose "$@"
fi
echo "AVISO: Usando docker-compose v1 (puede fallar al recrear con KeyError ContainerConfig)." >&2
echo "       Instala v2: bash scripts/install-docker-compose-plugin.sh" >&2
echo "       Recuperación: bash scripts/fix-compose-recreate.sh php" >&2
exec docker-compose "$@"
