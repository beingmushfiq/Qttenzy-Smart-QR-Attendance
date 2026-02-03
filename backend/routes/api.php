<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\AdminController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    // Public Routes
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);

    // Health Check
    Route::get('/health', function () {
        return response()->json([
            'status' => 'ok',
            'timestamp' => now(),
            'database' => DB::connection()->getPdo() ? 'connected' : 'disconnected'
        ]);
    });

    // Organizations (Public - for registration)
    Route::get('/organizations', [App\Http\Controllers\OrganizationController::class, 'index']);

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
        Route::get('/user/face-enrollment', [UserController::class, 'getFaceEnrollment']);
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

        // Organization Admin Routes
        Route::middleware('role:organization_admin,admin')->group(function () {
            Route::get('/organizations/{id}/statistics', [App\Http\Controllers\OrganizationController::class, 'statistics']);
            Route::get('/users', [UserController::class, 'index']);
            Route::post('/users', [UserController::class, 'store']);
            Route::put('/users/{id}', [UserController::class, 'update']);
            Route::delete('/users/{id}', [UserController::class, 'destroy']);
        });

        // Admin
        Route::prefix('admin')->middleware('role:admin')->group(function () {
            Route::get('/dashboard', [AdminController::class, 'dashboard']);
            Route::get('/users', [AdminController::class, 'users']);
            Route::put('/users/{id}/status', [AdminController::class, 'updateUserStatus']);

            // Attendance Management
            Route::get('/attendances/pending', [AdminController::class, 'pendingAttendances']);
            Route::put('/attendances/{id}/approve', [AdminController::class, 'approveAttendance']);
            Route::put('/attendances/{id}/reject', [AdminController::class, 'rejectAttendance']);
            Route::put('/attendances/{id}/override', [AdminController::class, 'overrideAttendance']);
            Route::get('/attendances/{id}/logs', [AdminController::class, 'attendanceLogs']);

            // Analytics
            Route::get('/analytics/attendance-trends', [AdminController::class, 'attendanceTrends']);
            Route::get('/analytics/session-stats', [AdminController::class, 'sessionStats']);
            Route::get('/analytics/user-summary/{userId}', [AdminController::class, 'userSummary']);

            // Reports & Exports
            Route::get('/reports/attendance', [AdminController::class, 'attendanceReport']);
            Route::get('/reports/attendance/export', [AdminController::class, 'exportAttendanceReport']);
            Route::get('/reports/sessions/export', [AdminController::class, 'exportSessionReport']);

            // Organization Management
            Route::get('/organizations', [App\Http\Controllers\OrganizationController::class, 'adminIndex']);
            Route::get('/organizations/{id}', [App\Http\Controllers\OrganizationController::class, 'show']);
            Route::post('/organizations', [App\Http\Controllers\OrganizationController::class, 'store']);
            Route::put('/organizations/{id}', [App\Http\Controllers\OrganizationController::class, 'update']);
            Route::delete('/organizations/{id}', [App\Http\Controllers\OrganizationController::class, 'destroy']);
            Route::post('/organizations/{id}/restore', [App\Http\Controllers\OrganizationController::class, 'restore']);
            Route::post('/organizations/{id}/toggle-status', [App\Http\Controllers\OrganizationController::class, 'toggleStatus']);
            Route::get('/organizations/{id}/statistics', [App\Http\Controllers\OrganizationController::class, 'statistics']);
        });
    });

    // Payment Webhooks (Public but secured)
    Route::post('/payment/webhook/{gateway}', [PaymentController::class, 'webhook']);
});

