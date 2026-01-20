# Qttenzy Backend
## Laravel 12 REST API

---

## 🚀 Quick Start

```bash
# Install dependencies
composer install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Generate JWT secret
php artisan jwt:secret

# Configure database in .env
# DB_DATABASE=qttenzy
# DB_USERNAME=root
# DB_PASSWORD=your_password

# Run migrations
php artisan migrate

# Seed database
php artisan db:seed

# Start development server
php artisan serve
```

The API will be available at `http://localhost:8000`

---

## 📁 Project Structure

```
backend/
├── app/
│   ├── Http/
│   │   ├── Controllers/    # API controllers
│   │   ├── Middleware/      # Custom middleware
│   │   └── Requests/        # Form request validation
│   ├── Models/              # Eloquent models
│   ├── Services/            # Business logic
│   ├── Repositories/        # Data access layer
│   └── Helpers/            # Helper functions
├── config/                  # Configuration files
├── database/
│   ├── migrations/         # Database migrations
│   └── seeders/           # Database seeders
├── routes/
│   └── api.php            # API routes
└── composer.json
```

---

## 🛠️ Available Commands

- `php artisan serve` - Start development server
- `php artisan migrate` - Run database migrations
- `php artisan db:seed` - Seed database
- `php artisan queue:work` - Process queue jobs
- `php artisan tinker` - Laravel REPL
- `php artisan route:list` - List all routes

---

## 📦 Key Dependencies

- **Laravel 12** - PHP framework
- **tymon/jwt-auth** - JWT authentication
- **simplesoftwareio/simple-qrcode** - QR code generation
- **guzzlehttp/guzzle** - HTTP client
- **maatwebsite/excel** - Excel export
- **spatie/laravel-permission** - Role permissions

---

## 🔧 Configuration

### Environment Variables

Edit `backend/.env`:

```env
APP_ENV=local
APP_DEBUG=true
DB_DATABASE=qttenzy
DB_USERNAME=root
DB_PASSWORD=your_password
JWT_SECRET=your_jwt_secret
FRONTEND_URL=http://localhost:5173
```

### JWT Configuration

After running `php artisan jwt:secret`, configure in `config/jwt.php`:

```php
'ttl' => env('JWT_TTL', 60), // 1 hour
'refresh_ttl' => env('JWT_REFRESH_TTL', 20160), // 2 weeks
```

---

## 🔌 API Endpoints

### Authentication
- `POST /api/v1/auth/register` - Register user
- `POST /api/v1/auth/login` - Login user
- `POST /api/v1/auth/logout` - Logout user
- `POST /api/v1/auth/refresh` - Refresh token
- `GET /api/v1/auth/me` - Get current user

### Sessions
- `GET /api/v1/sessions` - List sessions
- `GET /api/v1/sessions/{id}` - Get session
- `POST /api/v1/sessions` - Create session (Admin/Manager)
- `PUT /api/v1/sessions/{id}` - Update session (Admin/Manager)
- `DELETE /api/v1/sessions/{id}` - Delete session (Admin)

### Attendance
- `POST /api/v1/attendance/verify` - Verify attendance
- `GET /api/v1/attendance/history` - Get attendance history
- `GET /api/v1/attendance/session/{id}` - Get session attendance

See [docs/API.md](../docs/API.md) for complete API documentation.

---

## 🗄️ Database

### Run Migrations
```bash
php artisan migrate
```

### Seed Database
```bash
php artisan db:seed
```

### Reset Database
```bash
php artisan migrate:fresh --seed
```

---

## 🔒 Authentication

All protected endpoints require JWT token:

```
Authorization: Bearer {token}
```

Token expires in 1 hour (configurable). Use `/auth/refresh` to get a new token.

---

## 📚 Documentation

See [docs/BACKEND.md](../docs/BACKEND.md) for complete backend development guide.

---

## 🐛 Troubleshooting

### JWT secret not set
```bash
php artisan jwt:secret
```

### Database connection error
- Check MySQL is running
- Verify credentials in `.env`
- Test connection: `mysql -u root -p`

### Permission errors (Linux/Mac)
```bash
chmod -R 775 storage bootstrap/cache
```

### Route not found
- Clear route cache: `php artisan route:clear`
- Cache routes: `php artisan route:cache`

---

## 🚀 Deployment

### Production Build
```bash
composer install --no-dev --optimize-autoloader
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Queue Workers
Use Supervisor to run queue workers:
```bash
php artisan queue:work
```

See [docs/DEPLOYMENT.md](../docs/DEPLOYMENT.md) for detailed deployment instructions.

---

## 🧪 Testing

```bash
# Run tests
php artisan test

# Run specific test
php artisan test --filter AttendanceTest
```

---

## 📝 Code Style

Laravel uses PSR-12 coding standards. Use Laravel Pint:

```bash
composer pint
```

