<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\UserProfile;
use App\Enums\RoleEnum;
use App\Enums\ProfileVerificationStatusEnum; // Use the correct Enum
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Facades\Cache;

class UserVerificationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $rider;
    private User $nonRider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => RoleEnum::Admin]);
        $this->rider = User::factory()->create(['role' => RoleEnum::Rider, 'verification_status' => ProfileVerificationStatusEnum::PENDING]);
        $this->nonRider = User::factory()->create();
        if ($this->nonRider->role === RoleEnum::Rider) {
             $this->nonRider = User::factory()->create(['role' => RoleEnum::Admin]);
        }

        // Create a profile for the rider
        UserProfile::factory()->create(['user_id' => $this->rider->id]);

        // Define the authorization gate (mirroring AuthServiceProvider)
        Gate::define('update-verification-status', function (User $user) {
            return $user->role === RoleEnum::Admin || $user->role === RoleEnum::SuperAdmin;
        });

        // Authenticate as admin for most tests
        Sanctum::actingAs($this->admin);
    }

    public function test_admin_can_verify_rider(): void
    {
        Sanctum::actingAs($this->admin);
        $response = $this->putJson(route('users.update_verification_status'), [
            'rider_id' => $this->rider->id,
            'status' => ProfileVerificationStatusEnum::VERIFIED->value,
        ]);

        $response->assertStatus(200)
                 ->assertJsonPath('data.verification_status', ProfileVerificationStatusEnum::VERIFIED->value)
                 ->assertJsonPath('message', 'Rider status updated to verified.');

        $this->rider->refresh();
        $this->assertEquals(ProfileVerificationStatusEnum::VERIFIED, $this->rider->verification_status);
    }

    public function test_admin_can_reject_rider(): void
    {
        Sanctum::actingAs($this->admin);
        $response = $this->putJson(route('users.update_verification_status'), [
            'rider_id' => $this->rider->id,
            'status' => ProfileVerificationStatusEnum::REJECTED->value,
        ]);

        $response->assertStatus(200)
                 ->assertJsonPath('data.verification_status', ProfileVerificationStatusEnum::REJECTED->value)
                 ->assertJsonPath('message', 'Rider status updated to rejected.');

        $this->rider->refresh();
        $this->assertEquals(ProfileVerificationStatusEnum::REJECTED, $this->rider->verification_status);
    }

    public function test_admin_can_set_rider_status_to_pending(): void
    {
        Sanctum::actingAs($this->admin);
        $response = $this->putJson(route('users.update_verification_status'), [
            'rider_id' => $this->rider->id,
            'status' => ProfileVerificationStatusEnum::PENDING->value,
        ]);

        $response->assertStatus(200)
                 ->assertJsonPath('data.verification_status', ProfileVerificationStatusEnum::PENDING->value)
                 ->assertJsonPath('message', 'Rider status updated to pending.');

        $this->rider->refresh();
        $this->assertEquals(ProfileVerificationStatusEnum::PENDING, $this->rider->verification_status);
    }

    public function test_cannot_update_status_for_non_rider_user(): void
    {
        Sanctum::actingAs($this->admin);
        $response = $this->putJson(route('users.update_verification_status'), [
            'rider_id' => $this->nonRider->id,
            'status' => ProfileVerificationStatusEnum::VERIFIED->value,
        ]);

        $response->assertStatus(400)
                 ->assertJsonPath('message', 'User is not a rider.');
    }

    public function test_cannot_verify_rider_without_profile(): void
    {
        $riderWithoutProfile = User::factory()->create(['role' => RoleEnum::Rider]);
        Sanctum::actingAs($this->admin);
        $response = $this->putJson(route('users.update_verification_status'), [
            'rider_id' => $riderWithoutProfile->id,
            'status' => ProfileVerificationStatusEnum::VERIFIED->value,
        ]);

        $response->assertStatus(400)
                 ->assertJsonPath('message', 'Cannot verify rider without a profile.');
    }

    public function test_validation_fails_with_invalid_status(): void
    {
        Sanctum::actingAs($this->admin);
        $response = $this->putJson(route('users.update_verification_status'), [
            'rider_id' => $this->rider->id,
            'status' => 'invalid_status_value',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('status');
    }

    public function test_validation_fails_without_status(): void
    {
        Sanctum::actingAs($this->admin);
        $response = $this->putJson(route('users.update_verification_status'), [
            'rider_id' => $this->rider->id,
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('status');
    }

    public function test_non_admin_cannot_update_rider_status(): void
    {
        $regularUser = User::factory()->create(['role' => RoleEnum::Regular]);
        Sanctum::actingAs($regularUser);
        $response = $this->putJson(route('users.update_verification_status'), [
            'rider_id' => $this->rider->id,
            'status' => ProfileVerificationStatusEnum::VERIFIED->value,
        ]);
        $response->assertStatus(403); // Forbidden
    }
} 