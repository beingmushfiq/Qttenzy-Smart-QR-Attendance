<?php

namespace Database\Seeders;

use App\Models\Session;
use App\Models\User;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class SessionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::where('email', 'admin@qttenzy.com')->first();
        $manager = User::where('email', 'manager@qttenzy.com')->first();

        // Active Session
        Session::create([
            'title' => 'Workshop on AI and Machine Learning',
            'description' => 'Learn the fundamentals of AI and ML',
            'start_time' => Carbon::now()->addHours(2),
            'end_time' => Carbon::now()->addHours(4),
            'location_lat' => 23.8103,
            'location_lng' => 90.4125,
            'location_name' => 'Dhaka University',
            'radius_meters' => 100,
            'session_type' => 'pre_registered',
            'status' => 'active',
            'requires_payment' => true,
            'payment_amount' => 500.00,
            'max_attendees' => 50,
            'created_by' => $admin->id,
        ]);

        // Upcoming Session
        Session::create([
            'title' => 'Web Development Bootcamp',
            'description' => 'Full-stack web development course',
            'start_time' => Carbon::now()->addDays(1),
            'end_time' => Carbon::now()->addDays(1)->addHours(3),
            'location_lat' => 23.8103,
            'location_lng' => 90.4125,
            'location_name' => 'Tech Hub',
            'radius_meters' => 150,
            'session_type' => 'open',
            'status' => 'draft',
            'requires_payment' => false,
            'max_attendees' => 100,
            'created_by' => $manager->id,
        ]);

        // Completed Session
        Session::create([
            'title' => 'Introduction to React',
            'description' => 'Learn React basics',
            'start_time' => Carbon::now()->subDays(1),
            'end_time' => Carbon::now()->subDays(1)->addHours(2),
            'location_lat' => 23.8103,
            'location_lng' => 90.4125,
            'location_name' => 'Online',
            'radius_meters' => 200,
            'session_type' => 'open',
            'status' => 'completed',
            'requires_payment' => false,
            'created_by' => $admin->id,
        ]);
    }
}

