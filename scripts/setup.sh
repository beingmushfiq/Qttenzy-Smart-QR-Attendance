#!/bin/bash

# Qttenzy - Setup Script
# This script sets up the development environment
# For Windows users: Use setup.ps1 (PowerShell) or setup.bat (CMD) instead
# This script works on Linux, Mac, and Windows Git Bash

set -e

echo "🚀 Qttenzy Setup Script"
echo "========================"

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check if running on Windows (Git Bash)
if [[ "$OSTYPE" == "msys" || "$OSTYPE" == "win32" ]]; then
    echo -e "${YELLOW}Windows detected. Some commands may need adjustment.${NC}"
fi

# Check prerequisites
echo -e "\n${GREEN}Checking prerequisites...${NC}"

command -v node >/dev/null 2>&1 || { echo "❌ Node.js is required but not installed. Aborting." >&2; exit 1; }
command -v npm >/dev/null 2>&1 || { echo "❌ npm is required but not installed. Aborting." >&2; exit 1; }
command -v php >/dev/null 2>&1 || { echo "❌ PHP is required but not installed. Aborting." >&2; exit 1; }
command -v composer >/dev/null 2>&1 || { echo "❌ Composer is required but not installed. Aborting." >&2; exit 1; }
command -v mysql >/dev/null 2>&1 || { echo "⚠️  MySQL is not found. Make sure it's installed and running." >&2; }

echo -e "${GREEN}✓ Prerequisites check passed${NC}"

# Frontend Setup
echo -e "\n${GREEN}Setting up Frontend...${NC}"
if [ -d "frontend" ]; then
    cd frontend
    echo "Installing npm dependencies..."
    npm install
    if [ ! -f ".env" ]; then
        echo "Creating .env file..."
        cp .env.example .env
        echo -e "${YELLOW}⚠️  Please edit frontend/.env with your API URL${NC}"
    fi
    cd ..
    echo -e "${GREEN}✓ Frontend setup complete${NC}"
else
    echo -e "${YELLOW}⚠️  Frontend directory not found. Skipping...${NC}"
fi

# Backend Setup
echo -e "\n${GREEN}Setting up Backend...${NC}"
if [ -d "backend" ]; then
    cd backend
    echo "Installing Composer dependencies..."
    composer install
    
    if [ ! -f ".env" ]; then
        echo "Creating .env file..."
        cp .env.example .env
        echo -e "${YELLOW}⚠️  Please edit backend/.env with your database credentials${NC}"
    fi
    
    echo "Generating application key..."
    php artisan key:generate || echo -e "${YELLOW}⚠️  Could not generate key. Make sure .env exists.${NC}"
    
    echo "Generating JWT secret..."
    php artisan jwt:secret || echo -e "${YELLOW}⚠️  JWT secret generation skipped. Run manually: php artisan jwt:secret${NC}"
    
    echo -e "${YELLOW}⚠️  Database setup required:${NC}"
    echo "  1. Create MySQL database"
    echo "  2. Update backend/.env with database credentials"
    echo "  3. Run: php artisan migrate"
    echo "  4. Run: php artisan db:seed"
    
    cd ..
    echo -e "${GREEN}✓ Backend setup complete${NC}"
else
    echo -e "${YELLOW}⚠️  Backend directory not found. Skipping...${NC}"
fi

# Create necessary directories
echo -e "\n${GREEN}Creating directories...${NC}"
mkdir -p frontend/public/models
mkdir -p backend/storage/logs
mkdir -p backend/storage/framework/cache
mkdir -p backend/storage/framework/sessions
mkdir -p backend/storage/framework/views
echo -e "${GREEN}✓ Directories created${NC}"

# Set permissions (Linux/Mac only)
if [[ "$OSTYPE" != "msys" && "$OSTYPE" != "win32" ]]; then
    echo -e "\n${GREEN}Setting permissions...${NC}"
    if [ -d "backend/storage" ]; then
        chmod -R 775 backend/storage
        chmod -R 775 backend/bootstrap/cache
        echo -e "${GREEN}✓ Permissions set${NC}"
    fi
fi

echo -e "\n${GREEN}✅ Setup complete!${NC}"
echo -e "\n${YELLOW}Next steps:${NC}"
echo "1. Configure frontend/.env"
echo "2. Configure backend/.env"
echo "3. Create MySQL database"
echo "4. Run: cd backend && php artisan migrate && php artisan db:seed"
echo "5. Start backend: cd backend && php artisan serve"
echo "6. Start frontend: cd frontend && npm run dev"
echo -e "\n${GREEN}Happy coding! 🎉${NC}"

