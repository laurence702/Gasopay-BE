<?php

namespace Tests\Feature\Controllers\Api;

use App\Enums\RoleEnum;
use App\Enums\VehicleTypeEnum;
use App\Models\User;
use App\Models\Branch;
use App\Models\UserProfile;
use App\Models\VehicleType;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;
use Illuminate\Contracts\Auth\Authenticatable;
use function Pest\Laravel\{getJson, postJson, putJson, deleteJson};
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use App\Enums\ProfileVerificationStatusEnum;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $admin; // Branch Admin
    protected User $rider;
    protected User $regularUser;
    protected Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();


        $this->superAdmin = User::factory()->create(['role' => RoleEnum::SuperAdmin]);
        $this->branch = Branch::factory()->create();
        $this->admin = User::factory()->create([
            'role' => RoleEnum::Admin,
            'branch_id' => $this->branch->id,
        ]);
        $this->rider = User::factory()->create([
            'role' => RoleEnum::Rider, 
            'branch_id' => $this->branch->id,
            'verification_status' => ProfileVerificationStatusEnum::PENDING
        ]);
        $this->regularUser = User::factory()->create(['role' => RoleEnum::Regular]);

        UserProfile::factory()->for($this->rider)->create(); 
    }

    public function test_authenticated_user_can_list_users()
    {
        /** @var Authenticatable $user */
        $user = User::factory()->create(['role' => RoleEnum::Admin]);
        $this->actingAs($user);

        $response = $this->getJson('/api/users');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'fullname',
                        'email',
                        'phone',
                        'role',
                        'branch_id',
                        'branch',
                        'user_profile',
                        'created_at',
                        'updated_at',
                    ]
                ],
                'links',
                'meta'
            ]);
    }

    public function test_authenticated_user_can_list_users_with_order_aggregates()
    {
        /** @var Authenticatable $admin */
        $admin = User::factory()->create(['role' => RoleEnum::Admin]);
        $this->actingAs($admin);

        /** @var User $testUser */
        $testUser = User::factory()->create();
        $branch = Branch::factory()->create(); // Create a branch for the orders

        // Create orders for the testUser with the correct fields
        Order::factory()->for($testUser, 'payer')->for($branch, 'branch')->create([
            'amount_due' => 10000,
            'product' => 'cng',
            'payment_status' => 'pending' // Use payment_status instead of status
        ]);
        
        Order::factory()->for($testUser, 'payer')->for($branch, 'branch')->create([
            'amount_due' => 15000,
            'product' => 'cng',
            'payment_status' => 'pending' // Use payment_status instead of status
        ]);

        $response = $this->getJson('/api/users');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'fullname',
                        'email',
                        'phone',
                        'role',
                        'orders_count',
                    ]
                ],
                'links',
                'meta'
            ])
            ->assertJsonFragment([
                'id' => $testUser->id,
                'orders_count' => 2,
            ]);
    }

    public function test_branch_admin_can_register_a_rider()
    {
        /** @var Authenticatable $admin */
        $admin = User::factory()->create(['role' => RoleEnum::Admin]);
        $this->actingAs($admin);

        $userData = [
            'fullname' => 'Test Rider',
            'email' => 'rider@example.com',
            'phone' => '1234567890',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'address' => '123 Test St',
            'vehicle_type' => VehicleTypeEnum::Car->value,
            'nin' => '1234567890',
            'guarantors_name' => 'Test Guarantor',
            'guarantors_address' => '456 Guarantor Ave',
            'guarantors_phone' => '5551234567',
            'profilePicUrl' => 'http://example.com/pic.jpg',
        ];

        $response = $this->postJson('/api/register-rider', $userData);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'fullname',
                    'email',
                    'phone',
                    'role',
                    'branch_id',
                    'branch',
                    'user_profile',
                    'created_at',
                    'updated_at',
                ]
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'rider@example.com',
            'role' => RoleEnum::Rider->value,
        ]);

        $this->assertDatabaseHas('user_profiles', [
            'user_id' => $response->json('data.id'),
            'vehicle_type' => VehicleTypeEnum::Car->value,
        ]);
    }

    public function test_admin_can_view_a_specific_user()
    {
        /** @var Authenticatable $admin */
        $admin = User::factory()->create(['role' => RoleEnum::Admin]);
        $this->actingAs($admin);

        $targetUser = User::factory()->create();

        $response = $this->getJson("/api/users/{$targetUser->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'fullname',
                    'email',
                    'phone',
                    'role',
                    'branch_id',
                    'branch',
                    'user_profile',
                    'created_at',
                    'updated_at',
                ]
            ]);
    }

    public function test_authenticated_user_can_update_a_user()
    {
        /** @var Authenticatable $admin */
        $admin = User::factory()->create(['role' => RoleEnum::Admin]);
        $this->actingAs($admin);

        $targetUser = User::factory()->create();

        $updateData = [
            'fullname' => 'Updated Name',
            'email' => 'updated@example.com',
            'phone' => '0987654321',
        ];

        $response = $this->putJson("/api/users/{$targetUser->id}", $updateData);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'fullname',
                    'email',
                    'phone',
                    'role',
                    'branch_id',
                    'branch',
                    'user_profile',
                    'created_at',
                    'updated_at',
                ]
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $targetUser->id,
            'fullname' => 'Updated Name',
            'email' => 'updated@example.com',
            'phone' => '0987654321',
        ]);
    }

    public function test_authenticated_user_can_delete_a_user()
    {
        /** @var Authenticatable $admin */
        $admin = User::factory()->create(['role' => RoleEnum::Admin]);
        $this->actingAs($admin);

        $targetUser = User::factory()->create();

        $response = $this->deleteJson("/api/users/{$targetUser->id}");

        $response->assertOk()
            ->assertJson([
                'message' => 'User deleted successfully'
            ]);

        $this->assertSoftDeleted('users', [
            'id' => $targetUser->id,
        ]);
    }

    public function test_user_can_be_restored()
    {
        $admin = User::factory()->create(['role' => RoleEnum::Admin]);
        $this->actingAs($admin);

        // Create a trashed user directly using the factory state
        $trashedUser = User::factory()->trashed()->create();

        $response = $this->postJson("/api/users/{$trashedUser->id}/restore"); 

        $response->assertOk()
            ->assertJson([
                'message' => 'User restored successfully'
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $trashedUser->id,
            'deleted_at' => null,
        ]);
    }

    public function test_user_can_be_force_deleted()
    {
        $admin = User::factory()->create(['role' => RoleEnum::Admin]);
        $this->actingAs($admin);

         // Create a trashed user directly using the factory state
        $trashedUser = User::factory()->trashed()->create();

        $response = $this->deleteJson("/api/users/{$trashedUser->id}/force"); 

        $response->assertOk()
            ->assertJson([
                'message' => 'User permanently deleted'
            ]);

        $this->assertDatabaseMissing('users', [
            'id' => $trashedUser->id,
        ]);
    }

    public function test_unauthenticated_user_cannot_access_user_endpoints()
    {
        $response = $this->getJson('/api/users');
        $response->assertUnauthorized();

        // POST to /api/users is not allowed (405) as we use specific endpoints for user creation
        $response = $this->postJson('/api/users', []);
        $response->assertStatus(405);

        $response = $this->getJson('/api/users/1');
        $response->assertUnauthorized();

        $response = $this->putJson('/api/users/1', []);
        $response->assertUnauthorized();

        $response = $this->deleteJson('/api/users/1');
        $response->assertUnauthorized();
    }

    public function test_users_can_be_searched()
    {
        /** @var Authenticatable $admin */
        $admin = User::factory()->create(['role' => RoleEnum::Admin]);
        $this->actingAs($admin);

        $user = User::factory()->create([
            'fullname' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '1234567890',
        ]);

        $response = $this->getJson('/api/users?search=John');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'fullname',
                        'email',
                        'phone',
                        'role',
                        'branch_id',
                        'branch',
                        'user_profile',
                        'created_at',
                        'updated_at',
                    ]
                ],
                'links',
                'meta'
            ])
            ->assertJsonFragment([
                'fullname' => 'John Doe',
                'email' => 'john@example.com',
            ]);
    }

    // ======================================
    // Rider Verification Status Tests
    // ======================================

    public function test_super_admin_can_update_rider_verification_status()
    {
        $superAdmin = User::factory()->create(['role' => RoleEnum::SuperAdmin]);
        $rider = User::factory()->hasUserProfile()->create(['role' => RoleEnum::Rider]);

        Sanctum::actingAs($superAdmin);

        $response = $this->putJson(route('users.update_verification_status'), [
            'rider_id' => $rider->id,
            'status' => ProfileVerificationStatusEnum::VERIFIED->value,
        ]);

        $response->assertOk()
            ->assertJsonStructure(['status', 'message', 'data' => ['id', 'verification_status']])
            ->assertJsonPath('data.verification_status', ProfileVerificationStatusEnum::VERIFIED->value);

        $this->assertDatabaseHas('users', [
            'id' => $rider->id,
            'verification_status' => ProfileVerificationStatusEnum::VERIFIED->value,
        ]);
    }

    public function test_admin_can_update_rider_verification_status()
    {
        $admin = User::factory()->create(['role' => RoleEnum::Admin]);
        $rider = User::factory()->hasUserProfile()->create(['role' => RoleEnum::Rider]);

        Sanctum::actingAs($admin);

        $response = $this->putJson(route('users.update_verification_status'), [
            'rider_id' => $rider->id,
            'status' => ProfileVerificationStatusEnum::REJECTED->value,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.verification_status', ProfileVerificationStatusEnum::REJECTED->value);

        $this->assertDatabaseHas('users', [
            'id' => $rider->id,
            'verification_status' => ProfileVerificationStatusEnum::REJECTED->value,
        ]);
    }

    public function test_regular_user_cannot_update_rider_verification_status()
    {
        $regularUser = User::factory()->create(['role' => RoleEnum::Regular]);
        $rider = User::factory()->hasUserProfile()->create(['role' => RoleEnum::Rider]);

        Sanctum::actingAs($regularUser);

        $response = $this->putJson(route('users.update_verification_status'), [
            'rider_id' => $rider->id,
            'status' => ProfileVerificationStatusEnum::VERIFIED->value,
        ]);

        $response->assertForbidden(); // Expect 403 due to CheckAdminOrSuperAdmin middleware
    }

    public function test_cannot_update_status_for_non_rider()
    {
        $admin = User::factory()->create(['role' => RoleEnum::Admin]);
        $nonRider = User::factory()->create(['role' => RoleEnum::Regular]); // Not a rider

        Sanctum::actingAs($admin);

        $response = $this->putJson(route('users.update_verification_status'), [
            'rider_id' => $nonRider->id,
            'status' => ProfileVerificationStatusEnum::VERIFIED->value,
        ]);

        $response->assertStatus(400) // Bad request
            ->assertJsonPath('message', 'User is not a rider.');
    }

    public function test_cannot_verify_rider_without_profile()
    {
        $admin = User::factory()->create(['role' => RoleEnum::Admin]);
        // Create rider without calling hasUserProfile()
        $riderWithoutProfile = User::factory()->create(['role' => RoleEnum::Rider]);

        Sanctum::actingAs($admin);

        $response = $this->putJson(route('users.update_verification_status'), [
            'rider_id' => $riderWithoutProfile->id,
            'status' => ProfileVerificationStatusEnum::VERIFIED->value,
        ]);

        $response->assertStatus(400) // Bad request
             ->assertJsonPath('message', 'Cannot verify rider without a profile.');
    }

    public function test_update_verification_status_validation_fails()
    {
        $admin = User::factory()->create(['role' => RoleEnum::Admin]);
        $rider = User::factory()->hasUserProfile()->create(['role' => RoleEnum::Rider]);

        Sanctum::actingAs($admin);

        // Missing status
        $response = $this->putJson(route('users.update_verification_status'), [
            'rider_id' => $rider->id, 
        ]);
        $response->assertStatus(422)->assertJsonValidationErrors('status');

        // Invalid status
        $response = $this->putJson(route('users.update_verification_status'), [
            'rider_id' => $rider->id, 
            'status' => 'invalid_status'
        ]);
        $response->assertStatus(422)->assertJsonValidationErrors('status');

        // Missing rider_id
        $response = $this->putJson(route('users.update_verification_status'), [
            'status' => ProfileVerificationStatusEnum::VERIFIED->value,
        ]);
        $response->assertStatus(422)->assertJsonValidationErrors('rider_id');
    }

    // ======================================
    // Ban User Tests
    // ======================================

    public function test_super_admin_can_ban_user()
    {
        $superAdmin = User::factory()->create(['role' => RoleEnum::SuperAdmin]);
        $userToBan = User::factory()->create(['role' => RoleEnum::Regular]);
        Sanctum::actingAs($superAdmin);

        $response = $this->postJson(route('users.ban', ['user' => $userToBan->id]));

        $response->assertOk()
                 ->assertJsonPath('message', 'User banned successfully.')
                 ->assertJsonPath('data.id', $userToBan->id)
                 ->assertJsonPath('data.banned_at', fn ($bannedAt) => $bannedAt !== null);

        $this->assertNotNull($userToBan->fresh()->banned_at);
    }

    public function test_admin_can_ban_user()
    {
        $admin = User::factory()->create(['role' => RoleEnum::Admin]);
        $userToBan = User::factory()->create(['role' => RoleEnum::Rider]);
        Sanctum::actingAs($admin);

        $response = $this->postJson(route('users.ban', ['user' => $userToBan->id]));

        $response->assertOk();
        $this->assertNotNull($userToBan->fresh()->banned_at);
    }

    public function test_cannot_ban_super_admin()
    {
        $admin = User::factory()->create(['role' => RoleEnum::Admin]);
        $superAdminToBan = User::factory()->create(['role' => RoleEnum::SuperAdmin]);
        Sanctum::actingAs($admin);

        $response = $this->postJson(route('users.ban', ['user' => $superAdminToBan->id]));

        $response->assertForbidden() // 403
                 ->assertJsonPath('message', 'Cannot ban a Super Admin.');
        $this->assertNull($superAdminToBan->fresh()->banned_at);
    }

    public function test_cannot_ban_self()
    {
        $admin = User::factory()->create(['role' => RoleEnum::Admin]);
        Sanctum::actingAs($admin);

        $response = $this->postJson(route('users.ban', ['user' => $admin->id]));

        $response->assertStatus(400) // Bad Request
                 ->assertJsonPath('message', 'You cannot ban yourself.');
        $this->assertNull($admin->fresh()->banned_at);
    }

    public function test_regular_user_cannot_ban_user()
    {
        $regularUser = User::factory()->create(['role' => RoleEnum::Regular]);
        $userToBan = User::factory()->create(['role' => RoleEnum::Rider]);
        Sanctum::actingAs($regularUser);

        $response = $this->postJson(route('users.ban', ['user' => $userToBan->id]));

        $response->assertForbidden(); // Expect 403 due to CheckAdminOrSuperAdmin middleware
        $this->assertNull($userToBan->fresh()->banned_at);
    }

    public function test_cannot_ban_already_banned_user()
    {
        $admin = User::factory()->create(['role' => RoleEnum::Admin]);
        $userToBan = User::factory()->create(['role' => RoleEnum::Regular, 'banned_at' => now()]);
        Sanctum::actingAs($admin);

        $response = $this->postJson(route('users.ban', ['user' => $userToBan->id]));

        $response->assertStatus(400) // Bad Request
                 ->assertJsonPath('message', 'User is already banned.');
    }

    public function test_banned_user_cannot_login()
    {
        $bannedUser = User::factory()->create([
            'email' => 'banned@example.com',
            'password' => Hash::make('password123'),
            'role' => RoleEnum::Regular,
            'banned_at' => now()
        ]);

        $response = $this->postJson(route('login'), [
            'login_identifier' => 'banned@example.com',
            'password' => 'password123',
        ]);

        $response->assertForbidden() // 403 as implemented in AuthController
            ->assertJsonPath('message', 'Your account has been suspended.');
    }
} 
