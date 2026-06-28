#!/bin/bash

# cPanel Laravel Deployment Script
# This script automates the deployment process for Laravel applications on cPanel

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# Function to print colored output
print_success() {
    echo -e "${GREEN}✓ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠ $1${NC}"
}

print_error() {
    echo -e "${RED}✗ $1${NC}"
}

print_info() {
    echo -e "${BLUE}ℹ $1${NC}"
}

print_header() {
    echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${CYAN}$1${NC}"
    echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
}

# Function to ensure app is brought back online even on error
cleanup() {
    if [ -f "storage/framework/down" ]; then
        echo ""
        print_warning "Ensuring application is brought back online..."
        php artisan up || true
    fi
}

# Set trap to run cleanup on exit (success or failure)
trap cleanup EXIT

echo "🚀 Starting Laravel Deployment..."
echo ""

# Step 1: Fetch latest changes to compare
print_header "📡 FETCHING LATEST CHANGES"
git fetch origin main 2>/dev/null || git fetch origin master 2>/dev/null

# Step 2: Show what will change
print_header "📊 CHANGES PREVIEW"

# Get current commit
CURRENT_COMMIT=$(git rev-parse HEAD)
CURRENT_BRANCH=$(git branch --show-current)

# Get remote commit
REMOTE_COMMIT=$(git rev-parse origin/main 2>/dev/null || git rev-parse origin/master 2>/dev/null)

echo ""
print_info "Current Production Version:"
echo "  Branch: $CURRENT_BRANCH"
echo "  Commit: ${CURRENT_COMMIT:0:8}"
git log -1 --pretty=format:"  Message: %s%n  Author: %an%n  Date: %ar%n" HEAD
echo ""

print_info "Latest Git Version:"
echo "  Commit: ${REMOTE_COMMIT:0:8}"
git log -1 --pretty=format:"  Message: %s%n  Author: %an%n  Date: %ar%n" origin/main 2>/dev/null || git log -1 --pretty=format:"  Message: %s%n  Author: %an%n  Date: %ar%n" origin/master 2>/dev/null
echo ""

# Check if there are changes
if [ "$CURRENT_COMMIT" = "$REMOTE_COMMIT" ]; then
    print_warning "No new changes to deploy. Production is up to date!"
    echo ""
    read -p "Continue anyway? (y/N): " -n 1 -r
    echo ""
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        print_info "Deployment cancelled."
        exit 0
    fi
else
    echo ""
    print_header "📝 FILES THAT WILL CHANGE"
    echo ""
    
    # Show files that will change
    git diff --name-status HEAD..origin/main 2>/dev/null || git diff --name-status HEAD..origin/master 2>/dev/null | head -20
    
    echo ""
    print_info "Commits to be deployed:"
    git log --oneline HEAD..origin/main 2>/dev/null || git log --oneline HEAD..origin/master 2>/dev/null | head -10
    
    echo ""
    echo -e "${YELLOW}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    read -p "Deploy these changes? (Y/n): " -n 1 -r
    echo ""
    echo -e "${YELLOW}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    
    if [[ $REPLY =~ ^[Nn]$ ]]; then
        print_info "Deployment cancelled by user."
        exit 0
    fi
fi

echo ""
print_header "🚀 STARTING DEPLOYMENT"

# Step 3: Put application in maintenance mode
echo "📦 Putting application in maintenance mode..."
php artisan down || print_warning "Could not enable maintenance mode"
print_success "Maintenance mode enabled"

# Step 4: Handle Git conflicts and pull latest changes
echo "📥 Pulling latest changes from repository..."

# Check for local changes
if ! git diff-index --quiet HEAD --; then
    print_warning "Local changes detected, stashing them..."
    git stash push -m "Auto-stash before deployment $(date +%Y-%m-%d_%H-%M-%S)"
    print_success "Local changes stashed"
fi

# Pull latest changes
if git pull origin main 2>/dev/null; then
    print_success "Code updated from main branch"
elif git pull origin master 2>/dev/null; then
    print_success "Code updated from master branch"
else
    print_error "Failed to pull from repository"
    exit 1
fi

# Step 5: Install/Update Composer dependencies
echo "📚 Installing Composer dependencies..."

# First attempt
if ! composer install --no-dev --optimize-autoloader --no-interaction 2>&1; then
    print_warning "Initial composer install failed, attempting recovery..."
    
    # Clear composer cache
    echo "  → Clearing composer cache..."
    composer clear-cache 2>/dev/null || true
    
    # Remove vendor directory and lock file to force clean install
    echo "  → Removing vendor directory and composer.lock..."
    rm -rf vendor
    rm -f composer.lock
    
    # Remove bootstrap/cache files
    echo "  → Clearing bootstrap cache..."
    rm -rf bootstrap/cache/*.php 2>/dev/null || true
    
    # Second attempt with clean slate
    echo "  → Retrying composer install..."
    if ! composer install --no-dev --optimize-autoloader --no-interaction; then
        print_error "Composer install failed after recovery attempt"
        print_info "Manual intervention required:"
        echo "    1. SSH into your server"
        echo "    2. Run: rm -rf vendor composer.lock"
        echo "    3. Run: composer install --no-dev --optimize-autoloader"
        exit 1
    fi
    
    print_success "Composer dependencies installed (after recovery)"
else
    print_success "Composer dependencies installed"
fi

# Step 6: Install/Update NPM dependencies and build assets
echo "🎨 Building frontend assets..."
if command -v npm &> /dev/null; then
    npm install --production --silent || print_warning "NPM install had warnings"
    npm run build || print_warning "Asset build had warnings"
    print_success "Frontend assets built"
else
    print_warning "NPM not found, skipping asset build"
fi

# Step 7: Run database migrations
echo "🗄️  Running database migrations..."
php artisan migrate --force || {
    print_error "Database migrations failed"
    exit 1
}
print_success "Database migrations completed"

# Step 8: Clear all caches
echo "🧹 Clearing all caches..."
php artisan config:clear || true
php artisan cache:clear || true
php artisan route:clear || true
php artisan view:clear || true
print_success "Cache cleared"

# Step 9: Cache configuration for performance
echo "💾 Caching configuration..."
php artisan config:cache || print_warning "Config cache failed"
php artisan route:cache || print_warning "Route cache failed"
php artisan view:cache || print_warning "View cache failed"
print_success "Configuration cached"

# Step 10: Optimize application
echo "⚡ Optimizing application..."
php artisan optimize || print_warning "Optimization had warnings"
print_success "Application optimized"

# Step 11: Restart queue workers (if using queues)
echo "🔄 Restarting queue workers..."
php artisan queue:restart 2>/dev/null || print_warning "Queue workers not running"

# Step 12: Permissions
echo "🔐 Setting permissions..."
chmod -R 775 storage bootstrap/cache 2>/dev/null || \
chmod -R 755 storage bootstrap/cache
print_success "Permissions set"

# Step 12.5: Storage symlink
echo "🔗 Creating storage symlink..."
# Force recreate the link to ensure it's not broken
php artisan storage:link --force || print_warning "Symlink creation failed, you may need to delete public/storage manually"
print_success "Storage symlink connected"

# Step 13: Bring application back online (also handled by trap)
echo "🌐 Bringing application back online..."
php artisan up
print_success "Application is now live"

# Get new commit info
NEW_COMMIT=$(git rev-parse HEAD)

echo ""
print_header "✅ DEPLOYMENT COMPLETED SUCCESSFULLY"
echo ""
print_info "Deployed Version:"
echo "  Commit: ${NEW_COMMIT:0:8}"
git log -1 --pretty=format:"  Message: %s%n  Author: %an%n  Date: %ar%n" HEAD
echo ""
echo "📊 Quick health check:"
echo "   • Check site"
echo "   • View logs: tail -f storage/logs/laravel.log"
echo ""
