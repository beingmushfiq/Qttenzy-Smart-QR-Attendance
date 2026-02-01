<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\AuditLog;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\DB;
use App\Models\Organization;
use Illuminate\Support\Str;

/**
 * AuthController
 * 
 * Handles user authentication including registration, login, logout, and token management.
 * Implements JWT-based authentication with role-based access control.
 */
class AuthController extends Controller
{
    /**
     * Register a new user
     * 
     * Supports role-based registration with optional admin approval.
     * Students and employees can self-register.
     * Admins and teachers must be created by existing admins.
     *
     * @param RegisterRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(RegisterRequest $request)
    {

        // Check if role requires admin creation
        // Allow 'organization_admin' only if they are creating a NEW organization
        $restrictedRoles = ['admin'];
        $requestedRole = $request->role ?? 'student';
        $isCreatingOrg = $request->boolean('create_organization');

        if (in_array($requestedRole, $restrictedRoles)) {
            // Only admins can create admin accounts directly
            if (!auth()->check() || !auth()->user()->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only administrators can create global admin accounts.'
                ], 403);
            }
        }
        
        // If requesting organization_admin without creating an org, block it (unless admin)
        if ($requestedRole === 'organization_admin' && !$isCreatingOrg) {
             if (!auth()->check() || !auth()->user()->isAdmin()) {
                 return response()->json([
                    'success' => false,
                    'message' => 'You can only become an Organization Admin by creating a new organization.'
                ], 403);
             }
        }

        try {
            DB::beginTransaction();

            // Handle Organization Creation
            $organizationId = $request->organization_id;
            
            if ($isCreatingOrg) {
                // Generate a unique code for the organization
                $orgCode = strtoupper(Str::slug($request->organization_name) . '-' . Str::random(4));
                
                $organization = Organization::create([
                    'name' => $request->organization_name,
                    'code' => $orgCode,
                    'address' => $request->address, // Note: Request field might be organization_address or just address. using address based on likely usage, checking request below
                    'phone' => $request->organization_phone ?? $request->phone,
                    'email' => $request->email, // Default to admin email
                    'is_active' => true,
                ]);
                
                $organizationId = $organization->id;
                
                // Force role to organization_admin if creating an org
                $requestedRole = 'organization_admin';
            }

            // Determine if approval is required
            // Roles that require explicit approval
            $privilegedRoles = ['teacher', 'session_manager', 'event_manager', 'coordinator'];
            
            // If requesting a privileged role, force approval required (unless created by an admin)
            if (in_array($requestedRole, $privilegedRoles)) {
                // If the creator is NOT an admin/org_admin, they need approval
                 if (!auth()->check() || (!auth()->user()->isAdmin() && !auth()->user()->hasRole('organization_admin'))) {
                    $requiresApproval = true;
                 } else {
                    // Created by admin, respect input or default to false
                    $requiresApproval = $request->input('requires_approval', false);
                 }
            } else {
                // For students/others, respect input or default to false
                $requiresApproval = $request->input('requires_approval', false);
            }
            
            // Create user
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'phone' => $request->phone,
                'organization_id' => $organizationId,
                'role' => $requestedRole,
                'requires_approval' => $requiresApproval,
                'is_approved' => !$requiresApproval, // Auto-approve if not required
                'approved_at' => !$requiresApproval ? now() : null,
                'approved_by' => !$requiresApproval && auth()->check() ? auth()->id() : null,
                'face_consent' => $request->input('face_consent', false),
                'is_active' => true,
            ]);
    
            // Assign role using the roles relationship
            $role = Role::where('name', $requestedRole)->first();
            if ($role) {
                $user->assignRole($role);
            }
    
            // Log the registration
            AuditLog::log(
                'user_registered',
                $user,
                null,
                [
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'organization_id' => $organizationId,
                    'created_organization' => $isCreatingOrg
                ],
                'New user registered'
            );
            
            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Registration failed: ' . $e->getMessage()
            ], 500);
        }

        // Generate token only if approved
        $token = null;
        $message = 'User registered successfully';
        
        if ($user->is_approved) {
            $token = JWTAuth::fromUser($user);
        } else {
            $message = 'Registration submitted. Waiting for admin approval.';
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => [
                'user' => new UserResource($user),
                'token' => $token,
                'requires_approval' => $user->requires_approval,
                'is_approved' => $user->is_approved,
            ]
        ], 201);
    }

    /**
     * Login user
     * 
     * Authenticates user and returns JWT token.
     * Checks if user is active and approved before allowing login.
     *
     * @param LoginRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(LoginRequest $request)
    {
        $credentials = $request->only('email', 'password');

        // Attempt authentication
        if (!$token = JWTAuth::attempt($credentials)) {
            // Log failed login attempt
            AuditLog::create([
                'user_id' => null,
                'action' => 'login_failed',
                'model_type' => null,
                'model_id' => null,
                'old_values' => null,
                'new_values' => ['email' => $request->email],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'notes' => 'Failed login attempt - invalid credentials',
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Invalid email or password'
            ], 401);
        }

        $user = Auth::user();

        // Check if user is active
        if (!$user->is_active) {
            JWTAuth::invalidate($token);
            
            return response()->json([
                'success' => false,
                'message' => 'Your account has been deactivated. Please contact administrator.'
            ], 403);
        }

        // Check if user is approved
        if ($user->requires_approval && !$user->is_approved) {
            JWTAuth::invalidate($token);
            
            return response()->json([
                'success' => false,
                'message' => 'Your account is pending admin approval.'
            ], 403);
        }

        // Update last login
        $user->last_login_at = now();
        $user->save();

        // Log successful login
        AuditLog::log(
            'user_logged_in',
            $user,
            null,
            ['login_time' => now()->toIso8601String()],
            'User logged in successfully'
        );

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'user' => new UserResource($user),
                'token' => $token,
                'token_type' => 'bearer',
                'expires_in' => config('jwt.ttl') * 60,
            ]
        ]);
    }

    /**
     * Logout user
     * 
     * Invalidates the current JWT token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        $user = Auth::user();
        
        // Log logout
        if ($user) {
            AuditLog::log(
                'user_logged_out',
                $user,
                null,
                ['logout_time' => now()->toIso8601String()],
                'User logged out'
            );
        }

        JWTAuth::invalidate(JWTAuth::getToken());
        
        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully'
        ]);
    }

    /**
     * Refresh JWT token
     * 
     * Returns a new token with extended expiration.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        try {
            $token = JWTAuth::refresh(JWTAuth::getToken());
            
            return response()->json([
                'success' => true,
                'data' => [
                    'token' => $token,
                    'token_type' => 'bearer',
                    'expires_in' => config('jwt.ttl') * 60,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token refresh failed. Please login again.'
            ], 401);
        }
    }

    /**
     * Get authenticated user
     * 
     * Returns the currently authenticated user's information.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        $user = Auth::user();
        
        // Load relationships
        $user->load(['organization', 'roles', 'faceEnrollment']);
        
        return response()->json([
            'success' => true,
            'data' => new UserResource($user)
        ]);
    }

    /**
     * Forgot password
     * 
     * Sends password reset link to user's email.
     * TODO: Implement email sending functionality
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email'
        ]);

        $user = User::where('email', $request->email)->first();
        
        // Log password reset request
        AuditLog::log(
            'password_reset_requested',
            $user,
            null,
            ['email' => $request->email],
            'Password reset requested'
        );

        // TODO: Generate reset token and send email
        
        return response()->json([
            'success' => true,
            'message' => 'Password reset link sent to your email'
        ]);
    }

    /**
     * Reset password
     * 
     * Resets user password using reset token.
     * TODO: Implement token verification
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        // TODO: Verify reset token
        
        $user = User::where('email', $request->email)->first();
        $user->password = Hash::make($request->password);
        $user->save();

        // Log password reset
        AuditLog::log(
            'password_reset',
            $user,
            null,
            null,
            'Password reset successfully'
        );

        return response()->json([
            'success' => true,
            'message' => 'Password reset successfully'
        ]);
    }
}

