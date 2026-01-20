<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Admin User
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@qttenzy.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        // Session Manager
        User::create([
            'name' => 'Session Manager',
            'email' => 'manager@qttenzy.com',
            'password' => Hash::make('password'),
            'role' => 'session_manager',
            'is_active' => true,
        ]);

        // Student Users
        for ($i = 1; $i <= 5; $i++) {
            User::create([
                'name' => "Student {$i}",
                'email' => "student{$i}@qttenzy.com",
                'password' => Hash::make('password'),
                'role' => 'student',
                'is_active' => true,
            ]);
        }

        // Employee Users
        for ($i = 1; $i <= 3; $i++) {
            User::create([
                'name' => "Employee {$i}",
                'email' => "employee{$i}@qttenzy.com",
                'password' => Hash::make('password'),
                'role' => 'employee',
                'is_active' => true,
            ]);
        }
    }
}

