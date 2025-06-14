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
use App\Enums\PaymentMethodEnum;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Response;

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

        // Create branch first
        $this->branch = Branch::factory()->create();

        // Create users with proper roles and branch assignments
        $this->superAdmin = User::factory()->superAdmin()->create(['branch_id' => null]);
        $this->admin = User::factory()->admin()->create(['branch_id' => $this->branch->id]);
        $this->rider = User::factory()->rider()->create([
            'branch_id' => $this->branch->id,
            'verification_status' => ProfileVerificationStatusEnum::PENDING
        ]);
        $this->regularUser = User::factory()->create([
            'role' => RoleEnum::Regular,
            'branch_id' => $this->branch->id
        ]);

        // Create profile for rider
        UserProfile::factory()->for($this->rider)->create();
    }

    public function test_authenticated_user_can_list_users()
    {
        /** @var Authenticatable $user */
        $user = User::factory()->admin()->create(['branch_id' => $this->branch->id]);
        $this->actingAs($user);

        $response = $this->getJson('/api/users');

        $response->assertStatus(Response::HTTP_OK)
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
        $admin = User::factory()->admin()->create(['branch_id' => $this->branch->id]);
        $this->actingAs($admin);

        /** @var User $testUser */
        $testUser = User::factory()->create(['branch_id' => $this->branch->id]);

        // Create orders for the testUser with the correct fields
        $order1 = Order::factory()->create([
            'payer_id' => $testUser->id,
            'branch_id' => $this->branch->id,
            'amount_due' => 10000,
            'product' => 'cng',
            'payment_status' => 'pending',
            'payment_method' => PaymentMethodEnum::Cash->value,
        ]);
        
        $order2 = Order::factory()->create([
            'payer_id' => $testUser->id,
            'branch_id' => $this->branch->id,
            'amount_due' => 15000,
            'product' => 'cng',
            'payment_status' => 'pending',
            'payment_method' => PaymentMethodEnum::Cash->value,
        ]);

        $response = $this->getJson('/api/users');

        $response->assertStatus(Response::HTTP_OK)
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
        $admin = User::factory()->admin()->create(['branch_id' => $this->branch->id]);
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
            'branch_id' => $this->branch->id,
            'verify_now' => true,
        ];

        $response = $this->postJson('/api/branch-admin/riders', $userData);

        $response->assertCreated()
            ->assertJsonStructure([
                'message',
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
            ])
            ->assertJsonPath('message', 'Rider registered successfully by admin.');

        $this->assertDatabaseHas('users', [
            'email' => 'rider@example.com',
            'role' => RoleEnum::Rider->value,
            'branch_id' => $this->branch->id,
            'verification_status' => ProfileVerificationStatusEnum::VERIFIED->value,
        ]);

        $this->assertDatabaseHas('user_profiles', [
            'user_id' => $response->json('data.id'),
            'vehicle_type' => VehicleTypeEnum::Car->value,
        ]);
    }

    public function test_admin_can_view_a_specific_user()
    {
        /** @var Authenticatable $admin */
        $admin = User::factory()->admin()->create(['branch_id' => $this->branch->id]);
        $this->actingAs($admin);

        $targetUser = User::factory()->create(['branch_id' => $this->branch->id]);

        $response = $this->getJson("/api/users/{$targetUser->id}");

        $response->assertStatus(Response::HTTP_OK)
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

        $response->assertStatus(Response::HTTP_OK)
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

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'message' => 'User deleted successfully'
            ]);

        $this->assertSoftDeleted('users', [
            'id' => $targetUser->id,
        ]);
    }

    public function test_user_can_be_restored()
    {
        /** @var User $admin */
        $admin = User::factory()->create(['role' => RoleEnum::Admin]);
        $this->actingAs($admin);

        // Create a trashed user directly using the factory state
        $trashedUser = User::factory()->trashed()->create();

        $response = $this->postJson("/api/users/{$trashedUser->id}/restore"); 

        $response->assertStatus(Response::HTTP_OK)
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
        /** @var User $admin */
        $admin = User::factory()->create(['role' => RoleEnum::Admin]);
        $this->actingAs($admin);

         // Create a trashed user directly using the factory state
        $trashedUser = User::factory()->trashed()->create();

        $response = $this->deleteJson("/api/users/{$trashedUser->id}/force"); 

        $response->assertStatus(Response::HTTP_OK)
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
        $response->assertStatus(Response::HTTP_UNAUTHORIZED);

        // POST to /api/users is not allowed (405) as we use specific endpoints for user creation
        $response = $this->postJson('/api/users', []);
        $response->assertStatus(Response::HTTP_METHOD_NOT_ALLOWED);

        $response = $this->getJson('/api/users/1');
        $response->assertStatus(Response::HTTP_UNAUTHORIZED);

        $response = $this->putJson('/api/users/1', []);
        $response->assertStatus(Response::HTTP_UNAUTHORIZED);

        $response = $this->deleteJson('/api/users/1');
        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
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

        $response->assertStatus(Response::HTTP_OK)
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
    // Ban User Tests
    // ======================================

    public function test_super_admin_can_ban_user()
    {
        $superAdmin = User::factory()->create(['role' => RoleEnum::SuperAdmin->value]);
        $userToBan = User::factory()->create();
        Sanctum::actingAs($superAdmin);

        $response = $this->postJson(route('users.ban', ['user' => $userToBan->id]), [
            'ban_reason' => 'Optional reason for ban'
        ]);

        Log::info('Request payload for ban in test:', ['test' => __FUNCTION__, 'request' => $response->json()]);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonPath('message', 'User banned successfully.')
            ->assertJsonPath('data.id', $userToBan->id)
            ->assertJsonPath('data.banned_at', fn ($bannedAt) => $bannedAt !== null);
    }

    public function test_admin_can_ban_user()
    {
        $admin = User::factory()->create(['role' => RoleEnum::Admin->value]);
        $userToBan = User::factory()->create();
        Sanctum::actingAs($admin);

        $response = $this->postJson(route('users.ban', ['user' => $userToBan->id]), [
            'ban_reason' => 'Optional reason for ban'
        ]);

        Log::info('Request payload for ban in test:', ['test' => __FUNCTION__, 'request' => $response->json()]);

        $response->assertStatus(Response::HTTP_OK);
        $this->assertNotNull($userToBan->fresh()->banned_at);
    }

    public function test_cannot_ban_super_admin()
    {
        $admin = User::factory()->create(['role' => RoleEnum::Admin->value]);
        $superAdminToBan = User::factory()->create(['role' => RoleEnum::SuperAdmin->value]);
        Sanctum::actingAs($admin);

        $response = $this->postJson(route('users.ban', ['user' => $superAdminToBan->id]), [
            'ban_reason' => 'Optional reason for ban'
        ]);

        $response->assertStatus(Response::HTTP_FORBIDDEN); // 403
        $this->assertNull($superAdminToBan->fresh()->banned_at);
    }

    public function test_cannot_ban_self()
    {
        $admin = User::factory()->create(['role' => RoleEnum::Admin->value]);
        Sanctum::actingAs($admin);

        $response = $this->postJson(route('users.ban', ['user' => $admin->id]), [
            'ban_reason' => 'Optional reason for ban'
        ]);

        $response->assertStatus(Response::HTTP_FORBIDDEN); // 403
        $this->assertNull($admin->fresh()->banned_at);
    }

    public function test_regular_user_cannot_ban_user()
    {
        $regularUser = User::factory()->create(['role' => RoleEnum::Regular->value]);
        $userToBan = User::factory()->create();
        Sanctum::actingAs($regularUser);

        $response = $this->postJson(route('users.ban', ['user' => $userToBan->id]), [
            'ban_reason' => 'Optional reason for ban'
        ]);

        $response->assertStatus(Response::HTTP_FORBIDDEN); // 403
        $this->assertNull($userToBan->fresh()->banned_at);
    }

    public function test_cannot_ban_already_banned_user()
    {
        $admin = User::factory()->create(['role' => RoleEnum::Admin]);
        $userToBan = User::factory()->create(['role' => RoleEnum::Regular, 'banned_at' => now()]);
        Sanctum::actingAs($admin);

        $response = $this->postJson(route('users.ban', ['user' => $userToBan->id]));

        $response->assertStatus(Response::HTTP_FORBIDDEN); // 403
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

        $response->assertStatus(Response::HTTP_FORBIDDEN) // 403 as implemented in AuthController
            ->assertJsonPath('message', 'Your account has been suspended.');
    }

    public function test_can_access_soft_deleted_user()
    {
        /** @var Authenticatable $admin */
        $admin = User::factory()->admin()->create(['branch_id' => $this->branch->id]);
        $this->actingAs($admin);

        $targetUser = User::factory()->create(['branch_id' => $this->branch->id]);
        $targetUser->delete(); // Soft delete the user

        $response = $this->getJson("/api/users/{$targetUser->id}");

        $response->assertStatus(Response::HTTP_OK)
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
                    'deleted_at'
                ]
            ])
            ->assertJsonFragment([
                'id' => $targetUser->id,
                'deleted_at' => $targetUser->deleted_at->toJSON()
            ]);
    }

    public function test_can_restore_soft_deleted_user()
    {
        /** @var Authenticatable $admin */
        $admin = User::factory()->admin()->create(['branch_id' => $this->branch->id]);
        $this->actingAs($admin);

        $targetUser = User::factory()->create(['branch_id' => $this->branch->id]);
        $targetUser->delete(); // Soft delete the user

        $response = $this->postJson("/api/users/{$targetUser->id}/restore");

        $response->assertStatus(Response::HTTP_OK)
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
                    'deleted_at'
                ]
            ])
            ->assertJsonFragment([
                'id' => $targetUser->id,
                'deleted_at' => null
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $targetUser->id,
            'deleted_at' => null
        ]);
    }

    public function test_can_force_delete_soft_deleted_user()
    {
        /** @var Authenticatable $admin */
        $admin = User::factory()->admin()->create(['branch_id' => $this->branch->id]);
        $this->actingAs($admin);

        $targetUser = User::factory()->create(['branch_id' => $this->branch->id]);
        $targetUser->delete(); // Soft delete the user

        $response = $this->deleteJson("/api/users/{$targetUser->id}/force");

        $response->assertStatus(Response::HTTP_OK);

        $this->assertDatabaseMissing('users', [
            'id' => $targetUser->id
        ]);
    }
} 
