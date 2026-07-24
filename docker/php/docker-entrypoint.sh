#!/bin/sh
set -e

mkdir -p /app/public/storage/despacho
mkdir -p /app/var/cache/prod/doctrine/orm/Proxies /app/var/log
mkdir -p /app/var/clientes /app/var/expedientes /app/var/documentos/convertidos

# Evitar chown -R de todo /app (código) en cada arranque: en WSL puede tardar minutos.
# Solo directorios de escritura en runtime para php-fpm (www-data).
chown -R www-data:www-data /app/public/storage 2>/dev/null || true
chmod -R ug+rwX /app/public/storage 2>/dev/null || chmod -R 777 /app/public/storage 2>/dev/null || true
# Caché/log: Proxies Doctrine tras cache:clear como root.
chown -R www-data:www-data /app/var/cache /app/var/log 2>/dev/null || true
chmod -R ug+rwX /app/var/cache /app/var/log 2>/dev/null || chmod -R 777 /app/var/cache /app/var/log 2>/dev/null || true
# Uploads: www-data debe poder crear {clienteId}/documento-identidad/
chown -R www-data:www-data /app/var/clientes /app/var/expedientes /app/var/documentos 2>/dev/null || true
chmod -R ug+rwX /app/var/clientes /app/var/expedientes /app/var/documentos 2>/dev/null \
  || chmod -R 777 /app/var/clientes /app/var/expedientes /app/var/documentos 2>/dev/null || true

# Evitar aviso "dubious ownership" al montar el repo desde WSL (composer usa git internamente)
git config --global --add safe.directory /app 2>/dev/null || true

exec docker-php-entrypoint php-fpm
