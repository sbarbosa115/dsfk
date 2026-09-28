#!/usr/bin/env bash
# Run by deploy/cpanel-update.sh after it copies a new release into the app folder.
# Can be re-run by hand from the app folder:
#
#   PHP=/opt/cpanel/ea-php84/root/usr/bin/php  (if plain `php` on the command line is not 8.4+)
#
# Steps: backup → remove files deleted in this version → cache → migrations → checks.
set -euo pipefail

APP="$(cd "$(dirname "$0")/.." && pwd)"
PHP="${PHP:-php}"
cd "$APP"
log() { echo "[$(date '+%F %T')] $*"; }

if [ ! -f .env.local ]; then
  echo "Missing $APP/.env.local — copy deploy/env.local.example to .env.local and fill it in." >&2
  exit 1
fi
"$PHP" -r 'exit(PHP_VERSION_ID >= 80401 ? 0 : 1);' || { echo "PHP 8.4.1+ required on the command line ($("$PHP" -r 'echo PHP_VERSION;')). Set PHP=/opt/cpanel/ea-php84/root/usr/bin/php" >&2; exit 1; }

log "Version $(cat VERSION 2>/dev/null || echo unknown)"

if "$PHP" bin/console dbal:run-sql -q "SELECT 1 FROM budget LIMIT 1" >/dev/null 2>&1; then
  log "Backing up before migrating"
  PHP="$PHP" "$APP/deploy/backup.sh"
fi

log "Removing files deleted in this version"
if [ -f MANIFEST ]; then
  dirs=""
  # public/app: the frontend of versions before the move to Symfony UX (public/build), removed once.
  for dir in src config templates migrations vendor public/build public/app translations bin; do
    [ -d "$dir" ] && dirs="$dirs $dir"
  done
  # Never run find without folders: it would walk the whole app folder, .env.local and var/ included.
  if [ -n "$dirs" ]; then
    # Files on disk that the new release does not contain (LC_ALL=C: same sort order as the build).
    find $dirs -type f | LC_ALL=C sort | LC_ALL=C comm -23 - <(LC_ALL=C sort MANIFEST) | while read -r file; do
      rm -f "$file" && echo "  removed $file"
    done
    # Folders left empty, such as public/app from before the move to Symfony UX.
    find $dirs -type d -empty -delete
  fi
fi

log "Refreshing cache"
rm -rf var/cache/prod
"$PHP" bin/console cache:warmup -q

log "Running database migrations"
"$PHP" bin/console doctrine:migrations:migrate -n --allow-no-migration

log "Checking the installation"
"$PHP" bin/console app:doctor
log "Update finished"
