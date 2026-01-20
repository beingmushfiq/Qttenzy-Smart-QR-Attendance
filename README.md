# Qttenzy – Smart QR-Based Attendance & Verification System

A production-ready, enterprise-level attendance management system featuring QR code scanning, real-time face verification, GPS validation, and optional biometric authentication.

---

## 🎯 Features

- ✅ **QR Code Attendance**: Time-based rotating QR codes for secure session access
- ✅ **Face Recognition**: Real-time face verification using Face-API.js
- ✅ **GPS Validation**: Location-based attendance verification
- ✅ **WebAuthn Support**: Optional biometric authentication (fingerprint/face unlock)
- ✅ **Multi-Role System**: Admin, Session Manager, Student/Employee roles
- ✅ **Session Management**: Create and manage sessions with location requirements
- ✅ **Payment Integration**: SSLCommerz and Stripe support for paid sessions
- ✅ **Comprehensive Reports**: Daily, monthly, and session-wise attendance reports
- ✅ **Secure Authentication**: JWT-based stateless authentication
- ✅ **Modern UI**: React + Tailwind CSS responsive interface

---

## 🏗️ Architecture

### Technology Stack

**Frontend:**
- React 18 + Vite
- Tailwind CSS
- Zustand (State Management)
- Axios (API Client)
- ZXing (QR Scanner)
- Face-API.js (Face Recognition)
- React Router v6

**Backend:**
- Laravel 12
- MySQL 8.0
- JWT Authentication
- SimpleSoftwareIO QR Code
- Guzzle HTTP Client

**Infrastructure:**
- Nginx (Web Server)
- PHP-FPM 8.2
- Redis (Caching/Queue)
- Supervisor (Queue Workers)

---

## 📚 Documentation

- **[Architecture Guide](ARCHITECTURE.md)** - Complete system architecture and design patterns
- **[Database Schema](docs/DATABASE.md)** - Database structure and ER diagrams
- **[API Documentation](docs/API.md)** - Complete REST API reference
- **[Frontend Guide](docs/FRONTEND.md)** - React development guide and component structure
- **[Backend Guide](docs/BACKEND.md)** - Laravel development guide and code examples
- **[Security Guide](docs/SECURITY.md)** - Security requirements and best practices
- **[Deployment Guide](docs/DEPLOYMENT.md)** - Production deployment instructions

---

## 🚀 Quick Start

### Prerequisites

- Node.js 18+
- PHP 8.2+
- Composer
- MySQL 8.0+
- Git

> **Windows Users:** See [WINDOWS_SETUP.md](WINDOWS_SETUP.md) for Windows-specific instructions. Use `setup.ps1` or `setup.bat` instead of `setup.sh`.

### Quick Setup (Automated)

**Windows PowerShell:**
```powershell
.\scripts\setup.ps1
```

**Windows Command Prompt:**
```cmd
scripts\setup.bat
```

**Linux/Mac/Git Bash:**
```bash
chmod +x scripts/setup.sh
./scripts/setup.sh
```

### Manual Setup

**Frontend:**
```bash
cd frontend
npm install
cp .env.example .env  # Windows: copy .env.example .env
# Edit .env with your API URL
npm run dev
```

**Backend:**
```bash
cd backend
composer install
cp .env.example .env  # Windows: copy .env.example .env
php artisan key:generate
php artisan jwt:secret
php artisan migrate
php artisan db:seed
php artisan serve
```

---

## 📁 Project Structure

```
Qttenzy/
├── frontend/          # React + Vite application
├── backend/           # Laravel REST API
├── docs/              # Documentation
├── scripts/           # Setup & deployment scripts
└── README.md
```

---

## 🔐 Security Features

- JWT token-based authentication
- Role-based access control (RBAC)
- Multi-factor verification (QR + Face + GPS)
- Rate limiting on API endpoints
- SQL injection prevention
- XSS protection
- CSRF protection
- Secure password hashing
- Encrypted sensitive data storage

---

## 🎨 Key Components

### Attendance Flow
1. User scans QR code from session
2. Face verification against enrolled face
3. GPS location validation
4. Optional WebAuthn biometric check
5. Attendance recorded with all verification data

### Session Creation
1. Admin/Manager creates session
2. System generates unique QR code
3. QR code rotates every 5 minutes
4. Location and time window set
5. Session published for attendance

---

## 📊 Database Schema

10 main tables:

- `users` - User accounts and profiles
- `sessions` - Session/event information
- `qr_codes` - QR code management
- `attendances` - Attendance records
- `face_enrollments` - Face recognition data
- `location_logs` - GPS location tracking
- `payments` - Payment transactions
- `registrations` - Session registrations

See [Database Schema](docs/DATABASE.md) for complete details.

---

## 🔌 API Endpoints

### Authentication
- `POST /api/v1/auth/register` - User registration
- `POST /api/v1/auth/login` - User login
- `POST /api/v1/auth/logout` - User logout
- `POST /api/v1/auth/refresh` - Refresh token

### Sessions
- `GET /api/v1/sessions` - List sessions
- `GET /api/v1/sessions/{id}` - Get session details
- `POST /api/v1/sessions` - Create session (Admin/Manager)
- `GET /api/v1/sessions/{id}/qr` - Get QR code

### Attendance
- `POST /api/v1/attendance/verify` - Verify attendance
- `GET /api/v1/attendance/history` - Get attendance history
- `GET /api/v1/attendance/session/{id}` - Get session attendance

### Admin
- `GET /api/v1/admin/dashboard` - Dashboard statistics
- `GET /api/v1/admin/users` - User management
- `GET /api/v1/admin/reports/attendance` - Attendance reports

See [API Documentation](docs/API.md) for complete reference.

---

## 🛠️ Development

### Frontend Development
```bash
cd frontend
npm run dev          # Start dev server
npm run build        # Build for production
npm run preview      # Preview production build
```

### Backend Development
```bash
cd backend
php artisan serve              # Start dev server
php artisan migrate            # Run migrations
php artisan db:seed           # Seed database
php artisan queue:work         # Process queue
php artisan schedule:run       # Run scheduler
```

### Testing
```bash
# Backend tests
cd backend
php artisan test

# Frontend tests (if configured)
cd frontend
npm test
```

---

## 🚀 Deployment

### Frontend Deployment

- **Vercel**: `vercel --prod`
- **Netlify**: `netlify deploy --prod`
- **VPS**: Build and serve with Nginx

### Backend Deployment

- **Laravel Forge**: Automated deployment
- **cPanel**: Upload via FTP/SFTP
- **VPS**: Manual setup with Nginx + PHP-FPM

See [Deployment Guide](docs/DEPLOYMENT.md) for detailed instructions.

---

## 🔒 Environment Variables

### Frontend (.env)
```env
VITE_API_BASE_URL=http://localhost:8000/api/v1
VITE_APP_NAME=Qttenzy
VITE_FACE_API_MODELS_PATH=/models
```

### Backend (.env)
```env
APP_ENV=production
APP_DEBUG=false
DB_DATABASE=qttenzy
JWT_SECRET=your_secret_key
FRONTEND_URL=https://app.qttenzy.com
```

---

## 📝 License

This project is proprietary software. All rights reserved.

---

## 👥 Support

For support and inquiries:

- Email: support@qttenzy.com
- Documentation: See `/docs` directory
- Issues: Contact development team

---

## 🎯 Roadmap

- [ ] Mobile app (React Native)
- [ ] Real-time notifications (WebSocket)
- [ ] Advanced analytics dashboard
- [ ] Multi-language support
- [ ] Integration with external systems
- [ ] AI-powered anomaly detection

---

## 📄 Version History

- **v1.0.0** - Initial release
  - QR-based attendance
  - Face verification
  - GPS validation
  - Payment integration
  - Admin dashboard

---

## 🙏 Acknowledgments

Built with:

- Laravel Framework
- React Library
- Face-API.js
- ZXing Library
- Tailwind CSS

---

Built with ❤️ for Modern Attendance Management

