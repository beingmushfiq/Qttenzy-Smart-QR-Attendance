# 🚀 Deployment Guide - Qttenzy

Complete deployment guide for deploying Qttenzy to various platforms.

---

## Table of Contents

1. [Free Hosting Platforms](#free-hosting-platforms)
2. [Railway + Vercel (Recommended)](#railway--vercel-recommended)
3. [Render + Netlify](#render--netlify)
4. [Docker Deployment](#docker-deployment)
5. [VPS Deployment](#vps-deployment)
6. [Environment Variables](#environment-variables)
7. [Troubleshooting](#troubleshooting)

---

## Free Hosting Platforms

### Best Free Combinations

| Frontend | Backend | Database | Total Cost |
|----------|---------|----------|------------|
| Vercel | Railway | Railway MySQL | $0 |
| Netlify | Render | Render PostgreSQL | $0 |
| Vercel | Render | PlanetScale | $0 |
| Cloudflare Pages | Railway | Railway MySQL | $0 |

**Recommended for Beginners**: Vercel + Railway (see [QUICK_DEPLOY.md](QUICK_DEPLOY.md))

---

## Railway + Vercel (Recommended)

### Why This Combination?

✅ **Easiest setup** - Both have excellent GitHub integration  
✅ **Free tier** - Railway: $5/month credits, Vercel: 100GB bandwidth  
✅ **Auto-deploy** - Push to GitHub = automatic deployment  
✅ **MySQL included** - Railway provides free MySQL database  

### Quick Start

See [QUICK_DEPLOY.md](QUICK_DEPLOY.md) for step-by-step instructions.

### Detailed Steps

#### 1. Deploy Database on Railway

```bash
# Railway automatically detects MySQL
# No configuration needed!
```

**Get credentials from Railway dashboard:**
- MYSQL_HOST
- MYSQL_PORT
- MYSQL_DATABASE
- MYSQL_USER
- MYSQL_PASSWORD

#### 2. Deploy Backend on Railway

**Root Directory**: `backend`

**Build Command**:
```bash
composer install --no-dev --optimize-autoloader
```

**Start Command**:
```bash
php artisan migrate --force && php artisan db:seed --force && php artisan serve --host=0.0.0.0 --port=$PORT
```

**Environment Variables**:
```env
APP_NAME=Qttenzy
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:YOUR_GENERATED_KEY
APP_URL=https://your-backend.railway.app
FRONTEND_URL=https://your-frontend.vercel.app

# Railway uses MYSQLHOST, MYSQLPORT etc. (no underscores)
# Bind Laravel's DB_ vars to Railway's provided vars:
DB_CONNECTION=mysql
DB_HOST=${MYSQLHOST}
DB_PORT=${MYSQLPORT}
DB_DATABASE=${MYSQLDATABASE}
DB_USERNAME=${MYSQLUSER}
DB_PASSWORD=${MYSQLPASSWORD}

JWT_SECRET=YOUR_64_CHAR_RANDOM_STRING

SESSION_DRIVER=file
CACHE_DRIVER=file

PAYMENT_DEMO_MODE=true
```

**Generate APP_KEY**:
```bash
# In Railway CLI or local terminal
php artisan key:generate --show
```

#### 3. Deploy Frontend on Vercel

**Framework**: Vite  
**Root Directory**: `frontend`  
**Build Command**: `npm run build`  
**Output Directory**: `dist`

**Environment Variables**:
```env
VITE_API_BASE_URL=https://your-backend.railway.app/api/v1
VITE_APP_NAME=Qttenzy
VITE_FACE_API_MODELS_PATH=/models
```

---

## Render + Netlify

### Deploy Database on Render

1. Create new **PostgreSQL** database
2. Copy connection string

### Deploy Backend on Render

**Build Command**:
```bash
composer install --no-dev --optimize-autoloader
```

**Start Command**:
```bash
php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=$PORT
```

**Environment Variables**:
```env
APP_NAME=Qttenzy
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:YOUR_KEY
APP_URL=https://your-backend.onrender.com
FRONTEND_URL=https://your-frontend.netlify.app

DB_CONNECTION=pgsql
DATABASE_URL=${DATABASE_URL}

JWT_SECRET=YOUR_SECRET
SESSION_DRIVER=file
CACHE_DRIVER=file
PAYMENT_DEMO_MODE=true
```

### Deploy Frontend on Netlify

**Build Command**: `npm run build`  
**Publish Directory**: `frontend/dist`  
**Base Directory**: `frontend`

**Environment Variables**:
```env
VITE_API_BASE_URL=https://your-backend.onrender.com/api/v1
VITE_APP_NAME=Qttenzy
VITE_FACE_API_MODELS_PATH=/models
```

---

## Docker Deployment

### Prerequisites

- Docker installed
- Docker Compose installed

### Quick Start

```bash
# Clone repository
git clone https://github.com/beingmushfiq/Qttenzy-Smart-QR-Attendance.git
cd Qttenzy-Smart-QR-Attendance

# Copy environment files
cp backend/.env.example backend/.env
cp frontend/.env.example frontend/.env

# Edit .env files with your configuration
# Then build and start
docker-compose up -d
```

### Docker Compose Configuration

Create `docker-compose.yml`:

```yaml
version: '3.8'

services:
  # MySQL Database
  db:
    image: mysql:8.0
    environment:
      MYSQL_ROOT_PASSWORD: rootpassword
      MYSQL_DATABASE: qttenzy
      MYSQL_USER: qttenzy_user
      MYSQL_PASSWORD: qttenzy_pass
    volumes:
      - mysql_data:/var/lib/mysql
    ports:
      - "3306:3306"
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-h", "localhost"]
      interval: 10s
      timeout: 5s
      retries: 5

  # Laravel Backend
  backend:
    build:
      context: ./backend
      dockerfile: Dockerfile
    depends_on:
      db:
        condition: service_healthy
    environment:
      - APP_ENV=production
      - DB_HOST=db
      - DB_DATABASE=qttenzy
      - DB_USERNAME=qttenzy_user
      - DB_PASSWORD=qttenzy_pass
    ports:
      - "8000:8000"
    volumes:
      - ./backend:/var/www/html
    command: >
      sh -c "php artisan migrate --force &&
             php artisan db:seed --force &&
             php artisan serve --host=0.0.0.0 --port=8000"

  # React Frontend
  frontend:
    build:
      context: ./frontend
      dockerfile: Dockerfile
    ports:
      - "3000:80"
    depends_on:
      - backend

volumes:
  mysql_data:
```

### Backend Dockerfile

Create `backend/Dockerfile`:

```dockerfile
FROM php:8.2-fpm

# Install dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . .

# Install dependencies
RUN composer install --no-dev --optimize-autoloader

# Set permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 8000

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
```

### Frontend Dockerfile

Create `frontend/Dockerfile`:

```dockerfile
# Build stage
FROM node:18-alpine AS build

WORKDIR /app

COPY package*.json ./
RUN npm ci

COPY . .
RUN npm run build

# Production stage
FROM nginx:alpine

COPY --from=build /app/dist /usr/share/nginx/html
COPY nginx.conf /etc/nginx/conf.d/default.conf

EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]
```

### Frontend Nginx Config

Create `frontend/nginx.conf`:

```nginx
server {
    listen 80;
    server_name localhost;
    root /usr/share/nginx/html;
    index index.html;

    location / {
        try_files $uri $uri/ /index.html;
    }

    location /assets {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

---

## VPS Deployment

### Prerequisites

- Ubuntu 20.04+ or Debian 11+
- Root or sudo access
- Domain name (optional)

### 1. Install Dependencies

```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install Nginx
sudo apt install nginx -y

# Install PHP 8.2
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
sudo apt install php8.2 php8.2-fpm php8.2-mysql php8.2-mbstring \
    php8.2-xml php8.2-bcmath php8.2-curl php8.2-zip -y

# Install MySQL
sudo apt install mysql-server -y

# Install Node.js 18
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt install nodejs -y

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

### 2. Setup Database

```bash
sudo mysql -u root -p

CREATE DATABASE qttenzy;
CREATE USER 'qttenzy_user'@'localhost' IDENTIFIED BY 'strong_password';
GRANT ALL PRIVILEGES ON qttenzy.* TO 'qttenzy_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 3. Deploy Backend

```bash
# Clone repository
cd /var/www
sudo git clone https://github.com/beingmushfiq/Qttenzy-Smart-QR-Attendance.git qttenzy
cd qttenzy/backend

# Install dependencies
sudo composer install --no-dev --optimize-autoloader

# Setup environment
sudo cp .env.example .env
sudo nano .env  # Edit with your configuration

# Generate keys
sudo php artisan key:generate
sudo php artisan jwt:secret

# Run migrations
sudo php artisan migrate --force
sudo php artisan db:seed --force

# Set permissions
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

### 4. Deploy Frontend

```bash
cd /var/www/qttenzy/frontend

# Install dependencies
sudo npm install

# Setup environment
sudo cp .env.example .env
sudo nano .env  # Edit with your backend URL

# Build for production
sudo npm run build
```

### 5. Configure Nginx

Create `/etc/nginx/sites-available/qttenzy`:

```nginx
# Backend API
server {
    listen 80;
    server_name api.yourdomain.com;
    root /var/www/qttenzy/backend/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}

# Frontend
server {
    listen 80;
    server_name yourdomain.com www.yourdomain.com;
    root /var/www/qttenzy/frontend/dist;

    index index.html;

    location / {
        try_files $uri $uri/ /index.html;
    }

    location /assets {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

Enable site:
```bash
sudo ln -s /etc/nginx/sites-available/qttenzy /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

### 6. Setup SSL (Optional but Recommended)

```bash
# Install Certbot
sudo apt install certbot python3-certbot-nginx -y

# Get SSL certificates
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com -d api.yourdomain.com
```

---

## Environment Variables

### Backend (.env)

```env
# Application
APP_NAME=Qttenzy
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:YOUR_GENERATED_KEY
APP_URL=https://api.yourdomain.com
FRONTEND_URL=https://yourdomain.com

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=qttenzy
DB_USERNAME=qttenzy_user
DB_PASSWORD=your_password

# JWT
JWT_SECRET=your_64_character_random_string

# Cache & Session
SESSION_DRIVER=file
CACHE_DRIVER=file
QUEUE_CONNECTION=sync

# Payment (Optional)
PAYMENT_DEMO_MODE=true
SSLCOMMERZ_STORE_ID=
SSLCOMMERZ_STORE_PASSWORD=
SSLCOMMERZ_MODE=sandbox
STRIPE_KEY=
STRIPE_SECRET=
STRIPE_WEBHOOK_SECRET=
```

### Frontend (.env)

```env
VITE_API_BASE_URL=https://api.yourdomain.com/api/v1
VITE_APP_NAME=Qttenzy
VITE_FACE_API_MODELS_PATH=/models
```

---

## Troubleshooting

See [TROUBLESHOOTING.md](TROUBLESHOOTING.md) for common issues and solutions.

### Quick Fixes

**500 Internal Server Error**
```bash
# Check Laravel logs
tail -f backend/storage/logs/laravel.log

# Clear cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

**Database Connection Failed**
- Verify database credentials in `.env`
- Check database service is running
- Test connection: `php artisan tinker` → `DB::connection()->getPdo();`

**CORS Errors**
- Update `FRONTEND_URL` in backend `.env`
- Restart backend service

**Build Failures**
- Clear node_modules: `rm -rf node_modules && npm install`
- Clear composer cache: `composer clear-cache && composer install`

---

## Next Steps

1. **Custom Domain**: Point your domain to deployment platform
2. **SSL Certificate**: Enable HTTPS (automatic on Vercel/Netlify)
3. **Monitoring**: Set up error tracking (Sentry, LogRocket)
4. **Backups**: Schedule database backups
5. **CI/CD**: Automate deployments with GitHub Actions

---

**Need Help?** Check [QUICK_DEPLOY.md](QUICK_DEPLOY.md) for beginner-friendly guide or [TROUBLESHOOTING.md](TROUBLESHOOTING.md) for common issues.
