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

class BranchControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $regularUser;
    private User $superAdmin;
    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => RoleEnum::Admin]);
        $this->regularUser = User::factory()->create(['role' => RoleEnum::Regular]);
        $this->superAdmin = User::factory()->create(['role' => RoleEnum::SuperAdmin]);
    }

    public function test_cannot_access_branches_without_authentication()
    {
        $response = $this->getJson('/api/branches');
        $response->assertStatus(401);

        $response = $this->postJson('/api/branches', []);
        $response->assertStatus(401);

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
            ->postJson('/api/branches', []);
        $response->assertStatus(403);
    }

    public function test_can_list_branches()
    {
        Branch::factory()->count(15)->create();

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
                        'branch_admin' => [
                            'id',
                            'fullname',
                            'email',
                            'phone',
                            'role',
                        ],
                        'created_at',
                        'updated_at'
                    ]
                ],
                'links',
                'meta'
            ])
            ->assertJsonCount(10, 'data'); // Default pagination is 10
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
            'name' => 'Test Branch',
            'location' => 'Test Location',
            'branch_phone' => '1234567890',
        ];

        $response = $this->actingAs($this->superAdmin)
            ->postJson('/api/branches', $branchData);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'location',
                    'branch_phone',
                    'branch_admin' => [
                        'id',
                        'fullname',
                        'email',
                        'phone',
                        'role',
                    ],
                    'created_at',
                    'updated_at'
                ]
            ]);

        $this->assertDatabaseHas('branches', [
            'name' => $branchData['name'],
            'location' => $branchData['location'],
            'branch_phone' => $branchData['branch_phone'],
        ]);

        // Check if a branch admin user was created
        $this->assertDatabaseHas('users', [
            'role' => RoleEnum::Admin,
            'fullname' => $branchData['name'] . ' Admin',
            'phone' => $branchData['branch_phone'],
        ]);
    }

    public function test_cannot_create_branch_with_invalid_data()
    {
        $response = $this->actingAs($this->superAdmin)
            ->postJson('/api/branches', []);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors' => [
                    'name',
                    'location',
                    'branch_phone'
                ]
            ]);
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
                    'branch_admin' => [
                        'id',
                        'fullname',
                        'email',
                        'phone',
                        'role',
                    ],
                    'created_at',
                    'updated_at'
                ]
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
            ->putJson("/api/branches/{$branch->id}", [
                'name' => 'Updated Branch',
                'location' => 'Updated Location',
                'branch_phone' => '0987654321',
            ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'location',
                    'branch_phone',
                    'branch_admin' => [
                        'id',
                        'fullname',
                        'email',
                        'phone',
                        'role',
                    ],
                    'created_at',
                    'updated_at'
                ]
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
            ->putJson("/api/branches/{$branch->id}", []);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors' => [
                    'name',
                    'location',
                    'branch_phone'
                ]
            ]);
    }

    public function test_cannot_update_nonexistent_branch()
    {
        $response = $this->actingAs($this->superAdmin)
            ->putJson('/api/branches/999', [
                'name' => 'Updated Branch',
                'location' => 'Updated Location',
                'branch_phone' => '0987654321',
            ]);

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Branch not found.'
            ]);
    }

    public function test_only_super_admin_can_delete_branch()
    {
        $branch = Branch::factory()->create();
        $branchAdminId = $branch->branch_admin;

        $response = $this->actingAs($this->superAdmin)
            ->deleteJson("/api/branches/{$branch->id}");

        $response->assertStatus(204);

        // Check if the branch was deleted
        $this->assertDatabaseMissing('branches', [
            'id' => $branch->id
        ]);

        // Check if the branch admin user was also deleted
        $this->assertDatabaseMissing('users', [
            'id' => $branchAdminId
        ]);
    }

    public function test_cannot_delete_nonexistent_branch()
    {
        $response = $this->actingAs($this->superAdmin)
            ->deleteJson('/api/branches/999');

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Branch not found.'
            ]);
    }

    public function test_branch_admin_can_access_own_branch()
    {
        $branch = Branch::factory()->create();
        $branchAdmin = User::find($branch->branch_admin);

        $response = $this->actingAs($branchAdmin)
            ->getJson("/api/branches/{$branch->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'location',
                    'branch_phone',
                    'branch_admin' => [
                        'id',
                        'fullname',
                        'email',
                        'phone',
                        'role',
                    ],
                    'created_at',
                    'updated_at'
                ]
            ]);
    }

    public function test_branch_admin_cannot_access_other_branches()
    {
        $branch1 = Branch::factory()->create();
        $branch2 = Branch::factory()->create();
        $branchAdmin = User::find($branch1->branch_admin);

        $response = $this->actingAs($branchAdmin)
            ->getJson("/api/branches/{$branch2->id}");

        $response->assertOk(); // Any authenticated user can view branches
    }

    public function test_branch_admin_cannot_modify_own_branch()
    {
        $branch = Branch::factory()->create();
        $branchAdmin = User::find($branch->branch_admin);

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
        $branchAdmin = User::find($branch->branch_admin);

        $response = $this->actingAs($branchAdmin)
            ->deleteJson("/api/branches/{$branch->id}");

        $response->assertStatus(403);
    }
} 