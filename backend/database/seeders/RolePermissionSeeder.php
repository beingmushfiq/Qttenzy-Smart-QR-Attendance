<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;

/**
 * RolePermissionSeeder
 * 
 * Seeds the roles and permissions tables with initial data.
 * Creates admin, teacher, and student roles with appropriate permissions.
 */
class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Permissions
        $permissions = [
            // User Management
            ['name' => 'view_users', 'display_name' => 'View Users', 'group' => 'users', 'description' => 'View user list and details'],
            ['name' => 'create_users', 'display_name' => 'Create Users', 'group' => 'users', 'description' => 'Create new users'],
            ['name' => 'edit_users', 'display_name' => 'Edit Users', 'group' => 'users', 'description' => 'Edit user information'],
            ['name' => 'delete_users', 'display_name' => 'Delete Users', 'group' => 'users', 'description' => 'Delete users'],
            ['name' => 'approve_users', 'display_name' => 'Approve Users', 'group' => 'users', 'description' => 'Approve pending user registrations'],
            
            // Session Management
            ['name' => 'view_sessions', 'display_name' => 'View Sessions', 'group' => 'sessions', 'description' => 'View session list and details'],
            ['name' => 'create_sessions', 'display_name' => 'Create Sessions', 'group' => 'sessions', 'description' => 'Create new sessions'],
            ['name' => 'edit_sessions', 'display_name' => 'Edit Sessions', 'group' => 'sessions', 'description' => 'Edit session information'],
            ['name' => 'delete_sessions', 'display_name' => 'Delete Sessions', 'group' => 'sessions', 'description' => 'Delete sessions'],
            ['name' => 'generate_qr', 'display_name' => 'Generate QR Codes', 'group' => 'sessions', 'description' => 'Generate QR codes for sessions'],
            
            // Attendance Management
            ['name' => 'view_attendance', 'display_name' => 'View Attendance', 'group' => 'attendance', 'description' => 'View attendance records'],
            ['name' => 'mark_attendance', 'display_name' => 'Mark Attendance', 'group' => 'attendance', 'description' => 'Mark own attendance'],
            ['name' => 'approve_attendance', 'display_name' => 'Approve Attendance', 'group' => 'attendance', 'description' => 'Approve/reject attendance requests'],
            ['name' => 'override_attendance', 'display_name' => 'Override Attendance', 'group' => 'attendance', 'description' => 'Override attendance status'],
            
            // Organization Management
            ['name' => 'manage_organizations', 'display_name' => 'Manage Organizations', 'group' => 'organizations', 'description' => 'Manage organizations'],
            
            // Reports
            ['name' => 'view_reports', 'display_name' => 'View Reports', 'group' => 'reports', 'description' => 'View attendance and system reports'],
            ['name' => 'export_reports', 'display_name' => 'Export Reports', 'group' => 'reports', 'description' => 'Export reports to PDF/CSV/Excel'],
            
            // Audit Logs
            ['name' => 'view_audit_logs', 'display_name' => 'View Audit Logs', 'group' => 'audit', 'description' => 'View system audit logs'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission['name']],
                $permission
            );
        }

        // Create Roles
        $adminRole = Role::firstOrCreate(
            ['name' => 'admin'],
            [
                'display_name' => 'Administrator',
                'description' => 'Full system access with all permissions'
            ]
        );

        $teacherRole = Role::firstOrCreate(
            ['name' => 'teacher'],
            [
                'display_name' => 'Teacher',
                'description' => 'Can create sessions and manage attendance'
            ]
        );

        $sessionManagerRole = Role::firstOrCreate(
            ['name' => 'session_manager'],
            [
                'display_name' => 'Session Manager',
                'description' => 'Can create and manage sessions'
            ]
        );

        $studentRole = Role::firstOrCreate(
            ['name' => 'student'],
            [
                'display_name' => 'Student',
                'description' => 'Can mark attendance and view own records'
            ]
        );

        // Assign Permissions to Admin (all permissions)
        $allPermissions = Permission::all();
        $adminRole->permissions()->sync($allPermissions->pluck('id'));

        // Assign Permissions to Teacher
        $teacherPermissions = Permission::whereIn('name', [
            'view_sessions',
            'create_sessions',
            'edit_sessions',
            'generate_qr',
            'view_attendance',
            'approve_attendance',
            'view_reports',
            'export_reports',
        ])->get();
        $teacherRole->permissions()->sync($teacherPermissions->pluck('id'));

        // Assign Permissions to Session Manager
        $sessionManagerPermissions = Permission::whereIn('name', [
            'view_sessions',
            'create_sessions',
            'edit_sessions',
            'generate_qr',
            'view_attendance',
            'view_reports',
        ])->get();
        $sessionManagerRole->permissions()->sync($sessionManagerPermissions->pluck('id'));

        // Create Organization Admin Role
        $orgAdminRole = Role::firstOrCreate(
            ['name' => 'organization_admin'],
            [
                'display_name' => 'Organization Admin',
                'description' => 'Can manage users and sessions for their organization'
            ]
        );

        // Assign Permissions to Student
        $studentPermissions = Permission::whereIn('name', [
            'view_sessions',
            'mark_attendance',
            'view_attendance',
        ])->get();
        $studentRole->permissions()->sync($studentPermissions->pluck('id'));

        // Assign Permissions to Organization Admin
        $orgAdminPermissions = Permission::whereIn('name', [
            'view_users',
            'create_users',
            'edit_users',
            'delete_users',
            'view_sessions',
            'create_sessions',
            'edit_sessions',
            'delete_sessions',
            'generate_qr',
            'view_attendance',
            'approve_attendance',
            'view_reports',
            'export_reports',
        ])->get();
        $orgAdminRole->permissions()->sync($orgAdminPermissions->pluck('id'));

        $this->command->info('✓ Roles and permissions seeded successfully!');
    }
}
