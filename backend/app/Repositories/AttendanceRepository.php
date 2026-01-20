<?php

namespace App\Repositories;

use App\Models\Attendance;
use Illuminate\Database\Eloquent\Collection;

class AttendanceRepository
{
    /**
     * Create attendance record
     */
    public function create(array $data): Attendance
    {
        return Attendance::create($data);
    }

    /**
     * Find attendance by user and session
     */
    public function findByUserAndSession(int $userId, int $sessionId): ?Attendance
    {
        return Attendance::where('user_id', $userId)
            ->where('session_id', $sessionId)
            ->first();
    }

    /**
     * Get user attendance history
     */
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

    /**
     * Get session attendance list
     */
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

