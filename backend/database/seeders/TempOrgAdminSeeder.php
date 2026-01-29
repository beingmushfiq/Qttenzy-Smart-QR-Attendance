<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Organization;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class TempOrgAdminSeeder extends Seeder
{
    public function run()
    {
        try {
            $this->command->info('Creating Organization...');
            $org = Organization::firstOrCreate(
                ['code' => 'DEMO_ORG'],
                ['name' => 'Demo Corp', 'is_active' => true]
            );
            $this->command->info("Organization ID: {$org->id}");

            $this->command->info('Creating Org Admin...');
            $orgAdmin = User::firstOrCreate(
                ['email' => 'org_admin@qttenzy.com'],
                [
                    'name' => 'Org Admin',
                    'password' => Hash::make('password'),
                    'role' => 'organization_admin',
                    'organization_id' => $org->id,
                    'is_active' => true,
                    'is_approved' => true
                ]
            );
            $orgAdmin->assignRole('organization_admin');
            $this->command->info("Org Admin created: {$orgAdmin->email}");

            // Create demo students
            $this->command->info('Creating demo students...');
            $students = [];
            for ($i = 1; $i <= 5; $i++) {
                $student = User::firstOrCreate(
                    ['email' => "student{$i}@democorp.com"],
                    [
                        'name' => "Student {$i}",
                        'password' => Hash::make('password'),
                        'role' => 'student',
                        'organization_id' => $org->id,
                        'is_active' => true,
                        'is_approved' => true
                    ]
                );
                $student->assignRole('student');
                $students[] = $student;
            }
            $studentCount = count($students);
            $this->command->info("Created {$studentCount} students");

            // Create demo sessions
            $this->command->info('Creating demo sessions...');
            
            // Active session
            $activeSession = \App\Models\Session::firstOrCreate(
                ['title' => 'Mathematics 101 - Active'],
                [
                    'description' => 'Current mathematics class in progress',
                    'start_time' => now()->subHours(1),
                    'end_time' => now()->addHours(1),
                    'location_name' => 'Room 101',
                    'location_lat' => 23.8103,
                    'location_lng' => 90.4125,
                    'status' => 'active',
                    'created_by' => $orgAdmin->id,
                    'organization_id' => $org->id,
                ]
            );

            // Scheduled session
            $scheduledSession = \App\Models\Session::firstOrCreate(
                ['title' => 'Physics 201 - Upcoming'],
                [
                    'description' => 'Physics lecture scheduled for tomorrow',
                    'start_time' => now()->addDay(),
                    'end_time' => now()->addDay()->addHours(2),
                    'location_name' => 'Lab 202',
                    'location_lat' => 23.8103,
                    'location_lng' => 90.4125,
                    'status' => 'draft',
                    'created_by' => $orgAdmin->id,
                    'organization_id' => $org->id,
                ]
            );

            // Completed session
            $completedSession = \App\Models\Session::firstOrCreate(
                ['title' => 'Chemistry 301 - Completed'],
                [
                    'description' => 'Yesterday\'s chemistry lab',
                    'start_time' => now()->subDay(),
                    'end_time' => now()->subDay()->addHours(2),
                    'location_name' => 'Lab 303',
                    'location_lat' => 23.8103,
                    'location_lng' => 90.4125,
                    'status' => 'completed',
                    'created_by' => $orgAdmin->id,
                    'organization_id' => $org->id,
                ]
            );

            $this->command->info('Created 3 demo sessions');
            
            $this->command->info("✓ SUCCESS: Demo organization setup complete!");
            $this->command->info("  - Organization: {$org->name}");
            $this->command->info("  - Admin: {$orgAdmin->email}");
            $this->command->info("  - Students: {$studentCount}");
            $this->command->info("  - Sessions: 3 (1 active, 1 draft, 1 completed)");
        } catch (\Exception $e) {
            $this->command->error("Error: " . $e->getMessage());
            $this->command->error($e->getTraceAsString());
        }
    }
}
