# 🔧 Troubleshooting Guide - Qttenzy

Common issues and solutions for deploying and running Qttenzy.

---

## Table of Contents

1. [Deployment Issues](#deployment-issues)
2. [Backend Issues](#backend-issues)
3. [Frontend Issues](#frontend-issues)
4. [Database Issues](#database-issues)
5. [Authentication Issues](#authentication-issues)
6. [Payment Issues](#payment-issues)
7. [Face Recognition Issues](#face-recognition-issues)

---

## Deployment Issues

### Railway: Build Failed

**Error**: `Build failed with exit code 1`

**Solutions**:
1. Check build logs in Railway dashboard
2. Verify `composer.json` is valid
3. Ensure PHP version is 8.2+:
   ```json
   // In composer.json
   "require": {
     "php": "^8.2"
   }
   ```
4. Clear build cache and redeploy

### Vercel: Build Timeout

**Error**: `Build exceeded maximum duration`

**Solutions**:
1. Optimize `node_modules`:
   ```bash
   npm ci --production
   ```
2. Use build cache:
   ```json
   // In vercel.json
   "github": {
     "silent": true
   }
   ```
3. Reduce bundle size - check for large dependencies

### Environment Variables Not Working

**Error**: Variables showing as `undefined`

**Solutions**:

**Backend (Railway/Render)**:
- Ensure no quotes around values
- Restart service after adding variables
- Check variable names match exactly (case-sensitive)

**Frontend (Vercel/Netlify)**:
- Must prefix with `VITE_`
- Redeploy after adding variables
- Check in build logs that variables are set

---

## Backend Issues

### APP_KEY Not Set

**Error**: `No application encryption key has been specified`

**Solution**:
```bash
# Generate key locally
php artisan key:generate --show

# Copy output (e.g., base64:abc123...)
# Add to Railway/Render environment variables:
APP_KEY=base64:abc123...

# Redeploy
```

### JWT Secret Not Set

**Error**: `The JWT secret is not set`

**Solution**:
```bash
# Generate JWT secret locally
php artisan jwt:secret --show

# Copy output
# Add to environment variables:
JWT_SECRET=your_generated_secret

# Redeploy
```

### Storage Permission Denied

**Error**: `The stream or file "storage/logs/laravel.log" could not be opened`

**Solution**:

**On VPS**:
```bash
cd /var/www/qttenzy/backend
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

**On Railway/Render**:
- Usually auto-handled
- If persists, add to start command:
  ```bash
  chmod -R 775 storage && php artisan serve --host=0.0.0.0 --port=$PORT
  ```

### 500 Internal Server Error

**Error**: Generic 500 error

**Solutions**:
1. Check logs:
   ```bash
   # Railway: View in dashboard logs
   # VPS:
   tail -f storage/logs/laravel.log
   ```

2. Enable debug mode temporarily:
   ```env
   APP_DEBUG=true
   ```
   **⚠️ Disable in production after debugging!**

3. Clear all caches:
   ```bash
   php artisan cache:clear
   php artisan config:clear
   php artisan route:clear
   php artisan view:clear
   ```

### CORS Errors

**Error**: `Access to XMLHttpRequest blocked by CORS policy`

**Solutions**:
1. Update `FRONTEND_URL` in backend `.env`:
   ```env
   FRONTEND_URL=https://your-exact-frontend-url.vercel.app
   ```
   ⚠️ No trailing slash!

2. Verify CORS middleware in `bootstrap/app.php`:
   ```php
   ->withMiddleware(function (Middleware $middleware) {
       $middleware->validateCsrfTokens(except: [
           'api/*',
       ]);
   })
   ```

3. Check `config/cors.php`:
   ```php
   'allowed_origins' => [env('FRONTEND_URL', 'http://localhost:5173')],
   'supports_credentials' => true,
   ```

4. Restart backend service

---

## Frontend Issues

### API Network Error

**Error**: `Network Error` or `ERR_CONNECTION_REFUSED`

**Solutions**:
1. Verify `VITE_API_BASE_URL` in Vercel/Netlify:
   ```env
   VITE_API_BASE_URL=https://your-backend.railway.app/api/v1
   ```
   ✅ Must end with `/api/v1`

2. Test backend directly:
   ```bash
   curl https://your-backend.railway.app/api/v1/health
   ```

3. Check backend is running in Railway/Render dashboard

4. Verify CORS settings (see above)

### Blank Page / White Screen

**Error**: Page loads but shows nothing

**Solutions**:
1. Check browser console for errors (F12)

2. Verify build output:
   ```bash
   # Locally
   cd frontend
   npm run build
   # Check dist/ folder is created
   ```

3. Check Vercel/Netlify build logs for errors

4. Verify `index.html` exists in build output

### Face Recognition Models Not Loading

**Error**: `Failed to load face recognition models`

**Solutions**:
1. Verify models exist in `frontend/public/models/`:
   ```
   frontend/public/models/
   ├── face_landmark_68_model-weights_manifest.json
   ├── face_landmark_68_model-shard1
   ├── face_recognition_model-weights_manifest.json
   ├── face_recognition_model-shard1
   ├── ssd_mobilenetv1_model-weights_manifest.json
   └── ssd_mobilenetv1_model-shard1
   ```

2. Check `VITE_FACE_API_MODELS_PATH`:
   ```env
   VITE_FACE_API_MODELS_PATH=/models
   ```

3. Verify models are included in build:
   ```javascript
   // In vite.config.js
   export default defineConfig({
     publicDir: 'public',
   })
   ```

4. Download models if missing:
   ```bash
   cd frontend/public
   mkdir -p models
   cd models
   # Download from: https://github.com/justadudewhohacks/face-api.js-models
   ```

### Camera Permission Denied

**Error**: `NotAllowedError: Permission denied`

**Solutions**:
1. **HTTPS Required**: Camera only works on HTTPS or localhost
   - Vercel/Netlify automatically provide HTTPS ✅
   - Local development uses localhost ✅

2. Check browser permissions:
   - Click lock icon in address bar
   - Allow camera access

3. Test on different browser (Chrome recommended)

---

## Database Issues

### Connection Refused

**Error**: `SQLSTATE[HY000] [2002] Connection refused`

**Solutions**:
1. Verify database is running:
   - Railway: Check MySQL service status
   - Render: Check PostgreSQL status
   - VPS: `sudo systemctl status mysql`

2. Check credentials in `.env`:
   ```env
   DB_HOST=containers-us-west-123.railway.app
   DB_PORT=6543
   DB_DATABASE=railway
   DB_USERNAME=root
   DB_PASSWORD=your_password
   ```

3. Test connection:
   ```bash
   php artisan tinker
   >>> DB::connection()->getPdo();
   ```

### Migration Failed

**Error**: `Migration table not found` or migration errors

**Solutions**:
1. Ensure database exists:
   ```bash
   # Railway: Auto-created
   # VPS:
   mysql -u root -p
   CREATE DATABASE qttenzy;
   ```

2. Run migrations manually:
   ```bash
   php artisan migrate:fresh --force
   ```
   ⚠️ This drops all tables!

3. Check migration files for syntax errors

4. Verify database user has permissions:
   ```sql
   GRANT ALL PRIVILEGES ON qttenzy.* TO 'user'@'%';
   FLUSH PRIVILEGES;
   ```

### Seeder Failed

**Error**: Seeder errors or incomplete data

**Solutions**:
1. Run specific seeder:
   ```bash
   php artisan db:seed --class=DatabaseSeeder --force
   ```

2. Check seeder logs:
   ```bash
   tail -f storage/logs/laravel.log
   ```

3. Verify foreign key constraints are met

4. Run seeders in order if dependencies exist

---

## Authentication Issues

### Login Failed - Invalid Credentials

**Error**: `Invalid credentials` even with correct password

**Solutions**:
1. Verify user exists in database:
   ```bash
   php artisan tinker
   >>> User::where('email', 'test@example.com')->first();
   ```

2. Reset password:
   ```bash
   php artisan tinker
   >>> $user = User::where('email', 'test@example.com')->first();
   >>> $user->password = Hash::make('newpassword');
   >>> $user->save();
   ```

3. Check password hashing in `RegisterController`

### JWT Token Invalid

**Error**: `Token invalid` or `Token expired`

**Solutions**:
1. Verify `JWT_SECRET` is set in backend `.env`

2. Check token expiration in `config/jwt.php`:
   ```php
   'ttl' => env('JWT_TTL', 60), // 60 minutes
   ```

3. Clear auth tokens and re-login

4. Verify token is sent in Authorization header:
   ```javascript
   headers: {
     'Authorization': `Bearer ${token}`
   }
   ```

### Session Expired Immediately

**Error**: User logged out immediately after login

**Solutions**:
1. Check `SESSION_DRIVER` in `.env`:
   ```env
   SESSION_DRIVER=file
   ```

2. Verify storage permissions (see above)

3. Check session lifetime in `config/session.php`

---

## Payment Issues

### SSLCommerz Payment Failed

**Error**: Payment initialization failed

**Solutions**:
1. Verify credentials in `.env`:
   ```env
   SSLCOMMERZ_STORE_ID=your_store_id
   SSLCOMMERZ_STORE_PASSWORD=your_password
   SSLCOMMERZ_MODE=sandbox
   ```

2. For testing, enable demo mode:
   ```env
   PAYMENT_DEMO_MODE=true
   ```

3. Check SSLCommerz API status

4. Verify callback URLs are accessible

### Stripe Payment Failed

**Error**: Stripe initialization failed

**Solutions**:
1. Verify API keys in `.env`:
   ```env
   STRIPE_KEY=pk_test_...
   STRIPE_SECRET=sk_test_...
   ```

2. Use test keys for development:
   - Get from: https://dashboard.stripe.com/test/apikeys

3. Check webhook secret if using webhooks:
   ```env
   STRIPE_WEBHOOK_SECRET=whsec_...
   ```

---

## Face Recognition Issues

### Models Loading Slow

**Issue**: Face recognition takes too long to initialize

**Solutions**:
1. Use CDN for models (faster):
   ```javascript
   const MODEL_URL = 'https://cdn.jsdelivr.net/npm/@vladmandic/face-api/model';
   ```

2. Lazy load models only when needed

3. Cache models in browser storage

### Face Detection Not Working

**Error**: No face detected in camera

**Solutions**:
1. Ensure good lighting
2. Face camera directly
3. Remove glasses/masks if possible
4. Try different detection options:
   ```javascript
   const detections = await faceapi
     .detectSingleFace(video, new faceapi.TinyFaceDetectorOptions({
       inputSize: 512,
       scoreThreshold: 0.5
     }))
   ```

### Face Match Always Fails

**Error**: Face verification always returns "not matched"

**Solutions**:
1. Re-enroll face with better quality image
2. Adjust match threshold:
   ```javascript
   const threshold = 0.6; // Lower = more lenient
   if (distance < threshold) {
     // Match!
   }
   ```
3. Ensure same model version for enrollment and verification

---

## General Tips

### Debugging Checklist

1. ✅ Check all environment variables are set
2. ✅ Verify services are running
3. ✅ Check logs (backend and frontend)
4. ✅ Test API endpoints directly
5. ✅ Clear all caches
6. ✅ Restart services
7. ✅ Check browser console for errors

### Getting Help

1. Check logs first (most issues show here)
2. Search error message in GitHub issues
3. Verify environment variables match `.env.example`
4. Test locally before deploying
5. Use demo mode for payment testing

### Useful Commands

```bash
# Backend
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan optimize:clear

# Frontend
npm run build
rm -rf node_modules && npm install

# Database
php artisan migrate:fresh --seed
php artisan db:seed --force

# Logs
tail -f backend/storage/logs/laravel.log
```

---

**Still Having Issues?**

1. Check [DEPLOYMENT.md](DEPLOYMENT.md) for deployment guides
2. Review [QUICK_DEPLOY.md](QUICK_DEPLOY.md) for step-by-step instructions
3. Verify your setup matches the examples exactly

---

**Pro Tip**: Enable `APP_DEBUG=true` temporarily to see detailed error messages, but **always disable in production**!
