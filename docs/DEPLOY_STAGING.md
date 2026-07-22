# Manual: desarrollo local → staging (Hetzner)

Guía práctica para seguir desarrollando en tu PC y publicar cambios en el entorno compartido con Marta.

---

## Los dos entornos

| | **Desarrollo (tu PC / WSL)** | **Staging (VPS)** |
|--|-----------------------------|-------------------|
| Dónde | `~/.../bufete-app-marta` | `/opt/bufete-app` |
| URL | `http://localhost:5173` (+ API `8080`) o ngrok | **https://app.martamelianguerraabogados.com** |
| Compose | `docker compose up -d` | `docker compose -f docker-compose.yml -f docker-compose.staging.yml` |
| Frontend | Vite en caliente (`npm run dev`) | Build estático en `frontend/dist` |
| `.env` | Local (ngrok, mocks, etc.) | **No tocar** al subir código (secretos del servidor) |
| Quién lo usa | Tú | Tú + Marta |

```
Tu PC (código)  →  git push  →  GitHub  →  git pull en VPS  →  deploy-staging.sh
```

---

## Día a día: desarrollar en local

1. Levanta el stack:

   ```bash
   cd ~/workspace/bufete-app-marta   # tu ruta
   docker compose up -d
   ```

2. Frontend (si no va en Docker):

   ```bash
   cd frontend && npm run dev
   ```

3. Trabaja, prueba, commits como siempre.

4. **No subas** `.env`, `config/jwt/*.pem` ni secretos.

Documentación local útil: `docs/ENV_DESARROLLO.md`, `docs/NGROK_DESARROLLO.md`.

---

## Cómo está montado staging (recordatorio)

```
Internet (HTTPS)
    │
 Caddy  →  app.martamelianguerraabogados.com
    │
 127.0.0.1:8088  →  nginx (Docker)
                      ├── /           → React (frontend/dist)
                      ├── /api, /health → PHP
                      └── /.well-known/mercure → Mercure
```

- IP del VPS: `178.104.183.66`
- SSH: `ssh -i ~/.ssh/hetzner-bufete root@178.104.183.66`
- Mailpit (correos de prueba): `http://178.104.183.66:8025`

---

## Subir cambios a staging (flujo recomendado)

### A) En tu PC — guardar y publicar a GitHub

```bash
cd ~/workspace/bufete-app-marta

git status
git add -A
git commit -m "Describe el cambio"
git push origin main
```

(Usa la rama que tengáis; si no es `main`, cámbiala.)

### B) En el servidor — bajar código y redesplegar

```bash
ssh -i ~/.ssh/hetzner-bufete root@178.104.183.66
```

Dentro del VPS:

```bash
cd /opt/bufete-app

# 1. Código nuevo (no pisa .env)
git pull origin main

# 2. Build frontend + contenedores + migraciones
bash scripts/deploy-staging.sh
```

### C) Comprobar

```bash
curl -s https://app.martamelianguerraabogados.com/health
```

Abre en el navegador: https://app.martamelianguerraabogados.com

---

## Atajo: un solo comando desde tu PC

Desde WSL / Git Bash (con la clave SSH):

```bash
bash scripts/remote-deploy-staging.sh
```

Ese script hace `git pull` + `deploy-staging.sh` por SSH.  
Requisitos: haber hecho `git push` antes, y que el remoto del VPS apunte al mismo repo.

---

## Qué hace `deploy-staging.sh`

1. `npm ci` + `npm run build` del frontend  
2. `docker compose ... build php`  
3. Levanta `postgres`, `mailpit`, `mercure`, `php`, `nginx`  
4. `composer install`  
5. Genera JWT si faltan las claves  
6. Ejecuta migraciones Doctrine  
7. Limpia caché Symfony (`prod`)

**No modifica** el `.env` del servidor.

---

## Cuándo hace falta cada cosa

| Cambio | Qué hacer en staging |
|--------|----------------------|
| Solo PHP / Symfony | `git pull` + `deploy-staging.sh` (o al menos `composer install` + `cache:clear` + migraciones si hay) |
| Frontend React (UI) | `git pull` + **siempre** `deploy-staging.sh` (hay que regenerar `frontend/dist`) |
| Nueva migración BD | `git pull` + `deploy-staging.sh` (incluye `doctrine:migrations:migrate`) |
| Solo texto / docs | No hace falta desplegar |
| Cambio en `.env` del servidor | Editar a mano en el VPS (`nano /opt/bufete-app/.env`) y reiniciar `php` (y `mercure` si cambias su secret) |

Reinicio puntual de PHP tras editar `.env`:

```bash
cd /opt/bufete-app
docker compose -f docker-compose.yml -f docker-compose.staging.yml up -d php
docker compose -f docker-compose.yml -f docker-compose.staging.yml exec php php bin/console cache:clear --env=prod
```

---

## Variables importantes en staging (no sobrescribir)

En `/opt/bufete-app/.env` deben apuntar al subdominio HTTPS:

```env
APP_ENV=prod
APP_DEBUG=0
FRONTEND_BASE_URL=https://app.martamelianguerraabogados.com
FRONTEND_SUCCESS_URL=https://app.martamelianguerraabogados.com/payment/success
FRONTEND_CANCEL_URL=https://app.martamelianguerraabogados.com/payment/cancel
DEFAULT_URI=https://app.martamelianguerraabogados.com
MERCURE_PUBLIC_URL=https://app.martamelianguerraabogados.com
MERCURE_CORS_ORIGINS="https://app.martamelianguerraabogados.com"
```

También: `APP_SECRET`, `MERCURE_JWT_SECRET`, Stripe, Twilio, etc. (solo en el servidor).

---

## Conflictos de `git pull` en el VPS

Si editaste a mano ficheros en el servidor (p. ej. `docker-compose.staging.yml`) y `git pull` se queja:

```bash
cd /opt/bufete-app
git status
# Opción segura: ver diferencias
git diff

# Si quieres quedarte con lo del repo (cuidado: pierdes cambios locales no commitados)
git checkout -- docker-compose.staging.yml docker/nginx/staging.conf
git pull origin main
```

**Nunca** hagas `git clean -fd` sin mirar: puedes borrar uploads o ficheros útiles.

El `.env` no está en Git → `git pull` no lo borra.

---

## Problemas frecuentes

| Síntoma | Qué mirar |
|---------|-----------|
| nginx no arranca / “host not found mercure” | `docker compose ... up -d mercure nginx` y reiniciar nginx |
| Puerto 8080/8088 ocupado | Staging usa `127.0.0.1:8088`; Caddy apunta ahí |
| HTTPS caído | `systemctl status caddy` y `journalctl -u caddy -n 50` |
| UI vieja tras deploy | Confirmar que `deploy-staging.sh` terminó el build; hard refresh (Ctrl+F5) |
| Enlaces WhatsApp con IP antigua | Revisar `FRONTEND_BASE_URL` en `.env` del VPS |
| Migración falla | Ver log; no forzar a ciegas. Revisar estado: `php bin/console doctrine:migrations:status` |

Logs útiles:

```bash
cd /opt/bufete-app
docker compose -f docker-compose.yml -f docker-compose.staging.yml logs -f nginx php
```

---

## Checklist rápido antes de avisar a Marta

- [ ] `git push` hecho desde tu PC  
- [ ] `git pull` + `bash scripts/deploy-staging.sh` en el VPS sin errores  
- [ ] https://app.martamelianguerraabogados.com/health → `ok`  
- [ ] Login funciona  
- [ ] Si tocaste pagos/enlaces: probar un enlace de acceso o pago  

---

## Resumen en 3 líneas

1. Desarrollas en local.  
2. `git push` a GitHub.  
3. En el VPS: `git pull` + `bash scripts/deploy-staging.sh`.
