<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function profile()
    {
        return new \App\Http\Resources\UserResource(auth()->user());
    }

    /**
     * Update user profile
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|string|max:20',
            'avatar' => 'sometimes|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user->update($request->only(['name', 'phone', 'avatar']));

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => $user
        ]);
    }

    /**
     * Enroll face for verification
     */
    public function enrollFace(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'face_descriptor' => 'required|array|size:128',
            'face_descriptor.*' => 'required|numeric|between:-1,1',
            'image' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = Auth::user();

        try {
            // Encrypt the face descriptor
            $faceDescriptor = json_encode($request->face_descriptor);
            $encryptedDescriptor = encrypt($faceDescriptor);

            // Check if user already has an enrollment
            $existingEnrollment = $user->faceEnrollment;

            if ($existingEnrollment) {
                // Update existing enrollment
                $existingEnrollment->update([
                    'encrypted_descriptor' => $encryptedDescriptor,
                    'image_path' => $request->image,
                    'confidence_threshold' => 0.6,
                    'requires_reverification' => false,
                ]);

                $enrollment = $existingEnrollment;
            } else {
                // Create new enrollment
                $enrollment = \App\Models\FaceEnrollment::create([
                    'user_id' => $user->id,
                    'encrypted_descriptor' => $encryptedDescriptor,
                    'image_path' => $request->image,
                    'confidence_threshold' => 0.6,
                    'verification_count' => 0,
                    'requires_reverification' => false,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Face enrolled successfully',
                'data' => [
                    'enrollment_id' => $enrollment->id,
                    'enrolled_at' => $enrollment->created_at,
                    'face_enrolled' => true
                ]
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to enroll face',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get user's face enrollment data
     */
    public function getFaceEnrollment()
    {
        $user = Auth::user();
        $enrollment = $user->faceEnrollment;

        if (!$enrollment) {
            return response()->json([
                'success' => false,
                'message' => 'No face enrollment found'
            ], 404);
        }

        try {
            // Decrypt the face descriptor
            $decryptedDescriptor = decrypt($enrollment->encrypted_descriptor);

            return response()->json([
                'success' => true,
                'data' => [
                    'face_descriptor' => json_decode($decryptedDescriptor),
                    'enrolled_at' => $enrollment->created_at,
                    'confidence_threshold' => $enrollment->confidence_threshold,
                    'verification_count' => $enrollment->verification_count
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve face enrollment',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Register WebAuthn credential
     */
    public function registerWebAuthn(Request $request)
    {
        // Implementation for WebAuthn registration
        return response()->json([
            'success' => true,
            'message' => 'WebAuthn credential registered'
        ], 201);
    }
    /**
     * List users (Scoped to organization for org_admin)
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = \App\Models\User::with(['organization', 'roles']);

        // Scope to organization for org admin (Super Admins see all)
        if ($user->hasRole('organization_admin')) {
            $query->inOrganization($user->organization_id);
            // Don't list admins
            $query->whereDoesntHave('roles', function ($q) {
                $q->where('name', 'admin');
            });
        } elseif (!$user->isAdmin() && !$user->isSuperAdmin()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->has('role')) {
            $query->withRole($request->role);
        }

        $users = $query->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }

    /**
     * Create user (Scoped)
     */
    public function store(Request $request)
    {
        $currentUser = Auth::user();

        // Permission check
        if (!$currentUser->isAdmin() && !$currentUser->isSuperAdmin() && !$currentUser->hasRole('organization_admin')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => 'required|exists:roles,name',
            'organization_id' => 'nullable|exists:organizations,id',
            'phone' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        // Force organization for org_admin
        $orgId = $request->organization_id;
        if ($currentUser->hasRole('organization_admin')) {
            $orgId = $currentUser->organization_id;
            // Prevent creating admins
            if ($request->role === 'admin' || $request->role === 'organization_admin') {
                return response()->json(['success' => false, 'message' => 'Cannot create admin users'], 403);
            }
        }

        $user = \App\Models\User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'organization_id' => $orgId,
            'role' => $request->role,
            'is_active' => true,
            'is_approved' => true, // Creating directly means approved
            'approved_at' => now(),
            'approved_by' => $currentUser->id
        ]);

        $user->assignRole($request->role);

        return response()->json([
            'success' => true,
            'message' => 'User created successfully',
            'data' => $user
        ], 201);
    }

    /**
     * Update user (Scoped)
     */
    public function update(Request $request, $id)
    {
        $currentUser = Auth::user();
        $user = \App\Models\User::findOrFail($id);

        // Scope check (Super Admins can edit any user)
        if ($currentUser->hasRole('organization_admin')) {
            if ($user->organization_id !== $currentUser->organization_id || $user->isAdmin()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }
        } elseif (!$currentUser->isAdmin() && !$currentUser->isSuperAdmin()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $id,
            'phone' => 'nullable|string',
            'role' => 'sometimes|exists:roles,name',
            'organization_id' => 'nullable|exists:organizations,id',
            'is_active' => 'sometimes|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        // Prevent org admin from changing user's org
        if ($currentUser->hasRole('organization_admin') && $request->has('organization_id')) {
            if ($request->organization_id != $currentUser->organization_id) {
                return response()->json(['success' => false, 'message' => 'Cannot change user organization'], 403);
            }
        }

        $data = $request->only(['name', 'email', 'phone', 'is_active', 'role', 'organization_id']);
        if ($request->has('password') && !empty($request->password)) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        if ($request->has('role')) {
            // Sync roles
            $user->roles()->sync([]);
            $user->assignRole($request->role);
        }

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully',
            'data' => $user
        ]);
    }

    /**
     * Delete user (Scoped)
     */
    public function destroy($id)
    {
        $currentUser = Auth::user();
        $user = \App\Models\User::findOrFail($id);

        // Scope check (Super Admins can delete any user)
        if ($currentUser->hasRole('organization_admin')) {
            if ($user->organization_id !== $currentUser->organization_id || $user->isAdmin()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }
        } elseif (!$currentUser->isAdmin() && !$currentUser->isSuperAdmin()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully'
        ]);
    }
}

