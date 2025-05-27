<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class LocationDepartmentTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;
    protected $token;
    protected $admin;
    protected $adminToken;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a regular user
        $this->user = User::factory()->create([
            'role' => 'user'
        ]);
        $this->token = $this->user->createToken('test-token')->plainTextToken;

        // Create an admin user
        $this->admin = User::factory()->create([
            'role' => 'admin'
        ]);
        $this->adminToken = $this->admin->createToken('admin-token')->plainTextToken;
    }

    /** @test */
    public function user_can_view_locations()
    {
        // Create some locations
        Location::factory()->count(3)->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/locations');

        $response->assertStatus(200)
            ->assertJsonCount(3);
    }

    /** @test */
    public function admin_can_create_location()
    {
        $locationData = [
            'name' => 'Test Location',
            'address' => '123 Test Street',
            'latitude' => 59.3293,
            'longitude' => 18.0686,
            'radius' => 100
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
        ])->postJson('/api/locations', $locationData);

        $response->assertStatus(201)
            ->assertJsonFragment([
                'name' => 'Test Location',
                'address' => '123 Test Street',
                'latitude' => 59.3293,
                'longitude' => 18.0686,
                'radius' => 100
            ]);

        // Verify location was created in database
        $this->assertDatabaseHas('locations', [
            'name' => 'Test Location',
            'address' => '123 Test Street'
        ]);
    }

    /** @test */
    public function regular_user_cannot_create_location()
    {
        $locationData = [
            'name' => 'Test Location',
            'address' => '123 Test Street',
            'latitude' => 59.3293,
            'longitude' => 18.0686,
            'radius' => 100
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/locations', $locationData);

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_update_location()
    {
        $location = Location::factory()->create();

        $updateData = [
            'name' => 'Updated Location',
            'address' => '456 Updated Street',
            'latitude' => 60.1282,
            'longitude' => 18.6435,
            'radius' => 200
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
        ])->putJson('/api/locations/' . $location->id, $updateData);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'name' => 'Updated Location',
                'address' => '456 Updated Street'
            ]);

        // Verify location was updated in database
        $this->assertDatabaseHas('locations', [
            'id' => $location->id,
            'name' => 'Updated Location',
            'address' => '456 Updated Street'
        ]);
    }

    /** @test */
    public function admin_can_delete_location()
    {
        $location = Location::factory()->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
        ])->deleteJson('/api/locations/' . $location->id);

        $response->assertStatus(200);

        // Verify location was deleted from database
        $this->assertDatabaseMissing('locations', [
            'id' => $location->id
        ]);
    }

    /** @test */
    public function user_can_view_departments()
    {
        // Create some departments
        Department::factory()->count(3)->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/departments');

        $response->assertStatus(200)
            ->assertJsonCount(3);
    }

    /** @test */
    public function admin_can_create_department()
    {
        $departmentData = [
            'name' => 'Test Department',
            'description' => 'This is a test department'
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
        ])->postJson('/api/departments', $departmentData);

        $response->assertStatus(201)
            ->assertJsonFragment([
                'name' => 'Test Department',
                'description' => 'This is a test department'
            ]);

        // Verify department was created in database
        $this->assertDatabaseHas('departments', [
            'name' => 'Test Department',
            'description' => 'This is a test department'
        ]);
    }

    /** @test */
    public function admin_can_update_department()
    {
        $department = Department::factory()->create();

        $updateData = [
            'name' => 'Updated Department',
            'description' => 'This is an updated department'
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
        ])->putJson('/api/departments/' . $department->id, $updateData);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'name' => 'Updated Department',
                'description' => 'This is an updated department'
            ]);

        // Verify department was updated in database
        $this->assertDatabaseHas('departments', [
            'id' => $department->id,
            'name' => 'Updated Department',
            'description' => 'This is an updated department'
        ]);
    }

    /** @test */
    public function admin_can_delete_department()
    {
        $department = Department::factory()->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
        ])->deleteJson('/api/departments/' . $department->id);

        $response->assertStatus(200);

        // Verify department was deleted from database
        $this->assertDatabaseMissing('departments', [
            'id' => $department->id
        ]);
    }
}
