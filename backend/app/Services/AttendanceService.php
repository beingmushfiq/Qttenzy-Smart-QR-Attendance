<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Session;
use App\Models\User;
use App\Models\AttendanceLog;
use App\Models\AuditLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * AttendanceService
 * 
 * Handles attendance verification with multi-factor authentication.
 * Combines QR code, face recognition, GPS, and optional WebAuthn verification.
 */
class AttendanceService
{
    protected $faceService;
    protected $locationService;
    protected $qrService;

    public function __construct(
        FaceVerificationService $faceService,
        LocationService $locationService,
        QRService $qrService
    ) {
        $this->faceService = $faceService;
        $this->locationService = $locationService;
        $this->qrService = $qrService;
    }

    /**
     * Verify and create attendance record
     * 
     * @param array $data Verification data
     * @return array Result with attendance record or error
     */
    public function verifyAndCreateAttendance(array $data): array
    {
        DB::beginTransaction();
        
        try {
            // Extract data
            $userId = $data['user_id'];
            $sessionId = $data['session_id'];
            $qrCode = $data['qr_code'];
            $faceDescriptor = $data['face_descriptor'] ?? null;
            $location = $data['location'] ?? null;
            $webauthnUsed = $data['webauthn_used'] ?? false;

            // Step 1: Validate QR Code
            $qrValidation = $this->qrService->validateQR($qrCode, $sessionId);
            if (!$qrValidation['valid']) {
                return [
                    'success' => false,
                    'message' => $qrValidation['message'],
                ];
            }

            $session = Session::findOrFail($sessionId);
            $user = User::findOrFail($userId);

            // Step 2: Check for duplicate attendance
            $existing = Attendance::where('user_id', $userId)
                ->where('session_id', $sessionId)
                ->first();

            if ($existing) {
                // Log fraud attempt
                AuditLog::log(
                    'fraud_attempt_duplicate_attendance',
                    $existing,
                    null,
                    [
                        'user_id' => $userId,
                        'session_id' => $sessionId,
                        'existing_attendance_id' => $existing->id,
                    ],
                    'Duplicate attendance attempt detected'
                );

                return [
                    'success' => false,
                    'message' => 'Attendance already marked for this session',
                ];
            }

            // Step 3: Verify Face (if descriptor provided)
            $faceMatch = false;
            $faceMatchScore = 0;
            
            if ($faceDescriptor) {
                $faceResult = $this->faceService->verifyFace($userId, $faceDescriptor);
                $faceMatch = $faceResult['match'];
                $faceMatchScore = $faceResult['score'];

                if (!$faceMatch) {
                    // Log fraud attempt
                    AuditLog::log(
                        'fraud_attempt_face_mismatch',
                        null,
                        null,
                        [
                            'user_id' => $userId,
                            'session_id' => $sessionId,
                            'face_match_score' => $faceMatchScore,
                            'threshold' => $faceResult['threshold'],
                        ],
                        'Face verification failed - possible impersonation attempt'
                    );
                }
            }

            // Step 4: Verify GPS Location (if location provided)
            $gpsValid = false;
            $distanceFromVenue = null;
            $locationLat = null;
            $locationLng = null;

            if ($location) {
                $locationResult = $this->locationService->validateLocation(
                    $location['latitude'],
                    $location['longitude'],
                    $session->location_lat,
                    $session->location_lng,
                    $session->radius_meters
                );

                $gpsValid = $locationResult['valid'];
                $distanceFromVenue = $locationResult['distance'];
                $locationLat = $location['latitude'];
                $locationLng = $location['longitude'];

                if (!$gpsValid) {
                    // Log fraud attempt
                    AuditLog::log(
                        'fraud_attempt_location_spoofing',
                        null,
                        null,
                        [
                            'user_id' => $userId,
                            'session_id' => $sessionId,
                            'distance_from_venue' => $distanceFromVenue,
                            'allowed_radius' => $session->radius_meters,
                        ],
                        'Location validation failed - possible location spoofing'
                    );
                }
            }

            // Step 5: Determine verification method
            $verificationMethod = $this->determineVerificationMethod([
                'qr' => true,
                'face' => $faceDescriptor !== null,
                'gps' => $location !== null,
                'webauthn' => $webauthnUsed,
            ]);

            // Step 6: Determine attendance status
            $verifiedAt = Carbon::now();
            $status = $this->determineAttendanceStatus(
                $session,
                $verifiedAt,
                $faceMatch,
                $gpsValid
            );

            // Step 7: Create attendance record
            $attendance = Attendance::create([
                'user_id' => $userId,
                'session_id' => $sessionId,
                'qr_code_id' => $qrValidation['qr_code_id'],
                'verified_at' => $verifiedAt,
                'face_match_score' => $faceMatchScore,
                'face_match' => $faceMatch,
                'gps_valid' => $gpsValid,
                'location_lat' => $locationLat,
                'location_lng' => $locationLng,
                'distance_from_venue' => $distanceFromVenue,
                'ip_address' => request()->ip(),
                'device_info' => [
                    'user_agent' => request()->userAgent(),
                    'platform' => $data['platform'] ?? 'unknown',
                ],
                'webauthn_used' => $webauthnUsed,
                'verification_method' => $verificationMethod,
                'status' => $status,
                'entry_type' => 'entry',
            ]);

            // Step 8: Update session attendance count if approved
            if (in_array($status, ['present', 'late'])) {
                $session->incrementAttendanceCount();
            }

            // Step 9: Log attendance creation
            AttendanceLog::logChange(
                $attendance,
                'created',
                null,
                $status,
                'Attendance marked via ' . $verificationMethod
            );

            AuditLog::log(
                'attendance_marked',
                $attendance,
                null,
                [
                    'user_id' => $userId,
                    'session_id' => $sessionId,
                    'status' => $status,
                    'verification_method' => $verificationMethod,
                ],
                'Attendance marked successfully'
            );

            DB::commit();

            return [
                'success' => true,
                'message' => $this->getStatusMessage($status),
                'attendance' => $attendance,
                'status' => $status,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Attendance verification failed', [
                'user_id' => $data['user_id'] ?? null,
                'session_id' => $data['session_id'] ?? null,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Attendance verification failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Determine attendance status based on verification time and factors
     * 
     * @param Session $session
     * @param Carbon $verifiedAt
     * @param bool $faceMatch
     * @param bool $gpsValid
     * @return string Status (present, late, pending, rejected)
     */
    private function determineAttendanceStatus(
        Session $session,
        Carbon $verifiedAt,
        bool $faceMatch,
        bool $gpsValid
    ): string {
        // STRICT: All attendance is PENDING until approved by admin
        // No auto-approval based on face/gps.
        
        // Exception: If an Admin is marking attendance (unlikely via this flow, but safe to add)
        if (auth()->check() && (auth()->user()->isAdmin() || auth()->user()->isSuperAdmin())) {
            return 'present';
        }

        return 'pending';
    }

    /**
     * Determine verification method string
     * 
     * @param array $methods
     * @return string
     */
    private function determineVerificationMethod(array $methods): string
    {
        $usedMethods = [];
        
        if ($methods['qr']) $usedMethods[] = 'qr';
        if ($methods['face']) $usedMethods[] = 'face';
        if ($methods['gps']) $usedMethods[] = 'gps';
        if ($methods['webauthn']) $usedMethods[] = 'webauthn';

        return implode('_', $usedMethods);
    }

    /**
     * Get user-friendly status message
     * 
     * @param string $status
     * @return string
     */
    private function getStatusMessage(string $status): string
    {
        return match($status) {
            'present' => 'Attendance marked successfully!',
            'late' => 'Attendance marked as late.',
            'pending' => 'Attendance submitted and pending admin approval.',
            'rejected' => 'Attendance rejected. Please contact your instructor.',
            default => 'Attendance processed.',
        };
    }

    /**
     * Approve pending attendance
     * 
     * @param int $attendanceId
     * @param int $approvedBy
     * @param string $status
     * @param string|null $notes
     * @return bool
     */
    public function approveAttendance(int $attendanceId, int $approvedBy, string $status, ?string $notes = null): bool
    {
        $attendance = Attendance::findOrFail($attendanceId);
        
        if (!$attendance->isPending()) {
            throw new \Exception('Only pending attendances can be approved');
        }

        $attendance->approve($status, $approvedBy, $notes);

        // Update session count if approved
        if (in_array($status, ['present', 'late'])) {
            $attendance->session->incrementAttendanceCount();
        }

        return true;
    }

    /**
     * Reject pending attendance
     * 
     * @param int $attendanceId
     * @param int $rejectedBy
     * @param string $reason
     * @return bool
     */
    public function rejectAttendance(int $attendanceId, int $rejectedBy, string $reason): bool
    {
        $attendance = Attendance::findOrFail($attendanceId);
        
        if (!$attendance->isPending()) {
            throw new \Exception('Only pending attendances can be rejected');
        }

        $attendance->reject($rejectedBy, $reason);

        return true;
    }

    /**
     * Get user attendance history
     * 
     * @param int $userId
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUserHistory(int $userId, array $filters = [])
    {
        $query = Attendance::where('user_id', $userId)
            ->with(['session', 'approver']);

        if (isset($filters['session_id'])) {
            $query->where('session_id', $filters['session_id']);
        }

        if (isset($filters['start_date'])) {
            $query->where('verified_at', '>=', $filters['start_date']);
        }

        if (isset($filters['end_date'])) {
            $query->where('verified_at', '<=', $filters['end_date']);
        }

        return $query->orderBy('verified_at', 'desc')->get();
    }

    /**
     * Get session attendance list
     * 
     * @param int $sessionId
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getSessionAttendance(int $sessionId, array $filters = [])
    {
        $query = Attendance::where('session_id', $sessionId)
            ->with(['user', 'approver']);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderBy('verified_at', 'desc')->get();
    }

    /**
     * Check for duplicate attendance
     * 
     * @param int $userId
     * @param int $sessionId
     * @return Attendance|null
     */
    public function checkDuplicate(int $userId, int $sessionId)
    {
        return Attendance::where('user_id', $userId)
            ->where('session_id', $sessionId)
            ->first();
    }

    /**
     * Create attendance record
     * 
     * @param array $data
     * @return Attendance
     */
    public function create(array $data)
    {
        return Attendance::create($data);
    }

    /**
     * Determine verification method
     * 
     * @param array $data
     * @return string
     */
    public function determineMethod(array $data)
    {
        $methods = [];
        
        if (isset($data['qr_code'])) {
            $methods[] = 'QR';
        }
        
        if (isset($data['face_descriptor'])) {
            $methods[] = 'Face';
        }
        
        if (isset($data['location'])) {
            $methods[] = 'GPS';
        }
        
        if (isset($data['webauthn_credential_id'])) {
            $methods[] = 'WebAuthn';
        }
        
        return implode('+', $methods);
    }
}

