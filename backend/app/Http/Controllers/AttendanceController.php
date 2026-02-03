<?php

namespace App\Http\Controllers;

use App\Http\Requests\AttendanceRequest;
use App\Http\Resources\AttendanceResource;
use App\Services\AttendanceService;
use App\Services\QRService;
use App\Services\FaceVerificationService;
use App\Services\LocationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    protected $attendanceService;
    protected $qrService;
    protected $faceService;
    protected $locationService;

    public function __construct(
        AttendanceService $attendanceService,
        QRService $qrService,
        FaceVerificationService $faceService,
        LocationService $locationService
    ) {
        $this->attendanceService = $attendanceService;
        $this->qrService = $qrService;
        $this->faceService = $faceService;
        $this->locationService = $locationService;
    }

    /**
     * Verify and mark attendance
     */
    public function verify(AttendanceRequest $request)
    {
        DB::beginTransaction();
        try {
            $user = auth()->user();
            $data = $request->all();
            $session = null;
            $qrCodeId = null;
            $faceMatchScore = null;
            $faceMatch = false;

            // 1. Validate QR Code (if provided)
            if (isset($data['qr_code'])) {
                $qrValidation = $this->qrService->validateQR(
                    $data['qr_code'],
                    $data['session_id']
                );

                if (!$qrValidation['valid']) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid or expired QR code'
                    ], 400);
                }

                $session = $qrValidation['session'];
                $qrCodeId = $qrValidation['qr_code_id'];
            } else {
                // If no QR code, get session directly
                $session = \App\Models\Session::findOrFail($data['session_id']);
            }

            // 2. Check for duplicate attendance (Disabled for demo - let admin approve/reject)
            // $existingAttendance = $this->attendanceService->checkDuplicate(
            //     $user->id,
            //     $data['session_id']
            // );

            // if ($existingAttendance) {
            //     return response()->json([
            //         'success' => false,
            //         'message' => 'Attendance already recorded for this session'
            //     ], 409);
            // }

            // 3. Face Verification (if provided)
            if (isset($data['face_descriptor'])) {
                $faceResult = $this->faceService->verifyFace(
                    $user->id,
                    $data['face_descriptor']
                );

                $faceMatchScore = $faceResult['score'];
                $faceMatch = $faceResult['match'];

                // STRICT: If face verification was attempted but failed, reject attendance
                if (!$faceMatch) {
                     return response()->json([
                        'success' => false,
                        'message' => 'Face verification failed. Match score: ' . round($faceMatchScore * 100, 1) . '%',
                        'data' => ['score' => $faceMatchScore]
                    ], 403);
                }
            }

            // 4. GPS Validation (Optional for demo)
            $locationResult = ['valid' => true, 'distance' => 0];

            if (isset($data['location']) && isset($data['location']['lat']) && isset($data['location']['lng'])) {
                $locationResult = $this->locationService->validateLocation(
                    $data['location']['lat'],
                    $data['location']['lng'],
                    $session->location_lat ?? 0,
                    $session->location_lng ?? 0,
                    $session->radius_meters ?? 10000
                );
            }

            // 5. Create Attendance Record (Always succeeds - pending admin approval)
            $attendance = $this->attendanceService->create([
                'user_id' => $user->id,
                'session_id' => $data['session_id'],
                'qr_code_id' => $qrCodeId,
                'verified_at' => now(),
                'face_match_score' => $faceMatchScore ?? 0,
                'face_match' => $faceMatch,
                'gps_valid' => $locationResult['valid'],
                'location_lat' => $data['location']['lat'] ?? null,
                'location_lng' => $data['location']['lng'] ?? null,
                'distance_from_venue' => $locationResult['distance'],
                'ip_address' => $request->ip(),
                'device_info' => [
                    'user_agent' => $request->userAgent(),
                    'platform' => $request->header('User-Agent')
                ],
                'webauthn_used' => isset($data['webauthn_credential_id']),
                'verification_method' => $faceMatch ? ($locationResult['valid'] ? 'qr_face_gps' : 'qr_face') : 'qr_only',
                'status' => 'pending' // Always pending for admin approval in demo mode
            ]);

            // 6. Log Location (if provided)
            if (isset($data['location']) && isset($data['location']['lat']) && isset($data['location']['lng'])) {
                $this->locationService->logLocation(
                    $user->id,
                    $data['session_id'],
                    $data['location']
                );
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Attendance submitted successfully! Pending admin approval.',
                'data' => [
                    'attendance_id' => $attendance->id,
                    'verified_at' => $attendance->verified_at,
                    'verification_method' => $attendance->verification_method,
                    'face_match_score' => $attendance->face_match_score,
                    'gps_valid' => $attendance->gps_valid,
                    'distance_from_venue' => $attendance->distance_from_venue,
                    'status' => 'pending',
                    'requires_approval' => true
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Attendance verification failed',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get user attendance history
     */
    public function history(Request $request)
    {
        $user = auth()->user();
        $filters = $request->only(['session_id', 'start_date', 'end_date']);

        $attendances = $this->attendanceService->getUserHistory($user->id, $filters);

        return response()->json([
            'success' => true,
            'data' => AttendanceResource::collection($attendances)
        ]);
    }

    /**
     * Get session attendance list (Admin/Manager only)
     */
    public function sessionAttendance($sessionId, Request $request)
    {
        $filters = $request->only(['status']);
        $attendances = $this->attendanceService->getSessionAttendance($sessionId, $filters);

        return response()->json([
            'success' => true,
            'data' => AttendanceResource::collection($attendances)
        ]);
    }
}

