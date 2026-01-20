#!/bin/bash

# Qttenzy - Database Seeding Script
# This script seeds the database with sample data

set -e

echo "🌱 Qttenzy Database Seeding Script"
echo "==================================="

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

cd backend

# Check if .env exists
if [ ! -f ".env" ]; then
    echo -e "${YELLOW}⚠️  .env file not found. Please create it first.${NC}"
    exit 1
fi

# Run migrations first
echo -e "\n${GREEN}Running migrations...${NC}"
php artisan migrate --force

# Seed database
echo -e "\n${GREEN}Seeding database...${NC}"
php artisan db:seed

echo -e "\n${GREEN}✅ Database seeding complete!${NC}"
echo -e "\n${YELLOW}Sample data created:${NC}"
echo "- Admin user (email: admin@qttenzy.com)"
echo "- Test users"
echo "- Sample sessions"
echo "- Sample attendances"

echo -e "\n${GREEN}You can now login with the admin credentials.${NC}"

