#!/bin/sh
set -e

mkdir -p /app/public/storage/despacho
mkdir -p /app/var/cache /app/var/log

# Evitar chown -R en cada arranque: en WSL puede tardar minutos y bloquea php-fpm.
chmod -R ug+rwX /app/public/storage 2>/dev/null || true
chmod -R ug+rwX /app/var 2>/dev/null || chmod -R 777 /app/var 2>/dev/null || true

exec docker-php-entrypoint php-fpm
