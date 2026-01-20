<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Organization;

/**
 * OrganizationSeeder
 * 
 * Seeds the organizations table with demo organizations.
 * Creates multiple organizations for multi-tenant demonstration.
 */
class OrganizationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $organizations = [
            [
                'name' => 'Dhaka University',
                'code' => 'DU',
                'address' => 'Dhaka University Campus, Dhaka-1000, Bangladesh',
                'phone' => '+880-2-9661900',
                'email' => 'info@du.ac.bd',
                'is_active' => true,
                'settings' => [
                    'timezone' => 'Asia/Dhaka',
                    'late_threshold_minutes' => 15,
                    'face_confidence_threshold' => 0.7,
                    'gps_radius_meters' => 100,
                ]
            ],
            [
                'name' => 'BUET - Bangladesh University of Engineering and Technology',
                'code' => 'BUET',
                'address' => 'Dhaka-1000, Bangladesh',
                'phone' => '+880-2-9665650',
                'email' => 'registrar@buet.ac.bd',
                'is_active' => true,
                'settings' => [
                    'timezone' => 'Asia/Dhaka',
                    'late_threshold_minutes' => 10,
                    'face_confidence_threshold' => 0.75,
                    'gps_radius_meters' => 150,
                ]
            ],
            [
                'name' => 'Corporate Training Center',
                'code' => 'CTC',
                'address' => 'Gulshan-2, Dhaka-1212, Bangladesh',
                'phone' => '+880-2-8833445',
                'email' => 'info@ctc-bd.com',
                'is_active' => true,
                'settings' => [
                    'timezone' => 'Asia/Dhaka',
                    'late_threshold_minutes' => 5,
                    'face_confidence_threshold' => 0.8,
                    'gps_radius_meters' => 50,
                ]
            ],
        ];

        foreach ($organizations as $org) {
            Organization::firstOrCreate(
                ['code' => $org['code']],
                $org
            );
        }

        $this->command->info('✓ Organizations seeded successfully!');
    }
}
