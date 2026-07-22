#!/usr/bin/env bash
# Despliega en el VPS lo que ya está en GitHub (haz git push antes).
# Uso (desde tu PC / WSL):
#   bash scripts/remote-deploy-staging.sh
set -euo pipefail

SSH_KEY="${STAGING_SSH_KEY:-$HOME/.ssh/hetzner-bufete}"
SSH_HOST="${STAGING_SSH_HOST:-root@178.104.183.66}"
REMOTE_DIR="${STAGING_REMOTE_DIR:-/opt/bufete-app}"
GIT_BRANCH="${STAGING_GIT_BRANCH:-main}"

if [[ ! -f "$SSH_KEY" ]]; then
  echo "No encuentro la clave SSH: $SSH_KEY"
  echo "Define STAGING_SSH_KEY o crea ~/.ssh/hetzner-bufete"
  exit 1
fi

echo "==> SSH $SSH_HOST → $REMOTE_DIR (rama $GIT_BRANCH)"
ssh -i "$SSH_KEY" -o StrictHostKeyChecking=accept-new "$SSH_HOST" bash -s <<EOF
set -euo pipefail
cd "$REMOTE_DIR"
git fetch origin
git checkout "$GIT_BRANCH"
git pull origin "$GIT_BRANCH"
bash scripts/deploy-staging.sh
echo "==> Health:"
curl -sS https://app.martamelianguerraabogados.com/health || curl -sS http://127.0.0.1:8088/health || true
echo
EOF

echo "==> Listo. App: https://app.martamelianguerraabogados.com"
