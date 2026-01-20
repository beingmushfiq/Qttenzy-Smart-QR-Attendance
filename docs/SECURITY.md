# Security Requirements & Best Practices
## Qttenzy - Security Implementation Guide

---

## 🔐 AUTHENTICATION & AUTHORIZATION

### JWT Protection

#### Token Configuration
```php
// config/jwt.php
return [
    'ttl' => env('JWT_TTL', 60), // 1 hour
    'refresh_ttl' => env('JWT_REFRESH_TTL', 20160), // 2 weeks
    'algo' => 'HS256',
    'secret' => env('JWT_SECRET'),
    'blacklist_enabled' => true,
    'blacklist_grace_period' => 60, // seconds
];
```

#### Token Security Best Practices
1. **Store tokens securely**: Use httpOnly cookies or secure localStorage
2. **Token rotation**: Implement refresh token mechanism
3. **Token blacklisting**: Invalidate tokens on logout
4. **Short expiration**: Set reasonable TTL (1 hour default)
5. **HTTPS only**: Enforce HTTPS in production

#### Implementation
```php
// Middleware to validate token on every request
Route::middleware(['jwt.auth'])->group(function () {
    // Protected routes
});

// Logout with token blacklist
public function logout()
{
    JWTAuth::invalidate(JWTAuth::getToken());
    return response()->json(['success' => true]);
}
```

---

## 🛡️ CORS CONFIGURATION

### Laravel CORS Setup (`config/cors.php`)
```php
return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
    'allowed_origins' => [
        env('FRONTEND_URL', 'http://localhost:5173'),
        env('APP_URL', 'http://localhost:8000')
    ],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With'],
    'exposed_headers' => [],
    'max_age' => 3600,
    'supports_credentials' => true,
];
```

### Frontend CORS Handling
```javascript
// services/api/client.js
const apiClient = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL,
  withCredentials: true, // For cookies
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  }
});
```

---

## ✅ INPUT VALIDATION

### Form Request Validation

#### LoginRequest (`app/Http/Requests/LoginRequest.php`)
```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8']
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Email is required',
            'email.email' => 'Invalid email format',
            'password.required' => 'Password is required',
            'password.min' => 'Password must be at least 8 characters'
        ];
    }
}
```

#### AttendanceRequest (`app/Http/Requests/AttendanceRequest.php`)
```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AttendanceRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'session_id' => ['required', 'integer', 'exists:sessions,id'],
            'qr_code' => ['required', 'string', 'max:255'],
            'face_descriptor' => ['required', 'array', 'size:128'],
            'face_descriptor.*' => ['required', 'numeric', 'between:-1,1'],
            'location' => ['required', 'array'],
            'location.lat' => ['required', 'numeric', 'between:-90,90'],
            'location.lng' => ['required', 'numeric', 'between:-180,180'],
            'location.accuracy' => ['nullable', 'numeric', 'min:0'],
            'webauthn_credential_id' => ['nullable', 'string']
        ];
    }
}
```

### Frontend Validation (Yup Schema)
```javascript
import * as yup from 'yup';

export const attendanceSchema = yup.object().shape({
  session_id: yup.number().required('Session is required'),
  qr_code: yup.string().required('QR code is required'),
  face_descriptor: yup
    .array()
    .of(yup.number().min(-1).max(1))
    .length(128, 'Face descriptor must have 128 values')
    .required(),
  location: yup.object().shape({
    lat: yup.number().min(-90).max(90).required(),
    lng: yup.number().min(-180).max(180).required(),
    accuracy: yup.number().min(0)
  }).required()
});
```

---

## 👥 ROLE-BASED PERMISSIONS

### Role Definitions
- **admin**: Full system access
- **session_manager**: Create/manage sessions, view reports
- **student/employee**: Basic user access, mark attendance

### Permission Middleware
```php
// app/Http/Middleware/RoleMiddleware.php
public function handle($request, Closure $next, ...$roles)
{
    $user = auth()->user();
    
    if (!$user || !in_array($user->role, $roles)) {
        return response()->json([
            'success' => false,
            'message' => 'Unauthorized access'
        ], 403);
    }

    return $next($request);
}
```

### Route Protection
```php
// Admin only routes
Route::middleware(['jwt.auth', 'role:admin'])->group(function () {
    Route::delete('/sessions/{id}', [SessionController::class, 'destroy']);
    Route::get('/admin/dashboard', [AdminController::class, 'dashboard']);
});

// Manager and Admin routes
Route::middleware(['jwt.auth', 'role:admin,session_manager'])->group(function () {
    Route::post('/sessions', [SessionController::class, 'store']);
    Route::get('/sessions/{id}/qr', [SessionController::class, 'getQR']);
});
```

### Frontend Role Protection
```javascript
// middleware/RoleRoute.jsx
const RoleRoute = ({ children, allowedRoles = [] }) => {
  const { user, isAuthenticated } = useAuthStore();
  
  if (!isAuthenticated) {
    return <Navigate to="/login" />;
  }
  
  if (allowedRoles.length > 0 && !allowedRoles.includes(user.role)) {
    return <Navigate to="/dashboard" />;
  }
  
  return children;
};

// Usage
<Route
  path="/admin"
  element={
    <RoleRoute allowedRoles={['admin']}>
      <AdminDashboard />
    </RoleRoute>
  }
/>
```

---

## 🚫 ANTI-PROXY ATTENDANCE RULES

### Multi-Factor Verification
1. **QR Code**: Time-based, rotating codes
2. **Face Recognition**: Real-time face matching
3. **GPS Validation**: Location-based verification
4. **WebAuthn**: Optional biometric authentication
5. **IP Tracking**: Log IP addresses for audit
6. **Device Fingerprinting**: Track device information

### Implementation

#### QR Code Rotation
```php
// Rotate QR codes every 5 minutes
public function rotateQR(int $sessionId): void
{
    QRCode::where('session_id', $sessionId)
        ->where('is_active', true)
        ->update(['is_active' => false]);
    
    $this->generateQR($sessionId);
}

// Scheduled task (app/Console/Kernel.php)
protected function schedule(Schedule $schedule)
{
    $schedule->call(function () {
        $sessions = Session::where('status', 'active')
            ->where('end_time', '>', now())
            ->get();
        
        foreach ($sessions as $session) {
            app(QRService::class)->rotateQR($session->id);
        }
    })->everyFiveMinutes();
}
```

#### Duplicate Prevention
```php
// Check for duplicate attendance
public function checkDuplicate(int $userId, int $sessionId): ?Attendance
{
    return Attendance::where('user_id', $userId)
        ->where('session_id', $sessionId)
        ->where('status', 'verified')
        ->first();
}
```

#### Location Validation
```php
// Strict location checking
public function validateLocation(
    float $userLat,
    float $userLng,
    float $venueLat,
    float $venueLng,
    int $radiusMeters
): array {
    $distance = LocationHelper::calculateDistance(
        $userLat, $userLng, $venueLat, $venueLng
    );

    // Reject if outside radius
    if ($distance > $radiusMeters) {
        return [
            'valid' => false,
            'distance' => $distance,
            'allowed_radius' => $radiusMeters
        ];
    }

    return ['valid' => true, 'distance' => $distance];
}
```

#### Face Match Threshold
```php
// Strict face matching (70% threshold)
public function verifyFace(int $userId, array $currentDescriptor): array
{
    $enrollment = FaceEnrollment::where('user_id', $userId)
        ->where('enrollment_status', 'approved')
        ->latest()
        ->first();

    $enrolledDescriptor = json_decode($enrollment->face_descriptor, true);
    $result = FaceHelper::compareDescriptors(
        $enrolledDescriptor,
        $currentDescriptor
    );

    // Require 70% match score
    $match = $result['score'] >= 70.0;

    return [
        'match' => $match,
        'score' => $result['score'],
        'distance' => $result['distance']
    ];
}
```

#### IP and Device Tracking
```php
// Log IP and device info
$attendance = Attendance::create([
    // ... other fields
    'ip_address' => $request->ip(),
    'device_info' => json_encode([
        'user_agent' => $request->userAgent(),
        'platform' => $request->header('User-Agent'),
        'accept_language' => $request->header('Accept-Language')
    ])
]);
```

---

## 🔒 DATA PROTECTION

### Password Hashing
```php
// Laravel automatically uses bcrypt
$user->password = Hash::make($password);
// or
$user->password = bcrypt($password);
```

### Sensitive Data Encryption
```php
// Encrypt face descriptors
use Illuminate\Support\Facades\Crypt;

// Store
$faceEnrollment->face_descriptor = Crypt::encrypt(json_encode($descriptor));

// Retrieve
$descriptor = json_decode(Crypt::decrypt($faceEnrollment->face_descriptor));
```

### Payment Data Security
```php
// Never store full card details
// Only store transaction IDs and gateway responses (encrypted)
$payment->gateway_response = Crypt::encrypt(json_encode($gatewayData));
```

---

## 🛡️ RATE LIMITING

### API Rate Limiting
```php
// routes/api.php
Route::middleware(['throttle:60,1'])->group(function () {
    // Public routes: 60 requests per minute
});

Route::middleware(['jwt.auth', 'throttle:120,1'])->group(function () {
    // Authenticated routes: 120 requests per minute
});

Route::middleware(['jwt.auth', 'role:admin', 'throttle:200,1'])->group(function () {
    // Admin routes: 200 requests per minute
});
```

### Custom Rate Limiting Middleware
```php
// app/Http/Middleware/RateLimitMiddleware.php
public function handle($request, Closure $next)
{
    $key = 'attendance_verify_' . auth()->id();
    
    if (RateLimiter::tooManyAttempts($key, 5)) {
        return response()->json([
            'success' => false,
            'message' => 'Too many attendance attempts. Please try again later.'
        ], 429);
    }
    
    RateLimiter::hit($key, 60); // 5 attempts per minute
    
    return $next($request);
}
```

---

## 🔍 SECURITY HEADERS

### Laravel Security Headers Middleware
```php
// app/Http/Middleware/SecurityHeaders.php
public function handle($request, Closure $next)
{
    $response = $next($request);
    
    $response->headers->set('X-Content-Type-Options', 'nosniff');
    $response->headers->set('X-Frame-Options', 'DENY');
    $response->headers->set('X-XSS-Protection', '1; mode=block');
    $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
    $response->headers->set('Content-Security-Policy', "default-src 'self'");
    
    return $response;
}
```

---

## 🚨 SQL INJECTION PREVENTION

### Use Eloquent ORM (Parameterized Queries)
```php
// ✅ Safe - Eloquent handles parameterization
$user = User::where('email', $email)->first();

// ✅ Safe - Query Builder with bindings
DB::table('users')->where('email', '=', $email)->first();

// ❌ Never do this
DB::select("SELECT * FROM users WHERE email = '$email'");
```

---

## 🔐 XSS PROTECTION

### Output Escaping
```php
// Blade templates automatically escape
{{ $user->name }} // Safe

// For raw HTML (use with caution)
{!! $htmlContent !!} // Only if trusted
```

### Frontend XSS Prevention
```javascript
// Use React's built-in escaping
<div>{user.name}</div> // Safe

// For HTML content, use dangerouslySetInnerHTML only if sanitized
import DOMPurify from 'dompurify';
<div dangerouslySetInnerHTML={{ __html: DOMPurify.sanitize(html) }} />
```

---

## 🔑 ENVIRONMENT SECURITY

### .env Security
```env
# Never commit .env to version control
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:generated_key_here

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=qttenzy
DB_USERNAME=secure_user
DB_PASSWORD=strong_password_here

# JWT
JWT_SECRET=strong_random_secret_here
JWT_TTL=60

# Payment Gateways
SSLCOMMERZ_STORE_ID=your_store_id
SSLCOMMERZ_STORE_PASSWORD=encrypted_password
STRIPE_KEY=sk_live_key_here
STRIPE_SECRET=sk_live_secret_here

# CORS
FRONTEND_URL=https://app.qttenzy.com
```

### File Permissions
```bash
# Secure file permissions
chmod 600 .env
chmod -R 755 storage
chmod -R 755 bootstrap/cache
```

---

## 🛡️ CSRF PROTECTION

### Laravel CSRF (for web routes)
```php
// web.php routes automatically protected
Route::post('/form', [Controller::class, 'handle']);
// CSRF token required in forms
```

### API CSRF (if using cookies)
```php
// Sanctum for SPA authentication
Route::middleware(['sanctum'])->group(function () {
    // Protected API routes
});
```

---

## 📊 AUDIT LOGGING

### Activity Logging
```php
// Log important actions
use Illuminate\Support\Facades\Log;

Log::info('Attendance verified', [
    'user_id' => $user->id,
    'session_id' => $sessionId,
    'ip_address' => $request->ip(),
    'timestamp' => now()
]);

// Store in database
ActivityLog::create([
    'user_id' => $user->id,
    'action' => 'attendance_verified',
    'description' => 'User verified attendance',
    'ip_address' => $request->ip(),
    'user_agent' => $request->userAgent()
]);
```

---

## 🔐 SECURE FILE UPLOADS

### File Upload Validation
```php
public function rules(): array
{
    return [
        'avatar' => [
            'nullable',
            'image',
            'mimes:jpeg,png,jpg',
            'max:2048', // 2MB
            'dimensions:min_width=100,min_height=100'
        ]
    ];
}
```

### Secure Storage
```php
// Store in private storage
$path = $request->file('avatar')->store('avatars', 'private');

// Serve with authentication
Route::get('/storage/{path}', [FileController::class, 'serve'])
    ->middleware('auth')
    ->where('path', '.*');
```

---

## 🚨 SECURITY CHECKLIST

### Pre-Deployment Checklist
- [ ] All environment variables secured
- [ ] Debug mode disabled in production
- [ ] HTTPS enforced
- [ ] CORS properly configured
- [ ] Rate limiting enabled
- [ ] SQL injection prevention verified
- [ ] XSS protection enabled
- [ ] CSRF protection active
- [ ] Password hashing verified
- [ ] Sensitive data encrypted
- [ ] File uploads validated
- [ ] Error messages don't expose sensitive info
- [ ] Security headers configured
- [ ] JWT tokens properly secured
- [ ] Database backups configured
- [ ] Audit logging enabled

---

## 🔍 SECURITY TESTING

### Penetration Testing Checklist
1. **Authentication**: Test JWT token manipulation
2. **Authorization**: Test role bypass attempts
3. **Input Validation**: Test SQL injection, XSS
4. **Rate Limiting**: Test DoS attempts
5. **Session Management**: Test token hijacking
6. **File Uploads**: Test malicious file uploads
7. **API Endpoints**: Test unauthorized access
8. **Data Exposure**: Check for sensitive data leaks

---

## 📝 INCIDENT RESPONSE

### Security Incident Procedure
1. **Identify**: Detect security breach
2. **Contain**: Isolate affected systems
3. **Eradicate**: Remove threat
4. **Recover**: Restore services
5. **Document**: Log incident details
6. **Review**: Post-incident analysis

### Emergency Contacts
- Security Team: security@qttenzy.com
- System Admin: admin@qttenzy.com
- Emergency Hotline: +880-XXX-XXXX

