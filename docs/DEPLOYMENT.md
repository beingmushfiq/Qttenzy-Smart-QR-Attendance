# Deployment Guide
## Qttenzy - Production Deployment Instructions

---

## 🚀 FRONTEND DEPLOYMENT

### Option 1: Vercel Deployment

#### Prerequisites
- Vercel account
- GitHub/GitLab repository connected

#### Steps
```bash
# Install Vercel CLI
npm install -g vercel

# Login to Vercel
vercel login

# Deploy to production
cd frontend
vercel --prod
```

#### Environment Variables (Vercel Dashboard)
```
VITE_API_BASE_URL=https://api.qttenzy.com/api/v1
VITE_APP_NAME=Qttenzy
VITE_FACE_API_MODELS_PATH=/models
VITE_GOOGLE_MAPS_API_KEY=your_key_here
VITE_PAYMENT_GATEWAY=sslcommerz
```

#### Vercel Configuration (`vercel.json`)
```json
{
  "buildCommand": "npm run build",
  "outputDirectory": "dist",
  "devCommand": "npm run dev",
  "installCommand": "npm install",
  "framework": "vite",
  "rewrites": [
    {
      "source": "/(.*)",
      "destination": "/index.html"
    }
  ],
  "headers": [
    {
      "source": "/(.*)",
      "headers": [
        {
          "key": "X-Content-Type-Options",
          "value": "nosniff"
        },
        {
          "key": "X-Frame-Options",
          "value": "DENY"
        },
        {
          "key": "X-XSS-Protection",
          "value": "1; mode=block"
        }
      ]
    }
  ]
}
```

---

### Option 2: Netlify Deployment

#### Steps
```bash
# Install Netlify CLI
npm install -g netlify-cli

# Login
netlify login

# Deploy
cd frontend
netlify deploy --prod --dir=dist
```

#### Netlify Configuration (`netlify.toml`)
```toml
[build]
  command = "npm run build"
  publish = "dist"

[[redirects]]
  from = "/*"
  to = "/index.html"
  status = 200

[build.environment]
  NODE_VERSION = "18"

[[headers]]
  for = "/*"
  [headers.values]
    X-Frame-Options = "DENY"
    X-Content-Type-Options = "nosniff"
    X-XSS-Protection = "1; mode=block"
```

---

### Option 3: Traditional VPS Deployment (Nginx)

#### Build Frontend
```bash
cd frontend
npm install
npm run build
```

#### Nginx Configuration (`/etc/nginx/sites-available/qttenzy`)
```nginx
server {
    listen 80;
    server_name app.qttenzy.com;
    
    # Redirect HTTP to HTTPS
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name app.qttenzy.com;
    
    root /var/www/qttenzy/frontend/dist;
    index index.html;
    
    # SSL Configuration
    ssl_certificate /etc/letsencrypt/live/app.qttenzy.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/app.qttenzy.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    
    # Security Headers
    add_header X-Frame-Options "DENY" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
    
    # Gzip Compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_types text/plain text/css text/xml text/javascript application/javascript application/xml+rss application/json;
    
    # SPA Routing
    location / {
        try_files $uri $uri/ /index.html;
    }
    
    # Static Assets Caching
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
    
    # Face-API Models
    location /models {
        alias /var/www/qttenzy/frontend/public/models;
        expires 1y;
    }
}
```

#### Deploy Script
```bash
#!/bin/bash
# deploy-frontend.sh

cd /var/www/qttenzy/frontend
git pull origin main
npm install
npm run build
sudo systemctl reload nginx
```

---

## 🔧 BACKEND DEPLOYMENT

### Option 1: Laravel Forge

#### Setup Steps
1. Connect GitHub repository to Forge
2. Create new server (DigitalOcean/Linode/AWS)
3. Configure site:
   - Domain: `api.qttenzy.com`
   - Web Directory: `/public`
   - PHP Version: 8.2
4. Set environment variables in Forge dashboard
5. Enable SSL certificate
6. Configure deployment script

#### Deployment Script (Forge)
```bash
cd /home/forge/api.qttenzy.com
git pull origin main
composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart
```

---

### Option 2: cPanel Deployment

#### Steps
1. Upload files via FTP/SFTP to `public_html/api`
2. Set document root to `public_html/api/public`
3. Configure database in cPanel
4. Set environment variables in `.env`
5. Run migrations via SSH:
```bash
cd public_html/api
php artisan migrate --force
php artisan config:cache
php artisan route:cache
```

#### .htaccess Configuration (`public/.htaccess`)
```apache
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    RewriteEngine On

    # Handle Authorization Header
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

    # Redirect Trailing Slashes
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]

    # Send Requests To Front Controller
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>
```

---

### Option 3: VPS Deployment (Ubuntu/Debian)

#### Server Setup Script
```bash
#!/bin/bash
# setup-server.sh

# Update system
sudo apt update && sudo apt upgrade -y

# Install PHP 8.2
sudo apt install -y software-properties-common
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
sudo apt install -y php8.2 php8.2-fpm php8.2-mysql php8.2-xml php8.2-mbstring php8.2-curl php8.2-zip php8.2-gd php8.2-bcmath

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Install MySQL
sudo apt install -y mysql-server
sudo mysql_secure_installation

# Install Nginx
sudo apt install -y nginx

# Install Node.js (for asset compilation)
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt install -y nodejs

# Install Redis (optional, for caching)
sudo apt install -y redis-server

# Create application directory
sudo mkdir -p /var/www/qttenzy
sudo chown -R $USER:$USER /var/www/qttenzy
```

#### Clone and Setup Application
```bash
cd /var/www/qttenzy
git clone https://github.com/yourusername/qttenzy-backend.git backend
cd backend
composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate
php artisan jwt:secret
```

#### Nginx Configuration (`/etc/nginx/sites-available/qttenzy-api`)
```nginx
server {
    listen 80;
    server_name api.qttenzy.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name api.qttenzy.com;
    root /var/www/qttenzy/backend/public;

    index index.php index.html;

    charset utf-8;

    # SSL Configuration
    ssl_certificate /etc/letsencrypt/live/api.qttenzy.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/api.qttenzy.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;

    # Security Headers
    add_header X-Frame-Options "DENY" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;

    # Gzip Compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_types text/plain text/css text/xml text/javascript application/javascript application/json;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Increase upload size
    client_max_body_size 10M;
}
```

#### Enable Site
```bash
sudo ln -s /etc/nginx/sites-available/qttenzy-api /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

#### SSL Certificate (Let's Encrypt)
```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d api.qttenzy.com
```

#### Supervisor Configuration (Queue Workers)
```bash
# Install Supervisor
sudo apt install -y supervisor

# Create config file
sudo nano /etc/supervisor/conf.d/qttenzy-worker.conf
```

```ini
[program:qttenzy-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/qttenzy/backend/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/qttenzy/backend/storage/logs/worker.log
stopwaitsecs=3600
```

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start qttenzy-worker:*
```

#### Cron Job (Scheduler)
```bash
sudo crontab -e -u www-data
```

Add:
```
* * * * * cd /var/www/qttenzy/backend && php artisan schedule:run >> /dev/null 2>&1
```

---

## 🗄️ DATABASE CONFIGURATION

### MySQL Setup
```sql
-- Create database
CREATE DATABASE qttenzy CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Create user
CREATE USER 'qttenzy_user'@'localhost' IDENTIFIED BY 'strong_password_here';

-- Grant privileges
GRANT ALL PRIVILEGES ON qttenzy.* TO 'qttenzy_user'@'localhost';
FLUSH PRIVILEGES;
```

### Run Migrations
```bash
cd /var/www/qttenzy/backend
php artisan migrate --force
php artisan db:seed
```

### Database Backup Script
```bash
#!/bin/bash
# backup-db.sh

DB_NAME="qttenzy"
DB_USER="qttenzy_user"
DB_PASS="password"
BACKUP_DIR="/var/backups/qttenzy"
DATE=$(date +%Y%m%d_%H%M%S)

mkdir -p $BACKUP_DIR
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME | gzip > $BACKUP_DIR/qttenzy_$DATE.sql.gz

# Keep only last 7 days
find $BACKUP_DIR -name "*.sql.gz" -mtime +7 -delete
```

### Automated Backups (Cron)
```bash
# Add to crontab
0 2 * * * /var/www/qttenzy/scripts/backup-db.sh
```

---

## 🔐 ENVIRONMENT CONFIGURATION

### Production `.env` Template
```env
APP_NAME="Qttenzy"
APP_ENV=production
APP_KEY=base64:generated_key_here
APP_DEBUG=false
APP_URL=https://api.qttenzy.com

LOG_CHANNEL=stack
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=qttenzy
DB_USERNAME=qttenzy_user
DB_PASSWORD=strong_password_here

CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

JWT_SECRET=strong_random_secret_here
JWT_TTL=60
JWT_REFRESH_TTL=20160

FRONTEND_URL=https://app.qttenzy.com

# Payment Gateways
SSLCOMMERZ_STORE_ID=your_store_id
SSLCOMMERZ_STORE_PASSWORD=your_store_password
SSLCOMMERZ_MODE=live

STRIPE_KEY=pk_live_key
STRIPE_SECRET=sk_live_secret

# Mail Configuration
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@qttenzy.com
MAIL_FROM_NAME="${APP_NAME}"
```

---

## 📊 MONITORING & LOGGING

### Laravel Logging Configuration
```php
// config/logging.php
'channels' => [
    'stack' => [
        'driver' => 'stack',
        'channels' => ['daily', 'slack'],
    ],
    'daily' => [
        'driver' => 'daily',
        'path' => storage_path('logs/laravel.log'),
        'level' => env('LOG_LEVEL', 'error'),
        'days' => 14,
    ],
],
```

### Application Monitoring (Optional)
- **Sentry**: Error tracking
- **New Relic**: Performance monitoring
- **Laravel Telescope**: Local debugging (dev only)

---

## 🚀 DEPLOYMENT CHECKLIST

### Pre-Deployment
- [ ] All tests passing
- [ ] Environment variables configured
- [ ] Database migrations ready
- [ ] SSL certificates installed
- [ ] Domain DNS configured
- [ ] Backup strategy in place
- [ ] Monitoring configured

### Deployment Steps
- [ ] Pull latest code
- [ ] Install dependencies (`composer install --no-dev`)
- [ ] Run migrations (`php artisan migrate --force`)
- [ ] Clear caches (`php artisan config:cache`, `route:cache`, `view:cache`)
- [ ] Restart queue workers
- [ ] Restart PHP-FPM (`sudo systemctl restart php8.2-fpm`)
- [ ] Reload Nginx (`sudo systemctl reload nginx`)

### Post-Deployment
- [ ] Verify API endpoints
- [ ] Test authentication flow
- [ ] Check error logs
- [ ] Monitor performance
- [ ] Verify SSL certificate
- [ ] Test payment integration (sandbox)

---

## 🔄 CI/CD PIPELINE (GitHub Actions)

### Workflow File (`.github/workflows/deploy.yml`)
```yaml
name: Deploy to Production

on:
  push:
    branches: [ main ]

jobs:
  deploy-backend:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          extensions: mbstring, xml, mysql, gd, zip
      
      - name: Install Dependencies
        run: composer install --no-dev --optimize-autoloader
      
      - name: Deploy to Server
        uses: appleboy/scp-action@master
        with:
          host: ${{ secrets.SERVER_HOST }}
          username: ${{ secrets.SERVER_USER }}
          key: ${{ secrets.SERVER_SSH_KEY }}
          source: "backend/*"
          target: "/var/www/qttenzy/backend"
      
      - name: Run Migrations
        uses: appleboy/ssh-action@master
        with:
          host: ${{ secrets.SERVER_HOST }}
          username: ${{ secrets.SERVER_USER }}
          key: ${{ secrets.SERVER_SSH_KEY }}
          script: |
            cd /var/www/qttenzy/backend
            php artisan migrate --force
            php artisan config:cache
            php artisan route:cache
            php artisan view:cache
            sudo systemctl restart php8.2-fpm

  deploy-frontend:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      
      - name: Setup Node.js
        uses: actions/setup-node@v3
        with:
          node-version: '18'
      
      - name: Install Dependencies
        run: |
          cd frontend
          npm install
      
      - name: Build
        run: |
          cd frontend
          npm run build
        env:
          VITE_API_BASE_URL: ${{ secrets.API_BASE_URL }}
      
      - name: Deploy to Vercel
        uses: amondnet/vercel-action@v20
        with:
          vercel-token: ${{ secrets.VERCEL_TOKEN }}
          vercel-org-id: ${{ secrets.VERCEL_ORG_ID }}
          vercel-project-id: ${{ secrets.VERCEL_PROJECT_ID }}
          working-directory: ./frontend
```

---

## 📱 MOBILE APP CONSIDERATIONS (Future)

### API Compatibility
- Ensure API supports mobile app requirements
- Add mobile-specific endpoints if needed
- Implement push notifications (Firebase)

---

## 🔧 TROUBLESHOOTING

### Common Issues

#### 500 Internal Server Error
```bash
# Check Laravel logs
tail -f storage/logs/laravel.log

# Check PHP-FPM logs
sudo tail -f /var/log/php8.2-fpm.log

# Check Nginx error logs
sudo tail -f /var/log/nginx/error.log
```

#### Permission Issues
```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

#### Queue Not Processing
```bash
# Check supervisor status
sudo supervisorctl status

# Restart workers
sudo supervisorctl restart qttenzy-worker:*
```

---

## 📞 SUPPORT & MAINTENANCE

### Maintenance Mode
```bash
php artisan down
# Perform maintenance
php artisan up
```

### Health Check Endpoint
```php
// routes/api.php
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now(),
        'database' => DB::connection()->getPdo() ? 'connected' : 'disconnected'
    ]);
});
```

---

## 🎯 PERFORMANCE OPTIMIZATION

### Laravel Optimizations
```bash
# Cache configuration
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache

# Optimize autoloader
composer install --optimize-autoloader --no-dev
```

### Database Optimization
- Add indexes on frequently queried columns
- Use database query caching
- Optimize slow queries

### CDN Configuration
- Serve static assets via CDN
- Configure CloudFlare or AWS CloudFront
- Enable browser caching

