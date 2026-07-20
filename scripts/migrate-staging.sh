#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

COMPOSE=(docker compose -f docker-compose.yml -f docker-compose.staging.yml)

ensure_bin_console() {
  if [[ -f bin/console ]]; then
    return 0
  fi

  echo "==> Crear bin/console (no está en Git)"
  mkdir -p bin
  cat > bin/console << 'EOF'
#!/usr/bin/env php
<?php

use App\Kernel;
use Symfony\Bundle\FrameworkBundle\Console\Application;

if (!is_file(dirname(__DIR__).'/vendor/autoload_runtime.php')) {
    throw new LogicException('Symfony Runtime is missing. Run "composer require symfony/runtime".');
}

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (array $context): Application {
    $kernel = new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
    return new Application($kernel);
};
EOF
  chmod +x bin/console
}

if [[ ! -f .env ]]; then
  echo "Falta .env en $ROOT_DIR"
  exit 1
fi

echo "==> Levantar postgres + php"
"${COMPOSE[@]}" up -d postgres php

echo "==> Composer (sin scripts si falta bin/console)"
ensure_bin_console
"${COMPOSE[@]}" exec -T php composer install --no-dev --optimize-autoloader --no-scripts
ensure_bin_console
"${COMPOSE[@]}" exec -T php composer install --no-dev --optimize-autoloader

if [[ ! -f config/jwt/private.pem ]]; then
  echo "==> Generar JWT"
  "${COMPOSE[@]}" exec -T php php bin/console lexik:jwt:generate-keypair
fi

echo "==> Migraciones"
"${COMPOSE[@]}" exec -T php php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

echo "==> Estado migraciones"
"${COMPOSE[@]}" exec -T php php bin/console doctrine:migrations:status

echo "==> Tablas en PostgreSQL"
"${COMPOSE[@]}" exec -T postgres psql -U "${POSTGRES_USER:-bufete}" -d "${POSTGRES_DB:-bufete}" -c '\dt'

echo "==> Done"
