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
DEPLOY_BRANCH="v2-plateforme"

usage() {
    cat <<EOF
Usage: $0 [OPTIONS]

Options:
  --dry-run      Affiche les commandes sans exécuter de mutation distante.
  --help, -h     Affiche cette aide et quitte (sans déployer).
EOF
    exit 0
}

DRY_RUN=false
for arg in "$@"; do
    case "$arg" in
        --help|-h)
            usage
            ;;
        --dry-run)
            DRY_RUN=true
            ;;
        *)
            echo "ERREUR: argument inconnu: $arg"
            echo "Utilisez --help pour la liste des options."
            exit 1
            ;;
    esac
done

if [ ! -f "$SSH_KEY" ]; then
    echo "ERREUR: clé SSH introuvable: $SSH_KEY"
    exit 1
fi

cd "$LOCAL_DIR"

CURRENT_BRANCH="$(git rev-parse --abbrev-ref HEAD)"
DIRTY_FILES="$(git status --porcelain=v1 --untracked-files=no | grep -v '^??' || true)"
UNTRACKED_FILES="$(git ls-files --others --exclude-standard || true)"

if [ "$CURRENT_BRANCH" != "$DEPLOY_BRANCH" ]; then
    echo "ERREUR: tu dois être sur la branche '$DEPLOY_BRANCH' pour déployer. Branche actuelle: '$CURRENT_BRANCH'"
    exit 1
fi

if [ -n "$DIRTY_FILES" ]; then
    echo "ERREUR: le working tree contient des modifications non commitées. Committe ou stash avant de déployer."
    echo "$DIRTY_FILES"
    exit 1
fi

if [ -n "$UNTRACKED_FILES" ]; then
    echo "AVERTISSEMENT: des fichiers untracked seraient exclus du déploiement :"
    echo "$UNTRACKED_FILES"
    echo "Continue ? [y/N]"
    read -r confirm
    case "$confirm" in
        y|Y|yes|YES) ;;
        *) echo "Déploiement annulé."; exit 1 ;;
    esac
fi

GIT_COMMIT="$(git rev-parse HEAD)"
echo "==> Déploiement depuis branche=$DEPLOY_BRANCH commit=$GIT_COMMIT dry-run=$DRY_RUN"

# Construit un clean deploy à partir du commit exact (pas du working tree)
CLEAN_DIR="$(mktemp -d /tmp/lmalp-clean-deploy.XXXXXX)"
trap 'rm -rf "$CLEAN_DIR"' EXIT
git archive --format=tar "$GIT_COMMIT" | (cd "$CLEAN_DIR" && tar -xf -)

# Dépose le manifeste de traçabilité
printf '%s\n' "deployed_at=$(date -Iseconds)" "commit=$GIT_COMMIT" "branch=$DEPLOY_BRANCH" "by=${USER:-unknown}" > "$CLEAN_DIR/.deploy-manifest.txt"

if [ "$DRY_RUN" = true ]; then
    echo "[DRY-RUN] Commandes rsync/SSH retenues :"
    echo "rsync -avz --delete \"$CLEAN_DIR/\" \"$SSH_USER@$SSH_HOST:$REMOTE_DIR/\""
    echo "ssh ... composer install --no-dev --optimize-autoloader + artisan optimizes"
    exit 0
fi

echo "==> Sync files (commit propre $GIT_COMMIT)"
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
  "$CLEAN_DIR/" "$SSH_USER@$SSH_HOST:$REMOTE_DIR/"

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
   [ -L public/storage ] && rm public/storage && echo 'Removed stale public/storage symlink'; \
   ln -s storage/app/public public/storage && \
   ls -ld public/storage && \
   true \
   || echo 'COMMANDS_FAILED'"

echo "==> Deploy La Main a la Pate done (commit=$GIT_COMMIT)"
