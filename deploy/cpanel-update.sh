#!/usr/bin/env bash
# Updates this deployment to the latest commit on GitHub. Made for a cPanel account over SSH:
#
#   cd ~/dsfk && ./deploy/cpanel-update.sh
#
# It pulls, installs the PHP dependencies, rebuilds the UI, runs the database migrations (after a backup), clears
# the production cache, fixes what has to be writable and ends with the app:doctor checklist. Every step is safe to
# run again.
#
# First time on a new account, before this script is of any use (docs/DEPLOYMENT.md has the details):
#   1. git clone <repo> ~/dsfk            (a read-only deploy key for the SSH remote, or HTTPS + a token)
#   2. Point the domain's document root at ~/dsfk/backend/public
#   3. Create the database and user in cPanel, and leave the database empty: the migrations build it
#   4. cp deploy/env.local.example backend/.env.local and fill it in (chmod 600)
#   5. Run this script
#
# Settings it takes from the environment, all optional:
#   PHP           path to the PHP 8.4 CLI      (detected)
#   COMPOSER      path to composer             (detected)
#   NODE_DIR      folder with node and npm     (detected; cPanel installs it under /opt/alt/alt-nodejsNN)
#   BRANCH        branch to pull               (the checked out one)
#   SKIP_PULL     =1 to rebuild without pulling
#   ALLOW_DIRTY   =1 to run with local changes to tracked files (the pull fails if it touches one)
#   BACKUP_ALWAYS =1 to dump the database even when no migration is pending
#   BACKUP_DIR    where that dump goes         (default: ~/backups/control-proyectos, next to the nightly ones)
set -euo pipefail

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
BACKEND="$APP_DIR/backend"
cd "$APP_DIR"

step() { printf '\n\033[1;32m==> %s\033[0m\n' "$*"; }
note() { printf '    %s\n' "$*"; }
warn() { printf '\033[1;33m    %s\033[0m\n' "$*" >&2; }
fail() { printf '\033[1;31m%s\033[0m\n' "$*" >&2; exit 1; }

# The first PHP 8.4.1+ CLI that exists. cPanel installs each version under /opt/cpanel, and "php" is often an old one.
find_php() {
    local candidate
    for candidate in "${PHP:-}" ea-php84 /opt/cpanel/ea-php84/root/usr/bin/php ea-php85 /opt/cpanel/ea-php85/root/usr/bin/php php84 php85 php; do
        [[ -n "$candidate" ]] || continue
        command -v "$candidate" >/dev/null 2>&1 || continue
        if "$candidate" -r 'exit(PHP_VERSION_ID >= 80401 ? 0 : 1);' 2>/dev/null; then
            command -v "$candidate"
            return 0
        fi
    done
    return 1
}

find_composer() {
    local candidate
    for candidate in "${COMPOSER:-}" composer "$HOME/composer.phar" "$APP_DIR/composer.phar"; do
        [[ -n "$candidate" ]] || continue
        if command -v "$candidate" >/dev/null 2>&1; then command -v "$candidate"; return 0; fi
        if [[ -f "$candidate" ]]; then echo "$candidate"; return 0; fi
    done
    return 1
}

# Webpack Encore 7 needs Node 20.19+ or 22.12+. cPanel's "Setup Node.js App" installs it under /opt/alt without
# putting it on the PATH of an SSH session.
node_ok() { "$1/node" -e 'const [a,b]=process.versions.node.split(".").map(Number); process.exit(a>22||(a===22&&b>=12)||(a===20&&b>=19)?0:1)' 2>/dev/null; }
find_node_dir() {
    local candidate
    for candidate in "${NODE_DIR:-}" "$(dirname "$(command -v node 2>/dev/null || echo /nonexistent/node)")" \
                     /opt/alt/alt-nodejs24/root/usr/bin /opt/alt/alt-nodejs22/root/usr/bin /opt/alt/alt-nodejs20/root/usr/bin; do
        [[ -n "$candidate" && -x "$candidate/node" && -x "$candidate/npm" ]] || continue
        if node_ok "$candidate"; then echo "$candidate"; return 0; fi
    done
    return 1
}

step "Checking this account"
[[ -d "$APP_DIR/.git" ]] || fail "$APP_DIR is not a git clone: see the first-time steps at the top of this script."
[[ -f "$BACKEND/.env.local" ]] || fail "backend/.env.local is missing: copy deploy/env.local.example, fill it in, then chmod 600 it."
grep -q '^APP_ENV=prod' "$BACKEND/.env.local" || fail "backend/.env.local must set APP_ENV=prod."
if grep -qE '^[^#]*(change-me|:PASSWORD@|yourdomain)' "$BACKEND/.env.local"; then
    fail "backend/.env.local still has values from the template (change-me, PASSWORD, yourdomain): fill them in."
fi

PHP_BIN="$(find_php)" || fail "No PHP 8.4.1 or newer found. Set PHP=/opt/cpanel/ea-php84/root/usr/bin/php and run again."
note "PHP:      $PHP_BIN ($("$PHP_BIN" -r 'echo PHP_VERSION;'))"

# cPanel picks the web PHP with an AddHandler line in the document root's .htaccess, and it is easily a different
# version from the one on the command line — then Composer, the migrations and the compiled cache are built with
# one PHP while Apache runs the app with another, whose extensions may not match.
check_extensions() {
    local binary=$1 label=$2 extension
    for extension in pdo_mysql intl mbstring fileinfo; do
        "$binary" -m | grep -qix "$extension" || fail "$label has no \"$extension\". Enable it in cPanel → Select PHP Version."
    done
}
check_extensions "$PHP_BIN" "The command-line PHP"

# "|| true": with pipefail, a missing .htaccess (a fresh account) or no handler line would abort the script.
WEB_PHP=""
if [[ -f "$BACKEND/public/.htaccess" ]]; then
    WEB_PHP="$( { grep -hoE 'x-httpd-ea-php[0-9]+' "$BACKEND/public/.htaccess" || true; } | head -1 | sed 's/x-httpd-//')"
fi
if [[ -n "$WEB_PHP" ]]; then
    # Symfony 8 needs PHP 8.4: an older web PHP would serve a fatal error on every page once the new vendor/ is in.
    (( 10#${WEB_PHP#ea-php} >= 84 )) || fail "Apache runs the app with $WEB_PHP; it needs PHP 8.4. Set it in cPanel → MultiPHP Manager, then run this again."
    CLI_TAG="ea-php$("$PHP_BIN" -r 'echo PHP_MAJOR_VERSION, PHP_MINOR_VERSION;')"
    if [[ "$WEB_PHP" != "$CLI_TAG" ]]; then
        warn "Apache runs the app with $WEB_PHP, but this script is using $CLI_TAG."
        warn "Set the same version in cPanel → MultiPHP Manager, or run this with PHP=/opt/cpanel/$WEB_PHP/root/usr/bin/php."
    fi
    WEB_PHP_BIN="/opt/cpanel/$WEB_PHP/root/usr/bin/php"
    if [[ -x "$WEB_PHP_BIN" ]]; then
        check_extensions "$WEB_PHP_BIN" "$WEB_PHP (the one Apache uses)"
        note "Web PHP:  $WEB_PHP ($("$WEB_PHP_BIN" -r 'echo PHP_VERSION;'))"
    fi
fi

COMPOSER_BIN="$(find_composer || true)"
COMPOSER_CMD=()
if [[ -n "$COMPOSER_BIN" ]]; then
    note "Composer: $COMPOSER_BIN"
    # Composer must run on PHP 8.4 (the lock file requires it), not on whatever PHP its shebang picks; a shell
    # wrapper is left to run as it is.
    COMPOSER_CMD=("$COMPOSER_BIN")
    if head -n1 "$COMPOSER_BIN" | grep -q php || [[ "$COMPOSER_BIN" == *.phar ]]; then COMPOSER_CMD=("$PHP_BIN" "$COMPOSER_BIN"); fi
else
    warn "Composer not found: backend/vendor/ must already be uploaded and up to date."
fi

NODE_BIN_DIR="$(find_node_dir || true)"
if [[ -n "$NODE_BIN_DIR" ]]; then
    export PATH="$NODE_BIN_DIR:$PATH"
    note "Node:     $NODE_BIN_DIR/node ($(node -v)), npm $(npm -v)"
fi

console() { "$PHP_BIN" "$BACKEND/bin/console" "$@"; }

# Only tracked changes matter: they are what a fast-forward pull would fight over. Untracked files are left
# alone — a cPanel account puts its own .htaccess, .user.ini and php.ini in the document root, and they must stay.
if [[ "${ALLOW_DIRTY:-0}" == "1" ]]; then
    warn "ALLOW_DIRTY=1: keeping the local changes to tracked files. The pull will fail if it touches one of them."
elif ! git diff --quiet || ! git diff --cached --quiet; then
    git status --short --untracked-files=no
    # The usual reason on a new account: the settings were put in .env, which is the committed file of defaults.
    if ! git diff --quiet -- "$BACKEND/.env" || ! git diff --cached --quiet -- "$BACKEND/.env"; then
        warn "backend/.env is committed, so every pull will fight over it, and a secret there is one push from being public."
        warn "This account's settings belong in backend/.env.local, which overrides .env and is never committed:"
        warn "    git diff backend/.env     # the lines you changed there"
        warn "    # make sure each of them is in backend/.env.local, then:"
        warn "    git restore backend/.env"
    fi
    fail "Tracked files have local changes. Commit them, or discard them with \"git restore .\", then run again."
fi
UNTRACKED="$(git ls-files --others --exclude-standard | head -5 | tr '\n' ' ')"
if [[ -n "$UNTRACKED" ]]; then
    note "Leaving untracked files alone: $UNTRACKED"
fi

BEFORE="$(git rev-parse HEAD)"

if [[ "${SKIP_PULL:-0}" != "1" ]]; then
    BRANCH="${BRANCH:-$(git rev-parse --abbrev-ref HEAD)}"
    step "Pulling $BRANCH from GitHub"
    git pull --ff-only origin "$BRANCH"
    if [[ "$BEFORE" == "$(git rev-parse HEAD)" ]]; then
        note "Already up to date; the steps below run anyway."
    else
        git --no-pager log --oneline "$BEFORE..HEAD" | sed 's/^/    /'
        # The pull may have changed this very file, and bash reads a script as it runs it: start the new copy from
        # the top rather than run the rest of the old one.
        if ! git diff --quiet "$BEFORE" HEAD -- deploy/cpanel-update.sh; then
            note "This script changed with the pull: running the new version."
            exec env SKIP_PULL=1 PREVIOUS_HEAD="$BEFORE" bash "$APP_DIR/deploy/cpanel-update.sh"
        fi
    fi
fi
# After a restart on a new copy of this script, the rollback hint still points at where this deploy started.
BEFORE="${PREVIOUS_HEAD:-$BEFORE}"

if [[ -n "$COMPOSER_BIN" ]]; then
    step "PHP dependencies"
    # No scripts: they boot the kernel, which the cache warmup below does properly once the code is in place.
    (cd "$BACKEND" && "${COMPOSER_CMD[@]}" install --no-dev --optimize-autoloader --classmap-authoritative --no-interaction --no-progress --no-scripts)
else
    [[ -d "$BACKEND/vendor" ]] || fail "backend/vendor/ is missing and Composer is not available: upload vendor/ built with PHP 8.4, or install Composer."
fi

step "Production cache"
# Before the migrations, not after: right after a pull, var/cache/prod still holds the previous version's
# container, and in prod Symfony trusts it without checking. A migration check booting that stale container can
# fail for reasons that have nothing to do with the database. The directory is removed rather than cleared with
# cache:clear, because cache:clear has to boot that same stale container to run at all.
rm -rf "$BACKEND/var/cache/prod"
console cache:warmup --env=prod --no-debug

step "Frontend build"
# The build is not in git: without Node there is nothing to serve.
[[ -n "$NODE_BIN_DIR" ]] || fail "Node.js 20.19+ or 22.12+ not found. Install it in cPanel → Setup Node.js App, or set NODE_DIR=/folder/with/node."
# npm ci wipes and reinstalls node_modules, which takes minutes and a few hundred MB of the account's quota.
# It writes node_modules/.package-lock.json, so that file's age says whether the lock has moved since. A Composer
# update can bring another symfony/ux-react, which npm links from vendor/, so composer.lock counts too.
if [[ ! -d "$BACKEND/node_modules" || "$BACKEND/package-lock.json" -nt "$BACKEND/node_modules/.package-lock.json" \
      || "$BACKEND/composer.lock" -nt "$BACKEND/node_modules/.package-lock.json" ]]; then
    # NODE_ENV=production here would skip devDependencies, and Webpack Encore is one of them.
    (cd "$BACKEND" && NODE_ENV=development NPM_CONFIG_UPDATE_NOTIFIER=false npm ci --no-audit --no-fund)
else
    note "Dependencies unchanged since the last deploy; skipping npm ci."
fi
(cd "$BACKEND" && npx encore production)
[[ -f "$BACKEND/public/build/entrypoints.json" ]] || fail "The build wrote no entrypoints.json. Run \"npx encore production\" in backend/ and read the error above it."

step "Database server"
# Doctrine picks its SQL dialect from serverVersion in DATABASE_URL, not from the server. A MySQL version string on
# a MariaDB server (cPanel usually runs MariaDB) makes Doctrine report its migration table out of sync on every
# run, so every deploy would take a pointless backup and never trust the migration check.
REAL_VERSION="$(console dbal:run-sql 'SELECT VERSION()' --env=prod 2>/dev/null | grep -oE '[0-9]+\.[0-9]+\.[0-9]+[A-Za-z0-9.+~-]*' | head -1 || true)"
CONFIGURED_VERSION="$(grep -E '^DATABASE_URL=' "$BACKEND/.env.local" | tail -1 | grep -oE 'serverVersion=[^&"'"'"']+' | cut -d= -f2 || true)"
if [[ -z "$REAL_VERSION" ]]; then
    fail "Could not reach the database with DATABASE_URL from backend/.env.local."
fi
note "Server:     $REAL_VERSION"
note "Configured: serverVersion=${CONFIGURED_VERSION:-(missing)}"
# Both spellings Doctrine takes for MariaDB ("10.6.12-MariaDB", "mariadb-10.6.0"), in any case.
real_is_mariadb=0; [[ "${REAL_VERSION,,}" == *mariadb* ]] && real_is_mariadb=1
conf_is_mariadb=0; [[ "${CONFIGURED_VERSION,,}" == *mariadb* ]] && conf_is_mariadb=1
if [[ -z "$CONFIGURED_VERSION" || "$real_is_mariadb" != "$conf_is_mariadb" ]]; then
    SUGGESTED="${REAL_VERSION%%-*}"
    [[ "$real_is_mariadb" == "1" ]] && SUGGESTED="$SUGGESTED-MariaDB"
    fail "serverVersion does not match the database server. In backend/.env.local set serverVersion=$SUGGESTED in DATABASE_URL, then run again."
fi

step "Database migrations"
# A migration is the only step here that can destroy data, so it never runs without a dump first. The dump is
# taken with the credentials from .env.local, through a 0600 defaults file so the password is never an argument
# (arguments are visible to every user on the box via ps).
# Exit 0 from up-to-date means nothing to run, but a console that cannot boot exits non-zero too, and the two
# must not look alike: the message decides, and whatever it said is printed before a backup is taken.
MIGRATION_CHECK="$(console doctrine:migrations:up-to-date --env=prod 2>&1)" && CHECK_OK=1 || CHECK_OK=0
if [[ "$CHECK_OK" == "1" ]] && grep -q 'Up-to-date' <<<"$MIGRATION_CHECK" && [[ "${BACKUP_ALWAYS:-0}" != "1" ]]; then
    note "No migration is pending; the database is untouched and no backup was taken."
else
    if [[ "${BACKUP_ALWAYS:-0}" == "1" ]]; then
        note "BACKUP_ALWAYS=1."
    else
        note "Backing up because the migration check said:"
        grep -vE '^[[:space:]]*$' <<<"$MIGRATION_CHECK" | sed 's/^/      /'
    fi
    command -v mysqldump >/dev/null 2>&1 || fail "A migration is pending but mysqldump is not here: it must not run without a backup."

    DB_CONF="$(mktemp)"
    trap 'rm -f "$DB_CONF" "$DB_CONF.err"' EXIT
    chmod 600 "$DB_CONF"
    # PHP parses the DSN: it already understands the URL encoding a password needs (%40 and friends).
    DB_NAME="$("$PHP_BIN" -r '
        $line = preg_grep("/^DATABASE_URL=/", file($argv[1], FILE_IGNORE_NEW_LINES));
        $url = trim(substr(end($line), strlen("DATABASE_URL=")), "\"'"'"'");
        $p = parse_url($url) ?: exit(1);
        $conf = sprintf("[client]\nhost=%s\nport=%s\nuser=%s\npassword=%s\n",
            $p["host"] ?? "localhost", $p["port"] ?? 3306,
            rawurldecode($p["user"] ?? ""), rawurldecode($p["pass"] ?? ""));
        file_put_contents($argv[2], $conf);
        echo ltrim($p["path"] ?? "", "/");
    ' "$BACKEND/.env.local" "$DB_CONF")" || fail "Could not read DATABASE_URL from backend/.env.local."
    [[ -n "$DB_NAME" ]] || fail "DATABASE_URL has no database name."

    BACKUP_DEST="${BACKUP_DIR:-$HOME/backups/control-proyectos}"
    mkdir -p "$BACKUP_DEST" && chmod 700 "$BACKUP_DEST"
    BACKUP="$BACKUP_DEST/pre-migration-$DB_NAME-$(date +%Y%m%d-%H%M%S).sql"
    note "Backing up $DB_NAME before migrating…"
    dump() { mysqldump --defaults-extra-file="$DB_CONF" --single-transaction --no-tablespaces \
        --default-character-set=utf8mb4 --add-drop-table "$@" "$DB_NAME" > "$BACKUP"; }

    if ! dump 2>"$DB_CONF.err"; then
        # A MariaDB client against a MySQL server (or the reverse) refuses the server's self-signed certificate.
        # On a loopback connection there is nothing to protect, so retry without the check rather than block the
        # deploy; the flag differs between the two clients.
        DB_HOST="$(grep '^host=' "$DB_CONF" | cut -d= -f2-)"
        TLS_OFF=""
        case "$DB_HOST" in
            localhost|127.0.0.1|::1)
                if mysqldump --help 2>/dev/null | grep -q -- '--ssl-mode'; then
                    TLS_OFF="--ssl-mode=DISABLED"
                else
                    TLS_OFF="--skip-ssl-verify-server-cert"
                fi
                ;;
        esac
        if [[ -z "$TLS_OFF" ]] || ! dump "$TLS_OFF"; then
            sed 's/^/    /' "$DB_CONF.err" >&2
            # Leave no half-written file behind: an empty .sql next to the backups reads like a real one.
            rm -f "$DB_CONF.err" "$BACKUP"
            fail "The backup failed, so nothing was migrated. The database is untouched."
        fi
        warn "The database refused a verified TLS connection; the backup was taken over plain loopback."
    fi
    rm -f "$DB_CONF.err"

    # A truncated dump is worse than none: mysqldump writes this line last, only on success.
    if ! tail -5 "$BACKUP" | grep -q '^-- Dump completed'; then
        rm -f "$BACKUP"
        fail "The backup came out incomplete, so nothing was migrated. The database is untouched."
    fi
    if command -v gzip >/dev/null 2>&1; then
        gzip -f "$BACKUP"
        BACKUP="$BACKUP.gz"
    fi
    note "Backup: $BACKUP ($(du -h "$BACKUP" | cut -f1))"
    rm -f "$DB_CONF"
    trap - EXIT

    console doctrine:migrations:migrate --no-interaction --allow-no-migration --env=prod
fi

step "Apache rewrite rules"
# cPanel writes its own .htaccess (the PHP handler) into the document root and rewrites it whenever the PHP
# version changes, so the front-controller rules are appended once, between markers, instead of owning the file.
HTACCESS="$BACKEND/public/.htaccess"
if [[ -f "$HTACCESS" ]] && grep -q '>>> dsfk front controller >>>' "$HTACCESS"; then
    note "Already in $HTACCESS."
else
    [[ -f "$HTACCESS" ]] && cp -p "$HTACCESS" "$HTACCESS.before-dsfk" && note "Kept a copy as .htaccess.before-dsfk"
    cat "$APP_DIR/deploy/htaccess-symfony.conf" >> "$HTACCESS"
    note "Added the front-controller rules to $HTACCESS"
fi

step "Writable directories"
# var/ holds the cache, the logs, the sessions and the uploaded receipts (var/uploads, outside the document root).
mkdir -p "$BACKEND/var/uploads" "$BACKEND/var/log" "$BACKEND/var/sessions"
chmod -R u+rwX "$BACKEND/var"
chmod 700 "$BACKEND/var/uploads"
chmod 600 "$BACKEND/.env.local"

step "Queue and cron jobs"
# A worker started by the cron before this deploy would keep running the old code until its time limit; this asks it
# to stop after its current message, and the next cron run starts one on the new code.
console messenger:stop-workers --env=prod --no-debug >/dev/null 2>&1 || true
CRONTAB="$(crontab -l 2>/dev/null || true)"
cron_hint() { # $1: what the line runs, $2: what goes wrong without it, $3: the line to add
    if ! grep -q -- "$1" <<<"$CRONTAB"; then
        warn "No cron job runs $1: $2. Add it in cPanel › Cron Jobs:"
        note "  $3"
    fi
}
cron_hint 'messenger:consume' "emails stay queued and are never sent" \
    "* * * * * flock -n \$HOME/.dsfk-worker.lock $PHP_BIN $BACKEND/bin/console messenger:consume async --time-limit=55 --memory-limit=128M --env=prod --no-debug >> $BACKEND/var/log/worker.log 2>&1"
cron_hint 'app:alerts:daily' "no daily summary emails" \
    "0 7 * * * $PHP_BIN $BACKEND/bin/console app:alerts:daily --env=prod --no-debug -q"
cron_hint 'deploy/backup.sh' "no nightly backup of the database and the uploads" \
    "30 2 * * * PHP=$PHP_BIN $APP_DIR/deploy/backup.sh >/dev/null 2>&1"

step "Checking the installation"
console app:doctor --env=prod --no-debug

step "Done"
note "Now on $(git rev-parse --short HEAD) ($(git --no-pager log -1 --format=%s))"
# Only worth offering when this run actually moved the code.
if [[ "$BEFORE" != "$(git rev-parse HEAD)" ]]; then
    note "Roll back with:  git reset --hard ${BEFORE:0:7} && SKIP_PULL=1 $0"
    note "(Migrations are not reverted: restore the pre-migration backup if one ran.)"
fi
note "If a change does not show up, restart PHP from cPanel (OPcache may still hold the old files)."
