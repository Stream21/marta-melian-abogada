# ngrok en desarrollo (stack completo)

Expone el frontend Vite con una URL pública. Vite reenvía `/api` al backend y `/.well-known/mercure` a Mercure, así que **un solo túnel** basta en el plan gratuito.

## Requisitos

1. Cuenta en [ngrok](https://ngrok.com) y authtoken en `.env`:

```env
NGROK_AUTHTOKEN=tu_token_aqui
```

2. Stack Docker levantado.

## Arranque rápido

```bash
bash scripts/ngrok-up.sh
```

O manualmente:

```bash
docker-compose up -d
docker-compose --profile ngrok up -d ngrok
```

Inspector: [http://localhost:4040](http://localhost:4040)

## Túnel único (plan free)

| Túnel | Destino | Qué resuelve |
|-------|---------|--------------|
| `front` | node:5173 (Vite) | App, portal cliente, API vía proxy, Mercure vía proxy |

**No uses 3 túneles en plan gratuito:** ngrok asigna el mismo dominio y acaba sirviendo Mercure u otro servicio en la raíz `/`.

## Configurar `.env` tras levantar ngrok

Copie la URL del inspector (`front`) y use **la misma** para todo.

**Importante — HMR:** tener `NGROK_FRONT_URL` en `.env` **ya no** cambia el HMR de Vite.
Si trabajas por el túnel ngrok y quieres HMR por WSS, reinicia `node` con `VITE_HMR_NGROK=1`.
En `http://localhost:5173` no hace falta (evita el bucle de recargas).

Variables típicas:

```env
NGROK_FRONT_URL=https://xxxx.ngrok-free.dev
NGROK_API_URL=https://xxxx.ngrok-free.dev
NGROK_MERCURE_URL=https://xxxx.ngrok-free.dev

FRONTEND_BASE_URL=https://xxxx.ngrok-free.dev
FRONTEND_SUCCESS_URL=https://xxxx.ngrok-free.dev/payment/success
FRONTEND_CANCEL_URL=https://xxxx.ngrok-free.dev/payment/cancel
MERCURE_PUBLIC_URL=https://xxxx.ngrok-free.dev
MERCURE_CORS_ORIGINS=http://localhost:5173 http://localhost:8080 http://127.0.0.1:5173 https://xxxx.ngrok-free.dev
```

Reinicie servicios:

```bash
docker-compose restart php node mercure ngrok
```

## Stripe

1. Dashboard → **Developers** → **Webhooks** → **Add endpoint**
2. URL: `https://<NGROK_FRONT_URL>/api/webhooks/stripe`
3. Evento: `checkout.session.completed`
4. Copiar **Signing secret** → `STRIPE_WEBHOOK_SECRET` en `.env`
5. Reiniciar `php`

## Prueba de pago

1. Abrir `https://<NGROK_FRONT_URL>/` → debe verse el **login del bufete**, no «Welcome to Mercure».
2. Portal: `https://<NGROK_FRONT_URL>/acceso/{token}`
3. **Pagar ahora** → tarjeta test `4242 4242 4242 4242`
4. Verificar webhook en inspector ngrok y pantalla **Facturación**

## Notas

- Si ves «Welcome to Mercure» en la URL pública, reinicie ngrok tras el cambio a túnel único: `docker-compose --profile ngrok restart ngrok`
- Las URLs ngrok gratuitas pueden cambiar al reiniciar el túnel; actualice `.env` y el endpoint de Stripe.
