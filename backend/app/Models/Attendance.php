<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Attendance Model
 * 
 * Represents an attendance record for a user in a session.
 * Supports multi-factor verification (QR, Face, GPS, WebAuthn).
 */
class Attendance extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'session_id',
        'qr_code_id',
        'verified_at',
        'face_match_score',
        'face_match',
        'gps_valid',
        'location_lat',
        'location_lng',
        'distance_from_venue',
        'ip_address',
        'device_info',
        'webauthn_used',
        'verification_method',
        'status',
        'entry_type',
        'exit_time',
        'admin_notes',
        'approved_by',
        'approved_at',
        'rejection_reason'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'verified_at' => 'datetime',
        'exit_time' => 'datetime',
        'approved_at' => 'datetime',
        'face_match_score' => 'decimal:2',
        'distance_from_venue' => 'decimal:2',
        'face_match' => 'boolean',
        'gps_valid' => 'boolean',
        'webauthn_used' => 'boolean',
        'device_info' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user who marked this attendance.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the session this attendance belongs to.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class);
    }

    /**
     * Get the QR code used for this attendance.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function qrCode(): BelongsTo
    {
        return $this->belongsTo(QRCode::class);
    }

    /**
     * Get the admin who approved/rejected this attendance.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get attendance logs for this record.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function logs(): HasMany
    {
        return $this->hasMany(AttendanceLog::class);
    }

    /**
     * Check if attendance is approved.
     * 
     * @return bool
     */
    public function isApproved(): bool
    {
        return in_array($this->status, ['present', 'late']);
    }

    /**
     * Check if attendance is pending approval.
     * 
     * @return bool
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if attendance is rejected.
     * 
     * @return bool
     */
    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    /**
     * Check if this is an entry attendance.
     * 
     * @return bool
     */
    public function isEntry(): bool
    {
        return $this->entry_type === 'entry';
    }

    /**
     * Check if this is an exit attendance.
     * 
     * @return bool
     */
    public function isExit(): bool
    {
        return $this->entry_type === 'exit';
    }

    /**
     * Calculate duration (for entry/exit tracking).
     * 
     * @return int|null Duration in minutes
     */
    public function getDurationMinutes(): ?int
    {
        if (!$this->exit_time || !$this->verified_at) {
            return null;
        }
        
        return $this->verified_at->diffInMinutes($this->exit_time);
    }

    /**
     * Approve this attendance.
     * 
     * @param string $status
     * @param int $approvedBy
     * @param string|null $notes
     * @return void
     */
    public function approve(string $status, int $approvedBy, ?string $notes = null): void
    {
        $oldStatus = $this->status;
        
        $this->update([
            'status' => $status,
            'approved_by' => $approvedBy,
            'approved_at' => now(),
            'admin_notes' => $notes,
        ]);

        // Log the approval
        AttendanceLog::logChange(
            $this,
            'approved',
            $oldStatus,
            $status,
            $notes
        );
    }

    /**
     * Reject this attendance.
     * 
     * @param int $rejectedBy
     * @param string $reason
     * @return void
     */
    public function reject(int $rejectedBy, string $reason): void
    {
        $oldStatus = $this->status;
        
        $this->update([
            'status' => 'rejected',
            'approved_by' => $rejectedBy,
            'approved_at' => now(),
            'rejection_reason' => $reason,
            'admin_notes' => $reason,
        ]);

        // Log the rejection
        AttendanceLog::logChange(
            $this,
            'rejected',
            $oldStatus,
            'rejected',
            $reason
        );
    }

    /**
     * Scope a query to only include approved attendances.
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeApproved($query)
    {
        return $query->whereIn('status', ['present', 'late']);
    }

    /**
     * Scope a query to only include pending attendances.
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope a query to only include rejected attendances.
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Scope a query to filter by status.
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $status
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to filter by session.
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $sessionId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForSession($query, int $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }

    /**
     * Scope a query to filter by user.
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope a query to only include entry attendances.
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeEntries($query)
    {
        return $query->where('entry_type', 'entry');
    }

    /**
     * Scope a query to only include exit attendances.
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeExits($query)
    {
        return $query->where('entry_type', 'exit');
    }
}

