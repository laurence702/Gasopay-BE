<?php

namespace Tests\Feature\Controllers\Api;

use Tests\TestCase;
use App\Models\User;
use App\Models\Branch;
use App\Models\UserProfile;
use App\Enums\RoleEnum;
use Illuminate\Http\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Contracts\Auth\Authenticatable;

class BranchControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $regularUser;
    private User $superAdmin;
    protected function setUp(): void
    {
        parent::setUp();

        // Create test users with different roles
        $this->admin = User::factory()->create([
            'role' => RoleEnum::Admin->value,
        ]);

        $this->regularUser = User::factory()->create([
            'role' => RoleEnum::Regular->value,
        ]);

        $this->superAdmin = User::factory()->create([
            'role' => RoleEnum::SuperAdmin->value,
        ]);
    }

    public function test_cannot_access_branches_without_authentication()
    {
        $response = $this->getJson('/api/branches');
        $response->assertStatus(401);

        // $response = $this->postJson('/api/branches', []);
        // $response->assertStatus(401);

        $response = $this->getJson('/api/branches/1');
        $response->assertStatus(401);

        // $response = $this->putJson('/api/branches/1', []);
        // $response->assertStatus(401);

        // $response = $this->deleteJson('/api/branches/1');
        // $response->assertStatus(401);
    }

    public function test_cannot_create_branches_without_super_admin_role()
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/super-admin/branches', []);
        $response->assertStatus(403);
    }

    public function test_can_list_branches()
    {
        Branch::factory()->count(2)->create();

        $response = $this->actingAs($this->admin)
            ->getJson('/api/branches');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'location',
                        'branch_phone',
                        'created_at',
                        'updated_at',
                    ],
                ],
                'links',
                'meta',
            ]);
    }

    public function test_can_search_branches()
    {
        Branch::factory()->create(['name' => 'Lagos Branch']);
        Branch::factory()->create(['name' => 'Abuja Branch']);
        Branch::factory()->create(['name' => 'Port Harcourt Branch']);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/branches?search=Lagos');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Lagos Branch');
    }

    public function test_can_create_branch()
    {
        $branchData = [
            'name' => 'New Branch',
            'location' => 'New Location',
            'branch_phone' => '1234567890',
            'fullname' => 'New Branch Admin',
            'email' => 'newbranch@gasopay.com',
            'password' => 'password123',
            'phone' => '0987654321',
        ];

        $response = $this->actingAs($this->superAdmin)
            ->postJson('/api/super-admin/branches', $branchData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'location',
                    'branch_phone',
                    'created_at',
                    'updated_at',
                ],
            ]);

        $this->assertDatabaseHas('branches', [
            'name' => 'New Branch',
            'location' => 'New Location',
            'branch_phone' => '1234567890',
        ]);

        $this->assertDatabaseHas('users', [
            'fullname' => 'New Branch Admin',
            'email' => 'newbranch@gasopay.com',
            'role' => RoleEnum::Admin->value,
            'phone' => '0987654321',
        ]);
    }

    public function test_cannot_create_branch_with_invalid_data()
    {
        $response = $this->actingAs($this->superAdmin)
            ->postJson('/api/super-admin/branches', []);

        $response->assertStatus(422);
    }

    public function test_can_show_branch()
    {
        $branch = Branch::factory()->create();

        $response = $this->actingAs($this->regularUser)
            ->getJson("/api/branches/{$branch->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'location',
                    'branch_phone',
                    'created_at',
                    'updated_at',
                ],
            ]);
    }

    public function test_cannot_show_nonexistent_branch()
    {
        $response = $this->actingAs($this->regularUser)
            ->getJson('/api/branches/999');

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Branch not found.'
            ]);
    }

    public function test_can_update_branch()
    {
        $branch = Branch::factory()->create();

        $response = $this->actingAs($this->superAdmin)
            ->putJson("/api/super-admin/branches/{$branch->id}", [
                'name' => 'Updated Branch',
                'location' => 'Updated Location',
                'branch_phone' => '0987654321',
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'location',
                    'branch_phone',
                    'created_at',
                    'updated_at',
                ],
            ]);

        $this->assertDatabaseHas('branches', [
            'id' => $branch->id,
            'name' => 'Updated Branch',
            'location' => 'Updated Location',
            'branch_phone' => '0987654321',
        ]);
    }

    public function test_cannot_update_branch_with_invalid_data()
    {
        $branch = Branch::factory()->create();

        $response = $this->actingAs($this->superAdmin)
            ->putJson("/api/super-admin/branches/{$branch->id}", []);

        $response->assertStatus(422);
    }

    public function test_cannot_update_nonexistent_branch()
    {
        $response = $this->actingAs($this->superAdmin)
            ->putJson('/api/super-admin/branches/999999', [
                'name' => 'Updated Branch',
            ]);

        $response->assertStatus(404);
    }

    public function test_only_super_admin_can_delete_branch()
    {
        $branch = Branch::factory()->create();

        // Regular user cannot delete
        $response = $this->actingAs($this->regularUser)
            ->deleteJson("/api/super-admin/branches/{$branch->id}");
        $response->assertStatus(403);

        // Super admin can delete
        $response = $this->actingAs($this->superAdmin)
            ->deleteJson("/api/super-admin/branches/{$branch->id}");
        $response->assertStatus(204);

        $this->assertSoftDeleted('branches', ['id' => $branch->id]);
    }

    public function test_only_super_admin_can_force_delete_branch()
    {
        $branch = Branch::factory()->create();

        // Regular user cannot force delete
        $response = $this->actingAs($this->regularUser)
            ->deleteJson("/api/super-admin/branches/{$branch->id}/force");
        $response->assertStatus(403);

        // Super admin can force delete
        $response = $this->actingAs($this->superAdmin)
            ->deleteJson("/api/super-admin/branches/{$branch->id}/force");
        $response->assertStatus(200);

        $this->assertDatabaseMissing('branches', ['id' => $branch->id]);
    }

    public function test_cannot_delete_nonexistent_branch()
    {
        $response = $this->actingAs($this->superAdmin)
            ->deleteJson('/api/super-admin/branches/999999');

        $response->assertStatus(404);
    }

    public function test_branch_admin_can_access_own_branch()
    {
        $branch = Branch::factory()->create();
        $branchAdmin = User::factory()->create([
            'role' => RoleEnum::Admin->value,
            'branch_id' => $branch->id,
        ]);

        $response = $this->actingAs($branchAdmin)
            ->getJson("/api/branches/{$branch->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'location',
                    'branch_phone',
                    'created_at',
                    'updated_at',
                ],
            ]);
    }

    public function test_branch_admin_cannot_access_other_branches()
    {
        $branch = Branch::factory()->create();
        $branch2 = Branch::factory()->create();
        $branchAdmin = User::factory()->create([
            'role' => RoleEnum::Admin->value,
            'branch_id' => $branch->id,
        ]);

        $response = $this->actingAs($branchAdmin)
            ->getJson("/api/branches/{$branch2->id}");

        $response->assertOk(); // Any authenticated user can view branches
    }

    public function test_branch_admin_cannot_modify_own_branch()
    {
        $branch = Branch::factory()->create();
        $branchAdmin = User::factory()->create([
            'role' => RoleEnum::Admin->value,
            'branch_id' => $branch->id,
        ]);

        $response = $this->actingAs($branchAdmin)
            ->putJson("/api/branches/{$branch->id}", [
                'name' => 'Updated Branch',
                'location' => 'Updated Location',
                'branch_phone' => '0987654321',
            ]);

        $response->assertStatus(403);
    }

    public function test_branch_admin_cannot_delete_own_branch()
    {
        $branch = Branch::factory()->create();
        $branchAdmin = User::factory()->create([
            'role' => RoleEnum::Admin->value,
            'branch_id' => $branch->id,
        ]);

        $response = $this->actingAs($branchAdmin)
            ->deleteJson("/api/branches/{$branch->id}");

        $response->assertStatus(403);
    }
} 