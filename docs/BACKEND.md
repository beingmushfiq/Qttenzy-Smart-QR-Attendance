# Backend Development Guide
## Laravel 12 REST API Structure

---

## 🚀 SETUP & INSTALLATION

### Prerequisites
- PHP 8.2+
- Composer
- MySQL 8.0+
- Node.js (for asset compilation)

### Initial Setup
```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan jwt:secret
php artisan migrate
php artisan db:seed
php artisan serve
```

### Required Dependencies (`composer.json`)
```json
{
  "require": {
    "php": "^8.2",
    "laravel/framework": "^12.0",
    "tymon/jwt-auth": "^2.0",
    "simplesoftwareio/simple-qrcode": "^4.2",
    "guzzlehttp/guzzle": "^7.8",
    "spatie/laravel-permission": "^6.0",
    "maatwebsite/excel": "^3.1",
    "barryvdh/laravel-dompdf": "^2.0"
  }
}
```

---

## 📁 LARAVEL STRUCTURE

### Controllers Structure

#### AuthController (`app/Http/Controllers/Auth/AuthController.php`)
```php
<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();
        $result = $this->authService->login($credentials);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message']
            ], 401);
        }

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => $result['data']
        ]);
    }

    public function logout(): JsonResponse
    {
        auth()->logout();
        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully'
        ]);
    }

    public function refresh(): JsonResponse
    {
        $token = auth()->refresh();
        return response()->json([
            'success' => true,
            'data' => [
                'token' => $token,
                'expires_in' => auth()->factory()->getTTL() * 60
            ]
        ]);
    }

    public function me(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => new UserResource(auth()->user())
        ]);
    }
}
```

#### AttendanceController (`app/Http/Controllers/AttendanceController.php`)
```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\AttendanceRequest;
use App\Services\AttendanceService;
use App\Services\QRService;
use App\Services\FaceVerificationService;
use App\Services\LocationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    protected $attendanceService;
    protected $qrService;
    protected $faceService;
    protected $locationService;

    public function __construct(
        AttendanceService $attendanceService,
        QRService $qrService,
        FaceVerificationService $faceService,
        LocationService $locationService
    ) {
        $this->attendanceService = $attendanceService;
        $this->qrService = $qrService;
        $this->faceService = $faceService;
        $this->locationService = $locationService;
    }

    public function verify(AttendanceRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $user = auth()->user();
            $data = $request->validated();

            // 1. Validate QR Code
            $qrValidation = $this->qrService->validateQR(
                $data['qr_code'],
                $data['session_id']
            );

            if (!$qrValidation['valid']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or expired QR code'
                ], 400);
            }

            // 2. Check for duplicate attendance
            $existingAttendance = $this->attendanceService->checkDuplicate(
                $user->id,
                $data['session_id']
            );

            if ($existingAttendance) {
                return response()->json([
                    'success' => false,
                    'message' => 'Attendance already recorded for this session'
                ], 409);
            }

            // 3. Face Verification
            $faceResult = $this->faceService->verifyFace(
                $user->id,
                $data['face_descriptor']
            );

            if (!$faceResult['match']) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Face verification failed',
                    'data' => [
                        'face_match_score' => $faceResult['score'],
                        'threshold' => 70.0
                    ]
                ], 400);
            }

            // 4. GPS Validation
            $locationResult = $this->locationService->validateLocation(
                $data['location']['lat'],
                $data['location']['lng'],
                $qrValidation['session']->location_lat,
                $qrValidation['session']->location_lng,
                $qrValidation['session']->radius_meters
            );

            // 5. Create Attendance Record
            $attendance = $this->attendanceService->create([
                'user_id' => $user->id,
                'session_id' => $data['session_id'],
                'qr_code_id' => $qrValidation['qr_code_id'],
                'verified_at' => now(),
                'face_match_score' => $faceResult['score'],
                'face_match' => true,
                'gps_valid' => $locationResult['valid'],
                'location_lat' => $data['location']['lat'],
                'location_lng' => $data['location']['lng'],
                'distance_from_venue' => $locationResult['distance'],
                'ip_address' => $request->ip(),
                'device_info' => json_encode($request->userAgent()),
                'webauthn_used' => isset($data['webauthn_credential_id']),
                'verification_method' => $this->determineMethod($data),
                'status' => 'verified'
            ]);

            // 6. Log Location
            $this->locationService->logLocation(
                $user->id,
                $data['session_id'],
                $data['location']
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Attendance verified successfully',
                'data' => [
                    'attendance_id' => $attendance->id,
                    'verified_at' => $attendance->verified_at,
                    'verification_method' => $attendance->verification_method,
                    'face_match_score' => $attendance->face_match_score,
                    'gps_valid' => $attendance->gps_valid,
                    'distance_from_venue' => $attendance->distance_from_venue
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Attendance verification failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function determineMethod(array $data): string
    {
        $methods = ['qr_only'];
        if (isset($data['face_descriptor'])) $methods[] = 'face';
        if (isset($data['location'])) $methods[] = 'gps';
        if (isset($data['webauthn_credential_id'])) $methods[] = 'webauthn';
        
        return implode('_', $methods);
    }

    public function history(): JsonResponse
    {
        $user = auth()->user();
        $attendances = $this->attendanceService->getUserHistory(
            $user->id,
            request()->all()
        );

        return response()->json([
            'success' => true,
            'data' => AttendanceResource::collection($attendances)
        ]);
    }
}
```

---

## 🔧 SERVICES

### QRService (`app/Services/QRService.php`)
```php
<?php

namespace App\Services;

use App\Models\QRCode;
use App\Models\Session;
use SimpleSoftwareIO\QrCode\Facades\QrCode as QR;
use Carbon\Carbon;

class QRService
{
    public function generateQR(int $sessionId): array
    {
        $session = Session::findOrFail($sessionId);
        
        // Generate unique code
        $code = $this->generateUniqueCode($sessionId);
        
        // Set expiration (5 minutes default, or session end time)
        $expiresAt = min(
            now()->addMinutes(5),
            Carbon::parse($session->end_time)
        );

        $qrCode = QRCode::create([
            'session_id' => $sessionId,
            'code' => $code,
            'expires_at' => $expiresAt,
            'is_active' => true,
            'rotation_interval' => 300 // 5 minutes
        ]);

        // Generate QR image
        $qrImage = QR::format('png')
            ->size(300)
            ->generate($code);

        return [
            'qr_code' => $code,
            'qr_image' => 'data:image/png;base64,' . base64_encode($qrImage),
            'expires_at' => $expiresAt,
            'session_id' => $sessionId
        ];
    }

    public function validateQR(string $code, int $sessionId): array
    {
        $qrCode = QRCode::where('code', $code)
            ->where('session_id', $sessionId)
            ->where('is_active', true)
            ->where('expires_at', '>', now())
            ->with('session')
            ->first();

        if (!$qrCode) {
            return ['valid' => false, 'message' => 'Invalid or expired QR code'];
        }

        return [
            'valid' => true,
            'qr_code_id' => $qrCode->id,
            'session' => $qrCode->session
        ];
    }

    private function generateUniqueCode(int $sessionId): string
    {
        return 'SESSION_' . $sessionId . '_' . time() . '_' . bin2hex(random_bytes(4));
    }

    public function rotateQR(int $sessionId): void
    {
        // Deactivate old QR codes
        QRCode::where('session_id', $sessionId)
            ->where('is_active', true)
            ->update(['is_active' => false]);

        // Generate new QR code
        $this->generateQR($sessionId);
    }
}
```

### AttendanceService (`app/Services/AttendanceService.php`)
```php
<?php

namespace App\Services;

use App\Models\Attendance;
use App\Repositories\AttendanceRepository;
use Illuminate\Database\Eloquent\Collection;

class AttendanceService
{
    protected $repository;

    public function __construct(AttendanceRepository $repository)
    {
        $this->repository = $repository;
    }

    public function create(array $data): Attendance
    {
        return $this->repository->create($data);
    }

    public function checkDuplicate(int $userId, int $sessionId): ?Attendance
    {
        return $this->repository->findByUserAndSession($userId, $sessionId);
    }

    public function getUserHistory(int $userId, array $filters = []): Collection
    {
        return $this->repository->getUserHistory($userId, $filters);
    }

    public function getSessionAttendance(int $sessionId, array $filters = []): Collection
    {
        return $this->repository->getSessionAttendance($sessionId, $filters);
    }
}
```

### FaceVerificationService (`app/Services/FaceVerificationService.php`)
```php
<?php

namespace App\Services;

use App\Models\FaceEnrollment;
use App\Helpers\FaceHelper;

class FaceVerificationService
{
    public function verifyFace(int $userId, array $currentDescriptor): array
    {
        $enrollment = FaceEnrollment::where('user_id', $userId)
            ->where('enrollment_status', 'approved')
            ->latest()
            ->first();

        if (!$enrollment) {
            return [
                'match' => false,
                'score' => 0,
                'message' => 'No face enrollment found'
            ];
        }

        $enrolledDescriptor = json_decode($enrollment->face_descriptor, true);
        $result = FaceHelper::compareDescriptors(
            $enrolledDescriptor,
            $currentDescriptor
        );

        return [
            'match' => $result['distance'] < 0.6, // Threshold
            'score' => (1 - $result['distance']) * 100,
            'distance' => $result['distance']
        ];
    }

    public function enrollFace(int $userId, array $descriptor, ?string $imageUrl = null): FaceEnrollment
    {
        return FaceEnrollment::create([
            'user_id' => $userId,
            'face_descriptor' => json_encode($descriptor),
            'image_url' => $imageUrl,
            'enrollment_status' => 'pending'
        ]);
    }
}
```

### LocationService (`app/Services/LocationService.php`)
```php
<?php

namespace App\Services;

use App\Models\LocationLog;
use App\Helpers\LocationHelper;

class LocationService
{
    public function validateLocation(
        float $userLat,
        float $userLng,
        float $venueLat,
        float $venueLng,
        int $radiusMeters
    ): array {
        $distance = LocationHelper::calculateDistance(
            $userLat,
            $userLng,
            $venueLat,
            $venueLng
        );

        return [
            'valid' => $distance <= $radiusMeters,
            'distance' => $distance,
            'allowed_radius' => $radiusMeters
        ];
    }

    public function logLocation(int $userId, ?int $sessionId, array $location): LocationLog
    {
        return LocationLog::create([
            'user_id' => $userId,
            'session_id' => $sessionId,
            'latitude' => $location['lat'],
            'longitude' => $location['lng'],
            'accuracy' => $location['accuracy'] ?? null,
            'altitude' => $location['altitude'] ?? null,
            'heading' => $location['heading'] ?? null,
            'speed' => $location['speed'] ?? null,
            'timestamp' => now()
        ]);
    }
}
```

### PaymentService (`app/Services/PaymentService.php`)
```php
<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Session;
use Illuminate\Support\Facades\Http;

class PaymentService
{
    public function initiatePayment(int $userId, int $sessionId, string $gateway): array
    {
        $session = Session::findOrFail($sessionId);
        
        if (!$session->requires_payment) {
            throw new \Exception('Session does not require payment');
        }

        $payment = Payment::create([
            'user_id' => $userId,
            'session_id' => $sessionId,
            'amount' => $session->payment_amount,
            'currency' => 'BDT',
            'status' => 'pending',
            'gateway' => $gateway
        ]);

        if ($gateway === 'sslcommerz') {
            return $this->initiateSSLCommerz($payment);
        } elseif ($gateway === 'stripe') {
            return $this->initiateStripe($payment);
        }

        throw new \Exception('Invalid payment gateway');
    }

    private function initiateSSLCommerz(Payment $payment): array
    {
        $session = $payment->session;
        
        $response = Http::asForm()->post('https://sandbox.sslcommerz.com/gwprocess/v4/api.php', [
            'store_id' => config('payment.sslcommerz.store_id'),
            'store_passwd' => config('payment.sslcommerz.store_password'),
            'total_amount' => $payment->amount,
            'currency' => $payment->currency,
            'tran_id' => 'TXN' . $payment->id,
            'success_url' => config('app.url') . '/api/v1/payment/callback/sslcommerz',
            'fail_url' => config('app.url') . '/api/v1/payment/callback/sslcommerz',
            'cancel_url' => config('app.url') . '/api/v1/payment/callback/sslcommerz',
            'cus_name' => $payment->user->name,
            'cus_email' => $payment->user->email,
            'cus_phone' => $payment->user->phone,
            'product_name' => $session->title,
            'product_category' => 'Session Registration'
        ]);

        $payment->update([
            'transaction_id' => 'TXN' . $payment->id,
            'gateway_response' => json_encode($response->json())
        ]);

        return [
            'payment_id' => $payment->id,
            'amount' => $payment->amount,
            'currency' => $payment->currency,
            'gateway' => 'sslcommerz',
            'payment_url' => $response->json()['GatewayPageURL'] ?? null,
            'transaction_id' => $payment->transaction_id
        ];
    }

    private function initiateStripe(Payment $payment): array
    {
        // Stripe implementation
        // Similar structure to SSLCommerz
        return [];
    }

    public function handleWebhook(string $gateway, array $data): void
    {
        if ($gateway === 'sslcommerz') {
            $this->handleSSLCommerzWebhook($data);
        } elseif ($gateway === 'stripe') {
            $this->handleStripeWebhook($data);
        }
    }

    private function handleSSLCommerzWebhook(array $data): void
    {
        $payment = Payment::where('transaction_id', $data['tran_id'])->first();
        
        if (!$payment) {
            return;
        }

        if ($data['status'] === 'VALID') {
            $payment->update([
                'status' => 'completed',
                'paid_at' => now(),
                'gateway_response' => json_encode($data)
            ]);

            // Create registration
            $payment->user->registrations()->create([
                'session_id' => $payment->session_id,
                'payment_id' => $payment->id,
                'status' => 'confirmed',
                'registered_at' => now()
            ]);
        } else {
            $payment->update([
                'status' => 'failed',
                'gateway_response' => json_encode($data)
            ]);
        }
    }
}
```

---

## 🛡️ MIDDLEWARE

### JwtAuth Middleware (`app/Http/Middleware/JwtAuth.php`)
```php
<?php

namespace App\Http\Middleware;

use Closure;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class JwtAuth
{
    public function handle($request, Closure $next)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }
        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token invalid or expired'
            ], 401);
        }

        return $next($request);
    }
}
```

### RoleMiddleware (`app/Http/Middleware/RoleMiddleware.php`)
```php
<?php

namespace App\Http\Middleware;

use Closure;

class RoleMiddleware
{
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
}
```

---

## 📝 MODELS

### Attendance Model (`app/Models/Attendance.php`)
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    protected $fillable = [
        'user_id',
        'session_id',
        'qr_code_id',
        'verified_at',
        'face_match_score',
        'face_match',
        'gps_valid',
        'location_lat',
        'location_lng',
        'distance_from_venue',
        'ip_address',
        'device_info',
        'webauthn_used',
        'verification_method',
        'status',
        'rejection_reason'
    ];

    protected $casts = [
        'verified_at' => 'datetime',
        'face_match_score' => 'decimal:2',
        'face_match' => 'boolean',
        'gps_valid' => 'boolean',
        'webauthn_used' => 'boolean',
        'device_info' => 'array'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class);
    }

    public function qrCode(): BelongsTo
    {
        return $this->belongsTo(QRCode::class);
    }
}
```

---

## 🗄️ REPOSITORIES

### AttendanceRepository (`app/Repositories/AttendanceRepository.php`)
```php
<?php

namespace App\Repositories;

use App\Models\Attendance;
use Illuminate\Database\Eloquent\Collection;

class AttendanceRepository
{
    public function create(array $data): Attendance
    {
        return Attendance::create($data);
    }

    public function findByUserAndSession(int $userId, int $sessionId): ?Attendance
    {
        return Attendance::where('user_id', $userId)
            ->where('session_id', $sessionId)
            ->first();
    }

    public function getUserHistory(int $userId, array $filters = []): Collection
    {
        $query = Attendance::where('user_id', $userId)
            ->with(['session', 'qrCode'])
            ->orderBy('verified_at', 'desc');

        if (isset($filters['session_id'])) {
            $query->where('session_id', $filters['session_id']);
        }

        if (isset($filters['start_date'])) {
            $query->whereDate('verified_at', '>=', $filters['start_date']);
        }

        if (isset($filters['end_date'])) {
            $query->whereDate('verified_at', '<=', $filters['end_date']);
        }

        return $query->get();
    }

    public function getSessionAttendance(int $sessionId, array $filters = []): Collection
    {
        $query = Attendance::where('session_id', $sessionId)
            ->with(['user', 'qrCode'])
            ->orderBy('verified_at', 'desc');

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->get();
    }
}
```

---

## 🛣️ ROUTES (`routes/api.php`)
```php
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\AdminController;

Route::prefix('v1')->group(function () {
    
    // Public Routes
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/auth/forgot-password', [PasswordResetController::class, 'forgot']);
    Route::post('/auth/reset-password', [PasswordResetController::class, 'reset']);

    // Protected Routes
    Route::middleware(['jwt.auth'])->group(function () {
        
        // Auth
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::post('/auth/refresh', [AuthController::class, 'refresh']);
        Route::get('/auth/me', [AuthController::class, 'me']);

        // User
        Route::get('/user/profile', [UserController::class, 'profile']);
        Route::put('/user/profile', [UserController::class, 'updateProfile']);
        Route::post('/user/face-enroll', [UserController::class, 'enrollFace']);
        Route::post('/user/webauthn/register', [UserController::class, 'registerWebAuthn']);

        // Sessions
        Route::get('/sessions', [SessionController::class, 'index']);
        Route::get('/sessions/{id}', [SessionController::class, 'show']);
        Route::post('/sessions', [SessionController::class, 'store'])
            ->middleware('role:admin,session_manager');
        Route::put('/sessions/{id}', [SessionController::class, 'update'])
            ->middleware('role:admin,session_manager');
        Route::delete('/sessions/{id}', [SessionController::class, 'destroy'])
            ->middleware('role:admin');
        Route::get('/sessions/{id}/qr', [SessionController::class, 'getQR'])
            ->middleware('role:admin,session_manager');

        // Attendance
        Route::post('/attendance/verify', [AttendanceController::class, 'verify']);
        Route::get('/attendance/history', [AttendanceController::class, 'history']);
        Route::get('/attendance/session/{sessionId}', [AttendanceController::class, 'sessionAttendance'])
            ->middleware('role:admin,session_manager');

        // Payment
        Route::post('/payment/initiate', [PaymentController::class, 'initiate']);
        Route::get('/payment/status/{id}', [PaymentController::class, 'status']);

        // Admin
        Route::prefix('admin')->middleware('role:admin')->group(function () {
            Route::get('/dashboard', [AdminController::class, 'dashboard']);
            Route::get('/users', [AdminController::class, 'users']);
            Route::put('/users/{id}/status', [AdminController::class, 'updateUserStatus']);
            Route::get('/reports/attendance', [AdminController::class, 'attendanceReport']);
        });
    });

    // Payment Webhooks (Public but secured)
    Route::post('/payment/webhook/{gateway}', [PaymentController::class, 'webhook']);
});
```

---

## 🔧 HELPERS

### LocationHelper (`app/Helpers/LocationHelper.php`)
```php
<?php

namespace App\Helpers;

class LocationHelper
{
    /**
     * Calculate distance between two coordinates (Haversine formula)
     * Returns distance in meters
     */
    public static function calculateDistance(
        float $lat1,
        float $lng1,
        float $lat2,
        float $lng2
    ): float {
        $earthRadius = 6371000; // meters

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLng / 2) * sin($dLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
```

### FaceHelper (`app/Helpers/FaceHelper.php`)
```php
<?php

namespace App\Helpers;

class FaceHelper
{
    /**
     * Compare two face descriptors using Euclidean distance
     */
    public static function compareDescriptors(array $descriptor1, array $descriptor2): array
    {
        if (count($descriptor1) !== count($descriptor2)) {
            throw new \Exception('Descriptor dimensions do not match');
        }

        $sum = 0;
        for ($i = 0; $i < count($descriptor1); $i++) {
            $diff = $descriptor1[$i] - $descriptor2[$i];
            $sum += $diff * $diff;
        }

        $distance = sqrt($sum);

        return [
            'distance' => $distance,
            'match' => $distance < 0.6, // Threshold
            'score' => (1 - min($distance, 1)) * 100
        ];
    }
}
```

---

## 📊 MIGRATIONS

### Create Attendances Table (`database/migrations/xxxx_create_attendances_table.php`)
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('session_id')->constrained()->onDelete('cascade');
            $table->foreignId('qr_code_id')->constrained()->onDelete('cascade');
            $table->dateTime('verified_at');
            $table->decimal('face_match_score', 5, 2)->nullable();
            $table->boolean('face_match')->default(false);
            $table->boolean('gps_valid')->default(false);
            $table->decimal('location_lat', 10, 8)->nullable();
            $table->decimal('location_lng', 11, 8)->nullable();
            $table->decimal('distance_from_venue', 10, 2)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('device_info')->nullable();
            $table->boolean('webauthn_used')->default(false);
            $table->enum('verification_method', [
                'qr_only',
                'qr_face',
                'qr_face_gps',
                'qr_face_gps_webauthn'
            ])->default('qr_face_gps');
            $table->enum('status', ['pending', 'verified', 'rejected', 'flagged'])->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'session_id']);
            $table->index(['user_id', 'session_id', 'verified_at']);
            $table->index(['session_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
```

---

## 🧪 TESTING

### Feature Test Example (`tests/Feature/AttendanceTest.php`)
```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Session;
use Tymon\JWTAuth\Facades\JWTAuth;

class AttendanceTest extends TestCase
{
    public function test_attendance_verification_requires_authentication()
    {
        $response = $this->postJson('/api/v1/attendance/verify', []);
        $response->assertStatus(401);
    }

    public function test_user_can_verify_attendance()
    {
        $user = User::factory()->create();
        $session = Session::factory()->create();
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->postJson('/api/v1/attendance/verify', [
            'session_id' => $session->id,
            'qr_code' => 'valid_qr_code',
            'face_descriptor' => [0.1, 0.2, ...],
            'location' => [
                'lat' => $session->location_lat,
                'lng' => $session->location_lng
            ]
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }
}
```

