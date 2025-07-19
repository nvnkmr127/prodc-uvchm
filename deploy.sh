#!/bin/bash

set -e

echo "🚀 Starting UVCHM Production Deployment..."

APP_DIR="/home/digiclou/portal.uvchm.com"
BACKUP_DIR="/home/digiclou/backups"
LOG_FILE="/home/digiclou/deployment.log"

log() {
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] $1" | tee -a $LOG_FILE
}

cd $APP_DIR

# Create backup directory
mkdir -p $BACKUP_DIR

# Put in maintenance mode
log "Putting application in maintenance mode..."
php artisan down --render="errors::503" --secret="uvchm-deploy-$(date +%s)" || true

# Create backup
log "Creating database backup..."
php artisan backup:run --only-db --filename="pre-deployment-$(date +%Y%m%d_%H%M%S).zip" || log "Backup failed 
but continuing"

# Update code
log "Updating code..."
git stash || true
git fetch origin main
git reset --hard origin/main

# Install dependencies
log "Installing dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

# Clear caches
log "Clearing caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Run migrations
log "Running migrations..."
php artisan migrate --force

# Cache for production
log "Caching for production..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Set permissions
log "Setting permissions..."
find storage -type f -exec chmod 664 {} \; 2>/dev/null || true
find storage -type d -exec chmod 775 {} \; 2>/dev/null || true
find bootstrap/cache -type f -exec chmod 664 {} \; 2>/dev/null || true
find bootstrap/cache -type d -exec chmod 775 {} \; 2>/dev/null || true

# Run health check
log "Running health check..."
php artisan uvchm:health-check --quick || log "Health check failed but continuing"

# Bring back online
log "Bringing application online..."
php artisan up

log "✅ Deployment completed successfully!"
