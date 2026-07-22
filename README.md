# Bufete

Backend Symfony 7 + React (frontend en `frontend/`), Arquitectura Hexagonal, Docker. Gestión de expedientes y facturación para bufete de abogados.

## Requisitos

- Docker y Docker Compose (Windows con WSL2 recomendado).

## Puesta en marcha

1. **Copiar variables de entorno** (y rellenar API keys cuando las tengas)

   ```bash
   cp .env.example .env
   ```

2. **Construir y levantar los contenedores** (PHP-FPM, Nginx, PostgreSQL)

   ```bash
   docker compose up -d --build
   ```

3. **Instalar dependencias PHP** (dentro del contenedor)

   ```bash
   docker compose exec php composer install
   ```

4. **Comprobar que la API responde**

   - Abre en el navegador: [http://localhost:8080/health](http://localhost:8080/health)  
   - Deberías ver: `{"status":"ok","app":"bufete"}`

**Frontend (React):** cuando tengas el proyecto en `frontend/`, levanta también Node con:

```bash
docker compose --profile frontend up -d
```

## Comandos útiles (siempre con el servicio `php`)

```bash
docker compose exec php bin/console cache:clear
docker compose exec php bin/console list
docker compose exec php composer require symfony/orm-pack
```

## Staging (VPS / Marta)

Desarrollo local → GitHub → VPS: ver [docs/DEPLOY_STAGING.md](docs/DEPLOY_STAGING.md).

URL staging: https://app.martamelianguerraabogados.com

## Estructura del backend

Ver [ARCHITECTURE.md](ARCHITECTURE.md) para la descripción de la arquitectura hexagonal y la ubicación del frontend React.
