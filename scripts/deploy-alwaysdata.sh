#!/usr/bin/env bash
set -euo pipefail

: "${DEPLOY_HOST:?Adresse SSH manquante}"
: "${DEPLOY_USER:?Utilisateur SSH manquant}"
: "${DEPLOY_PATH:?Dossier distant manquant}"
: "${DEPLOY_URL:?Adresse HTTPS manquante}"

# Seuls un compte et un dossier explicites sont acceptes dans les commandes distantes.
[[ "$DEPLOY_HOST" =~ ^[a-zA-Z0-9.-]+$ ]]
[[ "$DEPLOY_USER" =~ ^[a-zA-Z0-9_-]+$ ]]
[[ "$DEPLOY_PATH" =~ ^/home/[a-zA-Z0-9_-]+/[a-zA-Z0-9_/-]+$ ]]
[[ "$DEPLOY_PATH" != *..* ]]
[[ "$DEPLOY_URL" == https://* ]]
target="$DEPLOY_USER@$DEPLOY_HOST"
ssh_options=(-o BatchMode=yes -o StrictHostKeyChecking=yes -o ConnectTimeout=20)

# Le serveur conserve son .env, sa cle Laravel et ses donnees.
ssh "${ssh_options[@]}" "$target" "test -f '$DEPLOY_PATH/.env' && test -f '$DEPLOY_PATH/artisan'"
scp "${ssh_options[@]}" -r release "$target:deploy-$GITHUB_RUN_ID-$GITHUB_RUN_ATTEMPT"

ssh "${ssh_options[@]}" "$target" bash -s -- "$DEPLOY_PATH" "deploy-$GITHUB_RUN_ID-$GITHUB_RUN_ATTEMPT" <<'REMOTE'
set -euo pipefail
app="$1"
incoming="$HOME/$2"
cd "$app"
umask 077
mkdir -p "$HOME/deployment-backups"
version="$(date +%Y%m%d-%H%M%S)-$2"
backup="$HOME/deployment-backups/code-$version.tar.gz"
tar -czf "$backup" app bootstrap config database/migrations lang public resources routes artisan composer.json composer.lock
echo "Sauvegarde du code : $backup"

# En cas d'erreur, on retire le mode maintenance sans masquer l'erreur initiale.
trap 'status=$?; php artisan up || true; exit "$status"' EXIT
php artisan down --retry=60
# Execute le nouveau script avec les dependances et la configuration du serveur actuel.
php "$incoming/scripts/backup-database.php" "$HOME/deployment-backups/database-$version.sql" "$app"
rsync -a --exclude=.env --exclude=storage --exclude=vendor --exclude=bootstrap/cache/ --exclude=public/storage --exclude=public/hot "$incoming/" "$app/"
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction --no-progress
php artisan migrate --force
php artisan optimize
php artisan up
trap - EXIT
REMOTE
