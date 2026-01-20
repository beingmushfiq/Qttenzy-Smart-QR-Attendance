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

        // Implementation for face enrollment
        // Store face descriptor in database

        return response()->json([
            'success' => true,
            'message' => 'Face enrolled successfully'
        ], 201);
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
}

