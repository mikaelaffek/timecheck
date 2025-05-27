<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class UserSettingsTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;
    protected $token;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a test user
        $this->user = User::factory()->create([
            'role' => 'user'
        ]);

        // Create a token for the user
        $this->token = $this->user->createToken('test-token')->plainTextToken;
    }

    /** @test */
    public function user_can_get_settings()
    {
        // Create settings for the user
        UserSetting::create([
            'user_id' => $this->user->id,
            'theme' => 'light',
            'notifications_enabled' => true,
            'language' => 'en'
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/user/settings');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'theme',
                'notifications_enabled',
                'language'
            ])
            ->assertJsonFragment([
                'theme' => 'light',
                'notifications_enabled' => true,
                'language' => 'en'
            ]);
    }

    /** @test */
    public function user_can_update_settings()
    {
        // Create initial settings
        UserSetting::create([
            'user_id' => $this->user->id,
            'theme' => 'light',
            'notifications_enabled' => true,
            'language' => 'en'
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->putJson('/api/user/settings', [
            'theme' => 'dark',
            'notifications_enabled' => false,
            'language' => 'sv'
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'theme' => 'dark',
                'notifications_enabled' => false,
                'language' => 'sv'
            ]);

        // Verify the database was updated
        $this->assertDatabaseHas('user_settings', [
            'user_id' => $this->user->id,
            'theme' => 'dark',
            'notifications_enabled' => 0,
            'language' => 'sv'
        ]);
    }

    /** @test */
    public function user_can_update_profile()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->putJson('/api/user/profile', [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'phone' => '123-456-7890',
            'department_id' => 1
        ]);

        $response->assertStatus(200);

        // Verify the database was updated
        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'phone' => '123-456-7890',
            'department_id' => 1
        ]);
    }

    /** @test */
    public function user_cannot_update_profile_with_invalid_data()
    {
        // Create another user with this email to test unique validation
        User::factory()->create([
            'email' => 'taken@example.com'
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->putJson('/api/user/profile', [
            'name' => '', // Empty name should fail validation
            'email' => 'taken@example.com', // Email already taken should fail
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email']);
    }
}
