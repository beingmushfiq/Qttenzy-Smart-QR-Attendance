#!/bin/bash

# Qttenzy - Deployment Script
# This script deploys the application to production

set -e

echo "🚀 Qttenzy Deployment Script"
echo "============================"

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

# Configuration
ENVIRONMENT=${1:-production}
BACKEND_DIR="backend"
FRONTEND_DIR="frontend"

# Check if we're in the right directory
if [ ! -d "$BACKEND_DIR" ] || [ ! -d "$FRONTEND_DIR" ]; then
    echo -e "${RED}❌ Backend or Frontend directory not found.${NC}"
    exit 1
fi

echo -e "${GREEN}Deploying to: ${ENVIRONMENT}${NC}\n"

# Backend Deployment
echo -e "${GREEN}Deploying Backend...${NC}"
cd $BACKEND_DIR

# Pull latest code (if using git)
if [ -d ".git" ]; then
    echo "Pulling latest code..."
    git pull origin main || echo -e "${YELLOW}⚠️  Git pull failed or not a git repo${NC}"
fi

# Install dependencies
echo "Installing Composer dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

# Run migrations
echo "Running database migrations..."
php artisan migrate --force || echo -e "${YELLOW}⚠️  Migration failed${NC}"

# Clear and cache configuration
echo "Optimizing application..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Restart queue workers (if using supervisor)
if command -v supervisorctl >/dev/null 2>&1; then
    echo "Restarting queue workers..."
    sudo supervisorctl restart qttenzy-worker:* || echo -e "${YELLOW}⚠️  Supervisor not configured${NC}"
fi

cd ..
echo -e "${GREEN}✓ Backend deployment complete${NC}\n"

# Frontend Deployment
echo -e "${GREEN}Deploying Frontend...${NC}"
cd $FRONTEND_DIR

# Pull latest code
if [ -d ".git" ]; then
    echo "Pulling latest code..."
    git pull origin main || echo -e "${YELLOW}⚠️  Git pull failed or not a git repo${NC}"
fi

# Install dependencies
echo "Installing npm dependencies..."
npm install

# Build for production
echo "Building for production..."
npm run build

cd ..
echo -e "${GREEN}✓ Frontend deployment complete${NC}\n"

# Restart services (if on VPS)
if [[ "$ENVIRONMENT" == "production" ]]; then
    echo -e "${GREEN}Restarting services...${NC}"
    
    # Restart PHP-FPM (adjust version as needed)
    if command -v systemctl >/dev/null 2>&1; then
        sudo systemctl restart php8.2-fpm || echo -e "${YELLOW}⚠️  PHP-FPM restart skipped${NC}"
    fi
    
    # Reload Nginx
    if command -v nginx >/dev/null 2>&1; then
        sudo nginx -t && sudo systemctl reload nginx || echo -e "${YELLOW}⚠️  Nginx reload skipped${NC}"
    fi
fi

echo -e "\n${GREEN}✅ Deployment complete!${NC}"
echo -e "\n${YELLOW}Verification checklist:${NC}"
echo "1. Check API endpoints: curl https://api.qttenzy.com/api/v1/health"
echo "2. Check frontend: https://app.qttenzy.com"
echo "3. Monitor logs: tail -f backend/storage/logs/laravel.log"
echo "4. Check queue workers: sudo supervisorctl status"

