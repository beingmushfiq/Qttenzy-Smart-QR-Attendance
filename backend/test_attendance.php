<?php

// Test script to check attendance data
// Run with: php test_attendance.php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Attendance Data Check ===\n\n";

// Count total attendances
$totalAttendances = \App\Models\Attendance::count();
echo "Total Attendances: $totalAttendances\n\n";

// Get first 5 attendances with relationships
$attendances = \App\Models\Attendance::with(['user', 'session'])
    ->limit(5)
    ->get();

echo "Sample Attendances:\n";
foreach ($attendances as $att) {
    echo sprintf(
        "ID: %d | User: %s | Session: %s | Status: %s\n",
        $att->id,
        $att->user->email ?? 'N/A',
        $att->session->title ?? 'N/A',
        $att->status
    );
}

echo "\n=== Testing getUserHistory ===\n\n";

// Get a student
$student = \App\Models\User::where('role', 'student')->first();
if ($student) {
    echo "Testing with student: {$student->email}\n";
    
    $service = new \App\Services\AttendanceService(
        new \App\Services\FaceVerificationService(),
        new \App\Services\LocationService(),
        new \App\Services\QRService()
    );
    
    $history = $service->getUserHistory($student->id);
    echo "Student's attendance count: " . $history->count() . "\n";
    
    foreach ($history as $att) {
        echo sprintf(
            "  - Session: %s | Status: %s | Date: %s\n",
            $att->session->title ?? 'N/A',
            $att->status,
            $att->verified_at->format('Y-m-d H:i')
        );
    }
} else {
    echo "No student found!\n";
}

echo "\n=== Done ===\n";
