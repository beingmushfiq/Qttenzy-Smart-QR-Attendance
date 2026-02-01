<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use App\Models\Organization;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

/**
 * DemoUserSeeder
 * 
 * Seeds the users table with demo users for academic defense.
 * Creates multiple admins, teachers, and students with realistic data.
 */
class DemoUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        try {
            // Get organizations
            $du = Organization::where('code', 'DU')->first();
            $buet = Organization::where('code', 'BUET')->first();
            $ctc = Organization::where('code', 'CTC')->first();

            // Get roles
            $adminRole = Role::where('name', 'admin')->first();
            $teacherRole = Role::where('name', 'teacher')->first();
            $studentRole = Role::where('name', 'student')->first();

            // Create Admin Users
            $admins = [
                [
                    'name' => 'Admin User',
                    'email' => 'admin@qttenzy.com',
                    'phone' => '+8801712345678',
                    'organization_id' => null, // Super admin
                    'role' => 'admin',
                ],
                [
                    'name' => 'Dr. Ahmed Hassan',
                    'email' => 'admin@du.ac.bd',
                    'phone' => '+8801712345679',
                    'organization_id' => $du?->id,
                    'role' => 'admin',
                ],
                [
                    'name' => 'Prof. Karim Rahman',
                    'email' => 'admin@buet.ac.bd',
                    'phone' => '+8801712345680',
                    'organization_id' => $buet?->id,
                    'role' => 'admin',
                ],
            ];

            foreach ($admins as $index => $adminData) {
                $this->command->info("Creating admin user {$index}: " . $adminData['email']);
                $admin = User::firstOrCreate(
                    ['email' => $adminData['email']],
                    array_merge($adminData, [
                        'password' => Hash::make('password'),
                        'is_active' => true,
                        'is_approved' => true,
                        'face_consent' => true,
                        'email_verified_at' => now(),
                    ])
                );
                
                // Assign admin role
                if ($adminRole && !$admin->roles()->where('role_id', $adminRole->id)->exists()) {
                    $admin->roles()->attach($adminRole->id);
                }
            }

            // Create Teacher/Session Manager Users
            $teachers = [
                [
                    'name' => 'Dr. Fatima Khan',
                    'email' => 'teacher1@qttenzy.com',
                    'phone' => '+8801712345681',
                    'organization_id' => $du?->id,
                    'role' => 'session_manager',
                ],
                [
                    'name' => 'Prof. Rahim Uddin',
                    'email' => 'teacher2@qttenzy.com',
                    'phone' => '+8801712345682',
                    'organization_id' => $du?->id,
                    'role' => 'session_manager',
                ],
                [
                    'name' => 'Dr. Nadia Islam',
                    'email' => 'teacher3@qttenzy.com',
                    'phone' => '+8801712345683',
                    'organization_id' => $buet?->id,
                    'role' => 'session_manager',
                ],
                [
                    'name' => 'Mr. Kamal Hossain',
                    'email' => 'teacher4@qttenzy.com',
                    'phone' => '+8801712345684',
                    'organization_id' => $buet?->id,
                    'role' => 'session_manager',
                ],
                [
                    'name' => 'Ms. Sarah Ahmed',
                    'email' => 'teacher5@qttenzy.com',
                    'phone' => '+8801712345685',
                    'organization_id' => $ctc?->id,
                    'role' => 'session_manager',
                ],
            ];

            foreach ($teachers as $teacherData) {
                $this->command->info("Creating teacher user: " . $teacherData['email']);
                
                try {
                    $teacher = User::firstOrCreate(
                        ['email' => $teacherData['email']],
                        array_merge($teacherData, [
                            'password' => Hash::make('password'),
                            'is_active' => true,
                            'is_approved' => true,
                            'face_consent' => true,
                            'email_verified_at' => now(),
                        ])
                    );
                    
                    if (!$teacher->id) {
                        $this->command->error("Teacher ID is null for " . $teacherData['email']);
                        continue;
                    }
                    $this->command->info("User ID: " . $teacher->id);

                    // Assign teacher role
                    if ($teacherRole && !$teacher->roles()->where('role_id', $teacherRole->id)->exists()) {
                        $teacher->roles()->attach($teacherRole->id);
                    } else {
                        if(!$teacherRole) $this->command->warn("Teacher role not found!");
                    }
                } catch (\Throwable $e) {
                    $this->command->error("FAILED to create teacher: " . $e->getMessage());
                    throw $e;
                }
            }

            // Create Student Users
            $studentNames = [
                'Aayan Rahman', 'Zara Khan', 'Omar Ali', 'Ayesha Begum', 'Hassan Ahmed',
                'Fatima Noor', 'Ibrahim Malik', 'Mariam Haque', 'Yusuf Chowdhury', 'Amina Sultana',
                'Bilal Hossain', 'Khadija Islam', 'Tariq Uddin', 'Layla Akter', 'Hamza Karim',
                'Safiya Rahman', 'Idris Khan', 'Noor Begum', 'Salman Ahmed', 'Hiba Noor'
            ];

            $organizations = [$du, $buet, $ctc];
            
            foreach ($studentNames as $index => $name) {
                $email = 'student' . ($index + 1) . '@qttenzy.com';
                $this->command->info("Creating student user {$index}: " . $email);
                $phone = '+880171234' . str_pad($index + 5686, 4, '0', STR_PAD_LEFT);
                $org = $organizations[$index % 3];
                
                try {
                    $existing = DB::table('users')->where('email', $email)->first();
                    if ($existing) {
                        $studentId = $existing->id;
                        $this->command->info("Student exists. ID: " . $studentId);
                    } else {
                        $studentId = DB::table('users')->insertGetId([
                            'name' => $name,
                            'email' => $email,
                            'phone' => $phone,
                            'password' => Hash::make('password'),
                            'organization_id' => $org?->id,
                            'role' => 'student',
                            'is_active' => true,
                            'is_approved' => true,
                            'face_consent' => true,
                            'email_verified_at' => now(),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        $this->command->info("Created student object. ID: " . $studentId);
                    }
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::error("Failed to create student {$email}: " . $e->getMessage());
                }
            }
            
            $this->command->info("Finished creating students. Now assigning roles...");
            
            // Assign roles to all students
            $allStudents = User::where('email', 'like', 'student%@qttenzy.com')->get();
            foreach ($allStudents as $student) {
                 if ($studentRole && !$student->roles()->where('role_id', $studentRole->id)->exists()) {
                     try {
                        $student->roles()->attach($studentRole->id);
                     } catch (\Throwable $e) {
                         $this->command->warn("Failed to assign role to {$student->email}: " . $e->getMessage());
                         \Illuminate\Support\Facades\Log::warning("Failed to assign role to {$student->email}: " . $e->getMessage());
                     }
                }
            }

            // Create some pending approval students
            $pendingStudents = [
                [
                    'name' => 'Pending Student 1',
                    'email' => 'pending1@qttenzy.com',
                    'phone' => '+8801712399901',
                    'organization_id' => $du?->id,
                ],
                [
                    'name' => 'Pending Student 2',
                    'email' => 'pending2@qttenzy.com',
                    'phone' => '+8801712399902',
                    'organization_id' => $buet?->id,
                ],
            ];

            foreach ($pendingStudents as $pendingData) {
                $pending = User::firstOrCreate(
                    ['email' => $pendingData['email']],
                    array_merge($pendingData, [
                        'password' => Hash::make('password'),
                        'role' => 'student',
                        'is_active' => true,
                        'requires_approval' => true,
                        'is_approved' => false,
                        'face_consent' => false,
                    ])
                );
                
                // Assign student role
                if ($studentRole && !$pending->roles()->where('role_id', $studentRole->id)->exists()) {
                    $pending->roles()->attach($studentRole->id);
                }
            }

            $this->command->info('✓ Demo users seeded successfully!');

        } catch (\Throwable $e) {
            $msg = "GLOBAL CATCH: " . $e->getMessage();
            $msg .= "\nTrace: " . $e->getTraceAsString();
            \Illuminate\Support\Facades\Log::error("Seeder Failed: " . $msg);
            throw $e;
        }
    }
}
