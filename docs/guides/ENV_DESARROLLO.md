# Entornos de desarrollo: Holded, Stripe, Twilio

Guía para obtener credenciales de **desarrollador / sandbox** y ajustar `.env` en local.

---

## 1. Holded (facturación) — Las Palmas / exenta de IVA

Holded **no tiene sandbox** separado. Para desarrollo local la app incluye un **mock** alineado con la API legacy `invoicing/v1` (header `key`, contactos, factura única, cobros parciales `/pay`, PDF). La **API real** usa **v2** con `Authorization: Bearer` (PAT `pat_...`) y rutas `/api/v2/...`.

Régimen: autónomo en **Las Palmas de Gran Canaria** → **exenta de IVA/IGIC (0%)**. Los honorarios acordados se facturan tal cual. Clientes particulares: IRPF 0%.

### Mock local (recomendado en dev)

En `.env`:

```env
HOLDED_API_KEY=dev-mock-key-123
HOLDED_API_BASE_URL=http://nginx/api/mock/holded/invoicing/v1
HOLDED_MOCK_KEY=dev-mock-key-123
HOLDED_TAX_PERCENT=0
HOLDED_TAX_KEY=exento
```

- **Docker:** `http://nginx/api/mock/holded/invoicing/v1` (PHP → nginx interno).
- **Sin Docker:** sustituye `nginx` por `localhost:8080`.
- Estrategia: **una factura** por el total del expediente; cada cuota llama a `POST .../pay`.

Endpoints mock (oficiales):

| Uso | Método / Ruta |
|-----|----------------|
| Contactos | `POST/GET /api/mock/holded/invoicing/v1/contacts` (`GET ?code=` filtra por documento) |
| Factura | `POST /api/mock/holded/invoicing/v1/documents/invoice` |
| Cobro parcial | `POST /api/mock/holded/invoicing/v1/documents/invoice/{id}/pay` |
| PDF | `GET /api/mock/holded/invoicing/v1/documents/invoice/{id}/pdf` |

Alias legacy: `/api/mock/holded/v1/...` reenvía al mock unificado.

### Ejemplos curl (mock)

```bash
# Crear contacto
curl -s -X POST http://localhost:8080/api/mock/holded/invoicing/v1/contacts \
  -H "key: dev-mock-key-123" -H "Content-Type: application/json" \
  -d '{"name":"Cliente Test","email":"test@example.com","code":"X1234567L","type":"client","isperson":true,"billAddress":{"address":"Calle 1","city":"Las Palmas","postalCode":"35001","country":"ES"}}'

# Crear factura (exenta: subtotal = total, sin impuestos)
curl -s -X POST http://localhost:8080/api/mock/holded/invoicing/v1/documents/invoice \
  -H "key: dev-mock-key-123" -H "Content-Type: application/json" \
  -d '{"contactId":"CONTACT_ID","desc":"Expediente 2026-001","date":1710000000,"items":[{"name":"Honorarios","subtotal":1000,"units":1,"taxes":["exento"]}]}'

# Registrar cobro parcial (cuota)
curl -s -X POST http://localhost:8080/api/mock/holded/invoicing/v1/documents/invoice/INVOICE_ID/pay \
  -H "key: dev-mock-key-123" -H "Content-Type: application/json" \
  -d '{"date":1710000000,"amount":500,"desc":"Cuota 1","paymentMethod":"STRIPE"}'

# Descargar PDF
curl -s -o factura.pdf http://localhost:8080/api/mock/holded/invoicing/v1/documents/invoice/INVOICE_ID/pdf \
  -H "key: dev-mock-key-123"
```

Cobro manual desde la app (autenticado):

```bash
curl -s -X POST http://localhost:8080/api/payments/manual \
  -H "Authorization: Bearer TOKEN" -H "Content-Type: application/json" \
  -d '{"expedienteId":"UUID","amount":"500.00","cuotaNumero":1}'
```

Descarga factura del expediente:

```bash
curl -s -o factura.pdf http://localhost:8080/api/expedientes/UUID/invoice/pdf \
  -H "Authorization: Bearer TOKEN"
```

### Cuenta real (API v2 + Bearer)

- **Registro:** [https://app.holded.com](https://app.holded.com) — cuenta en régimen **Canarias / exento** según su fiscalidad.
- **Token:** Configuración → Integraciones → API → Personal Access Token (`pat_...`).
- **En `.env`:**
  - `HOLDED_API_KEY=pat_...`
  - `HOLDED_API_BASE_URL=https://api.holded.com/api/v2`
  - `HOLDED_TAX_KEY=` clave del catálogo v2 (`GET /api/v2/taxes`), p. ej. `s_iva_exento` en cuentas exentas
- **Autenticación:** `Authorization: Bearer <HOLDED_API_KEY>` (no usar header `key` en v2).
- Rutas usadas por la app: `POST/GET /contacts`, `GET /contacts?code={NIF}` (lookup antes de crear), `POST /invoices`, `POST /invoices/{id}/payments`, `GET /invoices/{id}/pdf`.
- **Cliente en factura:** al sincronizar, se consulta Holded por documento (`code`). Si existe se reutiliza el id; si no, se crea el contacto. En ambos casos se guarda en `Cliente.holdedContactId`.

---

## 2. Stripe (pagos y enlaces)

- **Registro:** [https://dashboard.stripe.com/register](https://dashboard.stripe.com/register). La cuenta empieza en **modo test**.
- **Claves de desarrollo (Test):**
  - **Developers** → **API keys**
  - **Publishable key:** `pk_test_...` → `STRIPE_PUBLISHABLE_KEY` (para el frontend).
  - **Secret key:** `sk_test_...` → `STRIPE_SECRET_KEY` (para el backend).
- **Webhook en local:**
  - Instalar [ngrok](https://ngrok.com) o similar y exponer tu backend (ej. `https://abc123.ngrok.io`).
  - En Stripe: **Developers** → **Webhooks** → **Add endpoint**
  - URL: `https://tu-dominio-ngrok/api/webhooks/stripe`
  - Evento: `checkout.session.completed`
  - Copiar el **Signing secret** (`whsec_...`) → `STRIPE_WEBHOOK_SECRET`
- **En `.env`:**
  - `STRIPE_SECRET_KEY=sk_test_...`
  - `STRIPE_WEBHOOK_SECRET=whsec_...`
  - `STRIPE_PUBLISHABLE_KEY=pk_test_...`

Con claves `_test_` no se cobra dinero real.

---

## 3. Twilio (WhatsApp)

- **Registro:** [https://www.twilio.com/try-twilio](https://www.twilio.com/try-twilio). Dan crédito de prueba.
- **Credenciales:** [Console](https://www.twilio.com/console) → **Account SID** y **Auth Token**.
- **WhatsApp en desarrollo:**
  - Opción A – **Sandbox:** [Messaging → Try it out → Send a WhatsApp message](https://console.twilio.com/us1/develop/sms/try-it-out/whatsapp-learn). Te dan un número “From” (ej. `+14155238886`) y un código para unir tu número de prueba al sandbox. Solo puedes enviar a números unidos al sandbox.
  - Opción B – **Cuenta con WhatsApp activado:** Si activas WhatsApp en tu cuenta, usas tu número “From” real (suele tener coste).
- **En `.env`:**
  - `TWILIO_ACCOUNT_SID=` → Account SID de la consola
  - `TWILIO_AUTH_TOKEN=` → Auth Token de la consola
  - `TWILIO_WHATSAPP_FROM=` → Número “From” con código país, sin espacios (ej. `+34123456789` o el del sandbox `+14155238886`)

Para desarrollo suele bastar el **sandbox de WhatsApp** y el crédito de prueba de Twilio.

---

## 4. Correo (Symfony Mailer + Mailpit)

En desarrollo el proyecto incluye **Mailpit** en Docker: captura todos los correos sin enviarlos a internet.

- **Bandeja web:** [http://localhost:8025](http://localhost:8025)
- **SMTP (desde el contenedor php):** `smtp://mailpit:1025`

Variables en `.env`:

```env
MAILER_DSN=smtp://mailpit:1025
MAILER_FROM=notificaciones@bufete.local
MAILER_FROM_NAME=Marta Melián Abogados
```

**Probar:** Configuración del despacho → pestaña *Datos bancarios* → panel *Integración correo* → *Probar correo*.

**Producción:** sustituya `MAILER_DSN` por su proveedor SMTP real (Gmail, SendGrid, Amazon SES, etc.) y use un remitente válido en `MAILER_FROM`.

---

## 5. Resumen `.env` desarrollo

| Variable | Dónde obtenerla |
|----------|------------------|
| `DATABASE_URL` | Coherente con `POSTGRES_*` (ej. usuario `bufete`, contraseña la misma que `POSTGRES_PASSWORD`). |
| `HOLDED_API_KEY` | Holded → Configuración → Integraciones → API. |
| `HOLDED_API_BASE_URL` | Mock: `http://nginx/api/mock/holded/invoicing/v1`. Real v2: `https://api.holded.com/api/v2` |
| `HOLDED_TAX_PERCENT` | `0` (exenta de IVA/IGIC) |
| `HOLDED_TAX_KEY` | Mock: `exento`. Real v2: clave de `GET /api/v2/taxes` (p. ej. `s_iva_exento`) |
| `STRIPE_SECRET_KEY` | Stripe Dashboard → API keys → Secret key (test). |
| `STRIPE_WEBHOOK_SECRET` | Stripe → Webhooks → Endpoint → Signing secret (tras crear el endpoint con ngrok). |
| `STRIPE_PUBLISHABLE_KEY` | Stripe Dashboard → API keys → Publishable key (test). |
| `TWILIO_ACCOUNT_SID` | Twilio Console. |
| `TWILIO_AUTH_TOKEN` | Twilio Console. |
| `TWILIO_WHATSAPP_FROM` | Twilio sandbox WhatsApp o número WhatsApp de tu cuenta. |
| `MAILER_DSN` | Desarrollo: `smtp://mailpit:1025`. Producción: DSN SMTP del proveedor. |
| `MAILER_FROM` | Email remitente (ej. `notificaciones@tudominio.com`). |
| `MAILER_FROM_NAME` | Nombre visible del remitente. |
| `FRONTEND_SUCCESS_URL` | `http://localhost:5173/payment/success` (o la URL de tu front en dev). |
| `FRONTEND_CANCEL_URL` | `http://localhost:5173/payment/cancel` |

Después de editar `.env`, reinicia los contenedores si hace falta: `bash scripts/docker-up.sh` (o `docker compose up -d` si tienes el plugin v2).

**Docker Compose:** el proyecto usa un solo servicio `mailpit` (sin duplicado `mailer`). Si falla con `KeyError: 'ContainerConfig'`, instala el plugin v2: `bash scripts/install-docker-compose-plugin.sh`.

---

## 6. Frontend: proxy API (Docker vs local)

- **Frontend en Docker** (contenedor `node`): El compose define `PROXY_TARGET=http://nginx:80`. Las peticiones a `/api/*` las reenvía Vite al backend (nginx). No hace falta configurar nada más.
- **Frontend en local** (`npm run dev` en tu máquina): Por defecto el proxy usa `http://localhost:8080`. Asegúrate de tener el backend levantado en ese puerto (p. ej. `docker compose up` con php + nginx). Si tu API está en otra URL, define `PROXY_TARGET` antes de `npm run dev`.
