<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\TimeRegistration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $admin;
    protected $adminToken;
    protected $user;
    protected $userToken;

    protected function setUp(): void
    {
        parent::setUp();

        // Create an admin user
        $this->admin = User::factory()->create([
            'role' => 'admin'
        ]);
        $this->adminToken = $this->admin->createToken('admin-token')->plainTextToken;

        // Create a regular user
        $this->user = User::factory()->create([
            'role' => 'user'
        ]);
        $this->userToken = $this->user->createToken('user-token')->plainTextToken;
    }

    /** @test */
    public function admin_can_view_all_users()
    {
        // Create some additional users
        User::factory()->count(5)->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
        ])->getJson('/api/users');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'email',
                        'personal_id',
                        'role',
                        'created_at',
                        'updated_at'
                    ]
                ],
                'current_page',
                'total'
            ]);

        // Total should be 7 (admin + regular user + 5 additional users)
        $this->assertEquals(7, $response->json('total'));
    }

    /** @test */
    public function regular_user_cannot_view_all_users()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->userToken,
        ])->getJson('/api/users');

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_view_all_time_registrations()
    {
        // Create time registrations for the regular user
        TimeRegistration::factory()->count(3)->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
        ])->getJson('/api/time-registrations');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'current_page',
                'total'
            ]);

        // Should see all 3 time registrations
        $this->assertEquals(3, $response->json('total'));
    }

    /** @test */
    public function admin_can_filter_time_registrations_by_user()
    {
        // Create time registrations for multiple users
        TimeRegistration::factory()->count(3)->create([
            'user_id' => $this->user->id
        ]);
        
        $anotherUser = User::factory()->create();
        TimeRegistration::factory()->count(2)->create([
            'user_id' => $anotherUser->id
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
        ])->getJson('/api/time-registrations?user_id=' . $this->user->id);

        $response->assertStatus(200);
        
        // Should only see the 3 time registrations for the specified user
        $this->assertEquals(3, $response->json('total'));
    }

    /** @test */
    public function admin_can_create_user()
    {
        $userData = [
            'name' => 'New Test User',
            'email' => 'newuser@example.com',
            'personal_id' => '910202-1234',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'user'
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
        ])->postJson('/api/users', $userData);

        $response->assertStatus(201)
            ->assertJsonFragment([
                'name' => 'New Test User',
                'email' => 'newuser@example.com',
                'personal_id' => '910202-1234',
                'role' => 'user'
            ]);

        // Verify user was created in database
        $this->assertDatabaseHas('users', [
            'name' => 'New Test User',
            'email' => 'newuser@example.com',
            'personal_id' => '910202-1234',
            'role' => 'user'
        ]);
    }

    /** @test */
    public function regular_user_cannot_create_user()
    {
        $userData = [
            'name' => 'New Test User',
            'email' => 'newuser@example.com',
            'personal_id' => '910202-1234',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'user'
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->userToken,
        ])->postJson('/api/users', $userData);

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_update_user()
    {
        $updateData = [
            'name' => 'Updated User Name',
            'email' => 'updated@example.com',
            'role' => 'manager'
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
        ])->putJson('/api/users/' . $this->user->id, $updateData);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'name' => 'Updated User Name',
                'email' => 'updated@example.com',
                'role' => 'manager'
            ]);

        // Verify user was updated in database
        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'name' => 'Updated User Name',
            'email' => 'updated@example.com',
            'role' => 'manager'
        ]);
    }

    /** @test */
    public function admin_can_delete_user()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
        ])->deleteJson('/api/users/' . $this->user->id);

        $response->assertStatus(200);

        // Verify user was deleted from database
        $this->assertDatabaseMissing('users', [
            'id' => $this->user->id
        ]);
    }
}
