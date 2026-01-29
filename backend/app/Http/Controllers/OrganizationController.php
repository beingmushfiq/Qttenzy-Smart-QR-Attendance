<?php

namespace App\Http\Controllers;

use App\Http\Requests\OrganizationRequest;
use App\Http\Resources\OrganizationResource;
use App\Models\AuditLog;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * OrganizationController
 * 
 * Handles organization management including CRUD operations.
 * Public endpoint for listing organizations (for registration).
 * Admin-only endpoints for full CRUD operations.
 */
class OrganizationController extends Controller
{
    /**
     * List all active organizations
     * 
     * Public endpoint used for registration dropdown.
     * Only returns active organizations.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $organizations = Organization::active()
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => OrganizationResource::collection($organizations)
        ]);
    }

    /**
     * List all organizations with filters (Admin only)
     * 
     * Supports filtering, searching, and pagination.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function adminIndex(Request $request)
    {
        $query = Organization::withTrashed();

        // Search by name or code
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        // Filter by deleted status
        if ($request->has('with_trashed') && $request->with_trashed) {
            // Already included via withTrashed()
        } elseif ($request->has('only_trashed') && $request->only_trashed) {
            $query->onlyTrashed();
        } else {
            $query->whereNull('deleted_at');
        }

        // Load relationships
        $query->withCount(['users', 'sessions']);

        // Pagination
        $perPage = $request->input('per_page', 15);
        $organizations = $query->orderBy('name')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => OrganizationResource::collection($organizations),
            'meta' => [
                'current_page' => $organizations->currentPage(),
                'last_page' => $organizations->lastPage(),
                'per_page' => $organizations->perPage(),
                'total' => $organizations->total(),
            ]
        ]);
    }

    /**
     * Get a single organization
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $currentUser = Auth::user();
        
        // Scope check for org admin
        if ($currentUser->hasRole('organization_admin') && $currentUser->organization_id != $id) {
             return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $organization = Organization::withTrashed()
            ->withCount(['users', 'sessions'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => new OrganizationResource($organization)
        ]);
    }

    /**
     * Create a new organization
     *
     * @param OrganizationRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(OrganizationRequest $request)
    {
        $organization = Organization::create($request->validated());

        // Log the creation
        AuditLog::log(
            'organization_created',
            $organization,
            null,
            $organization->toArray(),
            'Organization created by ' . Auth::user()->name
        );

        return response()->json([
            'success' => true,
            'message' => 'Organization created successfully',
            'data' => new OrganizationResource($organization)
        ], 201);
    }

    /**
     * Update an organization
     *
     * @param OrganizationRequest $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(OrganizationRequest $request, $id)
    {
        $currentUser = Auth::user();
        
        // Scope check
        if ($currentUser->hasRole('organization_admin') && $currentUser->organization_id != $id) {
             return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $organization = Organization::findOrFail($id);
        $oldValues = $organization->toArray();

        $organization->update($request->validated());

        // Log the update
        AuditLog::log(
            'organization_updated',
            $organization,
            $oldValues,
            $organization->getChanges(),
            'Organization updated by ' . Auth::user()->name
        );

        return response()->json([
            'success' => true,
            'message' => 'Organization updated successfully',
            'data' => new OrganizationResource($organization)
        ]);
    }

    /**
     * Delete (soft delete) an organization
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        // Only super admin can delete organizations
        if (!Auth::user()->isAdmin()) {
             return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $organization = Organization::findOrFail($id);
        
        // Check if organization has active users
        $activeUsersCount = $organization->users()->where('is_active', true)->count();
        if ($activeUsersCount > 0) {
            return response()->json([
                'success' => false,
                'message' => "Cannot delete organization with {$activeUsersCount} active users. Please deactivate or transfer users first."
            ], 422);
        }

        $organization->delete();

        // Log the deletion
        AuditLog::log(
            'organization_deleted',
            $organization,
            $organization->toArray(),
            null,
            'Organization deleted by ' . Auth::user()->name
        );

        return response()->json([
            'success' => true,
            'message' => 'Organization deleted successfully'
        ]);
    }

    /**
     * Restore a soft-deleted organization
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function restore($id)
    {
        $organization = Organization::onlyTrashed()->findOrFail($id);
        $organization->restore();

        // Log the restoration
        AuditLog::log(
            'organization_restored',
            $organization,
            null,
            $organization->toArray(),
            'Organization restored by ' . Auth::user()->name
        );

        return response()->json([
            'success' => true,
            'message' => 'Organization restored successfully',
            'data' => new OrganizationResource($organization)
        ]);
    }

    /**
     * Toggle organization active status
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleStatus($id)
    {
        $organization = Organization::findOrFail($id);
        $organization->is_active = !$organization->is_active;
        $organization->save();

        $status = $organization->is_active ? 'activated' : 'deactivated';

        // Log the status change
        AuditLog::log(
            'organization_status_changed',
            $organization,
            null,
            ['is_active' => $organization->is_active],
            "Organization {$status} by " . Auth::user()->name
        );

        return response()->json([
            'success' => true,
            'message' => "Organization {$status} successfully",
            'data' => new OrganizationResource($organization)
        ]);
    }

    /**
     * Get organization statistics
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function statistics($id)
    {
        $currentUser = Auth::user();
        
        // Scope check
        if ($currentUser->hasRole('organization_admin') && $currentUser->organization_id != $id) {
             return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $organization = Organization::findOrFail($id);

        $stats = [
            'total_users' => $organization->users()->count(),
            'active_users' => $organization->users()->where('is_active', true)->count(),
            'total_sessions' => $organization->sessions()->count(),
            'active_sessions' => $organization->sessions()->where('status', 'active')->count(),
            'upcoming_sessions' => $organization->sessions()
                ->where('status', 'scheduled')
                ->where('start_time', '>', now())
                ->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}
