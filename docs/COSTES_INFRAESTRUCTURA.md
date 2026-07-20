# Costes de infraestructura y mensajería

Estimación de coste mensual para el despliegue en producción de **Bufete Melián** (`bufete-app-marta`).

**Fecha de referencia:** junio 2026  
**Moneda:** euros (€), salvo indicación contraria  
**IVA:** no incluido en las cifras principales; se indica aparte donde aplica

---

## Alcance

Este documento cubre:

- Servidor VPS y almacenamiento
- Copias de seguridad
- Base de datos (PostgreSQL en el mismo VPS)
- Correo electrónico transaccional
- SMS (OTP de firma de documentos vía Twilio)
- WhatsApp (notificaciones al cliente vía Twilio)

**Queda fuera del total principal:**

| Concepto | Motivo |
|----------|--------|
| **Holded** | Suscripción del bufete (software de facturación); la API de sincronización está incluida en el plan |
| **Stripe** | Comisión por cobro con tarjeta; depende del volumen de pagos, no es cuota fija mensual |

---

## Servicios utilizados por la aplicación

| Servicio | Uso en el proyecto |
|----------|-------------------|
| **VPS** | Docker: PHP-FPM, Nginx, PostgreSQL 16, Mercure, frontend React compilado |
| **PostgreSQL** | Base de datos principal; alojada en el mismo VPS (sin servicio gestionado) |
| **Correo** | Symfony Mailer — altas de expediente, enlaces de pago, contratación, avisos internos |
| **SMS** | Solo OTP de firma de documentos (`FirmaOtpService`) |
| **WhatsApp** | Altas de expediente, reenvío de enlace, enlaces de pago Stripe |
| **Holded** | Facturación y sincronización de contactos/cobros (coste de suscripción aparte) |
| **Stripe** | Pasarela de cobro al cliente final (comisiones aparte) |

---

## Supuestos de la estimación

| Parámetro | Valor |
|-----------|-------|
| VPS | Intermedio (8 GB RAM, ~80 GB disco) — suficiente para la carga prevista |
| Base de datos | PostgreSQL en el mismo VPS (sin BD gestionada externa) |
| Correos/mes | 500 |
| WhatsApp/mes | 500 |
| SMS/mes | 250 |
| Proveedor VPS de referencia | Hetzner Cloud (región EU) u equivalente (OVH, Scaleway) |
| Proveedor correo | Brevo (plan gratuito) o Amazon SES |
| Proveedor SMS/WhatsApp | Twilio |

---

## Costes fijos de infraestructura

| Concepto | €/mes | Notas |
|----------|------:|-------|
| VPS intermedio (8 GB RAM, ~80 GB disco) | 14 | Hetzner CPX32 o equivalente |
| Copias de seguridad | 3 | Snapshots automáticos o Storage Box |
| Dominio (prorrateado) | 1 | ~€10–15/año |
| Número Twilio (SMS) | 2 | Remitente para OTP |
| Base de datos (PostgreSQL en VPS) | 0 | Incluida en el servidor |
| **Subtotal fijo** | **20** | |

---

## Costes variables de mensajería

| Concepto | Volumen/mes | Precio unitario* | €/mes |
|----------|-------------|------------------|------:|
| Correo (Brevo free / SES) | 500 | ~€0 | **0** |
| WhatsApp (Twilio + Meta, categoría utility) | 500 | ~€0,022 | **11** |
| SMS OTP (Twilio España) | 250 | ~€0,08 | **20** |
| **Subtotal variable** | | | **31** |

\* **WhatsApp:** se asume plantillas de categoría *utility* (avisos transaccionales: alta de expediente, enlace de pago, etc.). Si Meta las reclasifica como *marketing*, el coste de WhatsApp podría subir a **~€25–30/mes** (tarifa España ~€0,05–0,06/mensaje).

\* **SMS:** precio Twilio España ~$0,0875/SMS (~€0,08). Solo se usa para OTP de firma de documentos.

\* **Correo:** 500 emails/mes entran en el plan gratuito de Brevo (300 emails/día). Por debajo de ese umbral, coste **€0**.

---

## Resumen total

| | €/mes | €/año |
|--|------:|------:|
| Costes fijos | 20 | 240 |
| Costes variables (mensajería) | 31 | 372 |
| **Total sin IVA** | **~51** | **~610** |
| **Total con IVA 21 %** | **~62** | **~740** |

---

## Costes al margen (no incluidos en el total)

### Holded (suscripción del bufete)

La sincronización con la API de Holded no tiene coste adicional; se paga la suscripción del software de facturación.

| Plan | Facturación anual (promo) | Facturación mensual |
|------|--------------------------|---------------------|
| Plus (autónomo, 250 facturas/año) | ~€7,50/mes | ~€15/mes |
| Básico (hasta 1.000 facturas/año) | ~€14,50/mes | ~€29/mes |
| Estándar (hasta 3.000 facturas/año) | ~€29,50/mes | ~€59/mes |
| Avanzado (hasta 10.000 facturas/año) | ~€49,50/mes | ~€99/mes |

Precios orientativos sin IVA. La promoción del 50 % suele aplicarse con pago anual; al renovar puede pasarse a tarifa completa.

### Stripe (comisiones por cobro)

Los cobros al cliente final se procesan vía Stripe. No es una cuota fija mensual.

| Concepto | Tarifa orientativa |
|----------|-------------------|
| Tarjeta europea | ~1,5 % + €0,25 por transacción exitosa |
| Ejemplo: cobro de 500 € | ~€7,75 de comisión |

El coste real depende del volumen y importe de los cobros mensuales.

---

## Desglose por proveedor

| Proveedor | Servicio | €/mes aprox. |
|-----------|----------|-------------:|
| Hetzner (o similar) | VPS + backups + dominio | 18 |
| Twilio | SMS + WhatsApp + número | 33 |
| Brevo / SES | Correo transaccional | 0 |
| **Total infraestructura + mensajería** | | **~51** |

---

## Notas y riesgos

1. **Precios variables:** Twilio y los proveedores cloud pueden actualizar tarifas. Revisar periódicamente en las consolas de cada proveedor.
2. **WhatsApp:** requiere plantillas aprobadas por Meta en producción. La categoría de cada plantilla afecta directamente al coste por mensaje.
3. **SMS en España:** es el canal más caro (~10× WhatsApp por mensaje). El uso está limitado en la app al OTP de firma.
4. **Correo:** si el volumen supera 9.000 emails/mes (límite diario de Brevo free), habría que pasar a plan de pago (~€9/mes).
5. **Desarrollo:** este documento no incluye mantenimiento, evolución del software ni soporte técnico.
6. **Entorno de desarrollo:** Mailpit y mocks de Holded/Stripe son gratuitos y solo aplican en local.

---

## Referencias

- Configuración Docker: `docker-compose.yml`
- Variables de entorno: `.env.example`, `docs/ENV_DESARROLLO.md`
- Integraciones: Holded (facturación), Stripe (pagos), Twilio (SMS/WhatsApp), Symfony Mailer (correo)
