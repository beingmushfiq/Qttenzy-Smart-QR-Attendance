<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Session;
use App\Models\User;
use App\Models\Organization;
use App\Models\QRCode;
use Carbon\Carbon;

class ActiveSessionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info("Seeding 5 Active Sessions...");

        // Fix: Use whereIn for role column instead of undefined scope
        $teacher = User::whereIn('role', ['teacher', 'session_manager', 'admin'])->first();
        $organization = Organization::first();

        if (!$teacher) {
            $this->command->warn('No suitable user found to create sessions.');
            return;
        }

        $now = Carbon::now();

        $sessionTopics = [
            'Advanced AI Architectures',
            'Cloud Native Deployment',
            'Cybersecurity Best Practices',
            'Modern Frontend Frameworks',
            'Technical Leadership Workshop'
        ];

        foreach ($sessionTopics as $index => $topic) {
            $session = Session::create([
                'title' => $topic,
                'description' => "A deep dive into $topic. Join us for a comprehensive session.",
                'organization_id' => $organization?->id,
                'start_time' => $now->copy()->addHours($index + 1), // Staggered start times
                'end_time' => $now->copy()->addHours($index + 3),
                'location_lat' => 23.7000 + ($index * 0.01), // Slightly different locations
                'location_lng' => 90.3000 + ($index * 0.01),
                'location_name' => "Room " . (101 + $index),
                'radius_meters' => 100,
                'session_type' => 'open',
                'status' => 'active',
                'requires_payment' => false,
                'recurrence_type' => 'one_time',
                'capacity' => 50,
                'current_count' => 0,
                'created_by' => $teacher->id,
            ]);

            // Create Active QR Code
            QRCode::create([
                'session_id' => $session->id,
                'code' => 'QR-' . strtoupper(bin2hex(random_bytes(8))),
                'is_active' => true,
                'expires_at' => $session->end_time,
            ]);

            $this->command->info("Created Active Session: $topic");
        }
    }
}
