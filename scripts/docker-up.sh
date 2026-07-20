#!/usr/bin/env bash
# Levanta el stack evitando el KeyError ContainerConfig de docker-compose v1
# y contenedores huérfanos del antiguo servicio mailer.
set -euo pipefail

cd "$(dirname "$0")/.."

COMPOSE=(bash scripts/compose.sh)

cleanup_mailpit() {
  echo "==> Eliminando contenedores mailpit/mailer (incluidos huérfanos con prefijo hash)"
  local ids
  ids=$(docker ps -a --format '{{.ID}} {{.Names}}' | grep -Ei 'mailpit|mailer' | awk '{print $1}' || true)
  if [ -n "${ids}" ]; then
    # shellcheck disable=SC2086
    docker rm -f ${ids}
  else
    echo "    No había contenedores mailpit/mailer."
  fi
}

if docker compose version >/dev/null 2>&1; then
  echo "==> Levantando con docker compose v2"
  cleanup_mailpit
  "${COMPOSE[@]}" up -d "$@"
else
  echo "==> Levantando con docker-compose v1 (recomendado: bash scripts/install-docker-compose-plugin.sh)"
  cleanup_mailpit
  if ! "${COMPOSE[@]}" up -d "$@"; then
    echo "==> Falló el up; down --remove-orphans y reintento..."
    "${COMPOSE[@]}" down --remove-orphans 2>/dev/null || true
    cleanup_mailpit
    "${COMPOSE[@]}" up -d "$@"
  fi
fi

echo ""
"${COMPOSE[@]}" ps
