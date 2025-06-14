<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Branch;
use App\Models\UserProfile;
use App\Enums\RoleEnum;
use App\Enums\ProfileVerificationStatusEnum; // Use the correct Enum
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Response;

class UserVerificationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $rider;
    private User $regular;
    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        // Create branch first
        $this->branch = Branch::factory()->create();

        // Create users with proper roles and branch assignments
        $this->admin = User::factory()->admin()->create(['branch_id' => $this->branch->id]);
        $this->rider = User::factory()->rider()->create([
            'branch_id' => $this->branch->id,
            'verification_status' => ProfileVerificationStatusEnum::PENDING
        ]);
        $this->regular = User::factory()->create(['branch_id' => $this->branch->id]);

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
        $response = $this->putJson(route('branch-admin.update-verification', ['id' => $this->rider->id]), [
            'status' => ProfileVerificationStatusEnum::VERIFIED->value,
        ]);

        $response->assertStatus(Response::HTTP_OK)
                 ->assertJsonPath('rider.verification_status', ProfileVerificationStatusEnum::VERIFIED->value)
                 ->assertJsonPath('message', 'Rider verification status updated successfully');

        $this->rider->refresh();
        $this->assertEquals(ProfileVerificationStatusEnum::VERIFIED, $this->rider->verification_status);
        $this->assertNotNull($this->rider->email_verified_at);
        $this->assertNull($this->rider->rejection_reason);
        $this->assertNull($this->rider->rejected_by);
        $this->assertNull($this->rider->rejected_at);
    }

    public function test_admin_can_reject_rider(): void
    {
        $rejectionReason = 'Incomplete documents provided';
        $response = $this->putJson(route('branch-admin.update-verification', ['id' => $this->rider->id]), [
            'status' => ProfileVerificationStatusEnum::REJECTED->value,
            'rejection_reason' => $rejectionReason,
        ]);

        $response->assertStatus(Response::HTTP_OK)
                 ->assertJsonPath('rider.verification_status', ProfileVerificationStatusEnum::REJECTED->value)
                 ->assertJsonPath('message', 'Rider verification status updated successfully');

        $this->rider->refresh();
        $this->assertEquals(ProfileVerificationStatusEnum::REJECTED, $this->rider->verification_status);
        $this->assertEquals($rejectionReason, $this->rider->rejection_reason);
        $this->assertEquals($this->admin->fullname, $this->rider->rejected_by);
        $this->assertNotNull($this->rider->rejected_at);
    }

    public function test_admin_can_set_rider_status_to_pending(): void
    {
        // First, set to verified, then try to set to pending
        $this->rider->update([
            'verification_status' => ProfileVerificationStatusEnum::VERIFIED,
            'email_verified_at' => now(),
        ]);

        $response = $this->putJson(route('branch-admin.update-verification', ['id' => $this->rider->id]), [
            'status' => ProfileVerificationStatusEnum::PENDING->value,
        ]);

        $response->assertStatus(Response::HTTP_OK)
                 ->assertJsonPath('rider.verification_status', ProfileVerificationStatusEnum::PENDING->value)
                 ->assertJsonPath('message', 'Rider verification status updated successfully');

        $this->rider->refresh();
        $this->assertEquals(ProfileVerificationStatusEnum::PENDING, $this->rider->verification_status);
        $this->assertNull($this->rider->email_verified_at);
    }

    public function test_cannot_update_status_for_non_rider_user(): void
    {
        $response = $this->putJson(route('branch-admin.update-verification', ['id' => $this->regular->id]), [
            'status' => ProfileVerificationStatusEnum::VERIFIED->value,
        ]);

        $response->assertStatus(Response::HTTP_BAD_REQUEST)
                 ->assertJsonPath('error', 'User is not a rider');
    }

    public function test_cannot_verify_rider_without_profile(): void
    {
        $riderWithoutProfile = User::factory()->rider()->create([
            'branch_id' => $this->branch->id,
            'verification_status' => ProfileVerificationStatusEnum::PENDING
        ]);
        // Ensure no user profile exists for this rider
        $riderWithoutProfile->userProfile()->delete();

        $response = $this->putJson(route('branch-admin.update-verification', ['id' => $riderWithoutProfile->id]), [
            'status' => ProfileVerificationStatusEnum::VERIFIED->value,
        ]);

        $response->assertStatus(Response::HTTP_BAD_REQUEST)
                 ->assertJsonPath('error', 'Cannot verify rider without a profile');
    }

    public function test_validation_fails_with_invalid_status(): void
    {
        $response = $this->putJson(route('branch-admin.update-verification', ['id' => $this->rider->id]), [
            'status' => 'invalid_status_value',
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)->assertJsonValidationErrors('status');
    }

    public function test_validation_fails_without_status(): void
    {
        $response = $this->putJson(route('branch-admin.update-verification', ['id' => $this->rider->id]), [
            // 'status' is missing
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)->assertJsonValidationErrors('status');
    }

    public function test_non_admin_cannot_update_rider_status(): void
    {
        $regularUser = User::factory()->create(); // Remove ->regular() as default is regular
        Sanctum::actingAs($regularUser);
        $response = $this->putJson(route('branch-admin.update-verification', ['id' => $this->rider->id]), [
            'status' => ProfileVerificationStatusEnum::VERIFIED->value,
        ]);
        $response->assertStatus(Response::HTTP_FORBIDDEN); // Forbidden
    }
} 