#!/usr/bin/env bash
# Builds an upload-ready release for cPanel shared hosting:
#   build/control-proyectos-<version>.zip
# Frontend is compiled, PHP dependencies are installed without dev packages and
# verified against PHP 8.2 (the minimum supported on the host).
#
# Usage: deploy/build-release.sh [version]
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
VERSION="${1:-$(git -C "$ROOT" describe --tags --always --dirty 2>/dev/null || date +%Y%m%d%H%M)}"
VERSION="${VERSION//\//-}"
NAME="control-proyectos"
BUILD="$ROOT/build"
STAGE="$BUILD/$NAME"
PHP_IMAGE="dsfk-php82"

echo "==> Release $VERSION"
rm -rf "$STAGE" && mkdir -p "$STAGE"

echo "==> Building frontend"
docker compose -f "$ROOT/compose.yaml" run --rm --no-deps node sh -c "npm ci --silent && npx tsc -b && npx vite build" >/dev/null

echo "==> Copying backend"
rsync -a "$ROOT/backend/" "$STAGE/" \
  --exclude '/vendor/' --exclude '/var/' --exclude '/tests/' --exclude '/.phpunit.cache/' \
  --exclude '/.env.local' --exclude '/.env.local.php' --exclude '/.env.*.local' --exclude '/.env.dev' --exclude '/.env.test' \
  --exclude '/phpunit.dist.xml' --exclude '/phpunit.xml'
# Production is the default environment of a release; .env.local on the server holds the secrets.
sed -i 's/^APP_ENV=.*/APP_ENV=prod/' "$STAGE/.env"
cp -r "$ROOT/deploy/server" "$STAGE/deploy"
chmod +x "$STAGE/deploy/"*.sh
echo "$VERSION" > "$STAGE/VERSION"

echo "==> Installing PHP dependencies on PHP 8.2 (no dev packages)"
docker build -q -t "$PHP_IMAGE" --build-arg PHP_VERSION=8.2 "$ROOT/docker/php" >/dev/null
docker run --rm -u "$(id -u):$(id -g)" -e COMPOSER_HOME=/tmp/composer -v "$STAGE":/app -w /app "$PHP_IMAGE" sh -c '
  composer install --no-dev --no-scripts --no-interaction --no-progress --optimize-autoloader --classmap-authoritative -q &&
  php -r "require \"vendor/autoload.php\"; echo \"platform check ok (PHP \", PHP_VERSION, \")\n\";"'

# Everything the release ships; update.sh deletes code files that are not listed (removed in this version).
(cd "$STAGE" && find . -type f ! -name MANIFEST | sed 's#^\./##' | LC_ALL=C sort > MANIFEST)

echo "==> Packing"
rm -f "$BUILD/$NAME-$VERSION.zip"
(cd "$BUILD" && zip -qr "$NAME-$VERSION.zip" "$NAME")
rm -rf "$STAGE"
echo "==> Done: build/$NAME-$VERSION.zip ($(du -h "$BUILD/$NAME-$VERSION.zip" | cut -f1))"
