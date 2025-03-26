<?php

namespace Tests\Feature\Controllers\Api;

use App\Models\User;
use App\Models\UserProfile;
use App\Models\VehicleType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserProfileControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private VehicleType $vehicleType;
    private array $profileData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->vehicleType = VehicleType::create(['name' => 'Car']);
        
        $this->profileData = [
            'user_id' => $this->user->id,
            'vehicle_type_id' => $this->vehicleType->id,
            'phone' => '1234567890',
            'address' => '123 Test Street',
            'nin' => 'NIN123456',
            'guarantors_name' => 'John Doe',
            'photo' => 'photo.jpg',
            'barcode' => 'BARCODE123',
        ];
    }

    public function test_can_list_user_profiles(): void
    {
        UserProfile::create($this->profileData);

        $response = $this->actingAs($this->user)
            ->getJson('/api/user-profiles');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'user_id',
                        'vehicle_type_id',
                        'phone',
                        'address',
                        'nin',
                        'guarantors_name',
                        'photo',
                        'barcode',
                        'created_at',
                        'updated_at',
                    ],
                ],
            ]);
    }

    public function test_can_create_user_profile(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/user-profiles', $this->profileData);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'user_id',
                    'vehicle_type_id',
                    'phone',
                    'address',
                    'nin',
                    'guarantors_name',
                    'photo',
                    'barcode',
                    'created_at',
                    'updated_at',
                ],
            ]);

        $this->assertDatabaseHas('user_profiles', $this->profileData);
    }

    public function test_can_show_user_profile(): void
    {
        $profile = UserProfile::create($this->profileData);

        $response = $this->actingAs($this->user)
            ->getJson("/api/user-profiles/{$profile->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'user_id',
                    'vehicle_type_id',
                    'phone',
                    'address',
                    'nin',
                    'guarantors_name',
                    'photo',
                    'barcode',
                    'created_at',
                    'updated_at',
                ],
            ]);
    }

    public function test_can_update_user_profile(): void
    {
        $profile = UserProfile::create($this->profileData);

        $updatedData = [
            'phone' => '0987654321',
            'address' => '456 Updated Street',
        ];

        $response = $this->actingAs($this->user)
            ->putJson("/api/user-profiles/{$profile->id}", $updatedData);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'user_id',
                    'vehicle_type_id',
                    'phone',
                    'address',
                    'nin',
                    'guarantors_name',
                    'photo',
                    'barcode',
                    'created_at',
                    'updated_at',
                ],
            ]);

        $this->assertDatabaseHas('user_profiles', [
            'id' => $profile->id,
            'phone' => '0987654321',
            'address' => '456 Updated Street',
        ]);
    }

    public function test_can_delete_user_profile(): void
    {
        $profile = UserProfile::create($this->profileData);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/user-profiles/{$profile->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('user_profiles', ['id' => $profile->id]);
    }
} 