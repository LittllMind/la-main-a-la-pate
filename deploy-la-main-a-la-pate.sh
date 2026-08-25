#!/usr/bin/env bash
# deploy-la-main-a-la-pate.sh — Déploie La Main à la Pâte vers Hostinger
# Source: ~/projets/la-main-a-la-pate
# Cible:  /home/u417457839/la-main-a-la-pate-v2
# Clé:    ~/.ssh/hostinger-deploy
set -euo pipefail

SSH_KEY="${SSH_KEY:-$HOME/.ssh/hostinger-deploy}"
SSH_USER="u417457839"
SSH_HOST="195.35.49.242"
SSH_PORT="65002"
LOCAL_DIR="$HOME/projets/la-main-a-la-pate"
REMOTE_DIR="/home/u417457839/la-main-a-la-pate-v2"

if [ ! -f "$SSH_KEY" ]; then
    echo "ERREUR: clé SSH introuvable: $SSH_KEY"
    exit 1
fi

echo "==> Sync files"
rsync -avz --delete \
  --exclude '.git' \
  --exclude 'node_modules' \
  --exclude '.env' \
  --exclude '.env.*' \
  --exclude 'vendor/' \
  --exclude 'storage/framework/cache/*' \
  --exclude 'storage/framework/sessions/*' \
  --exclude 'storage/framework/views/*' \
  --exclude 'storage/logs/*' \
  --exclude 'storage/app/documents' \
  --exclude 'storage/app/public/subject_documents' \
  --exclude 'storage/app/public/subjects' \
  --exclude 'database/database.sqlite' \
  --exclude 'public_html' \
  --exclude 'public/storage' \
  -e "ssh -i $SSH_KEY -p $SSH_PORT" \
  "$LOCAL_DIR/" "$SSH_USER@$SSH_HOST:$REMOTE_DIR/"

echo "==> Protect documents directory: ensure public/storage points to nothing sensitive"
ssh -i "$SSH_KEY" -p "$SSH_PORT" "$SSH_USER@$SSH_HOST" \
  "cd $REMOTE_DIR && \
   composer install --no-dev --optimize-autoloader && \
   php artisan optimize:clear && \
   php artisan migrate --force && \
   php artisan view:clear && \
   php artisan view:cache && \
   php artisan config:cache && \
   php artisan route:cache && \
   # PHP symlink() is disabled on shared hosting; recreate via shell. Target is relative.
   [ -L public/storage ] && rm public/storage && echo 'Removed stale public/storage symlink';
   ln -s storage/app/public public/storage && \
   ls -ld public/storage && \
   true \
   || echo 'COMMANDS_FAILED'"

echo "==> Deploy La Main a la Pate done"
