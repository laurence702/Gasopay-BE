<?php

namespace Tests\Feature\Controllers\Api;

use App\Models\Branch;
use App\Models\User;
use App\Enums\RoleEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BranchControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => RoleEnum::Admin]);
        $this->regularUser = User::factory()->create(['role' => RoleEnum::Regular]);
    }

    public function test_cannot_access_branches_without_authentication()
    {
        $response = $this->getJson('/api/branches');
        $response->assertStatus(401);

        $response = $this->postJson('/api/branches', []);
        $response->assertStatus(401);

        $response = $this->getJson('/api/branches/1');
        $response->assertStatus(401);

        $response = $this->putJson('/api/branches/1', []);
        $response->assertStatus(401);

        $response = $this->deleteJson('/api/branches/1');
        $response->assertStatus(401);
    }

    public function test_cannot_access_branches_without_admin_role()
    {
        $response = $this->actingAs($this->regularUser)
            ->getJson('/api/branches');
        $response->assertStatus(403);

        $response = $this->actingAs($this->regularUser)
            ->postJson('/api/branches', []);
        $response->assertStatus(403);

        $response = $this->actingAs($this->regularUser)
            ->getJson('/api/branches/1');
        $response->assertStatus(403);

        $response = $this->actingAs($this->regularUser)
            ->putJson('/api/branches/1', []);
        $response->assertStatus(403);

        $response = $this->actingAs($this->regularUser)
            ->deleteJson('/api/branches/1');
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

        $response = $this->actingAs($this->admin)
            ->postJson('/api/branches', $branchData);

        $response->assertCreated()
            ->assertJson([
                'data' => $branchData
            ]);

        $this->assertDatabaseHas('branches', $branchData);
    }

    public function test_cannot_create_branch_with_invalid_data()
    {
        $response = $this->actingAs($this->admin)
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

        $response = $this->actingAs($this->admin)
            ->getJson("/api/branches/{$branch->id}");

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'id' => $branch->id,
                    'name' => $branch->name,
                    'location' => $branch->location,
                    'branch_phone' => $branch->branch_phone,
                ]
            ]);
    }

    public function test_cannot_show_nonexistent_branch()
    {
        $response = $this->actingAs($this->admin)
            ->getJson('/api/branches/999');

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Branch not found.'
            ]);
    }

    public function test_can_update_branch()
    {
        $branch = Branch::factory()->create();
        $updateData = [
            'name' => 'Updated Branch',
            'location' => 'Updated Location',
            'branch_phone' => '0987654321',
        ];

        $response = $this->actingAs($this->admin)
            ->putJson("/api/branches/{$branch->id}", $updateData);

        $response->assertOk()
            ->assertJson([
                'data' => $updateData
            ]);

        $this->assertDatabaseHas('branches', $updateData);
    }

    public function test_cannot_update_branch_with_invalid_data()
    {
        $branch = Branch::factory()->create();
        $response = $this->actingAs($this->admin)
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
        $response = $this->actingAs($this->admin)
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

    public function test_can_delete_branch()
    {
        $branch = Branch::factory()->create();

        $response = $this->actingAs($this->admin)
            ->deleteJson("/api/branches/{$branch->id}");

        $response->assertNoContent();

        $this->assertDatabaseMissing('branches', [
            'id' => $branch->id
        ]);
    }

    public function test_cannot_delete_nonexistent_branch()
    {
        $response = $this->actingAs($this->admin)
            ->deleteJson('/api/branches/999');

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Branch not found.'
            ]);
    }
} 