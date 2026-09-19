#!/usr/bin/env bash
# Database dump + uploaded files, kept for $KEEP_DAYS days (default 14).
# Daily cPanel cron:  30 2 * * *  $HOME/control-proyectos/deploy/backup.sh >/dev/null 2>&1
set -euo pipefail

APP="$(cd "$(dirname "$0")/.." && pwd)"
PHP="${PHP:-php}"
DEST="${BACKUP_DIR:-$HOME/backups/control-proyectos}"
KEEP_DAYS="${KEEP_DAYS:-14}"
STAMP="$(date +%Y%m%d-%H%M%S)"
mkdir -p "$DEST"
chmod 700 "$DEST"

# Database credentials from the app's own configuration (DATABASE_URL in .env.local).
eval "$(cd "$APP" && "$PHP" -r '
  require "vendor/autoload.php";
  (new Symfony\Component\Dotenv\Dotenv())->bootEnv(".env");
  $u = parse_url($_SERVER["DATABASE_URL"]);
  foreach (["DB_HOST" => $u["host"] ?? "localhost", "DB_PORT" => $u["port"] ?? 3306, "DB_USER" => urldecode($u["user"] ?? ""),
            "DB_PASS" => urldecode($u["pass"] ?? ""), "DB_NAME" => ltrim($u["path"] ?? "", "/")] as $k => $v) {
      echo $k, "=", escapeshellarg((string) $v), "\n";
  }')"

# Password through the environment, never on the command line (visible to other users in `ps`).
MYSQL_PWD="$DB_PASS" mysqldump --single-transaction --quick --no-tablespaces \
  -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" "$DB_NAME" | gzip > "$DEST/db-$STAMP.sql.gz"

if [ -d "$APP/var/uploads" ]; then
  tar -czf "$DEST/uploads-$STAMP.tar.gz" -C "$APP/var" uploads
fi

find "$DEST" -type f -mtime +"$KEEP_DAYS" -delete
echo "Backup written to $DEST ($STAMP)"
