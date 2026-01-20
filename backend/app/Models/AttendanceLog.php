<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * AttendanceLog Model
 * 
 * Tracks all changes to attendance records.
 * Provides an audit trail for attendance approvals, rejections, and modifications.
 */
class AttendanceLog extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'attendance_id',
        'user_id',
        'action',
        'old_status',
        'new_status',
        'notes',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the attendance record this log belongs to.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    /**
     * Get the user who made the change.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only include logs for a specific attendance.
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $attendanceId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForAttendance($query, int $attendanceId)
    {
        return $query->where('attendance_id', $attendanceId);
    }

    /**
     * Scope a query to only include logs for a specific action.
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $action
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Create an attendance log entry.
     * 
     * @param Attendance $attendance
     * @param string $action
     * @param string|null $oldStatus
     * @param string|null $newStatus
     * @param string|null $notes
     * @return static
     */
    public static function logChange(
        Attendance $attendance,
        string $action,
        ?string $oldStatus = null,
        ?string $newStatus = null,
        ?string $notes = null,
        ?int $userId = null
    ): self {
        return static::create([
            'attendance_id' => $attendance->id,
            'user_id' => $userId ?? auth()->id(),
            'action' => $action,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'notes' => $notes,
            'metadata' => [
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }
}
