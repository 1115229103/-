#!/usr/bin/env bash
# AIStory — Production Deployment Script
# Usage: bash deploy.sh [production|staging]
set -euo pipefail

APP_ENV="${1:-production}"
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
cd "$SCRIPT_DIR/laravel"

# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
# Colors
# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'; NC='\033[0m'
info()  { echo -e "${GREEN}[✓]${NC} $*"; }
warn()  { echo -e "${YELLOW}[!]${NC} $*"; }
error() { echo -e "${RED}[✗]${NC} $*"; exit 1; }

# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
# 1. Prerequisites
# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
echo "=== AIStory Deployment (${APP_ENV}) ==="
echo ""

command -v php      >/dev/null 2>&1 || error "PHP 8.2+ required"
command -v composer >/dev/null 2>&1 || error "Composer required"
command -v node     >/dev/null 2>&1 || warn "Node.js not found — skipping frontend build"
command -v npm      >/dev/null 2>&1 || warn "npm not found — skipping frontend build"

# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
# 2. Environment
# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
if [ ! -f .env ]; then
    if [ -f .env.example ]; then
        cp .env.example .env
        info ".env created from .env.example"
    else
        error ".env not found and no .env.example to copy"
    fi
fi

if [ "$APP_ENV" = "production" ] && ! grep -q '^APP_KEY=.\{32,\}' .env; then
    php artisan key:generate --force
    info "APP_KEY generated"
fi

# Verify critical env vars
if [ "$APP_ENV" = "production" ]; then
    grep -q '^MASTER_KEK=.\{32,\}' .env || \
        warn "MASTER_KEK not set — generate with: openssl rand -hex 32"
    grep -q '^FASTAPI_INTERNAL_TOKEN=.\{16,\}' .env || \
        warn "FASTAPI_INTERNAL_TOKEN not set"
fi

# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
# 3. Install dependencies
# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
info "Installing Composer dependencies..."
if [ "$APP_ENV" = "production" ]; then
    composer install --no-dev --no-interaction --no-progress --optimize-autoloader
else
    composer install --no-interaction --no-progress
fi

# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
# 4. Frontend build
# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
if command -v npm >/dev/null 2>&1; then
    info "Building admin frontend (Vue 3)..."
    (cd admin-app && npm ci --silent && npm run build) || warn "Admin build failed"

    info "Building user frontend (React)..."
    (cd user-app && npm ci --silent && npm run build) || warn "User build failed"
fi

# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
# 5. Storage
info "Linking storage..."
php artisan storage:link 2>/dev/null || info "Storage link already exists"

# 6. Database
# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
info "Running migrations..."
php artisan migrate --force --seed

# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
# 7. Cache & Optimize
# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
if [ "$APP_ENV" = "production" ]; then
    info "Optimizing for production..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    php artisan event:cache
else
    info "Clearing caches..."
    php artisan config:clear
    php artisan route:clear
    php artisan view:clear
fi

# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
# 8. Permissions
# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
info "Setting permissions..."
chmod -R 775 storage bootstrap/cache 2>/dev/null || true
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || \
    warn "Could not set ownership — run as root if needed"

# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
# 9. Verify
# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
echo ""
echo "=== Deployment Complete ==="
echo ""
echo "Verify:"
echo "  php artisan about"
echo "  php tests/api_smoke.php"
echo "  php tests/admin_api_smoke.php"
echo ""
echo "Queue worker:"
echo "  php artisan queue:work --sleep=3 --tries=3 --max-time=3600"
echo ""
echo "Or via Supervisor:"
echo "  sudo cp deploy/supervisor.conf /etc/supervisor/conf.d/aistory.conf"
echo "  sudo supervisorctl reread && sudo supervisorctl update"
echo ""
info "Deployment finished at $(date)"
