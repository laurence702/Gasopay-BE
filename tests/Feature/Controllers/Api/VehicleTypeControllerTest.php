<?php

namespace Tests\Feature\Controllers\Api;

use Tests\TestCase;
use App\Models\User;
use App\Enums\RoleEnum;
use App\Models\VehicleType;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;

class VehicleTypeControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private array $vehicleTypeData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->vehicleTypeData = [
            'name' => 'Motorcycle',
        ];
    }

    public function test_can_list_vehicle_types(): void
    {
        VehicleType::create($this->vehicleTypeData);

        $response = $this->actingAs($this->user)
            ->getJson('/api/vehicle-types');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'created_at',
                        'updated_at',
                    ],
                ],
                'links' => [
                    'first',
                    'last',
                    'prev',
                    'next',
                ],
                'meta' => [
                    'current_page',
                    'from',
                    'last_page',
                    'path',
                    'per_page',
                    'to',
                    'total',
                ],
            ]);
    }

    public function test_can_list_vehicle_types_with_pagination(): void
    {
        VehicleType::factory()->count(15)->create();

        $response = $this->actingAs($this->user)
            ->getJson('/api/vehicle-types');

        $response->assertOk()
            ->assertJsonCount(10, 'data') // Default pagination is 10
            ->assertJsonStructure([
                'data',
                'links',
                'meta',
            ]);
    }

    public function test_can_search_vehicle_types(): void
    {
        VehicleType::create(['name' => 'Car']);
        VehicleType::create(['name' => 'Motorcycle']);
        VehicleType::create(['name' => 'Van']);

        $response = $this->actingAs($this->user)
            ->getJson('/api/vehicle-types?search=car');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Car');
    }

    public function test_super_admin_can_create_vehicle_type(): void
    {
        $superAdmin = User::factory()->create(['role' => RoleEnum::SuperAdmin]);
        Sanctum::actingAs($superAdmin);
        
        $response = $this->postJson('/api/vehicle-types', $this->vehicleTypeData);

        $response->assertCreated()
            ->assertJsonStructure([
                'id',
                'name',
                'created_at',
                'updated_at',
            ]);

        $this->assertDatabaseHas('vehicle_types', $this->vehicleTypeData);
    }

    public function test_cannot_create_duplicate_vehicle_type(): void
    {
        $superAdmin = User::factory()->create(['role' => RoleEnum::SuperAdmin]);
        VehicleType::create($this->vehicleTypeData);

        $response = $this->actingAs($superAdmin)
            ->postJson('/api/vehicle-types', $this->vehicleTypeData);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    public function test_can_show_vehicle_type(): void
    {
        $vehicleType = VehicleType::create($this->vehicleTypeData);

        $response = $this->actingAs($this->user)
            ->getJson("/api/vehicle-types/{$vehicleType->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'id',
                'name',
                'created_at',
                'updated_at',
            ]);
    }

    public function test_can_update_vehicle_type(): void
    {
        $superAdmin = User::factory()->create(['role' => RoleEnum::SuperAdmin]);
        $vehicleType = VehicleType::create($this->vehicleTypeData);

        $updatedData = [
            'name' => 'Car',
        ];

        $response = $this->actingAs($superAdmin)
            ->putJson("/api/vehicle-types/{$vehicleType->id}", $updatedData);

        $response->assertOk()
            ->assertJsonStructure([
                'id',
                'name',
                'created_at',
                'updated_at',
            ]);

        $this->assertDatabaseHas('vehicle_types', $updatedData);
    }

    public function test_can_delete_vehicle_type(): void
    {
        $superAdmin = User::factory()->create(['role' => RoleEnum::SuperAdmin]);
        $vehicleType = VehicleType::create($this->vehicleTypeData);

        $response = $this->actingAs($superAdmin)
            ->deleteJson("/api/vehicle-types/{$vehicleType->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('vehicle_types', ['id' => $vehicleType->id]);
    }
} 