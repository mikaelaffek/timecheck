<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /** @test */
    public function user_can_login_with_correct_credentials()
    {
        $user = User::factory()->create([
            'personal_id' => '900101-1234',
            'password' => bcrypt('password'),
            'role' => 'user'
        ]);

        $response = $this->postJson('/api/login', [
            'personal_id' => '900101-1234',
            'password' => 'password'
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'user' => [
                    'id',
                    'name',
                    'email',
                    'personal_id',
                    'role'
                ],
                'token'
            ]);
    }

    /** @test */
    public function user_cannot_login_with_incorrect_credentials()
    {
        $user = User::factory()->create([
            'personal_id' => '900101-1234',
            'password' => bcrypt('password'),
            'role' => 'user'
        ]);

        $response = $this->postJson('/api/login', [
            'personal_id' => '900101-1234',
            'password' => 'wrong-password'
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function user_can_logout()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/logout');

        $response->assertStatus(200)
            ->assertJsonFragment([
                'message' => 'Logged out successfully'
            ]);

        // Verify token is deleted
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    /** @test */
    public function unauthenticated_user_cannot_access_protected_routes()
    {
        $response = $this->getJson('/api/user');

        $response->assertStatus(401);
    }

    /** @test */
    public function authenticated_user_can_access_protected_routes()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/user');

        $response->assertStatus(200)
            ->assertJsonFragment([
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email
            ]);
    }

    /** @test */
    public function user_can_change_password()
    {
        $user = User::factory()->create([
            'password' => bcrypt('old-password')
        ]);
        
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson('/api/user/password', [
            'current_password' => 'old-password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password'
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'message' => 'Password updated successfully'
            ]);

        // Verify we can login with the new password
        $this->postJson('/api/login', [
            'personal_id' => $user->personal_id,
            'password' => 'new-password'
        ])->assertStatus(200);
    }
}
