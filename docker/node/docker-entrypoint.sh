#!/bin/sh
set -e

MARKER=node_modules/.install-marker

if [ ! -f package.json ]; then
  echo 'Frontend no inicializado. Crea el proyecto React en frontend/'
  sleep infinity
fi

if [ ! -d node_modules ] || [ ! -f "$MARKER" ] || [ package-lock.json -nt "$MARKER" ]; then
  echo 'Instalando dependencias npm…'
  if [ -f package-lock.json ]; then
    npm ci
  else
    npm install
  fi
  touch "$MARKER"
fi

echo 'Iniciando Vite…'
exec npm run dev -- --host 0.0.0.0 --port 5173
