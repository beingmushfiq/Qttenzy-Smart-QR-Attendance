<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

/**
 * Session Model
 * 
 * Represents a session/event where attendance can be marked.
 * Supports recurring sessions, capacity management, and multi-tenant organizations.
 */
class Session extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'organization_id',
        'title',
        'description',
        'start_time',
        'end_time',
        'location_lat',
        'location_lng',
        'location_name',
        'radius_meters',
        'session_type',
        'status',
        'requires_payment',
        'payment_amount',
        'max_attendees',
        'recurrence_type',
        'recurrence_end_date',
        'parent_session_id',
        'capacity',
        'current_count',
        'allow_entry_exit',
        'late_threshold_minutes',
        'created_by'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'recurrence_end_date' => 'date',
        'location_lat' => 'decimal:8',
        'location_lng' => 'decimal:8',
        'requires_payment' => 'boolean',
        'payment_amount' => 'decimal:2',
        'allow_entry_exit' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the organization this session belongs to.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the user who created this session.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the parent session (for recurring instances).
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function parentSession(): BelongsTo
    {
        return $this->belongsTo(Session::class, 'parent_session_id');
    }

    /**
     * Get child sessions (recurring instances).
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function childSessions(): HasMany
    {
        return $this->hasMany(Session::class, 'parent_session_id');
    }

    /**
     * Get QR codes for this session.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function qrCodes(): HasMany
    {
        return $this->hasMany(QRCode::class);
    }

    /**
     * Get attendance records for this session.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * Get payments for this session.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get registrations for this session.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }

    /**
     * Get the currently active QR code.
     * 
     * @return QRCode|null
     */
    public function activeQRCode()
    {
        return $this->qrCodes()
            ->where('is_active', true)
            ->where('expires_at', '>', now())
            ->latest()
            ->first();
    }

    /**
     * Check if session is currently active.
     * 
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->status === 'active' 
            && now() >= $this->start_time 
            && now() <= $this->end_time;
    }

    /**
     * Check if session is full (capacity reached).
     * 
     * @return bool
     */
    public function isFull(): bool
    {
        if (!$this->capacity) {
            return false;
        }
        
        return $this->current_count >= $this->capacity;
    }

    /**
     * Check if session is recurring.
     * 
     * @return bool
     */
    public function isRecurring(): bool
    {
        return $this->recurrence_type !== 'one_time';
    }

    /**
     * Get late threshold time.
     * 
     * @return Carbon
     */
    public function getLateThresholdTime(): Carbon
    {
        return $this->start_time->copy()->addMinutes($this->late_threshold_minutes);
    }

    /**
     * Determine attendance status based on verification time.
     * 
     * @param Carbon $verifiedAt
     * @return string
     */
    public function determineAttendanceStatus(Carbon $verifiedAt): string
    {
        $lateThreshold = $this->getLateThresholdTime();
        
        if ($verifiedAt->lte($this->start_time)) {
            return 'present';
        } elseif ($verifiedAt->lte($lateThreshold)) {
            return 'late';
        } else {
            return 'pending'; // Requires admin approval
        }
    }

    /**
     * Increment attendance count.
     * 
     * @return void
     */
    public function incrementAttendanceCount(): void
    {
        $this->increment('current_count');
    }

    /**
     * Decrement attendance count.
     * 
     * @return void
     */
    public function decrementAttendanceCount(): void
    {
        if ($this->current_count > 0) {
            $this->decrement('current_count');
        }
    }

    /**
     * Scope a query to only include active sessions.
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
                     ->where('start_time', '<=', now())
                     ->where('end_time', '>=', now());
    }

    /**
     * Scope a query to only include upcoming sessions.
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeUpcoming($query)
    {
        return $query->where('status', 'active')
                     ->where('start_time', '>', now());
    }

    /**
     * Scope a query to only include completed sessions.
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed')
                     ->orWhere(function ($q) {
                         $q->where('end_time', '<', now());
                     });
    }

    /**
     * Scope a query to filter by organization.
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $organizationId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInOrganization($query, int $organizationId)
    {
        return $query->where('organization_id', $organizationId);
    }

    /**
     * Scope a query to only include recurring sessions.
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRecurring($query)
    {
        return $query->where('recurrence_type', '!=', 'one_time');
    }

    /**
     * Scope a query to only include parent sessions (not instances).
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeParentOnly($query)
    {
        return $query->whereNull('parent_session_id');
    }
}

