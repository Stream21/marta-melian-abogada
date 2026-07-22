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
  echo "Falta .env. Copia .env.example y configura secretos:"
  echo "  cp .env.example .env && nano .env"
  exit 1
fi

echo "==> Build frontend"
cd frontend
npm ci
VITE_API_BASE_URL= npm run build
cd "$ROOT_DIR"

echo "==> Build PHP image"
"${COMPOSE[@]}" build php

echo "==> Start services"
"${COMPOSE[@]}" up -d postgres mailpit mercure php nginx

echo "==> Composer + Symfony"
ensure_bin_console
"${COMPOSE[@]}" exec -T php composer install --no-dev --optimize-autoloader --no-scripts
ensure_bin_console
"${COMPOSE[@]}" exec -T php composer install --no-dev --optimize-autoloader
if [[ ! -f config/jwt/private.pem ]]; then
  "${COMPOSE[@]}" exec -T php php bin/console lexik:jwt:generate-keypair
fi
"${COMPOSE[@]}" exec -T php php bin/console doctrine:migrations:migrate --no-interaction
"${COMPOSE[@]}" exec -T php php bin/console cache:clear --env=prod

echo "==> Done"
echo "App:     https://app.martamelianguerraabogados.com"
echo "Health:  https://app.martamelianguerraabogados.com/health"
echo "Mailpit: http://$(hostname -I | awk '{print $1}'):8025"
echo "Nginx local (detrás de Caddy): http://127.0.0.1:8088"
