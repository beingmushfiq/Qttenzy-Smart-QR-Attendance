<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceLog;
use App\Services\AttendanceService;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdminController extends Controller
{
    protected $attendanceService;
    protected $reportService;

    public function __construct(
        AttendanceService $attendanceService,
        ReportService $reportService
    ) {
        $this->attendanceService = $attendanceService;
        $this->reportService = $reportService;
    }

    /**
     * Get dashboard statistics
     */
    public function dashboard()
    {
        $user = auth()->user();
        $orgId = $user->hasRole('organization_admin') ? $user->organization_id : null;

        $stats = [
            'total_users' => User::when($orgId, fn($q) => $q->where('organization_id', $orgId))->count(),
            'total_students' => User::withRole('student')->when($orgId, fn($q) => $q->where('organization_id', $orgId))->count(),
            'total_teachers' => User::withRole('teacher')->when($orgId, fn($q) => $q->where('organization_id', $orgId))->count(),
            'pending_approvals' => User::where('is_approved', false)->when($orgId, fn($q) => $q->where('organization_id', $orgId))->count(),
            'pending_attendances' => Attendance::where('status', 'pending')
                ->when($orgId, fn($q) => $q->whereHas('session', fn($sq) => $sq->where('organization_id', $orgId)))
                ->count(),
            'today_attendances' => Attendance::whereDate('verified_at', today())
                ->when($orgId, fn($q) => $q->whereHas('session', fn($sq) => $sq->where('organization_id', $orgId)))
                ->count(),
            'today_sessions' => \App\Models\Session::whereDate('start_time', today())
                ->when($orgId, fn($q) => $q->where('organization_id', $orgId))
                ->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Get all users with filters
     */
    public function users(Request $request)
    {
        $query = User::with('organization');

        if ($request->has('role')) {
            $query->where('role', $request->role);
        }

        if ($request->has('status')) {
            $query->where('is_approved', $request->status === 'approved');
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }

    /**
     * Update user approval status
     */
    public function updateUserStatus($id, Request $request)
    {
        $validator = Validator::make($request->all(), [
            'is_approved' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::findOrFail($id);
        $user->update([
            'is_approved' => $request->is_approved,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User status updated successfully',
            'data' => $user
        ]);
    }

    /**
     * Get pending attendances for approval
     */
    public function pendingAttendances(Request $request)
    {
        $query = Attendance::with(['user', 'session'])
            ->where('status', 'pending')
            ->orderBy('verified_at', 'desc');

        if ($request->has('session_id')) {
            $query->where('session_id', $request->session_id);
        }

        $attendances = $query->get();

        return response()->json([
            'success' => true,
            'data' => $attendances
        ]);
    }

    /**
     * Approve pending attendance
     */
    public function approveAttendance($id, Request $request)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:present,late',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $this->attendanceService->approveAttendance(
                $id,
                auth()->id(),
                $request->status,
                $request->notes
            );

            return response()->json([
                'success' => true,
                'message' => 'Attendance approved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Reject pending attendance
     */
    public function rejectAttendance($id, Request $request)
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $this->attendanceService->rejectAttendance(
                $id,
                auth()->id(),
                $request->reason
            );

            return response()->json([
                'success' => true,
                'message' => 'Attendance rejected successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Delete attendance record
     */
    public function deleteAttendance($id)
    {
        try {
            $attendance = Attendance::findOrFail($id);
            
            // Log the deletion before deleting
            AttendanceLog::create([
                'attendance_id' => $attendance->id,
                'action' => 'deleted',
                'old_status' => $attendance->status,
                'new_status' => null,
                'notes' => 'Attendance record deleted by admin',
                'performed_by' => auth()->id(),
            ]);
            
            $attendance->delete();

            return response()->json([
                'success' => true,
                'message' => 'Attendance deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Override attendance status (admin can change any status)
     */
    public function overrideAttendance($id, Request $request)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:present,late,absent,pending,rejected',
            'notes' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $attendance = Attendance::findOrFail($id);
        $oldStatus = $attendance->status;

        $attendance->update([
            'status' => $request->status,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'admin_notes' => $request->notes,
        ]);

        // Log the override
        AttendanceLog::logChange(
            $attendance,
            'override',
            $oldStatus,
            $request->status,
            $request->notes,
            auth()->id()
        );

        // Update session count if status changed to/from approved
        if (in_array($request->status, ['present', 'late']) && !in_array($oldStatus, ['present', 'late'])) {
            $attendance->session->incrementAttendanceCount();
        } elseif (!in_array($request->status, ['present', 'late']) && in_array($oldStatus, ['present', 'late'])) {
            $attendance->session->decrementAttendanceCount();
        }

        return response()->json([
            'success' => true,
            'message' => 'Attendance status overridden successfully',
            'data' => $attendance->fresh(['user', 'session', 'approver'])
        ]);
    }

    /**
     * Get attendance logs (audit history)
     */
    public function attendanceLogs($attendanceId)
    {
        $attendance = Attendance::with(['logs.user'])->findOrFail($attendanceId);

        return response()->json([
            'success' => true,
            'data' => [
                'attendance' => $attendance,
                'logs' => $attendance->logs()->with('user')->orderBy('created_at', 'desc')->get()
            ]
        ]);
    }

    /**
     * Get attendance report with filters
     */
    public function attendanceReport(Request $request)
    {
        $query = Attendance::with(['user', 'session']);

        if ($request->has('session_id')) {
            $query->where('session_id', $request->session_id);
        }

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('verified_at', [
                $request->start_date,
                $request->end_date
            ]);
        }

        $attendances = $query->orderBy('verified_at', 'desc')
            ->paginate($request->get('per_page', 50));

        return response()->json([
            'success' => true,
            'data' => $attendances
        ]);
    }

    /**
     * Get attendance trends for analytics
     */
    public function attendanceTrends(Request $request)
    {
        $filters = $request->only(['start_date', 'end_date', 'group_by', 'session_id', 'organization_id']);
        
        $trends = $this->reportService->getAttendanceTrends($filters);

        return response()->json([
            'success' => true,
            'data' => $trends
        ]);
    }

    /**
     * Get session statistics
     */
    public function sessionStats(Request $request)
    {
        $filters = $request->only(['start_date', 'end_date']);
        
        $stats = $this->reportService->getSessionStatistics($filters);

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Get user attendance summary
     */
    public function userSummary($userId, Request $request)
    {
        $filters = $request->only(['start_date', 'end_date']);
        
        try {
            $summary = $this->reportService->generateUserSummary($userId, $filters);

            return response()->json([
                'success' => true,
                'data' => $summary
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Export attendance report
     */
    public function exportAttendanceReport(Request $request)
    {
        $filters = $request->only(['session_id', 'user_id', 'status', 'start_date', 'end_date', 'organization_id']);
        $format = $request->get('format', 'csv'); // csv, pdf, excel
        
        $reportData = $this->reportService->generateAttendanceReport($filters);

        if ($format === 'csv') {
            return $this->exportToCSV($reportData, 'attendance');
        }

        // For now, only CSV is implemented
        // PDF and Excel can be added with additional libraries
        return response()->json([
            'success' => false,
            'message' => 'Only CSV export is currently supported'
        ], 400);
    }

    /**
     * Export session report
     */
    public function exportSessionReport(Request $request)
    {
        $filters = $request->only(['status', 'session_type', 'created_by', 'start_date', 'end_date']);
        $format = $request->get('format', 'csv');
        
        $reportData = $this->reportService->generateSessionReport($filters);

        if ($format === 'csv') {
            return $this->exportToCSV($reportData, 'session');
        }

        return response()->json([
            'success' => false,
            'message' => 'Only CSV export is currently supported'
        ], 400);
    }

    /**
     * Helper method to export data as CSV
     */
    private function exportToCSV(array $reportData, string $type)
    {
        $csvData = $this->reportService->formatForCSV($reportData, $type);
        
        $filename = $type . '_report_' . now()->format('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($csvData) {
            $file = fopen('php://output', 'w');
            
            foreach ($csvData as $row) {
                fputcsv($file, $row);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}


