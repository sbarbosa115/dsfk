#!/usr/bin/env bash
# Deploys straight from GitHub on the cPanel server: fetch → build → install into the app folder → update.
# Run it from a git clone kept OUTSIDE public_html (the clone is a build workspace; local changes are discarded):
#
#   git clone git@github.com:sbarbosa115/dsfk.git ~/dsfk-src
#   ~/dsfk-src/deploy/cpanel-update.sh            # deploys origin/main
#   ~/dsfk-src/deploy/cpanel-update.sh v1.2.0     # deploys a tag, branch or commit
#
# Settings (environment variables, all optional):
#   APP_DIR    where the app is served from           (default /home/lentti/public_html/dsfk)
#   BUILD_DIR  staging area and lock file              (default $HOME/dsfk-build)
#   PHP        PHP 8.4 CLI                             (default ea-php84 if installed, else `php`)
#   NODE_DIR   folder containing node/npm 20.19+       (default: auto-detected)
#   COMPOSER_BIN  Composer executable                  (default: `composer` from PATH)

# Everything lives in main(): bash reads the whole function before running it, so `git checkout`
# can safely replace this file while it runs.
main() {
  set -euo pipefail

  local ref="${1:-main}"
  local src; src="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
  local app_dir="${APP_DIR:-/home/lentti/public_html/dsfk}"
  local build_dir="${BUILD_DIR:-$HOME/dsfk-build}"
  local stage="$build_dir/stage"

  log() { echo "[$(date '+%F %T')] $*"; }
  die() { echo "ERROR: $*" >&2; exit 1; }

  mkdir -p "$build_dir"
  if command -v flock >/dev/null; then
    exec 9>"$build_dir/.lock"
    flock -n 9 || die "another deployment is already running"
  fi

  # --- Toolchain -------------------------------------------------------------------------------
  local php="${PHP:-}"
  if [ -z "$php" ]; then
    if [ -x /opt/cpanel/ea-php84/root/usr/bin/php ]; then php=/opt/cpanel/ea-php84/root/usr/bin/php; else php=php; fi
  fi
  "$php" -r 'exit(PHP_VERSION_ID >= 80401 ? 0 : 1);' 2>/dev/null \
    || die "PHP 8.4.1+ required on the command line (got: $("$php" -r 'echo PHP_VERSION;' 2>/dev/null || echo none)). Set PHP=/path/to/php84"

  node_ok() { "$1/node" -e 'const [a,b]=process.versions.node.split(".").map(Number); process.exit(a>22||(a===22&&b>=12)||(a===20&&b>=19)?0:1)' 2>/dev/null; }
  local node_dir="${NODE_DIR:-}" candidate
  if [ -z "$node_dir" ]; then
    for candidate in "$(dirname "$(command -v node 2>/dev/null || echo /nonexistent/node)")" \
                     /opt/alt/alt-nodejs24/root/usr/bin /opt/alt/alt-nodejs22/root/usr/bin /opt/alt/alt-nodejs20/root/usr/bin; do
      if [ -x "$candidate/node" ] && node_ok "$candidate"; then node_dir="$candidate"; break; fi
    done
  fi
  [ -n "$node_dir" ] && node_ok "$node_dir" \
    || die "Node.js 20.19+ or 22.12+ not found. Enable it in cPanel (Setup Node.js App) or set NODE_DIR=/folder/with/node"
  export PATH="$node_dir:$PATH"

  local composer_bin="${COMPOSER_BIN:-$(command -v composer || true)}"
  [ -f "$composer_bin" ] || die "Composer not found. Set COMPOSER_BIN=/path/to/composer"
  # Composer must run on PHP 8.4 (the lock file requires it), not on whatever PHP its shebang picks.
  local -a composer=("$composer_bin")
  if head -n1 "$composer_bin" | grep -q php; then composer=("$php" "$composer_bin"); fi

  log "PHP $("$php" -r 'echo PHP_VERSION;'), Node $(node -v), $("${composer[@]}" --version --no-ansi 2>/dev/null | head -n1)"

  # --- Source ----------------------------------------------------------------------------------
  log "Fetching $ref from GitHub"
  git -C "$src" fetch --tags --prune --force origin
  local commit
  commit="$(git -C "$src" rev-parse --verify -q "origin/$ref^{commit}" || git -C "$src" rev-parse --verify -q "$ref^{commit}")" \
    || die "unknown branch, tag or commit: $ref"
  git -C "$src" checkout -q -f --detach "$commit"
  git -C "$src" clean -q -fd
  local version; version="$(git -C "$src" describe --tags --always "$commit")"
  version="${version//\//-}"
  log "Building $version ($(git -C "$src" log -1 --format='%h %s' "$commit"))"

  mkdir -p "$app_dir"
  if [ ! -f "$app_dir/.env.local" ]; then
    cp "$src/deploy/server/env.local.example" "$app_dir/.env.local"
    chmod 600 "$app_dir/.env.local"
    die "first deployment: fill in $app_dir/.env.local (database, mailer, APP_URL=https://omaha.lentti.shop), then run this again"
  fi

  # --- Frontend (compiled into backend/public/app) ---------------------------------------------
  log "Building frontend"
  (cd "$src/frontend" && npm ci --no-audit --no-fund --no-update-notifier --loglevel=error && npx tsc -b && npx vite build --logLevel warn)
  [ -f "$src/backend/public/app/index.html" ] || die "frontend build produced no public/app/index.html"

  # --- Backend release -------------------------------------------------------------------------
  log "Staging release"
  rm -rf "$stage" && mkdir -p "$stage"
  rsync -a "$src/backend/" "$stage/" \
    --exclude '/vendor/' --exclude '/var/' --exclude '/tests/' --exclude '/.phpunit.cache/' \
    --exclude '/.env.local' --exclude '/.env.local.php' --exclude '/.env.*.local' --exclude '/.env.dev' --exclude '/.env.test' \
    --exclude '/phpunit.dist.xml' --exclude '/phpunit.xml'
  sed -i 's/^APP_ENV=.*/APP_ENV=prod/' "$stage/.env"
  cp -r "$src/deploy/server" "$stage/deploy"
  chmod +x "$stage/deploy/"*.sh
  echo "$version" > "$stage/VERSION"

  log "Installing PHP dependencies (no dev packages)"
  (cd "$stage" && "${composer[@]}" install \
    --no-dev --no-scripts --no-interaction --no-progress --optimize-autoloader --classmap-authoritative -q)
  (cd "$stage" && "$php" -r 'require "vendor/autoload.php";') || die "PHP dependencies do not load on this PHP"

  (cd "$stage" && find . -type f ! -name MANIFEST | sed 's#^\./##' | LC_ALL=C sort > MANIFEST)

  # --- Install into the app folder -------------------------------------------------------------
  log "Copying release into $app_dir"
  # No --delete: .env.local, var/ (uploads, logs, sessions) stay; update.sh removes code files dropped from MANIFEST.
  rsync -a "$stage/" "$app_dir/"

  log "Running update"
  PHP="$php" "$app_dir/deploy/update.sh"

  rm -rf "$stage"
  log "Deployed $version to $app_dir"
}

main "$@"
exit
