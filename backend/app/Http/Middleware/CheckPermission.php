<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * CheckPermission Middleware
 * 
 * Checks if the authenticated user has the required permission.
 * Super Admins (organization_id = null) automatically pass all permission checks.
 */
class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $permission
     * @return mixed
     */
    public function handle(Request $request, Closure $next, string $permission)
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated'
            ], 401);
        }

        // Super Admin bypass (organization_id = null means global admin)
        if ($user->organization_id === null && $user->isAdmin()) {
            return $next($request);
        }

        // Check if user has the required permission
        if (!$user->hasPermission($permission)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Required permission: ' . $permission
            ], 403);
        }

        return $next($request);
    }
}
