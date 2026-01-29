<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Organization;
use App\Models\Session;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class MultiTenancyTest extends TestCase
{
    // use RefreshDatabase; // Commented out to run on existing DB if needed, or use a separate test DB

    public function test_organization_admin_can_only_see_own_organization_stats()
    {
        // Setup
        $org1 = Organization::create(['name' => 'Org 1', 'code' => 'ORG1', 'is_active' => true]);
        $org2 = Organization::create(['name' => 'Org 2', 'code' => 'ORG2', 'is_active' => true]);

        $admin1 = User::create([
            'name' => 'Admin 1',
            'email' => 'admin1@org1.com',
            'password' => Hash::make('password'),
            'organization_id' => $org1->id,
            'role' => 'organization_admin'
        ]);
        $admin1->assignRole('organization_admin');

        // Test
        $response = $this->actingAs($admin1)->getJson("/api/organizations/{$org1->id}/statistics");
        $response->assertStatus(200);

        $response = $this->actingAs($admin1)->getJson("/api/organizations/{$org2->id}/statistics");
        $response->assertStatus(403);
    }

    public function test_organization_admin_can_only_manage_own_users()
    {
        $org1 = Organization::create(['name' => 'Org 1 Users', 'code' => 'ORG1U', 'is_active' => true]);
        $org2 = Organization::create(['name' => 'Org 2 Users', 'code' => 'ORG2U', 'is_active' => true]);

        $admin1 = User::create([
            'name' => 'Admin 1',
            'email' => 'admin_users@org1.com',
            'password' => Hash::make('password'),
            'organization_id' => $org1->id,
            'role' => 'organization_admin'
        ]);
        $admin1->assignRole('organization_admin');

        $user1 = User::create([
            'name' => 'User 1',
            'email' => 'user1@org1.com',
            'password' => Hash::make('password'),
            'organization_id' => $org1->id,
            'role' => 'student'
        ]);

        $user2 = User::create([
            'name' => 'User 2',
            'email' => 'user2@org2.com',
            'password' => Hash::make('password'),
            'organization_id' => $org2->id,
            'role' => 'student'
        ]);

        // List users
        $response = $this->actingAs($admin1)->getJson('/api/users');
        $response->assertStatus(200);
        $response->assertJsonFragment(['email' => 'user1@org1.com']);
        $response->assertJsonMissing(['email' => 'user2@org2.com']);

        // Create user
        $response = $this->actingAs($admin1)->postJson('/api/users', [
            'name' => 'New User',
            'email' => 'newuser@org1.com',
            'password' => 'password',
            'role' => 'student'
        ]);
        $response->assertStatus(201);
        $this->assertEquals($org1->id, User::where('email', 'newuser@org1.com')->first()->organization_id);

        // Edit user 2 (should fail)
        $response = $this->actingAs($admin1)->putJson("/api/users/{$user2->id}", [
            'name' => 'Updated Name'
        ]);
        $response->assertStatus(403);
    }
}
