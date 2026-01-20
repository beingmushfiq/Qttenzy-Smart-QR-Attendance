# Qttenzy - Setup Script for Windows PowerShell
# This script sets up the development environment on Windows

Write-Host "🚀 Qttenzy Setup Script (Windows)" -ForegroundColor Green
Write-Host "=================================" -ForegroundColor Green

# Check prerequisites
Write-Host "`nChecking prerequisites..." -ForegroundColor Green

$nodeInstalled = Get-Command node -ErrorAction SilentlyContinue
$npmInstalled = Get-Command npm -ErrorAction SilentlyContinue
$phpInstalled = Get-Command php -ErrorAction SilentlyContinue
$composerInstalled = Get-Command composer -ErrorAction SilentlyContinue

if (-not $nodeInstalled) {
    Write-Host "❌ Node.js is required but not installed. Aborting." -ForegroundColor Red
    exit 1
}

if (-not $npmInstalled) {
    Write-Host "❌ npm is required but not installed. Aborting." -ForegroundColor Red
    exit 1
}

if (-not $phpInstalled) {
    Write-Host "❌ PHP is required but not installed. Aborting." -ForegroundColor Red
    exit 1
}

if (-not $composerInstalled) {
    Write-Host "❌ Composer is required but not installed. Aborting." -ForegroundColor Red
    exit 1
}

Write-Host "✓ Prerequisites check passed" -ForegroundColor Green

# Frontend Setup
Write-Host "`nSetting up Frontend..." -ForegroundColor Green
if (Test-Path "frontend") {
    Set-Location frontend
    Write-Host "Installing npm dependencies..."
    npm install
    
    if (-not (Test-Path ".env")) {
        Write-Host "Creating .env file..."
        Copy-Item .env.example .env -ErrorAction SilentlyContinue
        Write-Host "⚠️  Please edit frontend/.env with your API URL" -ForegroundColor Yellow
    }
    Set-Location ..
    Write-Host "✓ Frontend setup complete" -ForegroundColor Green
} else {
    Write-Host "⚠️  Frontend directory not found. Skipping..." -ForegroundColor Yellow
}

# Backend Setup
Write-Host "`nSetting up Backend..." -ForegroundColor Green
if (Test-Path "backend") {
    Set-Location backend
    Write-Host "Installing Composer dependencies..."
    composer install
    
    if (-not (Test-Path ".env")) {
        Write-Host "Creating .env file..."
        Copy-Item .env.example .env -ErrorAction SilentlyContinue
        Write-Host "⚠️  Please edit backend/.env with your database credentials" -ForegroundColor Yellow
    }
    
    Write-Host "Generating application key..."
    php artisan key:generate 2>$null
    if ($LASTEXITCODE -ne 0) {
        Write-Host "⚠️  Could not generate key. Make sure .env exists." -ForegroundColor Yellow
    }
    
    Write-Host "Generating JWT secret..."
    php artisan jwt:secret 2>$null
    if ($LASTEXITCODE -ne 0) {
        Write-Host "⚠️  JWT secret generation skipped. Run manually: php artisan jwt:secret" -ForegroundColor Yellow
    }
    
    Write-Host "`n⚠️  Database setup required:" -ForegroundColor Yellow
    Write-Host "  1. Create MySQL database"
    Write-Host "  2. Update backend/.env with database credentials"
    Write-Host "  3. Run: php artisan migrate"
    Write-Host "  4. Run: php artisan db:seed"
    
    Set-Location ..
    Write-Host "✓ Backend setup complete" -ForegroundColor Green
} else {
    Write-Host "⚠️  Backend directory not found. Skipping..." -ForegroundColor Yellow
}

# Create necessary directories
Write-Host "`nCreating directories..." -ForegroundColor Green
New-Item -ItemType Directory -Force -Path "frontend\public\models" | Out-Null
New-Item -ItemType Directory -Force -Path "backend\storage\logs" | Out-Null
New-Item -ItemType Directory -Force -Path "backend\storage\framework\cache" | Out-Null
New-Item -ItemType Directory -Force -Path "backend\storage\framework\sessions" | Out-Null
New-Item -ItemType Directory -Force -Path "backend\storage\framework\views" | Out-Null
Write-Host "✓ Directories created" -ForegroundColor Green

Write-Host "`n✅ Setup complete!" -ForegroundColor Green
Write-Host "`nNext steps:" -ForegroundColor Yellow
Write-Host "1. Configure frontend\.env"
Write-Host "2. Configure backend\.env"
Write-Host "3. Create MySQL database"
Write-Host "4. Run: cd backend; php artisan migrate; php artisan db:seed"
Write-Host "5. Start backend: cd backend; php artisan serve"
Write-Host "6. Start frontend: cd frontend; npm run dev"
Write-Host "`nHappy coding! 🎉" -ForegroundColor Green

