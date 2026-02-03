<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * DatabaseSeeder
 * 
 * Main seeder that orchestrates all other seeders.
 * Run with: php artisan db:seed
 */
class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('🌱 Starting database seeding...');
        $this->command->newLine();

        // Seed in specific order due to dependencies
        $this->call([
            // Core system data
            RolePermissionSeeder::class,
            OrganizationSeeder::class,
            
            // Super Admin - must be created after roles/permissions
            SuperAdminSeeder::class,
            
            // Demo users
            DemoUserSeeder::class,
            
            // Demo sessions and attendance
            DemoSessionSeeder::class,
            DemoAttendanceSeeder::class,
            
            // Fraud scenarios for academic defense
            FraudScenarioSeeder::class,
        ]);

        $this->command->newLine();
        $this->command->info('✅ Database seeding completed successfully!');
        $this->command->newLine();
        $this->command->info('📝 Demo Credentials:');
        $this->command->info('   Super Admin: admin@qttenzy.com / password');
        $this->command->info('   DU Admin: admin@du.ac.bd / password');
        $this->command->info('   Teacher: teacher1@qttenzy.com / password');
        $this->command->info('   Student: student1@qttenzy.com / password');
        $this->command->newLine();
        $this->command->info('📊 Seeded Data Summary:');
        $this->command->info('   • 4 Roles with 18 Permissions');
        $this->command->info('   • 3 Organizations');
        $this->command->info('   • 30 Users (3 admins, 5 teachers, 22 students)');
        $this->command->info('   • 10 Sessions (various types and states)');
        $this->command->info('   • 23 Attendance Records (mixed states)');
        $this->command->info('   • 5 Fraud Scenarios');
        $this->command->newLine();
    }
}
