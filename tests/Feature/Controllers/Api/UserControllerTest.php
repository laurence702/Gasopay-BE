<?php

namespace Tests\Feature\Controllers\Api;

use App\Enums\RoleEnum;
use App\Enums\VehicleTypeEnum;
use App\Models\User;
use App\Models\Branch;
use App\Models\UserProfile;
use App\Models\VehicleType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;
use Illuminate\Contracts\Auth\Authenticatable;
use function Pest\Laravel\{getJson, postJson, putJson, deleteJson};
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Str;

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
            'profile_verified' => false
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
            'role' => RoleEnum::Rider->value,
            'address' => '123 Test St',
            'vehicle_type' => VehicleTypeEnum::Car->value,
            'nin' => '1234567890',
            'guarantors_name' => 'Test Guarantor',
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
        /** @var Authenticatable $admin */
        $admin = User::factory()->create(['role' => RoleEnum::Admin]);
        $this->actingAs($admin);

        $targetUser = User::factory()->create();
        $targetUser->delete();

        $response = $this->postJson("/api/users/{$targetUser->id}/restore");

        $response->assertOk()
            ->assertJson([
                'message' => 'User restored successfully'
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $targetUser->id,
            'deleted_at' => null,
        ]);
    }

    public function test_user_can_be_force_deleted()
    {
        /** @var Authenticatable $admin */
        $admin = User::factory()->create(['role' => RoleEnum::Admin]);
        $this->actingAs($admin);

        $targetUser = User::factory()->create();
        $targetUser->delete();

        $response = $this->deleteJson("/api/users/{$targetUser->id}/force");

        $response->assertOk()
            ->assertJson([
                'message' => 'User permanently deleted'
            ]);

        $this->assertDatabaseMissing('users', [
            'id' => $targetUser->id,
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

    /** @test */
    public function super_admin_can_toggle_rider_verification(): void
    {
        Sanctum::actingAs($this->superAdmin);

        $this->assertFalse($this->rider->profile_verified); // Check initial state

        $response = $this->patchJson(route('users.toggle-verification', $this->rider->id));

        $response->assertOk()
                 ->assertJsonStructure(['success', 'message', 'data' => ['id', 'fullname', 'profile_verified']])
                 ->assertJsonPath('data.profile_verified', true);

        $this->rider->refresh();
        $this->assertTrue($this->rider->profile_verified);

        // Toggle back to unverified
        $response = $this->patchJson(route('users.toggle-verification', $this->rider->id));
        $response->assertOk()
                 ->assertJsonPath('data.profile_verified', false);
                 
        $this->rider->refresh();
        $this->assertFalse($this->rider->profile_verified);
    }

    /** @test */
    public function admin_can_toggle_rider_verification(): void
    {
        Sanctum::actingAs($this->admin);

        $this->assertFalse($this->rider->profile_verified);

        $response = $this->patchJson(route('users.toggle-verification', $this->rider->id));

        $response->assertOk()
                 ->assertJsonPath('data.profile_verified', true);

        $this->rider->refresh();
        $this->assertTrue($this->rider->profile_verified);
    }

    /** @test */
    public function regular_user_cannot_toggle_rider_verification(): void
    {
        Sanctum::actingAs($this->regularUser);

        $response = $this->patchJson(route('users.toggle-verification', $this->rider->id));

        $response->assertForbidden(); // Expecting 403 due to Gate
        $this->assertFalse($this->rider->refresh()->profile_verified); // Ensure status didn't change
    }

    /** @test */
    public function rider_cannot_toggle_own_verification(): void
    {
        Sanctum::actingAs($this->rider);

        $response = $this->patchJson(route('users.toggle-verification', $this->rider->id));

        $response->assertForbidden(); // Expecting 403 due to Gate
        $this->assertFalse($this->rider->refresh()->profile_verified);
    }

    /** @test */
    public function cannot_toggle_verification_for_non_rider(): void
    {
        Sanctum::actingAs($this->superAdmin);

        // Target the regular user instead of the rider
        $response = $this->patchJson(route('users.toggle-verification', $this->regularUser->id));

        // Expecting 400 Bad Request as implemented in the controller
        $response->assertStatus(400)
                 ->assertJsonPath('message', 'User is not a rider.'); 
    }

    /** @test */
    public function cannot_toggle_verification_for_non_existent_user(): void
    {
        Sanctum::actingAs($this->superAdmin);
        $nonExistentId = (string) Str::ulid(); // Generate a valid but non-existent ID

        $response = $this->patchJson(route('users.toggle-verification', $nonExistentId));

        $response->assertNotFound(); // Expecting 404
    }
} 
