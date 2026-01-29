# 🚀 Quick Deploy Guide - Qttenzy

**Deploy the entire Qttenzy application to free hosting platforms in under 30 minutes!**

This guide is designed for absolute beginners. No prior deployment experience needed.

---

## 📋 What You'll Need

1. **GitHub Account** (free) - [Sign up here](https://github.com/signup)
2. **Vercel Account** (free) - [Sign up here](https://vercel.com/signup)
3. **Railway Account** (free) - [Sign up here](https://railway.app/)
4. Your project code (already on GitHub)

**Total Cost: $0** ✅

---

## 🎯 Deployment Strategy

We'll deploy in 3 simple steps:

1. **Database** → Railway (MySQL)
2. **Backend** → Railway (Laravel API)
3. **Frontend** → Vercel (React App)

---

## Step 1: Deploy Database (5 minutes)

### 1.1 Create Railway Project

1. Go to [Railway.app](https://railway.app/)
2. Click **"Start a New Project"**
3. Click **"Deploy MySQL"**
4. Wait for deployment (30 seconds)

### 1.2 Get Database Credentials

1. Click on your **MySQL service**
2. Go to **"Variables"** tab
3. Copy these values (you'll need them later):
   - `MYSQL_HOST`
   - `MYSQL_PORT`
   - `MYSQL_DATABASE`
   - `MYSQL_USER`
   - `MYSQL_PASSWORD`

**✅ Database is ready!**

---

## Step 2: Deploy Backend (10 minutes)

### 2.1 Prepare Backend for Railway

1. In Railway, click **"New"** → **"GitHub Repo"**
2. Select your **Qttenzy repository**
3. Railway will detect it's a monorepo

### 2.2 Configure Backend Service

1. Click **"Settings"**
2. Set **Root Directory**: `backend`
3. Set **Build Command**: 
   ```bash
   composer install --no-dev --optimize-autoloader
   ```
4. Set **Start Command**:
   ```bash
   php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=$PORT
   ```

### 2.3 Add Environment Variables

Click **"Variables"** tab and add these:

```env
APP_NAME=Qttenzy
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-backend-url.railway.app
FRONTEND_URL=https://your-frontend-url.vercel.app

# Database (use values from Step 1.2)
DB_CONNECTION=mysql
DB_HOST=<your-mysql-host>
DB_PORT=<your-mysql-port>
DB_DATABASE=<your-mysql-database>
DB_USERNAME=<your-mysql-user>
DB_PASSWORD=<your-mysql-password>

# JWT Secret (generate random string)
JWT_SECRET=<generate-random-64-character-string>

# Payment Demo Mode (set to true for testing)
PAYMENT_DEMO_MODE=true

# Session & Cache
SESSION_DRIVER=file
CACHE_DRIVER=file
```

### 2.4 Generate APP_KEY

1. After deployment, go to Railway **"Deployments"**
2. Click on latest deployment
3. Click **"View Logs"**
4. Run this command in Railway CLI:
   ```bash
   php artisan key:generate --show
   ```
5. Copy the generated key
6. Add to **Variables**: `APP_KEY=base64:...`

### 2.5 Run Migrations

In Railway CLI or add to start command:
```bash
php artisan migrate --force
php artisan db:seed --force
```

**✅ Backend is live!** Copy your Railway backend URL (e.g., `https://qttenzy-backend.railway.app`)

---

## Step 3: Deploy Frontend (5 minutes)

### 3.1 Deploy to Vercel

1. Go to [Vercel.com](https://vercel.com/)
2. Click **"Add New"** → **"Project"**
3. Import your **Qttenzy repository**
4. Configure:
   - **Framework Preset**: Vite
   - **Root Directory**: `frontend`
   - **Build Command**: `npm run build`
   - **Output Directory**: `dist`

### 3.2 Add Environment Variables

In Vercel project settings → **"Environment Variables"**:

```env
VITE_API_BASE_URL=https://your-backend-url.railway.app/api/v1
VITE_APP_NAME=Qttenzy
VITE_FACE_API_MODELS_PATH=/models
```

Replace `your-backend-url.railway.app` with your actual Railway backend URL from Step 2.

### 3.3 Deploy

1. Click **"Deploy"**
2. Wait 2-3 minutes
3. Your app is live! 🎉

**✅ Frontend is live!** You'll get a URL like `https://qttenzy.vercel.app`

---

## Step 4: Update CORS Settings (2 minutes)

### 4.1 Update Backend Environment

Go back to Railway → Backend Service → Variables:

1. Update `FRONTEND_URL` with your Vercel URL:
   ```env
   FRONTEND_URL=https://qttenzy.vercel.app
   ```
2. Redeploy the backend

---

## 🎉 You're Done!

Your app is now live at:
- **Frontend**: `https://qttenzy.vercel.app`
- **Backend**: `https://qttenzy-backend.railway.app`
- **Database**: Running on Railway

---

## 🧪 Test Your Deployment

1. Visit your frontend URL
2. Click **"Register"**
3. Create a test account
4. Login and explore!

---

## 🔧 Troubleshooting

### Frontend shows "Network Error"
- ✅ Check `VITE_API_BASE_URL` in Vercel environment variables
- ✅ Make sure it ends with `/api/v1`
- ✅ Verify backend is running on Railway

### Backend shows "Database connection failed"
- ✅ Check database credentials in Railway variables
- ✅ Make sure MySQL service is running
- ✅ Verify `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`

### "APP_KEY not set" error
- ✅ Run `php artisan key:generate --show` in Railway CLI
- ✅ Add the generated key to Railway variables

### CORS errors
- ✅ Update `FRONTEND_URL` in Railway backend variables
- ✅ Make sure it matches your Vercel URL exactly (no trailing slash)

---

## 💰 Free Tier Limits

### Railway
- **$5 free credits/month** (enough for small projects)
- Automatically sleeps after inactivity
- Wakes up on first request

### Vercel
- **100 GB bandwidth/month**
- Unlimited deployments
- Always online

**Tip**: Both platforms offer generous free tiers perfect for development and small production apps!

---

## 🚀 Next Steps

1. **Custom Domain**: Add your own domain in Vercel settings
2. **SSL Certificate**: Automatically provided by both platforms
3. **Monitoring**: Enable Vercel Analytics (free)
4. **Backups**: Set up Railway database backups

---

## 📚 Need Help?

- Check [DEPLOYMENT.md](DEPLOYMENT.md) for advanced options
- See [README.md](README.md) for local development
- Review [TROUBLESHOOTING.md](TROUBLESHOOTING.md) for common issues

---

**Happy Deploying! 🎊**
