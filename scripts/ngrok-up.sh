#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

if [[ -z "${NGROK_AUTHTOKEN:-}" ]] && ! grep -qE '^NGROK_AUTHTOKEN=.+' .env 2>/dev/null; then
  echo "Error: define NGROK_AUTHTOKEN en .env (https://dashboard.ngrok.com/get-started/your-authtoken)"
  exit 1
fi

echo "Levantando stack base..."
bash scripts/compose.sh up -d php nginx node mercure postgres mailpit

echo "Levantando ngrok (perfil ngrok)..."
bash scripts/compose.sh --profile ngrok up -d ngrok

echo ""
echo "Esperando inspector ngrok en http://localhost:4040 ..."
for i in $(seq 1 30); do
  if curl -sf http://localhost:4040/api/tunnels >/dev/null 2>&1; then
    break
  fi
  sleep 1
done

echo ""
echo "=== Túnel activo (frontend Vite) ==="
curl -s http://localhost:4040/api/tunnels | python3 -c "
import json, sys
data = json.load(sys.stdin)
for t in data.get('tunnels', []):
    name = t.get('name', '?')
    url = t.get('public_url', '')
    print(f'  {name}: {url}')
" 2>/dev/null || curl -s http://localhost:4040/api/tunnels

echo ""
echo "Un solo túnel (plan free): la URL pública sirve Vite, que reenvía:"
echo "  /api/*              → backend (nginx)"
echo "  /.well-known/mercure → Mercure"
echo ""
echo "Actualiza .env con la URL pública:"
echo "  NGROK_FRONT_URL=     (túnel front)"
echo "  NGROK_API_URL=       (misma URL)"
echo "  NGROK_MERCURE_URL=   (misma URL)"
echo "  FRONTEND_BASE_URL=\${NGROK_FRONT_URL}"
echo "  FRONTEND_SUCCESS_URL=\${NGROK_FRONT_URL}/payment/success"
echo "  FRONTEND_CANCEL_URL=\${NGROK_FRONT_URL}/payment/cancel"
echo "  MERCURE_PUBLIC_URL=\${NGROK_MERCURE_URL}"
echo "  MERCURE_CORS_ORIGINS=... añade \${NGROK_FRONT_URL}"
echo ""
echo "Stripe webhook: https://<api-tunnel>/api/webhooks/stripe"
echo "Inspector: http://localhost:4040"
