---
name: deploy-production
description: >-
  Deploy Bufete Melián to the production VPS (app.martamelianguerraabogados.com).
  Use when the user asks to deploy, publicar, subir a producción/staging, o
  redesplegar el VPS Hetzner.
---

# Deploy production (VPS)

Production = staging VPS at `/opt/bufete-app` behind Caddy.
URL: https://app.martamelianguerraabogados.com

## Preconditions

- Changes committed and pushed to `origin/main` (or current deploy branch).
- Never overwrite `/opt/bufete-app/.env`.
- Prefer `docker compose` v2 (`bash scripts/compose.sh` if unsure).

## On the VPS (agent often already here)

```bash
cd /opt/bufete-app
git status
git pull origin main
bash scripts/deploy-staging.sh
curl -sS -o /dev/null -w '%{http_code}\n' https://app.martamelianguerraabogados.com/health
```

`deploy-staging.sh` does: frontend `npm ci` + build → `docker compose` build php → up services → composer → migrations → `cache:clear --env=prod` → www-data permissions.

Compose files: `-f docker-compose.yml -f docker-compose.staging.yml`.

## Partial deploys (only if user asks for a shortcut)

| Change | Minimum |
|--------|---------|
| Frontend only | `git pull` + frontend build section of deploy script (or full script) |
| PHP/Symfony only | `git pull` + `compose ... up -d php` + `composer install` + `cache:clear --env=prod` |
| Dockerfile / PHP version | **Full** `deploy-staging.sh` (rebuild image) |
| `.env` edit | Restart `php` (+ `mercure` if JWT secret changed) + `cache:clear` |

## Verify

```bash
curl -sS https://app.martamelianguerraabogados.com/health
docker compose -f docker-compose.yml -f docker-compose.staging.yml exec -T php php -v
docker compose -f docker-compose.yml -f docker-compose.staging.yml ps
```

Expect health `200` / OK and PHP ≥ patched version pinned in `Dockerfile`.

## Do not

- `docker compose down -v` (wipes postgres volume).
- Commit or edit secrets in `.env`.
- Skip frontend rebuild when React/UI files changed (prod serves `frontend/dist`).

## Docs

Long-form: `docs/guides/DEPLOY_STAGING.md`. Remote helper from laptop: `scripts/remote-deploy-staging.sh`.
