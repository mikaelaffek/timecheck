<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\TimeRegistration;
use Laravel\Sanctum\Sanctum;
use Carbon\Carbon;

class TimeRegistrationEditTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    public function setUp(): void
    {
        parent::setUp();
        
        // Create a test user
        $this->user = User::factory()->create();
    }

    /**
     * Test editing a time registration with valid data (no overlaps)
     */
    public function test_edit_time_registration_with_valid_data()
    {
        // Authenticate the user
        Sanctum::actingAs($this->user);
        
        // Create a time registration for the user
        $timeRegistration = TimeRegistration::create([
            'user_id' => $this->user->id,
            'date' => '2025-05-28',
            'clock_in' => '09:00:00',
            'clock_out' => '12:00:00',
            'total_hours' => 3,
            'status' => 'pending'
        ]);
        
        // Data for updating the time registration
        $updateData = [
            'clock_in' => '10:00:00',
            'clock_out' => '13:00:00'
        ];
        
        // Send update request
        $response = $this->putJson("/api/time-registrations/{$timeRegistration->id}", $updateData);
        
        // Assert the response is successful
        $response->assertStatus(200);
        
        // Assert the time registration was updated
        $this->assertDatabaseHas('time_registrations', [
            'id' => $timeRegistration->id,
            'clock_in' => '10:00:00',
            'clock_out' => '13:00:00',
            'total_hours' => 3 // Should be recalculated
        ]);
    }

    /**
     * Test editing a time registration with overlapping data
     */
    public function test_edit_time_registration_with_overlapping_data()
    {
        // Authenticate the user
        Sanctum::actingAs($this->user);
        
        // Create two time registrations for the user on the same day
        $timeRegistration1 = TimeRegistration::create([
            'user_id' => $this->user->id,
            'date' => '2025-05-28',
            'clock_in' => '09:00:00',
            'clock_out' => '12:00:00',
            'total_hours' => 3,
            'status' => 'pending'
        ]);
        
        $timeRegistration2 = TimeRegistration::create([
            'user_id' => $this->user->id,
            'date' => '2025-05-28',
            'clock_in' => '13:00:00',
            'clock_out' => '17:00:00',
            'total_hours' => 4,
            'status' => 'pending'
        ]);
        
        // Try to update the first time registration to overlap with the second one
        $updateData = [
            'clock_in' => '09:00:00',
            'clock_out' => '14:00:00' // This overlaps with timeRegistration2
        ];
        
        // Send update request
        $response = $this->putJson("/api/time-registrations/{$timeRegistration1->id}", $updateData);
        
        // Assert the response indicates an error
        $response->assertStatus(422);
        
        // Assert the response contains an error message about overlapping
        $response->assertJsonPath('message', 'Time registration overlaps with existing registration');
        
        // Assert the original time registration was not changed
        $this->assertDatabaseHas('time_registrations', [
            'id' => $timeRegistration1->id,
            'clock_in' => '09:00:00',
            'clock_out' => '12:00:00'
        ]);
    }

    /**
     * Test editing a time registration on a different date (should not check for overlaps with other dates)
     */
    public function test_edit_time_registration_on_different_date()
    {
        // Authenticate the user
        Sanctum::actingAs($this->user);
        
        // Create two time registrations for the user on different days
        $timeRegistration1 = TimeRegistration::create([
            'user_id' => $this->user->id,
            'date' => '2025-05-28',
            'clock_in' => '09:00:00',
            'clock_out' => '12:00:00',
            'total_hours' => 3,
            'status' => 'pending'
        ]);
        
        $timeRegistration2 = TimeRegistration::create([
            'user_id' => $this->user->id,
            'date' => '2025-05-29', // Different date
            'clock_in' => '10:00:00',
            'clock_out' => '14:00:00',
            'total_hours' => 4,
            'status' => 'pending'
        ]);
        
        // Update the second time registration with same times as the first (but on different date)
        $updateData = [
            'clock_in' => '09:00:00',
            'clock_out' => '12:00:00'
        ];
        
        // Send update request
        $response = $this->putJson("/api/time-registrations/{$timeRegistration2->id}", $updateData);
        
        // Assert the response is successful (no overlap check across different dates)
        $response->assertStatus(200);
        
        // Assert the time registration was updated
        $this->assertDatabaseHas('time_registrations', [
            'id' => $timeRegistration2->id,
            'date' => '2025-05-29',
            'clock_in' => '09:00:00',
            'clock_out' => '12:00:00'
        ]);
    }
}
