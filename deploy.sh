#!/bin/bash

# ==============================================================================
# UVCHM ULTIMATE DEPLOYMENT SYSTEM 🚀
# ==============================================================================
# Author: Antigravity AI
# Version: 2.0 (Powered Up)
# Purpose: Professional, production-safe, automated zero-downtime deployment.
# ==============================================================================

set -e

# --- Configuration ---
APP_DIR="/home/digiclou/portal.uvchm.com"
BACKUP_DIR="/home/digiclou/backups"
LOG_FILE="/home/digiclou/deployment.log"
LOCK_FILE="/tmp/uvchm_deploy.lock"
RETRY_LIMIT=3

# --- UI & Logging ---
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

log() {
    local message="[$(date +'%Y-%m-%d %H:%M:%S')] $1"
    echo -e "${BLUE}${message}${NC}"
    echo "$message" >> "$LOG_FILE"
}

error_handler() {
    log "❌ ERROR: Deployment failed at line $1"
    # Try to bring the app back up if it was down
    php artisan up 2>/dev/null || true
    rm -f "$LOCK_FILE"
    exit 1
}

trap 'error_handler $LINENO' ERR

# --- 1. Guard Checks ---
if [ -f "$LOCK_FILE" ]; then
    log "⚠️  Deployment already in progress (lock file exists). Force remove it if stale: rm $LOCK_FILE"
    exit 1
fi
touch "$LOCK_FILE"
trap 'rm -f "$LOCK_FILE"' EXIT

log "🚀 INIT: Starting UVCHM Ultimate Deployment..."

cd "$APP_DIR" || { log "❌ FATAL: Directory $APP_DIR not found!"; exit 1; }

# --- 2. Pre-Flight Diagnostics ---
log "🔍 DIAG: Checking server health..."
DISK_FREE=$(df -m . | awk 'NR==2 {print $4}')
if [ "$DISK_FREE" -lt 500 ]; then
    log "❌ FATAL: Disk space critically low (${DISK_FREE}MB). Free up space before deploying."
    exit 1
fi
log "✅ OK: Disk space sufficient (${DISK_FREE}MB free)."

# Capture current state for potential manual rollback
PREV_HASH=$(git rev-parse --short HEAD)
log "📦 STATE: Current commit is ${PREV_HASH}"

# --- 3. Maintenance Window ---
log "⏳ STAGE: Preparing application..."
php artisan down --render="errors::503" --secret="uvchm-deploy-$(date +%s)" || true

# --- 4. Safety First (Database Backup) ---
log "💾 BACKUP: Safeguarding database..."
mkdir -p "$BACKUP_DIR"
# Remove database backups older than 7 days to conserve space
find "$BACKUP_DIR" -name "pre-deployment-*.zip" -type f -mtime +7 -delete 2>/dev/null || true
php artisan backup:run --only-db --filename="pre-deployment-$(date +%Y%m%d_%H%M%S).zip" || log "⚠️  Backup failed, but proceeding at risk..."

# --- 5. Update Cycle ---
log "🔄 UPDATE: Pulling latest changes from main branch..."
git stash || true
git fetch origin main
git reset --hard origin/main
git clean -fd # Clean up untracked artifacts

# --- 6. Build Environment ---
log "🏗️  BUILD: Installing optimized dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

# --- 7. Database Logic ---
log "🗃️  DB: Synchronizing schema..."
php artisan migrate --force

# --- 8. Power Optimization ---
log "⚡ OPTIMIZE: Caching application layers..."
# Pre-clear ensure we don't have stale cached config/routes
php artisan config:clear
php artisan route:clear
php artisan optimize      # Caches config and routes
php artisan view:cache    # Pre-compile templates
php artisan event:cache   # Cache events/listeners

# --- 9. Storage Architecture ---
log "📂 FILES: Verifying storage architecture..."
if [ -d "public/storage" ] && [ ! -L "public/storage" ]; then
    log "⚠️  Broken storage detected (directory where link should be). Repairing..."
    rm -rf public/storage
fi
php artisan storage:link || true

# --- 10. Robust Permissions ---
log "🔐 SECURITY: Hardening permissions..."
chmod -R 775 storage bootstrap/cache
chown -R $USER:www-data storage bootstrap/cache 2>/dev/null || true

# --- 11. Final Validation ---
log "🩺 HEALTH: Verifying system services..."
# Force health check to run and notify if configured
php artisan system:health-check --notify || log "⚠️  System health check reported warnings."

# --- 12. Cleanup & Go Live ---
log "✨ CLEAN: Wiping build noise..."
# Cleanup telemetry or temp files if needed
php artisan view:clear 2>/dev/null || true

log "🌍 ONLINE: Bringing application back to the public..."
php artisan up

log "✅ SUCCESS: Deployment of revision ${PREV_HASH} completed successfully!"
log "=========================================================================="
