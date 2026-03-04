#!/usr/bin/env bash
set -e

# Simple entrypoint that waits for DB, installs dependencies and runs artisan tasks

: "${DB_HOST:=db}"
: "${DB_PORT:=5432}"

echo "Waiting for database at ${DB_HOST}:${DB_PORT}..."
until nc -z "${DB_HOST}" "${DB_PORT}"; do
  sleep 1
done
echo "Database reachable"

cd /var/www/html || exit 1

# mark repo as safe for git (fix 'detected dubious ownership')
git config --global --add safe.directory /var/www/html || true
mkdir -p storage/logs storage/framework/{sessions,views,cache} storage/app/public
chmod -R 775 storage bootstrap/cache

# Composer install (idempotent)
if [ -f composer.json ]; then
  if [ ! -d vendor ]; then
    echo "Installing composer dependencies..."
    composer install --no-interaction --prefer-dist --optimize-autoloader || true
  else
    echo "Running composer install (ensuring dependencies)"
    composer install --no-interaction --prefer-dist --optimize-autoloader || true
  fi
fi

# Se não houver .env, copia o exemplo de produção como ponto de partida
if [ ! -f .env ]; then
  if [ -f .env.example ]; then
    echo "No .env found — copying .env.example"
    cp .env.example .env
  else
    echo "WARNING: No .env and no .env.example found. Continuing without it."
  fi
fi

# Generate app key if missing
if [ -z "${APP_KEY}" ]; then
  if [ -f .env ]; then
    if ! grep -qE '^APP_KEY=[[:space:]]*.+$' .env; then
      if php artisan key:generate --force >/dev/null 2>&1; then
        echo "App key set"
      fi
    else
      echo "APP_KEY already set in .env; skipping"
    fi
  else
    if php artisan key:generate --force >/dev/null 2>&1; then
      echo "App key set"
    fi
  fi
fi

# Generate push notification VAPID keys if missing
if [ -z "${VAPID_PUBLIC_KEY}" ] || [ -z "${VAPID_PRIVATE_KEY}" ]; then
  if [ -f .env ]; then
    if ! grep -qE '^VAPID_PUBLIC_KEY=[[:space:]]*.+$' .env; then
      if php artisan vapid:generate --force >/dev/null 2>&1; then
        echo "VAPID keys set"
      fi
    else
      echo "VAPID keys already set in .env; skipping"
    fi
  else
    if php artisan vapid:generate --force >/dev/null 2>&1; then
      echo "VAPID keys set"
    fi
  fi
fi

composer run post-autoload-dump

# Run migrations
if php artisan migrate --force >/dev/null 2>&1; then
  echo "Migrations finished"
fi

# Run seeders
if php artisan db:seed --force >/dev/null 2>&1; then
  echo "Seeders finished"
fi

# Node install / build (if package.json exists and npm available)
if [ -f package.json ]; then
  if command -v npm >/dev/null 2>&1; then
    echo "Installing node modules..."
    npm ci --silent || npm install --silent || true

    # Update service worker
    php artisan service-worker:update || true

    MANIFEST_PATH=public/build/manifest.json
    if [ ! -f "${MANIFEST_PATH}" ]; then
      echo "Vite manifest not found; running npm run build to generate ${MANIFEST_PATH}"
      npm run build --silent || true
    else
      echo "Vite manifest found at ${MANIFEST_PATH}"
    fi

    # In local (non-production) environment, start dev server in background for HMR
    if [ "${APP_ENV}" != "production" ]; then
      if ! pgrep -f "vite" >/dev/null 2>&1; then
        echo "Starting Vite dev server in background (for HMR)..."
        (npm run dev > /var/www/html/storage/logs/vite.log 2>&1 &) || true
      else
        echo "Vite dev server already running"
      fi
    fi
  else
    echo "npm not found; skipping node install/build"
  fi
fi

# Permissions
# Ensure PHP session and log directories exist and have correct permissions
mkdir -p /var/lib/php/sessions /var/log/php || true
chown -R www-data:www-data /var/lib/php/sessions /var/log/php || true
chmod 1733 /var/lib/php/sessions || true

# Permissions for application
chown -R www-data:www-data storage bootstrap/cache || true
chmod -R 775 storage bootstrap/cache || true

# Clear and cache
php artisan config:clear || true
php artisan cache:clear || true
php artisan route:clear || true
php artisan view:clear || true
php artisan config:cache || true
php artisan storage:link || true

echo "Executing container command or fallback server..."

# Try to run provided CMD first. If it exits, fall back to starting a server.
if [ "$#" -gt 0 ]; then
  echo "Running CMD: $@"
  exec "$@" || true
  echo "CMD exited, falling back to server start"
fi

if command -v caddy >/dev/null 2>&1; then
  echo "Starting Caddy PHP server on 0.0.0.0:80"
  exec caddy php-server --listen 0.0.0.0:80 --root /var/www/html/public
elif command -v frankenphp >/dev/null 2>&1; then
  echo "Starting frankenphp php-server on 0.0.0.0:80"
  exec frankenphp php-server --listen 0.0.0.0:80 --root /var/www/html/public || exec php -S 0.0.0.0:80 -t public
else
  echo "caddy/frankenphp not found, starting PHP built-in server on 0.0.0.0:80"
  exec php -S 0.0.0.0:80 -t public
fi
