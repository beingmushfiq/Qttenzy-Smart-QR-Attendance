# 📖 Qttenzy Deployment Manual

**Complete Guide for Deploying Qttenzy Smart QR Attendance System**

Version 1.0 | Last Updated: January 2026

---

## Table of Contents

1. [Overview](#overview)
2. [Prerequisites](#prerequisites)
3. [Deployment Options](#deployment-options)
4. [Quick Deploy (Recommended)](#quick-deploy-recommended)
5. [Advanced Deployment](#advanced-deployment)
6. [Environment Configuration](#environment-configuration)
7. [Post-Deployment](#post-deployment)
8. [Troubleshooting](#troubleshooting)

---

## Overview

This manual provides complete instructions for deploying the Qttenzy Smart QR Attendance system. The application consists of:

- **Frontend**: React 18 + Vite application
- **Backend**: Laravel 12 REST API
- **Database**: MySQL 8.0

### Deployment Architecture

```
┌─────────────────┐
│   Frontend      │ ← Vercel/Netlify (Static Hosting)
│   (React/Vite)  │
└────────┬────────┘
         │ API Calls
         ↓
┌─────────────────┐
│   Backend       │ ← Railway/Render (Application Server)
│   (Laravel)     │
└────────┬────────┘
         │ Database Queries
         ↓
┌─────────────────┐
│   Database      │ ← Railway/Render (MySQL)
│   (MySQL 8.0)   │
└─────────────────┘
```

---

## Prerequisites

### Required Accounts (All Free)

1. **GitHub Account** - [Sign up](https://github.com/signup)
2. **Vercel Account** - [Sign up](https://vercel.com/signup)
3. **Railway Account** - [Sign up](https://railway.app/)

### Optional Tools

- Git CLI (for local development)
- Node.js 18+ (for local testing)
- PHP 8.2+ (for local testing)

---

## Deployment Options

### Option 1: Free Cloud Hosting ⭐ Recommended

**Cost**: $0/month  
**Time**: 30 minutes  
**Difficulty**: Beginner-friendly

- **Frontend**: Vercel (100GB bandwidth/month)
- **Backend**: Railway ($5 free credits/month)
- **Database**: Railway MySQL (included)

**Best for**: Beginners, testing, small-scale production

### Option 2: Docker Deployment

**Cost**: Varies by hosting  
**Time**: 45 minutes  
**Difficulty**: Intermediate

Complete containerized deployment with Docker Compose.

**Best for**: Developers familiar with Docker, consistent environments

### Option 3: VPS Deployment

**Cost**: $5-20/month  
**Time**: 1-2 hours  
**Difficulty**: Advanced

Manual deployment on Ubuntu/Debian server.

**Best for**: Full control, custom configurations, scaling

---

## Quick Deploy (Recommended)

### Step 1: Deploy Database (5 minutes)

1. Go to [Railway.app](https://railway.app/)
2. Click **"Start a New Project"**
3. Select **"Deploy MySQL"**
4. Wait for deployment (~30 seconds)

**Save these credentials** (found in Variables tab):
- `MYSQL_HOST`
- `MYSQL_PORT`
- `MYSQL_DATABASE`
- `MYSQL_USER`
- `MYSQL_PASSWORD`

### Step 2: Deploy Backend (10 minutes)

1. In Railway, click **"New"** → **"GitHub Repo"**
2. Select **`beingmushfiq/Qttenzy-Smart-QR-Attendance`**
3. Configure service:
   - **Root Directory**: `backend`
   - **Build Command**: 
     ```bash
     composer install --no-dev --optimize-autoloader
     ```
   - **Start Command**:
     ```bash
     php artisan migrate --force && php artisan db:seed --force && php artisan serve --host=0.0.0.0 --port=$PORT
     ```

4. Add environment variables (click **Variables** tab):

```env
APP_NAME=Qttenzy
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-backend.railway.app
FRONTEND_URL=https://your-frontend.vercel.app

# Database (from Step 1)
DB_CONNECTION=mysql
DB_HOST=<MYSQL_HOST>
DB_PORT=<MYSQL_PORT>
DB_DATABASE=<MYSQL_DATABASE>
DB_USERNAME=<MYSQL_USER>
DB_PASSWORD=<MYSQL_PASSWORD>

# Generate these (see below)
APP_KEY=
JWT_SECRET=

# Payment (demo mode for testing)
PAYMENT_DEMO_MODE=true
SESSION_DRIVER=file
CACHE_DRIVER=file
```

5. **Generate APP_KEY**:
   - Locally run: `php artisan key:generate --show`
   - Or use Railway CLI after deployment
   - Add to variables: `APP_KEY=base64:...`

6. **Generate JWT_SECRET**:
   - Locally run: `php artisan jwt:secret --show`
   - Or generate random 64-character string
   - Add to variables: `JWT_SECRET=...`

7. Deploy and copy your backend URL (e.g., `https://qttenzy-backend.railway.app`)

### Step 3: Deploy Frontend (5 minutes)

1. Go to [Vercel.com](https://vercel.com/)
2. Click **"Add New"** → **"Project"**
3. Import **`beingmushfiq/Qttenzy-Smart-QR-Attendance`**
4. Configure:
   - **Framework Preset**: Vite
   - **Root Directory**: `frontend`
   - **Build Command**: `npm run build`
   - **Output Directory**: `dist`

5. Add environment variables:

```env
VITE_API_BASE_URL=https://your-backend.railway.app/api/v1
VITE_APP_NAME=Qttenzy
VITE_FACE_API_MODELS_PATH=/models
```

6. Click **"Deploy"**
7. Copy your frontend URL (e.g., `https://qttenzy.vercel.app`)

### Step 4: Update CORS (2 minutes)

1. Go back to Railway → Backend Service → Variables
2. Update `FRONTEND_URL`:
   ```env
   FRONTEND_URL=https://qttenzy.vercel.app
   ```
3. Redeploy backend

### ✅ Deployment Complete!

Your application is now live:
- **Frontend**: `https://qttenzy.vercel.app`
- **Backend**: `https://qttenzy-backend.railway.app`

---

## Advanced Deployment

### Docker Deployment

See [DEPLOYMENT.md](DEPLOYMENT.md#docker-deployment) for complete Docker setup including:
- `docker-compose.yml` configuration
- Multi-stage Dockerfiles
- Volume management
- Network configuration

### VPS Deployment

See [DEPLOYMENT.md](DEPLOYMENT.md#vps-deployment) for manual deployment including:
- Server setup (Ubuntu/Debian)
- Nginx configuration
- SSL/HTTPS setup
- Process management

---

## Environment Configuration

### Backend Environment Variables

#### Required Variables

| Variable | Description | Example |
|----------|-------------|---------|
| `APP_NAME` | Application name | `Qttenzy` |
| `APP_ENV` | Environment | `production` |
| `APP_DEBUG` | Debug mode | `false` |
| `APP_KEY` | Encryption key | `base64:...` (generated) |
| `APP_URL` | Backend URL | `https://api.example.com` |
| `FRONTEND_URL` | Frontend URL for CORS | `https://example.com` |
| `DB_CONNECTION` | Database driver | `mysql` |
| `DB_HOST` | Database host | `containers-us-west.railway.app` |
| `DB_PORT` | Database port | `6543` |
| `DB_DATABASE` | Database name | `railway` |
| `DB_USERNAME` | Database user | `root` |
| `DB_PASSWORD` | Database password | `***` |
| `JWT_SECRET` | JWT signing key | 64-char random string |

#### Optional Variables

| Variable | Description | Default |
|----------|-------------|---------|
| `SESSION_DRIVER` | Session storage | `file` |
| `CACHE_DRIVER` | Cache storage | `file` |
| `PAYMENT_DEMO_MODE` | Demo payment mode | `true` |
| `SSLCOMMERZ_STORE_ID` | SSLCommerz store ID | - |
| `SSLCOMMERZ_STORE_PASSWORD` | SSLCommerz password | - |
| `STRIPE_KEY` | Stripe public key | - |
| `STRIPE_SECRET` | Stripe secret key | - |

### Frontend Environment Variables

| Variable | Description | Example |
|----------|-------------|---------|
| `VITE_API_BASE_URL` | Backend API URL | `https://api.example.com/api/v1` |
| `VITE_APP_NAME` | Application name | `Qttenzy` |
| `VITE_FACE_API_MODELS_PATH` | Face-API models path | `/models` |

### Generating Keys

**APP_KEY** (Laravel):
```bash
php artisan key:generate --show
```

**JWT_SECRET**:
```bash
php artisan jwt:secret --show
```

Or generate random string:
```bash
openssl rand -base64 64
```

---

## Post-Deployment

### 1. Verify Deployment

**Check Backend**:
```bash
curl https://your-backend.railway.app/api/v1/health
```

**Check Frontend**:
- Visit your Vercel URL
- Should load login page

### 2. Create Admin Account

1. Visit frontend URL
2. Click **"Register"**
3. Create account with:
   - Name: Admin User
   - Email: admin@qttenzy.com
   - Password: (secure password)
   - Role: Admin

### 3. Test Core Features

- ✅ User registration
- ✅ User login
- ✅ Session creation (Admin)
- ✅ QR code generation
- ✅ Face enrollment
- ✅ Attendance marking

### 4. Custom Domain (Optional)

**Vercel**:
1. Go to Project Settings → Domains
2. Add your domain
3. Update DNS records as instructed

**Railway**:
1. Go to Service Settings → Domains
2. Add custom domain
3. Update DNS records

### 5. SSL/HTTPS

Both Vercel and Railway provide automatic SSL certificates. No configuration needed!

### 6. Monitoring

**Vercel**:
- Analytics: Project → Analytics
- Logs: Project → Deployments → View Logs

**Railway**:
- Metrics: Service → Metrics
- Logs: Service → Deployments → View Logs

---

## Troubleshooting

### Common Issues

#### 1. Backend: "APP_KEY not set"

**Solution**:
```bash
# Generate key
php artisan key:generate --show

# Add to Railway variables
APP_KEY=base64:your_generated_key
```

#### 2. Frontend: "Network Error"

**Causes**:
- Wrong `VITE_API_BASE_URL`
- Backend not running
- CORS misconfiguration

**Solution**:
1. Verify `VITE_API_BASE_URL` ends with `/api/v1`
2. Check backend is running in Railway
3. Verify `FRONTEND_URL` in backend matches Vercel URL exactly

#### 3. Database Connection Failed

**Solution**:
1. Verify all database credentials in Railway variables
2. Check MySQL service is running
3. Test connection:
   ```bash
   php artisan tinker
   >>> DB::connection()->getPdo();
   ```

#### 4. CORS Errors

**Solution**:
1. Update `FRONTEND_URL` in backend `.env`:
   ```env
   FRONTEND_URL=https://your-exact-vercel-url.vercel.app
   ```
   ⚠️ No trailing slash!
2. Redeploy backend

#### 5. Face Recognition Models Not Loading

**Solution**:
1. Verify models exist in `frontend/public/models/`
2. Check `VITE_FACE_API_MODELS_PATH=/models`
3. Ensure models are included in Vercel build

### Getting More Help

- **Detailed Troubleshooting**: See [TROUBLESHOOTING.md](TROUBLESHOOTING.md)
- **Deployment Options**: See [DEPLOYMENT.md](DEPLOYMENT.md)
- **Quick Deploy Guide**: See [QUICK_DEPLOY.md](QUICK_DEPLOY.md)

---

## Deployment Checklist

### Pre-Deployment

- [ ] GitHub repository is up to date
- [ ] `.env.example` files reviewed
- [ ] Database schema is finalized
- [ ] Face-API models are in `frontend/public/models/`

### During Deployment

- [ ] Database deployed and credentials saved
- [ ] Backend deployed with all environment variables
- [ ] Frontend deployed with correct API URL
- [ ] CORS configured (FRONTEND_URL updated)
- [ ] APP_KEY and JWT_SECRET generated

### Post-Deployment

- [ ] Backend health check passes
- [ ] Frontend loads correctly
- [ ] Admin account created
- [ ] Test session creation
- [ ] Test QR code generation
- [ ] Test attendance marking
- [ ] Custom domain configured (optional)
- [ ] Monitoring set up

---

## Support & Resources

### Documentation

- [QUICK_DEPLOY.md](QUICK_DEPLOY.md) - Beginner-friendly step-by-step guide
- [DEPLOYMENT.md](DEPLOYMENT.md) - Comprehensive deployment options
- [TROUBLESHOOTING.md](TROUBLESHOOTING.md) - Common issues and solutions
- [README.md](README.md) - Project overview and local development

### Platform Documentation

- [Vercel Documentation](https://vercel.com/docs)
- [Railway Documentation](https://docs.railway.app/)
- [Laravel Deployment](https://laravel.com/docs/deployment)
- [Vite Deployment](https://vitejs.dev/guide/static-deploy.html)

---

## Maintenance

### Updating the Application

**Backend Updates**:
1. Push changes to GitHub
2. Railway auto-deploys from main branch
3. Migrations run automatically

**Frontend Updates**:
1. Push changes to GitHub
2. Vercel auto-deploys from main branch
3. New build created automatically

### Database Backups

**Railway**:
1. Go to MySQL service → Backups
2. Enable automatic backups
3. Download manual backup: `mysqldump` via Railway CLI

### Scaling

**Vercel**: Automatically scales based on traffic

**Railway**: 
- Upgrade plan for more resources
- Add horizontal scaling in service settings

---

## Security Best Practices

1. ✅ Never commit `.env` files to Git
2. ✅ Use strong `APP_KEY` and `JWT_SECRET`
3. ✅ Set `APP_DEBUG=false` in production
4. ✅ Use HTTPS for all deployments
5. ✅ Regularly update dependencies
6. ✅ Enable two-factor authentication on hosting accounts
7. ✅ Use environment-specific credentials
8. ✅ Implement rate limiting (already configured)

---

## Cost Breakdown

### Free Tier (Recommended for Starting)

| Service | Free Tier | Limits |
|---------|-----------|--------|
| Vercel | 100GB bandwidth/month | Unlimited deployments |
| Railway | $5 credits/month | ~500 hours runtime |
| Total | **$0/month** | Suitable for development & small production |

### Paid Tier (For Scaling)

| Service | Cost | Benefits |
|---------|------|----------|
| Vercel Pro | $20/month | 1TB bandwidth, priority support |
| Railway Pro | $20/month | More resources, priority support |
| Total | **$40/month** | Suitable for medium-scale production |

---

## Conclusion

Your Qttenzy Smart QR Attendance system is now deployed and ready to use! 

**Next Steps**:
1. Share the frontend URL with users
2. Create admin accounts for managers
3. Start creating sessions
4. Monitor usage and performance

**Need Help?** Check [TROUBLESHOOTING.md](TROUBLESHOOTING.md) or review the platform-specific documentation.

---

**Deployment Manual Version**: 1.0  
**Last Updated**: January 2026  
**Maintained By**: Qttenzy Development Team

---

*Built with ❤️ for Modern Attendance Management*
