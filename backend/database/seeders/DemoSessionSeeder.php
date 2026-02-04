<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Session;
use App\Models\User;
use App\Models\Organization;
use App\Models\QRCode;
use Carbon\Carbon;

/**
 * DemoSessionSeeder
 * 
 * Seeds the sessions table with comprehensive demo sessions.
 * Creates various session types: one-time, recurring (daily, weekly), with different statuses.
 */
class DemoSessionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info("STARTING DemoSessionSeeder");
        
        // Get organizations and teachers
        $du = Organization::where('code', 'DU')->first();
        $buet = Organization::where('code', 'BUET')->first();
        $ctc = Organization::where('code', 'CTC')->first();

        $teachers = User::withRole('teacher')->orWhere(function ($q) {
            $q->withRole('session_manager');
        })->get();

        if ($teachers->isEmpty()) {
            $this->command->warn('No teachers found. Please run DemoUserSeeder first.');
            return;
        }

        $now = Carbon::now();

        // Helper closure to cleanup duplicates
        $cleanupDuplicates = function($title, $orgId) {
            $sessions = Session::where('title', $title)
                              ->where('organization_id', $orgId)
                              ->orderBy('id')
                              ->get();
            
            if ($sessions->count() > 1) {
                $this->command->info("  - Found {$sessions->count()} duplicates for '{$title}'. Keeping one, deleting rest.");
                // Keep the first one, delete the rest
                $sessions->skip(1)->each->forceDelete();
            }
        };

        // Session 1: Active session happening now (DU)
        $cleanupDuplicates('Advanced Database Systems - Lecture 5', $du?->id);
        $session1 = Session::updateOrCreate(
            ['title' => 'Advanced Database Systems - Lecture 5'],
            [
                'organization_id' => $du?->id,
                'description' => 'Covering database normalization, indexing strategies, and query optimization.',
                'start_time' => $now->copy()->subMinutes(30),
                'end_time' => $now->copy()->addMinutes(60),
                'location_lat' => 23.7340,
                'location_lng' => 90.3926,
                'location_name' => 'DU Science Building, Room 301',
                'radius_meters' => 100,
                'session_type' => 'open',
                'status' => 'active',
                'requires_payment' => false,
                'recurrence_type' => 'one_time',
                'capacity' => 50,
                'current_count' => 0,
                'allow_entry_exit' => false,
                'late_threshold_minutes' => 15,
                'created_by' => $teachers->first()->id,
            ]
        );

        // Generate QR code for active session
        if ($session1->qrCodes()->doesntExist()) {
            QRCode::create([
                'session_id' => $session1->id,
                'code' => 'QR-' . strtoupper(bin2hex(random_bytes(8))),
                'is_active' => true,
                'expires_at' => $session1->end_time,
            ]);
        }

        // Session 2: Upcoming session (BUET)
        $cleanupDuplicates('Software Engineering Principles', $buet?->id);
        $session2 = Session::updateOrCreate(
            ['title' => 'Software Engineering Principles'],
            [
                'organization_id' => $buet?->id,
                'description' => 'Design patterns, SOLID principles, and clean code practices.',
                'start_time' => $now->copy()->addHours(2),
                'end_time' => $now->copy()->addHours(4),
                'location_lat' => 23.7265,
                'location_lng' => 90.3925,
                'location_name' => 'BUET ECE Building, Auditorium',
                'radius_meters' => 150,
                'session_type' => 'open',
                'status' => 'active',
                'requires_payment' => false,
                'recurrence_type' => 'one_time',
                'capacity' => 100,
                'current_count' => 0,
                'allow_entry_exit' => true,
                'late_threshold_minutes' => 10,
                'created_by' => $teachers->skip(2)->first()?->id ?? $teachers->first()->id,
            ]
        );

        // Session 3: Completed session (yesterday)
        $cleanupDuplicates('Machine Learning Fundamentals', $du?->id);
        $session3 = Session::updateOrCreate(
            ['title' => 'Machine Learning Fundamentals'],
            [
                'organization_id' => $du?->id,
                'description' => 'Introduction to supervised and unsupervised learning algorithms.',
                'start_time' => $now->copy()->subDay()->setHour(10)->setMinute(0),
                'end_time' => $now->copy()->subDay()->setHour(12)->setMinute(0),
                'location_lat' => 23.7340,
                'location_lng' => 90.3926,
                'location_name' => 'DU Science Building, Room 205',
                'radius_meters' => 100,
                'session_type' => 'open',
                'status' => 'completed',
                'requires_payment' => false,
                'recurrence_type' => 'one_time',
                'capacity' => 40,
                'current_count' => 35,
                'allow_entry_exit' => false,
                'late_threshold_minutes' => 15,
                'created_by' => $teachers->first()->id,
            ]
        );

        // Session 4: Recurring daily session (parent)
        $cleanupDuplicates('Data Structures & Algorithms - Daily Practice', $buet?->id);
        $recurringDaily = Session::updateOrCreate(
            ['title' => 'Data Structures & Algorithms - Daily Practice'],
            [
                'organization_id' => $buet?->id,
                'description' => 'Daily problem-solving session for competitive programming.',
                'start_time' => $now->copy()->addDay()->setHour(16)->setMinute(0),
                'end_time' => $now->copy()->addDay()->setHour(18)->setMinute(0),
                'location_lat' => 23.7265,
                'location_lng' => 90.3925,
                'location_name' => 'BUET CSE Lab 3',
                'radius_meters' => 100,
                'session_type' => 'open',
                'status' => 'active',
                'requires_payment' => false,
                'recurrence_type' => 'daily',
                'recurrence_end_date' => $now->copy()->addDays(30),
                'capacity' => 30,
                'current_count' => 0,
                'allow_entry_exit' => false,
                'late_threshold_minutes' => 10,
                'created_by' => $teachers->skip(2)->first()?->id ?? $teachers->first()->id,
            ]
        );

        // Session 5: Recurring weekly session (parent)
        $cleanupDuplicates('Professional Development Workshop', $ctc?->id);
        $recurringWeekly = Session::updateOrCreate(
            ['title' => 'Professional Development Workshop'],
            [
                'organization_id' => $ctc?->id,
                'description' => 'Weekly workshop on leadership, communication, and team management.',
                'start_time' => $now->copy()->next('Monday')->setHour(14)->setMinute(0),
                'end_time' => $now->copy()->next('Monday')->setHour(17)->setMinute(0),
                'location_lat' => 23.7808,
                'location_lng' => 90.4217,
                'location_name' => 'CTC Training Hall A',
                'radius_meters' => 50,
                'session_type' => 'admin_approved',
                'status' => 'active',
                'requires_payment' => true,
                'payment_amount' => 500.00,
                'recurrence_type' => 'weekly',
                'recurrence_end_date' => $now->copy()->addWeeks(8),
                'capacity' => 25,
                'current_count' => 0,
                'allow_entry_exit' => true,
                'late_threshold_minutes' => 5,
                'created_by' => $teachers->skip(4)->first()?->id ?? $teachers->first()->id,
            ]
        );

        // Session 6: Pre-registered session with payment
        $cleanupDuplicates('Advanced Excel & Data Analytics', $ctc?->id);
        $session6 = Session::updateOrCreate(
            ['title' => 'Advanced Excel & Data Analytics'],
            [
                'organization_id' => $ctc?->id,
                'description' => 'Comprehensive training on Excel formulas, pivot tables, and data visualization.',
                'start_time' => $now->copy()->addDays(3)->setHour(9)->setMinute(0),
                'end_time' => $now->copy()->addDays(3)->setHour(13)->setMinute(0),
                'location_lat' => 23.7808,
                'location_lng' => 90.4217,
                'location_name' => 'CTC Computer Lab 2',
                'radius_meters' => 50,
                'session_type' => 'pre_registered',
                'status' => 'active',
                'requires_payment' => true,
                'payment_amount' => 1500.00,
                'recurrence_type' => 'one_time',
                'capacity' => 20,
                'current_count' => 0,
                'allow_entry_exit' => false,
                'late_threshold_minutes' => 5,
                'created_by' => $teachers->skip(4)->first()?->id ?? $teachers->first()->id,
            ]
        );

        // Session 7: Draft session (not yet published)
        $cleanupDuplicates('Blockchain Technology & Cryptocurrencies', $du?->id);
        $session7 = Session::updateOrCreate(
            ['title' => 'Blockchain Technology & Cryptocurrencies'],
            [
                'organization_id' => $du?->id,
                'description' => 'Understanding blockchain fundamentals, smart contracts, and DeFi.',
                'start_time' => $now->copy()->addWeek()->setHour(14)->setMinute(0),
                'end_time' => $now->copy()->addWeek()->setHour(16)->setMinute(0),
                'location_lat' => 23.7340,
                'location_lng' => 90.3926,
                'location_name' => 'DU Business Building, Seminar Room',
                'radius_meters' => 100,
                'session_type' => 'open',
                'status' => 'draft',
                'requires_payment' => false,
                'recurrence_type' => 'one_time',
                'capacity' => 60,
                'current_count' => 0,
                'allow_entry_exit' => false,
                'late_threshold_minutes' => 15,
                'created_by' => $teachers->skip(1)->first()?->id ?? $teachers->first()->id,
            ]
        );

        // Session 8: Cancelled session
        $cleanupDuplicates('Network Security - CANCELLED', $buet?->id);
        $session8 = Session::updateOrCreate(
            ['title' => 'Network Security - CANCELLED'],
            [
                'organization_id' => $buet?->id,
                'description' => 'This session has been cancelled due to unforeseen circumstances.',
                'start_time' => $now->copy()->addDays(2)->setHour(10)->setMinute(0),
                'end_time' => $now->copy()->addDays(2)->setHour(12)->setMinute(0),
                'location_lat' => 23.7265,
                'location_lng' => 90.3925,
                'location_name' => 'BUET ECE Building, Room 404',
                'radius_meters' => 100,
                'session_type' => 'open',
                'status' => 'cancelled',
                'requires_payment' => false,
                'recurrence_type' => 'one_time',
                'capacity' => 35,
                'current_count' => 0,
                'allow_entry_exit' => false,
                'late_threshold_minutes' => 10,
                'created_by' => $teachers->skip(3)->first()?->id ?? $teachers->first()->id,
            ]
        );

        // Session 9: Full capacity session
        $cleanupDuplicates('Digital Marketing Masterclass - FULL', $ctc?->id);
        $session9 = Session::updateOrCreate(
            ['title' => 'Digital Marketing Masterclass - FULL'],
            [
                'organization_id' => $ctc?->id,
                'description' => 'SEO, SEM, social media marketing, and content strategy.',
                'start_time' => $now->copy()->addDays(5)->setHour(10)->setMinute(0),
                'end_time' => $now->copy()->addDays(5)->setHour(16)->setMinute(0),
                'location_lat' => 23.7808,
                'location_lng' => 90.4217,
                'location_name' => 'CTC Main Hall',
                'radius_meters' => 50,
                'session_type' => 'pre_registered',
                'status' => 'active',
                'requires_payment' => true,
                'payment_amount' => 2000.00,
                'recurrence_type' => 'one_time',
                'capacity' => 15,
                'current_count' => 15, // Full
                'allow_entry_exit' => false,
                'late_threshold_minutes' => 5,
                'created_by' => $teachers->skip(4)->first()?->id ?? $teachers->first()->id,
            ]
        );

        // Session 10: Past completed session (for attendance history)
        $cleanupDuplicates('Web Development Bootcamp - Day 1', $du?->id);
        $session10 = Session::updateOrCreate(
            ['title' => 'Web Development Bootcamp - Day 1'],
            [
                'organization_id' => $du?->id,
                'description' => 'HTML, CSS, JavaScript fundamentals.',
                'start_time' => $now->copy()->subDays(3)->setHour(9)->setMinute(0),
                'end_time' => $now->copy()->subDays(3)->setHour(17)->setMinute(0),
                'location_lat' => 23.7340,
                'location_lng' => 90.3926,
                'location_name' => 'DU Computer Lab 1',
                'radius_meters' => 100,
                'session_type' => 'open',
                'status' => 'completed',
                'requires_payment' => false,
                'recurrence_type' => 'one_time',
                'capacity' => 30,
                'current_count' => 28,
                'allow_entry_exit' => true,
                'late_threshold_minutes' => 15,
                'created_by' => $teachers->first()->id,
            ]
        );

        // Generate QR codes for all sessions
        $sessions = [$session1, $session2, $session3, $recurringDaily, $recurringWeekly, $session6, $session7, $session8, $session9, $session10];
        foreach ($sessions as $session) {
            if ($session->qrCodes()->doesntExist()) {
                QRCode::create([
                    'session_id' => $session->id,
                    'code' => 'QR-' . strtoupper(bin2hex(random_bytes(8))),
                    'is_active' => $session->status === 'active',
                    'expires_at' => $session->end_time,
                ]);
            }
        }

        $this->command->info('✓ Demo sessions seeded successfully!');
        $this->command->info('  - 10 sessions created/updated');

        // --- MERGED FROM ActiveSessionSeeder ---
        $this->command->info("Seeding Additional Active Sessions for ALL Organizations...");

        $organizations = Organization::all();

        // Find a fallback creator
        $fallbackCreator = User::whereIn('role', ['teacher', 'session_manager', 'admin', 'organization_admin'])->first();
        if (!$fallbackCreator) {
             // In DemoSessionSeeder we likely already have users, but keeping safety check
             $fallbackCreator = $teachers->first(); 
        }

        $sessionTopics = [
            'Advanced AI Architectures',
            'Cloud Native Deployment',
            'Cybersecurity Best Practices',
            'Modern Frontend Frameworks',
            'Technical Leadership Workshop',
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
            $this->command->info("Seeding extra sessions for Org: " . $organization->name);
            
            // Try to find a user IN this org, else use fallback
            $creator = User::where('organization_id', $organization->id)
                           ->whereIn('role', ['teacher', 'session_manager', 'organization_admin'])
                           ->first() ?? $fallbackCreator;

            if (!$creator) {
                continue; 
            }

            foreach ($sessionTopics as $index => $topic) {
                $cleanupDuplicates($topic, $organization->id);
                
                $sessionDate = Carbon::now();
                
                $session = Session::updateOrCreate(
                    [
                        'title' => $topic,
                        'organization_id' => $organization->id
                    ],
                    [
                        'description' => "A deep dive into $topic. Join us for a comprehensive session.",
                        'start_time' => $sessionDate->copy()->addHours($index + 1), // Staggered start times
                        'end_time' => $sessionDate->copy()->addHours($index + 3),
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
                    ]
                );

                // Create Active QR Code
                if ($session->qrCodes()->doesntExist()) {
                    QRCode::create([
                        'session_id' => $session->id,
                        'code' => 'QR-' . strtoupper(bin2hex(random_bytes(8))),
                        'is_active' => true,
                        'expires_at' => $session->end_time,
                    ]);
                }

                $this->command->info("  Created/Updated Extra Active Session: $topic");
            }
        }
    }
}
