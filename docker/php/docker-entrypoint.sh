#!/bin/sh
set -e

mkdir -p /app/public/storage/despacho
mkdir -p /app/var/cache /app/var/log
mkdir -p /app/var/clientes /app/var/expedientes /app/var/documentos/convertidos

# Evitar chown -R en cada arranque: en WSL puede tardar minutos y bloquea php-fpm.
chmod -R ug+rwX /app/public/storage 2>/dev/null || true
chmod -R ug+rwX /app/var 2>/dev/null || chmod -R 777 /app/var 2>/dev/null || true
# Subcarpetas de uploads: www-data debe poder crear {clienteId}/documento-identidad/
chown -R www-data:www-data /app/var/clientes /app/var/expedientes /app/var/documentos 2>/dev/null || true
chmod -R ug+rwX /app/var/clientes /app/var/expedientes /app/var/documentos 2>/dev/null \
  || chmod -R 777 /app/var/clientes /app/var/expedientes /app/var/documentos 2>/dev/null || true

# Evitar aviso "dubious ownership" al montar el repo desde WSL (composer usa git internamente)
git config --global --add safe.directory /app 2>/dev/null || true

exec docker-php-entrypoint php-fpm
