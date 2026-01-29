<?php

namespace App\Http\Controllers;

use App\Models\Session;
use App\Services\QRService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class SessionController extends Controller
{
    protected $qrService;
    protected $sessionService;

    public function __construct(QRService $qrService, \App\Services\SessionService $sessionService)
    {
        $this->qrService = $qrService;
        $this->sessionService = $sessionService;
    }

    /**
     * List all sessions with filters
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $query = Session::with(['creator', 'organization']);

        // Scope to organization for org admin
        if ($user->hasRole('organization_admin')) {
            $query->inOrganization($user->organization_id);
        }

        // Filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('type')) {
            $query->where('session_type', $request->type);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }
        
        // Default to not showing child sessions (instances) unless specifically requested
        if (!$request->has('include_instances')) {
            $query->whereNull('parent_session_id');
        }
        
        // Only show active/upcoming sessions for students unless searching
        if ($user->hasRole('student') && !$request->has('search')) {
            $query->where(function($q) {
                $q->where('status', 'active')
                  ->orWhere('start_time', '>=', now());
            });
        }

        $sessions = $query->orderBy('start_time', 'desc')->get();

        // Add additional data
        $sessions->transform(function ($session) {
            $session->current_attendees = $session->attendances()->where('status', 'verified')->count();
            return $session;
        });

        return response()->json([
            'success' => true,
            'data' => $sessions
        ]);
    }

    /**
     * Get single session details
     */
    public function show($id)
    {
        $session = Session::with(['creator', 'qrCodes', 'attendances.user', 'registrations.user', 'childSessions'])
            ->findOrFail($id);

        $session->current_attendees = $session->attendances()->where('status', 'verified')->count();
        $session->registration_status = $this->getRegistrationStatus($session);
        $session->attendance_status = $this->getAttendanceStatus($session);

        // Include active QR code if user is admin/manager
        if (auth()->user()->role === 'admin' || auth()->user()->role === 'session_manager') {
            $session->qr_code = $session->activeQRCode();
        }

        return response()->json([
            'success' => true,
            'data' => $session
        ]);
    }

    /**
     * Create new session
     */
    /**
     * Create new session
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_time' => 'required|date|after:now',
            'end_time' => 'required|date|after:start_time',
            'location_lat' => 'required|numeric|between:-90,90',
            'location_lng' => 'required|numeric|between:-180,180',
            'location_name' => 'nullable|string|max:255',
            'radius_meters' => 'nullable|integer|min:10|max:10000',
            'session_type' => 'required|in:admin_approved,pre_registered,open',
            'requires_payment' => 'nullable|boolean',
            'payment_amount' => 'required_if:requires_payment,true|numeric|min:0',
            'max_attendees' => 'nullable|integer|min:1',
            'recurrence_type' => 'nullable|in:one_time,daily,weekly,monthly',
            'recurrence_end_date' => 'required_if:recurrence_type,daily,weekly,monthly|nullable|date|after:start_time',
            'capacity' => 'nullable|integer|min:1',
            'allow_entry_exit' => 'nullable|boolean',
            'late_threshold_minutes' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            $session = Session::create([
                'title' => $request->title,
                'description' => $request->description,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'location_lat' => $request->location_lat,
                'location_lng' => $request->location_lng,
                'location_name' => $request->location_name,
                'radius_meters' => $request->radius_meters ?? config('attendance.default_radius_meters', 100),
                'session_type' => $request->session_type,
                'status' => 'draft',
                'requires_payment' => $request->requires_payment ?? false,
                'payment_amount' => $request->payment_amount,
                'max_attendees' => $request->max_attendees,
                'recurrence_type' => $request->recurrence_type ?? 'one_time',
                'recurrence_end_date' => $request->recurrence_end_date,
                'capacity' => $request->capacity ?? $request->max_attendees, // Use max_attendees as fallback if capacity not set
                'allow_entry_exit' => $request->allow_entry_exit ?? false,
                'late_threshold_minutes' => $request->late_threshold_minutes ?? 15,
                'late_threshold_minutes' => $request->late_threshold_minutes ?? 15,
                'created_by' => auth()->id(),
                'organization_id' => auth()->user()->hasRole('organization_admin') ? auth()->user()->organization_id : null,
                'organization_id' => auth()->user()->hasRole('organization_admin') ? auth()->user()->organization_id : null,
            ]);

            if ($session->isRecurring()) {
                $this->sessionService->createRecurringInstances($session);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Session created successfully',
                'data' => $session->load('creator')
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create session: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update session
     */
    public function update(Request $request, $id)
    {
        $session = Session::findOrFail($id);

        // Check permission
        if ($session->created_by !== auth()->id() && auth()->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'start_time' => 'sometimes|date',
            'end_time' => 'sometimes|date|after:start_time',
            'location_lat' => 'sometimes|numeric|between:-90,90',
            'location_lng' => 'sometimes|numeric|between:-180,180',
            'location_name' => 'nullable|string|max:255',
            'radius_meters' => 'nullable|integer|min:10|max:10000',
            'session_type' => 'sometimes|in:admin_approved,pre_registered,open',
            'status' => 'sometimes|in:draft,active,completed,cancelled',
            'requires_payment' => 'nullable|boolean',
            'payment_amount' => 'nullable|numeric|min:0',
            'max_attendees' => 'nullable|integer|min:1',
            'capacity' => 'nullable|integer|min:1',
            'allow_entry_exit' => 'nullable|boolean',
            'late_threshold_minutes' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $session->update($request->only([
            'title', 'description', 'start_time', 'end_time',
            'location_lat', 'location_lng', 'location_name',
            'radius_meters', 'session_type', 'status',
            'requires_payment', 'payment_amount', 'max_attendees',
            'capacity', 'allow_entry_exit', 'late_threshold_minutes'
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Session updated successfully',
            'data' => $session->load('creator')
        ]);
    }

    /**
     * Delete session
     */
    public function destroy($id)
    {
        $session = Session::findOrFail($id);

        // Check permission
        $user = auth()->user();
        if ($user->role !== 'admin') {
            if ($user->hasRole('organization_admin')) {
                if ($session->organization_id !== $user->organization_id) {
                    return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
                }
            } else {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }
        }

        // Also delete child sessions if this is a parent
        if ($session->childSessions()->exists()) {
            $session->childSessions()->delete();
        }

        $session->delete();

        return response()->json([
            'success' => true,
            'message' => 'Session deleted successfully'
        ]);
    }

    /**
     * Get QR code for session
     */
    public function getQR($id)
    {
        $session = Session::findOrFail($id);

        // Check permission
        if ($session->created_by !== auth()->id() && auth()->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        // Check if session is active (skip for admins)
        if (!$session->isActive() && $session->status !== 'active' && auth()->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Session is not active'
            ], 400);
        }

        try {
            // Get or generate QR code
            $activeQR = $session->activeQRCode();
            
            if (!$activeQR) {
                // Generate new QR code
                $qrData = $this->qrService->generateQR($session->id);
            } else {
                $qrData = [
                    'qr_code' => $activeQR->code,
                    'qr_text' => $activeQR->code,
                    'expires_at' => $activeQR->expires_at,
                    'session_id' => $session->id
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $qrData
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('QR Generation Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate QR code: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate QR code image (removed to avoid GD dependency)
     */
    private function generateQRImage(string $code): string
    {
        // Simplified - just return the code
        return $code;
    }

    /**
     * Get registration status for current user
     */
    private function getRegistrationStatus($session)
    {
        $user = auth()->user();
        if (!$user) return null;

        $registration = $session->registrations()
            ->where('user_id', $user->id)
            ->first();

        return $registration ? $registration->status : null;
    }

    /**
     * Get attendance status for current user
     */
    private function getAttendanceStatus($session)
    {
        $user = auth()->user();
        if (!$user) return null;

        $attendance = $session->attendances()
            ->where('user_id', $user->id)
            ->where('status', 'verified')
            ->first();

        return $attendance ? 'present' : null;
    }
}
