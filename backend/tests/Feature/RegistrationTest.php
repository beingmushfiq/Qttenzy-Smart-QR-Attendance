<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_register_with_simple_password()
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Test Student',
            'email' => 'student@test.com',
            'password' => '123456',
            'password_confirmation' => '123456',
            'role' => 'student',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('users', ['email' => 'student@test.com']);
    }

    public function test_teacher_can_register_with_simple_password()
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Test Teacher',
            'email' => 'teacher@test.com',
            'password' => 'secret',
            'password_confirmation' => 'secret',
            'role' => 'teacher',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('users', ['email' => 'teacher@test.com']);
    }

    public function test_event_manager_can_register_with_simple_password()
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Test EventManager',
            'email' => 'event@test.com',
            'password' => 'abcdef',
            'password_confirmation' => 'abcdef',
            'role' => 'event_manager',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('users', ['email' => 'event@test.com']);
    }

    public function test_coordinator_can_register_with_simple_password()
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Test Coordinator',
            'email' => 'coord@test.com',
            'password' => '123123',
            'password_confirmation' => '123123',
            'role' => 'coordinator',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('users', ['email' => 'coord@test.com']);
    }

    public function test_registration_fails_with_short_password()
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Should Fail',
            'email' => 'fail@test.com',
            'password' => '12345',
            'password_confirmation' => '12345',
            'role' => 'student',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }
}
