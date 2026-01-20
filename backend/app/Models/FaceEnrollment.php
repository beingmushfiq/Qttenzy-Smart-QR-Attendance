<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * FaceEnrollment Model
 * 
 * Stores encrypted face biometric data for user verification.
 * All face descriptors are AES encrypted and never stored in plain text.
 */
class FaceEnrollment extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'encrypted_descriptor',
        'encryption_key_id',
        'image_path',
        'confidence_threshold',
        'verification_count',
        'last_verified_at',
        'requires_reverification',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'confidence_threshold' => 'decimal:2',
        'verification_count' => 'integer',
        'last_verified_at' => 'datetime',
        'requires_reverification' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'encrypted_descriptor', // Never expose encrypted biometric data
    ];

    /**
     * Get the user this enrollment belongs to.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if enrollment is active (not requiring reverification).
     * 
     * @return bool
     */
    public function isActive(): bool
    {
        return !$this->requires_reverification;
    }

    /**
     * Check if enrollment needs reverification.
     * 
     * @return bool
     */
    public function needsReverification(): bool
    {
        return $this->requires_reverification;
    }
}


