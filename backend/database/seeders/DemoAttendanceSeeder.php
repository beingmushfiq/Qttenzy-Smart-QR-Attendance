<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Attendance;
use App\Models\Session;
use App\Models\User;
use App\Models\QRCode;
use App\Models\AttendanceLog;
use Carbon\Carbon;

/**
 * DemoAttendanceSeeder
 * 
 * Seeds the attendances table with comprehensive demo attendance records.
 * Creates various attendance states: present, late, absent, pending, rejected.
 */
class DemoAttendanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();
        
        // Get completed and active sessions
        $completedSession = Session::where('status', 'completed')
                                   ->where('start_time', '<', $now)
                                   ->first();
        
        $activeSession = Session::where('status', 'active')
                                ->where('start_time', '<=', $now)
                                ->where('end_time', '>=', $now)
                                ->first();

        if (!$completedSession || !$activeSession) {
            $this->command->warn('No sessions found. Please run DemoSessionSeeder first.');
            return;
        }

        // Get students and admins
        $students = User::withRole('student')->limit(15)->get();
        $admin = User::withRole('admin')->first();

        if ($students->isEmpty() || !$admin) {
            $this->command->warn('No students or admin found. Please run DemoUserSeeder first.');
            return;
        }

        // Get QR code for active session
        $qrCode = $activeSession->qrCodes()->first();
        if (!$qrCode) {
            $qrCode = QRCode::create([
                'session_id' => $activeSession->id,
                'code' => 'QR-' . strtoupper(bin2hex(random_bytes(8))),
                'is_active' => true,
                'expires_at' => $activeSession->end_time,
            ]);
        }

        // Scenario 1: Completed session with mixed attendance states
        foreach ($students->take(12) as $index => $student) {
            $verifiedAt = $completedSession->start_time->copy();
            $status = 'present';
            $faceMatchScore = rand(75, 98) / 100;
            $gpsValid = true;
            $distance = rand(5, 95);

            // Create different scenarios
            if ($index < 8) {
                // On-time attendance
                $verifiedAt->addMinutes(rand(-5, 5));
                $status = 'present';
            } elseif ($index < 10) {
                // Late attendance
                $verifiedAt->addMinutes(rand(16, 25));
                $status = 'late';
            } elseif ($index < 11) {
                // Very late (pending approval)
                $verifiedAt->addMinutes(rand(30, 45));
                $status = 'pending';
            } else {
                // Absent (no attendance record, but we'll create rejected for demo)
                $verifiedAt->addMinutes(rand(60, 90));
                $status = 'rejected';
            }

            $attendance = Attendance::updateOrCreate(
                [
                    'user_id' => $student->id,
                    'session_id' => $completedSession->id,
                ],
                [
                    'qr_code_id' => $completedSession->qrCodes()->first()?->id,
                    'verified_at' => $verifiedAt,
                    'face_match_score' => $faceMatchScore,
                    'face_match' => $faceMatchScore >= 0.7,
                    'gps_valid' => $gpsValid,
                    'location_lat' => $completedSession->location_lat + (rand(-10, 10) / 10000),
                    'location_lng' => $completedSession->location_lng + (rand(-10, 10) / 10000),
                    'distance_from_venue' => $distance,
                    'ip_address' => '192.168.1.' . rand(1, 254),
                    'device_info' => [
                        'platform' => 'Android',
                        'browser' => 'Chrome',
                        'version' => '120.0'
                    ],
                    'webauthn_used' => false,
                    'verification_method' => 'qr_face_gps',
                    'status' => $status,
                    'entry_type' => 'entry',
                    'approved_by' => in_array($status, ['late', 'rejected']) ? $admin->id : null,
                    'approved_at' => in_array($status, ['late', 'rejected']) ? $verifiedAt->copy()->addMinutes(5) : null,
                    'admin_notes' => $status === 'rejected' ? 'Too late for attendance' : null,
                    'rejection_reason' => $status === 'rejected' ? 'Arrived more than 1 hour late' : null,
                ]
            );

            // Create attendance log (only if newly created)
            if ($attendance->wasRecentlyCreated && in_array($status, ['late', 'rejected'])) {
                AttendanceLog::logChange(
                    $attendance,
                    $status === 'rejected' ? 'rejected' : 'approved',
                    'pending',
                    $status,
                    $status === 'rejected' ? 'Arrived more than 1 hour late' : 'Approved late attendance',
                    $admin->id
                );
            }
        }

        // Update session attendance count
        $completedSession->current_count = $completedSession->attendances()->approved()->count();
        $completedSession->save();

        // Scenario 2: Active session with ongoing attendance
        foreach ($students->take(8) as $index => $student) {
            $verifiedAt = $activeSession->start_time->copy()->addMinutes(rand(-10, 20));
            $status = $verifiedAt->lte($activeSession->getLateThresholdTime()) ? 'present' : 'late';
            
            $attendance = Attendance::updateOrCreate(
                [
                    'user_id' => $student->id,
                    'session_id' => $activeSession->id,
                ],
                [
                    'qr_code_id' => $qrCode->id,
                    'verified_at' => $verifiedAt,
                    'face_match_score' => rand(70, 99) / 100,
                    'face_match' => true,
                    'gps_valid' => true,
                    'location_lat' => $activeSession->location_lat + (rand(-5, 5) / 10000),
                    'location_lng' => $activeSession->location_lng + (rand(-5, 5) / 10000),
                    'distance_from_venue' => rand(10, 80),
                    'ip_address' => '192.168.1.' . rand(1, 254),
                    'device_info' => [
                        'platform' => rand(0, 1) ? 'Android' : 'iOS',
                        'browser' => 'Mobile Safari',
                        'version' => '17.0'
                    ],
                    'webauthn_used' => false,
                    'verification_method' => 'qr_face_gps',
                    'status' => $status,
                    'entry_type' => 'entry',
                ]
            );
        }

        // Update active session attendance count
        $activeSession->current_count = $activeSession->attendances()->approved()->count();
        $activeSession->save();

        // Scenario 3: Pending approval attendances (for admin demo)
        $pendingStudents = $students->skip(12)->take(3);
        foreach ($pendingStudents as $student) {
            $verifiedAt = $activeSession->start_time->copy()->addMinutes(rand(20, 40));
            
            Attendance::updateOrCreate(
                [
                    'user_id' => $student->id,
                    'session_id' => $activeSession->id,
                ],
                [
                    'qr_code_id' => $qrCode->id,
                    'verified_at' => $verifiedAt,
                    'face_match_score' => rand(65, 75) / 100,
                    'face_match' => true,
                    'gps_valid' => true,
                    'location_lat' => $activeSession->location_lat + (rand(-8, 8) / 10000),
                    'location_lng' => $activeSession->location_lng + (rand(-8, 8) / 10000),
                    'distance_from_venue' => rand(50, 95),
                    'ip_address' => '192.168.1.' . rand(1, 254),
                    'device_info' => [
                        'platform' => 'Android',
                        'browser' => 'Chrome',
                        'version' => '120.0'
                    ],
                    'webauthn_used' => false,
                    'verification_method' => 'qr_face_gps',
                    'status' => 'pending',
                    'entry_type' => 'entry',
                    'admin_notes' => null,
                ]
            );
        }

        $this->command->info('✓ Demo attendance records seeded successfully!');
        $this->command->info('  - Completed session: 12 attendance records');
        $this->command->info('    • 8 present, 2 late, 1 pending, 1 rejected');
        $this->command->info('  - Active session: 11 attendance records');
        $this->command->info('    • 8 approved, 3 pending approval');
    }
}
