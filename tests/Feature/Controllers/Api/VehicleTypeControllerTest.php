<?php

namespace Tests\Feature\Controllers\Api;

use App\Models\User;
use App\Models\VehicleType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

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
            ]);
    }

    public function test_can_create_vehicle_type(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/vehicle-types', $this->vehicleTypeData);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'created_at',
                    'updated_at',
                ],
            ]);

        $this->assertDatabaseHas('vehicle_types', $this->vehicleTypeData);
    }

    public function test_cannot_create_duplicate_vehicle_type(): void
    {
        VehicleType::create($this->vehicleTypeData);

        $response = $this->actingAs($this->user)
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
                'data' => [
                    'id',
                    'name',
                    'created_at',
                    'updated_at',
                ],
            ]);
    }

    public function test_can_update_vehicle_type(): void
    {
        $vehicleType = VehicleType::create($this->vehicleTypeData);

        $updatedData = [
            'name' => 'Car',
        ];

        $response = $this->actingAs($this->user)
            ->putJson("/api/vehicle-types/{$vehicleType->id}", $updatedData);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'created_at',
                    'updated_at',
                ],
            ]);

        $this->assertDatabaseHas('vehicle_types', $updatedData);
    }

    public function test_can_delete_vehicle_type(): void
    {
        $vehicleType = VehicleType::create($this->vehicleTypeData);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/vehicle-types/{$vehicleType->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('vehicle_types', ['id' => $vehicleType->id]);
    }
} 