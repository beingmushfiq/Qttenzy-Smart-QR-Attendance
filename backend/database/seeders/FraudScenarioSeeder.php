<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Attendance;
use App\Models\Session;
use App\Models\User;
use App\Models\AuditLog;
use Carbon\Carbon;

/**
 * FraudScenarioSeeder
 * 
 * Seeds fraud attempt scenarios for academic defense demonstration.
 * Creates various fraud scenarios: location spoofing, face mismatch, duplicate attempts.
 */
class FraudScenarioSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();
        
        // Get active session
        $activeSession = Session::where('status', 'active')
                                ->where('start_time', '<=', $now)
                                ->where('end_time', '>=', $now)
                                ->first();

        if (!$activeSession) {
            $this->command->warn('No active session found. Skipping fraud scenarios.');
            return;
        }

        // Get students
        $students = User::withRole('student')->skip(15)->limit(5)->get();
        $admin = User::withRole('admin')->first();

        if ($students->isEmpty()) {
            $this->command->warn('No students found for fraud scenarios.');
            return;
        }

        $qrCode = $activeSession->qrCodes()->first();

        // Fraud Scenario 1: Location Spoofing (GPS coordinates far from venue)
        $fraudStudent1 = $students->first();
        $attendance1 = Attendance::create([
            'user_id' => $fraudStudent1->id,
            'session_id' => $activeSession->id,
            'qr_code_id' => $qrCode?->id,
            'verified_at' => $activeSession->start_time->copy()->addMinutes(5),
            'face_match_score' => 0.82,
            'face_match' => true,
            'gps_valid' => false, // GPS validation failed
            'location_lat' => 23.8103, // Far from venue (different area of Dhaka)
            'location_lng' => 90.4125,
            'distance_from_venue' => 8500, // 8.5 km away
            'ip_address' => '103.92.84.' . rand(1, 254),
            'device_info' => [
                'platform' => 'Android',
                'browser' => 'Chrome',
                'version' => '120.0',
                'suspicious' => true
            ],
            'webauthn_used' => false,
            'verification_method' => 'qr_face_gps',
            'status' => 'rejected',
            'entry_type' => 'entry',
            'approved_by' => $admin?->id,
            'approved_at' => now(),
            'rejection_reason' => 'Location spoofing detected - GPS coordinates too far from venue',
            'admin_notes' => 'Automatic rejection: Distance from venue exceeds threshold (8.5km vs 100m allowed)',
        ]);

        // Log fraud attempt
        AuditLog::log(
            'fraud_attempt_location_spoofing',
            $attendance1,
            null,
            [
                'user_id' => $fraudStudent1->id,
                'session_id' => $activeSession->id,
                'distance_from_venue' => 8500,
                'allowed_radius' => $activeSession->radius_meters,
            ],
            'Location spoofing detected - user attempted to mark attendance from 8.5km away'
        );

        // Fraud Scenario 2: Face Mismatch (low confidence score)
        $fraudStudent2 = $students->skip(1)->first();
        $attendance2 = Attendance::create([
            'user_id' => $fraudStudent2->id,
            'session_id' => $activeSession->id,
            'qr_code_id' => $qrCode?->id,
            'verified_at' => $activeSession->start_time->copy()->addMinutes(8),
            'face_match_score' => 0.45, // Very low confidence
            'face_match' => false, // Face verification failed
            'gps_valid' => true,
            'location_lat' => $activeSession->location_lat + (rand(-3, 3) / 10000),
            'location_lng' => $activeSession->location_lng + (rand(-3, 3) / 10000),
            'distance_from_venue' => 45,
            'ip_address' => '192.168.1.' . rand(1, 254),
            'device_info' => [
                'platform' => 'Android',
                'browser' => 'Chrome',
                'version' => '120.0'
            ],
            'webauthn_used' => false,
            'verification_method' => 'qr_face_gps',
            'status' => 'rejected',
            'entry_type' => 'entry',
            'approved_by' => $admin?->id,
            'approved_at' => now(),
            'rejection_reason' => 'Face verification failed - confidence score too low (45%)',
            'admin_notes' => 'Possible impersonation attempt - face match score below threshold',
        ]);

        // Log fraud attempt
        AuditLog::log(
            'fraud_attempt_face_mismatch',
            $attendance2,
            null,
            [
                'user_id' => $fraudStudent2->id,
                'session_id' => $activeSession->id,
                'face_match_score' => 0.45,
                'required_threshold' => 0.7,
            ],
            'Face verification failed - possible impersonation attempt'
        );

        // Fraud Scenario 3: Duplicate Attendance Attempt
        $fraudStudent3 = $students->skip(2)->first();
        
        // First successful attendance
        $attendance3a = Attendance::create([
            'user_id' => $fraudStudent3->id,
            'session_id' => $activeSession->id,
            'qr_code_id' => $qrCode?->id,
            'verified_at' => $activeSession->start_time->copy()->addMinutes(2),
            'face_match_score' => 0.88,
            'face_match' => true,
            'gps_valid' => true,
            'location_lat' => $activeSession->location_lat + (rand(-2, 2) / 10000),
            'location_lng' => $activeSession->location_lng + (rand(-2, 2) / 10000),
            'distance_from_venue' => 35,
            'ip_address' => '192.168.1.100',
            'device_info' => [
                'platform' => 'iOS',
                'browser' => 'Safari',
                'version' => '17.0'
            ],
            'webauthn_used' => false,
            'verification_method' => 'qr_face_gps',
            'status' => 'present',
            'entry_type' => 'entry',
        ]);

        // Second attempt (duplicate) - should be rejected
        // Note: In production, this would be prevented by unique constraint
        // We're creating it here for demo purposes
        AuditLog::log(
            'fraud_attempt_duplicate_attendance',
            $attendance3a,
            null,
            [
                'user_id' => $fraudStudent3->id,
                'session_id' => $activeSession->id,
                'first_attempt_time' => $attendance3a->verified_at->toIso8601String(),
                'duplicate_attempt_time' => $activeSession->start_time->copy()->addMinutes(15)->toIso8601String(),
            ],
            'Duplicate attendance attempt detected - user already marked present'
        );

        // Fraud Scenario 4: Suspicious IP Address Pattern
        $fraudStudent4 = $students->skip(3)->first();
        $attendance4 = Attendance::create([
            'user_id' => $fraudStudent4->id,
            'session_id' => $activeSession->id,
            'qr_code_id' => $qrCode?->id,
            'verified_at' => $activeSession->start_time->copy()->addMinutes(12),
            'face_match_score' => 0.75,
            'face_match' => true,
            'gps_valid' => true,
            'location_lat' => $activeSession->location_lat + (rand(-4, 4) / 10000),
            'location_lng' => $activeSession->location_lng + (rand(-4, 4) / 10000),
            'distance_from_venue' => 65,
            'ip_address' => '10.0.0.1', // VPN/Proxy IP
            'device_info' => [
                'platform' => 'Android',
                'browser' => 'Chrome',
                'version' => '120.0',
                'vpn_detected' => true
            ],
            'webauthn_used' => false,
            'verification_method' => 'qr_face_gps',
            'status' => 'pending',
            'entry_type' => 'entry',
            'admin_notes' => 'Flagged for review - VPN/Proxy detected',
        ]);

        // Log suspicious activity
        AuditLog::log(
            'suspicious_activity_vpn_detected',
            $attendance4,
            null,
            [
                'user_id' => $fraudStudent4->id,
                'session_id' => $activeSession->id,
                'ip_address' => '10.0.0.1',
                'vpn_detected' => true,
            ],
            'VPN/Proxy usage detected - flagged for manual review'
        );

        // Fraud Scenario 5: Multiple Failed Attempts
        $fraudStudent5 = $students->skip(4)->first();
        
        // Log multiple failed attempts
        for ($i = 1; $i <= 3; $i++) {
            AuditLog::log(
                'failed_attendance_attempt',
                null,
                null,
                [
                    'user_id' => $fraudStudent5->id,
                    'session_id' => $activeSession->id,
                    'attempt_number' => $i,
                    'failure_reason' => $i === 1 ? 'Face match failed' : ($i === 2 ? 'GPS validation failed' : 'QR code expired'),
                    'timestamp' => $activeSession->start_time->copy()->addMinutes($i * 3)->toIso8601String(),
                ],
                "Failed attendance attempt #{$i}"
            );
        }

        $this->command->info('✓ Fraud scenarios seeded successfully!');
        $this->command->info('  - Location spoofing (8.5km away)');
        $this->command->info('  - Face mismatch (45% confidence)');
        $this->command->info('  - Duplicate attendance attempt');
        $this->command->info('  - VPN/Proxy detection');
        $this->command->info('  - Multiple failed attempts');
    }
}
