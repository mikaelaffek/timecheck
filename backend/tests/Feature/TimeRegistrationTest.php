<?php

namespace Tests\Feature;

use App\Models\TimeRegistration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class TimeRegistrationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;
    protected $token;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a test user
        $this->user = User::factory()->create([
            'personal_id' => '900101-1234',
            'password' => bcrypt('password'),
            'role' => 'user'
        ]);

        // Create a token for the user
        $this->token = $this->user->createToken('test-token')->plainTextToken;
    }

    /** @test */
    public function user_can_check_clock_in_status()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/check-clock-in-status');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'is_clocked_in',
                'time_registration'
            ]);
    }

    /** @test */
    public function user_can_clock_in()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/time-registrations/clock-in', [
            'latitude' => $this->faker->latitude,
            'longitude' => $this->faker->longitude
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id',
                'user_id',
                'date',
                'clock_in',
                'latitude',
                'longitude',
                'status'
            ]);

        $this->assertEquals($this->user->id, $response->json('user_id'));
        $this->assertNotNull($response->json('clock_in'));
        $this->assertNull($response->json('clock_out'));
    }

    /** @test */
    public function user_cannot_clock_in_twice()
    {
        // First clock-in
        $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/time-registrations/clock-in', [
            'latitude' => $this->faker->latitude,
            'longitude' => $this->faker->longitude
        ]);

        // Try to clock-in again
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/time-registrations/clock-in', [
            'latitude' => $this->faker->latitude,
            'longitude' => $this->faker->longitude
        ]);

        $response->assertStatus(422)
            ->assertJsonFragment([
                'message' => 'You are already clocked in'
            ]);
    }

    /** @test */
    public function user_can_clock_out()
    {
        // First clock-in
        $clockInResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/time-registrations/clock-in', [
            'latitude' => $this->faker->latitude,
            'longitude' => $this->faker->longitude
        ]);

        // Then clock-out
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/time-registrations/clock-out', [
            'latitude' => $this->faker->latitude,
            'longitude' => $this->faker->longitude
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'user_id',
                'date',
                'clock_in',
                'clock_out',
                'latitude',
                'longitude',
                'status'
            ]);

        $this->assertEquals($this->user->id, $response->json('user_id'));
        $this->assertNotNull($response->json('clock_in'));
        $this->assertNotNull($response->json('clock_out'));
    }

    /** @test */
    public function user_cannot_clock_out_without_clocking_in()
    {
        // Make sure user is not clocked in
        TimeRegistration::where('user_id', $this->user->id)->delete();

        // Try to clock-out
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/time-registrations/clock-out', [
            'latitude' => $this->faker->latitude,
            'longitude' => $this->faker->longitude
        ]);

        $response->assertStatus(422)
            ->assertJsonFragment([
                'message' => 'You are not clocked in'
            ]);
    }

    /** @test */
    public function user_can_get_recent_time_registrations()
    {
        // Create some time registrations
        TimeRegistration::factory()->count(3)->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/recent-time-registrations');

        $response->assertStatus(200)
            ->assertJsonCount(3);
    }

    /** @test */
    public function user_can_get_time_registrations_list()
    {
        // Create some time registrations
        TimeRegistration::factory()->count(5)->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/time-registrations');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'current_page',
                'total'
            ]);

        $this->assertCount(5, $response->json('data'));
    }

    /** @test */
    public function random_coordinates_are_generated_when_not_provided()
    {
        // Clock in without providing coordinates
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/time-registrations/clock-in', []);

        $response->assertStatus(201);
        
        // Check that coordinates were generated
        $this->assertNotNull($response->json('latitude'));
        $this->assertNotNull($response->json('longitude'));
    }
}
