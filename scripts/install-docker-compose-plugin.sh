#!/usr/bin/env bash
# Instala Docker Compose v2 (plugin) en WSL Ubuntu.
# Ejecutar una vez: bash scripts/install-docker-compose-plugin.sh
set -euo pipefail

if docker compose version >/dev/null 2>&1; then
  echo "Docker Compose v2 ya está instalado:"
  docker compose version
  exit 0
fi

echo "==> Instalando docker-compose-plugin..."
sudo apt-get update
sudo apt-get install -y docker-compose-plugin

echo ""
echo "==> Verificación"
docker compose version
echo ""
echo "Listo. Usa: docker compose up -d"
