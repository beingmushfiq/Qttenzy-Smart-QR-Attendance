<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Support\Facades\Hash;

/**
 * SuperAdminSeeder
 * 
 * Ensures the Super Admin user (admin@qttenzy.com) exists and has all permissions.
 * This user can approve, reject, and delete everything across all organizations.
 */
class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating Super Admin user...');

        // Get the admin role
        $adminRole = Role::where('name', 'admin')->first();
        
        if (!$adminRole) {
            $this->command->error('Admin role not found! Please run RolePermissionSeeder first.');
            return;
        }

        // Create or update the Super Admin user
        $superAdmin = User::updateOrCreate(
            ['email' => 'admin@qttenzy.com'],
            [
                'name' => 'Super Admin',
                'email' => 'admin@qttenzy.com',
                'password' => Hash::make('password'),
                'phone' => '+8801712345678',
                'organization_id' => null, // Super admin - not tied to any organization
                'role' => 'admin',
                'is_active' => true,
                'is_approved' => true,
                'requires_approval' => false,
                'face_consent' => true,
                'email_verified_at' => now(),
                'approved_at' => now(),
            ]
        );

        // Assign admin role if not already assigned
        if (!$superAdmin->roles()->where('role_id', $adminRole->id)->exists()) {
            $superAdmin->roles()->attach($adminRole->id);
            $this->command->info('✓ Admin role assigned to Super Admin');
        } else {
            $this->command->info('✓ Admin role already assigned');
        }

        // Verify all permissions are attached to admin role
        $allPermissions = Permission::all();
        $adminRole->permissions()->sync($allPermissions->pluck('id'));
        
        $this->command->info('✓ All permissions synced to admin role');
        $this->command->info("✓ Super Admin created: {$superAdmin->email}");
        $this->command->info("   - Total Permissions: {$allPermissions->count()}");
        $this->command->info("   - Organization: Global (all organizations)");
        $this->command->info("   - Status: Active & Approved");
    }
}
