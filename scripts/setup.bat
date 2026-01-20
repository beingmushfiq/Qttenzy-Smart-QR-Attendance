@echo off
REM Qttenzy - Setup Script for Windows Command Prompt
REM This script sets up the development environment on Windows

echo.
echo 🚀 Qttenzy Setup Script (Windows CMD)
echo =====================================
echo.

REM Check prerequisites
echo Checking prerequisites...
where node >nul 2>&1
if %errorlevel% neq 0 (
    echo ❌ Node.js is required but not installed. Aborting.
    exit /b 1
)

where npm >nul 2>&1
if %errorlevel% neq 0 (
    echo ❌ npm is required but not installed. Aborting.
    exit /b 1
)

where php >nul 2>&1
if %errorlevel% neq 0 (
    echo ❌ PHP is required but not installed. Aborting.
    exit /b 1
)

where composer >nul 2>&1
if %errorlevel% neq 0 (
    echo ❌ Composer is required but not installed. Aborting.
    exit /b 1
)

echo ✓ Prerequisites check passed
echo.

REM Frontend Setup
echo Setting up Frontend...
if exist "frontend" (
    cd frontend
    echo Installing npm dependencies...
    call npm install
    
    if not exist ".env" (
        echo Creating .env file...
        copy .env.example .env >nul 2>&1
        echo ⚠️  Please edit frontend\.env with your API URL
    )
    cd ..
    echo ✓ Frontend setup complete
) else (
    echo ⚠️  Frontend directory not found. Skipping...
)
echo.

REM Backend Setup
echo Setting up Backend...
if exist "backend" (
    cd backend
    echo Installing Composer dependencies...
    call composer install
    
    if not exist ".env" (
        echo Creating .env file...
        copy .env.example .env >nul 2>&1
        echo ⚠️  Please edit backend\.env with your database credentials
    )
    
    echo Generating application key...
    php artisan key:generate >nul 2>&1
    if %errorlevel% neq 0 (
        echo ⚠️  Could not generate key. Make sure .env exists.
    )
    
    echo Generating JWT secret...
    php artisan jwt:secret >nul 2>&1
    if %errorlevel% neq 0 (
        echo ⚠️  JWT secret generation skipped. Run manually: php artisan jwt:secret
    )
    
    echo.
    echo ⚠️  Database setup required:
    echo   1. Create MySQL database
    echo   2. Update backend\.env with database credentials
    echo   3. Run: php artisan migrate
    echo   4. Run: php artisan db:seed
    
    cd ..
    echo ✓ Backend setup complete
) else (
    echo ⚠️  Backend directory not found. Skipping...
)
echo.

REM Create necessary directories
echo Creating directories...
if not exist "frontend\public\models" mkdir "frontend\public\models"
if not exist "backend\storage\logs" mkdir "backend\storage\logs"
if not exist "backend\storage\framework\cache" mkdir "backend\storage\framework\cache"
if not exist "backend\storage\framework\sessions" mkdir "backend\storage\framework\sessions"
if not exist "backend\storage\framework\views" mkdir "backend\storage\framework\views"
echo ✓ Directories created
echo.

echo ✅ Setup complete!
echo.
echo Next steps:
echo 1. Configure frontend\.env
echo 2. Configure backend\.env
echo 3. Create MySQL database
echo 4. Run: cd backend ^&^& php artisan migrate ^&^& php artisan db:seed
echo 5. Start backend: cd backend ^&^& php artisan serve
echo 6. Start frontend: cd frontend ^&^& npm run dev
echo.
echo Happy coding! 🎉
echo.

pause

