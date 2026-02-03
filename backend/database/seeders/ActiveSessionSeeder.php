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
        $this->command->info("Seeding Active Sessions for ALL Organizations...");

        $organizations = Organization::all();

        if ($organizations->isEmpty()) {
            $this->command->warn('No organizations found. Creating one...');
            $organization = Organization::create([
                'name' => 'Demo Organization',
                'slug' => 'demo-org',
                'status' => 'active'
            ]);
            $organizations = collect([$organization]);
        }

        // Find a fallback creator
        $fallbackCreator = User::whereIn('role', ['teacher', 'session_manager', 'admin', 'organization_admin'])->first();
        if (!$fallbackCreator) {
             $this->command->warn('No suitable user found. Creating a temporary admin.');
             $fallbackCreator = User::create([
                 'name' => 'Seeder Admin',
                 'email' => 'seeder@example.com',
                 'password' => bcrypt('password'),
                 'role' => 'admin',
                 'is_active' => true
             ]);
        }

        $sessionTopics = [
            'Advanced AI Architectures',
            'Cloud Native Deployment',
            'Cybersecurity Best Practices',
            'Modern Frontend Frameworks',
            'Technical Leadership Workshop',
            // Added 10 more topics as requested
            'Database Optimization Techniques',
            'Mobile App Development with React Native',
            'DevOps and CI/CD Pipelines',
            'Introduction to Machine Learning',
            'Blockchain Fundamentals',
            'UX/UI Design Principles',
            'Agile Project Management',
            'Serverless Computing',
            'Data Science with Python',
            'Internet of Things (IoT) Security'
        ];

        foreach ($organizations as $organization) {
            $this->command->info("Seeding for Org: " . $organization->name);
            
            // Try to find a user IN this org, else use fallback
            $creator = User::where('organization_id', $organization->id)
                           ->whereIn('role', ['teacher', 'session_manager', 'organization_admin'])
                           ->first() ?? $fallbackCreator;

            foreach ($sessionTopics as $index => $topic) {
                // Check if session already exists to avoid duplicates if re-run
                $exists = Session::where('organization_id', $organization->id)
                                 ->where('title', $topic)
                                 ->exists();
                
                if ($exists) {
                    continue;
                }

                $now = Carbon::now();
                
                $session = Session::create([
                    'title' => $topic,
                    'description' => "A deep dive into $topic. Join us for a comprehensive session.",
                    'organization_id' => $organization->id,
                    'start_time' => $now->copy()->addHours($index + 1), // Staggered start times
                    'end_time' => $now->copy()->addHours($index + 3),
                    'location_lat' => 23.7000 + ($index * 0.01),
                    'location_lng' => 90.3000 + ($index * 0.01),
                    'location_name' => "Room " . (101 + $index),
                    'radius_meters' => 100,
                    'session_type' => 'open',
                    'status' => 'active',
                    'requires_payment' => false,
                    'recurrence_type' => 'one_time',
                    'capacity' => 50,
                    'current_count' => 0,
                    'created_by' => $creator->id,
                ]);

                // Create Active QR Code
                QRCode::create([
                    'session_id' => $session->id,
                    'code' => 'QR-' . strtoupper(bin2hex(random_bytes(8))),
                    'is_active' => true,
                    'expires_at' => $session->end_time,
                ]);

                $this->command->info("  Created Active Session: $topic");
            }
        }
    }
}
