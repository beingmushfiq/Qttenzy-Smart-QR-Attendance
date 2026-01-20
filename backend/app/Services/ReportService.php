<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Session;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * ReportService
 * 
 * Handles report generation, data aggregation, and export functionality.
 */
class ReportService
{
    /**
     * Generate attendance report with filters
     * 
     * @param array $filters
     * @return array
     */
    public function generateAttendanceReport(array $filters = []): array
    {
        $query = Attendance::with(['user', 'session', 'approver'])
            ->orderBy('verified_at', 'desc');

        // Apply filters
        if (isset($filters['session_id'])) {
            $query->where('session_id', $filters['session_id']);
        }

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['start_date']) && isset($filters['end_date'])) {
            $query->whereBetween('verified_at', [
                Carbon::parse($filters['start_date'])->startOfDay(),
                Carbon::parse($filters['end_date'])->endOfDay()
            ]);
        }

        if (isset($filters['organization_id'])) {
            $query->whereHas('user', function($q) use ($filters) {
                $q->where('organization_id', $filters['organization_id']);
            });
        }

        $attendances = $query->get();

        // Calculate summary statistics
        $summary = [
            'total' => $attendances->count(),
            'present' => $attendances->where('status', 'present')->count(),
            'late' => $attendances->where('status', 'late')->count(),
            'pending' => $attendances->where('status', 'pending')->count(),
            'rejected' => $attendances->where('status', 'rejected')->count(),
            'attendance_rate' => $attendances->count() > 0 
                ? round(($attendances->whereIn('status', ['present', 'late'])->count() / $attendances->count()) * 100, 2)
                : 0,
        ];

        return [
            'summary' => $summary,
            'data' => $attendances,
            'filters' => $filters,
            'generated_at' => now(),
        ];
    }

    /**
     * Generate session report with statistics
     * 
     * @param array $filters
     * @return array
     */
    public function generateSessionReport(array $filters = []): array
    {
        $query = Session::with(['creator', 'attendances', 'registrations'])
            ->orderBy('start_time', 'desc');

        // Apply filters
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['session_type'])) {
            $query->where('session_type', $filters['session_type']);
        }

        if (isset($filters['created_by'])) {
            $query->where('created_by', $filters['created_by']);
        }

        if (isset($filters['start_date']) && isset($filters['end_date'])) {
            $query->whereBetween('start_time', [
                Carbon::parse($filters['start_date'])->startOfDay(),
                Carbon::parse($filters['end_date'])->endOfDay()
            ]);
        }

        // Exclude child sessions by default
        if (!isset($filters['include_instances'])) {
            $query->whereNull('parent_session_id');
        }

        $sessions = $query->get();

        // Calculate statistics for each session
        $sessionsWithStats = $sessions->map(function($session) {
            $totalAttendances = $session->attendances()->count();
            $verifiedAttendances = $session->attendances()->whereIn('status', ['present', 'late'])->count();
            
            return [
                'session' => $session,
                'total_registrations' => $session->registrations()->count(),
                'total_attendances' => $totalAttendances,
                'verified_attendances' => $verifiedAttendances,
                'pending_attendances' => $session->attendances()->where('status', 'pending')->count(),
                'attendance_rate' => $totalAttendances > 0 
                    ? round(($verifiedAttendances / $totalAttendances) * 100, 2)
                    : 0,
                'capacity_utilization' => $session->capacity 
                    ? round(($session->current_count / $session->capacity) * 100, 2)
                    : null,
            ];
        });

        // Overall summary
        $summary = [
            'total_sessions' => $sessions->count(),
            'active_sessions' => $sessions->where('status', 'active')->count(),
            'completed_sessions' => $sessions->where('status', 'completed')->count(),
            'draft_sessions' => $sessions->where('status', 'draft')->count(),
            'cancelled_sessions' => $sessions->where('status', 'cancelled')->count(),
            'average_attendance_rate' => $sessionsWithStats->avg('attendance_rate') ?? 0,
        ];

        return [
            'summary' => $summary,
            'data' => $sessionsWithStats,
            'filters' => $filters,
            'generated_at' => now(),
        ];
    }

    /**
     * Generate user attendance summary
     * 
     * @param int $userId
     * @param array $filters
     * @return array
     */
    public function generateUserSummary(int $userId, array $filters = []): array
    {
        $user = User::with(['organization', 'roles'])->findOrFail($userId);

        $query = Attendance::where('user_id', $userId)
            ->with('session')
            ->orderBy('verified_at', 'desc');

        // Apply date filter if provided
        if (isset($filters['start_date']) && isset($filters['end_date'])) {
            $query->whereBetween('verified_at', [
                Carbon::parse($filters['start_date'])->startOfDay(),
                Carbon::parse($filters['end_date'])->endOfDay()
            ]);
        }

        $attendances = $query->get();

        // Calculate statistics
        $summary = [
            'user' => $user,
            'total_sessions_attended' => $attendances->count(),
            'present_count' => $attendances->where('status', 'present')->count(),
            'late_count' => $attendances->where('status', 'late')->count(),
            'pending_count' => $attendances->where('status', 'pending')->count(),
            'rejected_count' => $attendances->where('status', 'rejected')->count(),
            'attendance_rate' => $attendances->count() > 0
                ? round(($attendances->whereIn('status', ['present', 'late'])->count() / $attendances->count()) * 100, 2)
                : 0,
            'average_face_score' => round($attendances->where('face_match', true)->avg('face_match_score') ?? 0, 2),
            'gps_verification_rate' => $attendances->count() > 0
                ? round(($attendances->where('gps_valid', true)->count() / $attendances->count()) * 100, 2)
                : 0,
        ];

        return [
            'summary' => $summary,
            'recent_attendances' => $attendances->take(10),
            'filters' => $filters,
            'generated_at' => now(),
        ];
    }

    /**
     * Get attendance trends over time
     * 
     * @param array $filters
     * @return array
     */
    public function getAttendanceTrends(array $filters = []): array
    {
        $startDate = isset($filters['start_date']) 
            ? Carbon::parse($filters['start_date'])
            : Carbon::now()->subDays(30);
        
        $endDate = isset($filters['end_date'])
            ? Carbon::parse($filters['end_date'])
            : Carbon::now();

        $groupBy = $filters['group_by'] ?? 'day'; // day, week, month

        $query = Attendance::whereBetween('verified_at', [$startDate, $endDate]);

        // Apply additional filters
        if (isset($filters['session_id'])) {
            $query->where('session_id', $filters['session_id']);
        }

        if (isset($filters['organization_id'])) {
            $query->whereHas('user', function($q) use ($filters) {
                $q->where('organization_id', $filters['organization_id']);
            });
        }

        // Group by date format
        $dateFormat = match($groupBy) {
            'week' => '%Y-%u',
            'month' => '%Y-%m',
            default => '%Y-%m-%d',
        };

        $trends = $query
            ->select(
                DB::raw("DATE_FORMAT(verified_at, '{$dateFormat}') as period"),
                DB::raw('COUNT(*) as total'),
                DB::raw("SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present"),
                DB::raw("SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late"),
                DB::raw("SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending"),
                DB::raw("SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected")
            )
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        // Format for chart consumption
        return [
            'labels' => $trends->pluck('period')->toArray(),
            'datasets' => [
                [
                    'label' => 'Present',
                    'data' => $trends->pluck('present')->toArray(),
                ],
                [
                    'label' => 'Late',
                    'data' => $trends->pluck('late')->toArray(),
                ],
                [
                    'label' => 'Pending',
                    'data' => $trends->pluck('pending')->toArray(),
                ],
                [
                    'label' => 'Rejected',
                    'data' => $trends->pluck('rejected')->toArray(),
                ],
            ],
            'filters' => $filters,
            'generated_at' => now(),
        ];
    }

    /**
     * Get session statistics
     * 
     * @param array $filters
     * @return array
     */
    public function getSessionStatistics(array $filters = []): array
    {
        $query = Session::query();

        // Apply date filter
        if (isset($filters['start_date']) && isset($filters['end_date'])) {
            $query->whereBetween('start_time', [
                Carbon::parse($filters['start_date'])->startOfDay(),
                Carbon::parse($filters['end_date'])->endOfDay()
            ]);
        }

        // Exclude child sessions
        $query->whereNull('parent_session_id');

        $sessions = $query->get();

        // Calculate statistics
        $stats = [
            'total_sessions' => $sessions->count(),
            'by_status' => [
                'draft' => $sessions->where('status', 'draft')->count(),
                'scheduled' => $sessions->where('status', 'scheduled')->count(),
                'active' => $sessions->where('status', 'active')->count(),
                'completed' => $sessions->where('status', 'completed')->count(),
                'cancelled' => $sessions->where('status', 'cancelled')->count(),
            ],
            'by_type' => [
                'admin_approved' => $sessions->where('session_type', 'admin_approved')->count(),
                'pre_registered' => $sessions->where('session_type', 'pre_registered')->count(),
                'open' => $sessions->where('session_type', 'open')->count(),
            ],
            'by_recurrence' => [
                'one_time' => $sessions->where('recurrence_type', 'one_time')->count(),
                'daily' => $sessions->where('recurrence_type', 'daily')->count(),
                'weekly' => $sessions->where('recurrence_type', 'weekly')->count(),
                'monthly' => $sessions->where('recurrence_type', 'monthly')->count(),
            ],
            'average_capacity_utilization' => round($sessions->where('capacity', '>', 0)->avg(function($session) {
                return ($session->current_count / $session->capacity) * 100;
            }) ?? 0, 2),
            'total_attendances' => Attendance::whereIn('session_id', $sessions->pluck('id'))->count(),
        ];

        return [
            'statistics' => $stats,
            'filters' => $filters,
            'generated_at' => now(),
        ];
    }

    /**
     * Format data for CSV export
     * 
     * @param array $data
     * @param string $type
     * @return array
     */
    public function formatForCSV(array $data, string $type): array
    {
        return match($type) {
            'attendance' => $this->formatAttendanceForCSV($data),
            'session' => $this->formatSessionForCSV($data),
            'user_summary' => $this->formatUserSummaryForCSV($data),
            default => [],
        };
    }

    /**
     * Format attendance data for CSV
     */
    private function formatAttendanceForCSV(array $reportData): array
    {
        $rows = [];
        
        // Header row
        $rows[] = [
            'ID',
            'User Name',
            'User Email',
            'Session Title',
            'Session Date',
            'Verified At',
            'Status',
            'Verification Method',
            'Face Match',
            'GPS Valid',
            'Distance (m)',
            'Approved By',
            'Notes'
        ];

        // Data rows
        foreach ($reportData['data'] as $attendance) {
            $rows[] = [
                $attendance->id,
                $attendance->user->name,
                $attendance->user->email,
                $attendance->session->title,
                $attendance->session->start_time->format('Y-m-d'),
                $attendance->verified_at->format('Y-m-d H:i:s'),
                $attendance->status,
                $attendance->verification_method,
                $attendance->face_match ? 'Yes' : 'No',
                $attendance->gps_valid ? 'Yes' : 'No',
                $attendance->distance_from_venue,
                $attendance->approver?->name ?? 'N/A',
                $attendance->admin_notes ?? ''
            ];
        }

        return $rows;
    }

    /**
     * Format session data for CSV
     */
    private function formatSessionForCSV(array $reportData): array
    {
        $rows = [];
        
        // Header row
        $rows[] = [
            'ID',
            'Title',
            'Type',
            'Status',
            'Start Time',
            'End Time',
            'Capacity',
            'Current Count',
            'Registrations',
            'Total Attendances',
            'Verified Attendances',
            'Attendance Rate (%)',
            'Created By'
        ];

        // Data rows
        foreach ($reportData['data'] as $item) {
            $session = $item['session'];
            $rows[] = [
                $session->id,
                $session->title,
                $session->session_type,
                $session->status,
                $session->start_time->format('Y-m-d H:i'),
                $session->end_time->format('Y-m-d H:i'),
                $session->capacity ?? 'N/A',
                $session->current_count,
                $item['total_registrations'],
                $item['total_attendances'],
                $item['verified_attendances'],
                $item['attendance_rate'],
                $session->creator->name
            ];
        }

        return $rows;
    }

    /**
     * Format user summary for CSV
     */
    private function formatUserSummaryForCSV(array $reportData): array
    {
        $summary = $reportData['summary'];
        $rows = [];
        
        // Summary section
        $rows[] = ['User Summary Report'];
        $rows[] = ['Name', $summary['user']->name];
        $rows[] = ['Email', $summary['user']->email];
        $rows[] = ['Organization', $summary['user']->organization?->name ?? 'N/A'];
        $rows[] = ['Total Sessions', $summary['total_sessions_attended']];
        $rows[] = ['Attendance Rate', $summary['attendance_rate'] . '%'];
        $rows[] = [];

        // Recent attendances header
        $rows[] = [
            'Session Title',
            'Date',
            'Verified At',
            'Status',
            'Face Score',
            'GPS Valid'
        ];

        // Recent attendances data
        foreach ($reportData['recent_attendances'] as $attendance) {
            $rows[] = [
                $attendance->session->title,
                $attendance->session->start_time->format('Y-m-d'),
                $attendance->verified_at->format('Y-m-d H:i:s'),
                $attendance->status,
                $attendance->face_match_score ?? 'N/A',
                $attendance->gps_valid ? 'Yes' : 'No'
            ];
        }

        return $rows;
    }
}
