<?php

namespace Tests\Feature\Controllers\Api;

use Tests\TestCase;
use App\Models\User;
use App\Models\UserProfile;
use App\Enums\VehicleTypeEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class UserProfileControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private array $profileData;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->user = User::factory()->create();
        
        $this->profileData = [
            'user_id' => $this->user->id,
            'vehicle_type' => VehicleTypeEnum::Car->value,
            'phone' => '1234567890',
            'address' => '123 Test Street',
            'nin' => 'NIN123456',
            'guarantors_name' => 'John Doe',
            'guarantors_address' => '111 Guarantor Ln',
            'guarantors_phone' => '5559876543',
            'profile_pic_url' => UploadedFile::fake()->image('avatar.jpg'),
        ];
    }

    public function test_can_list_user_profiles(): void
    {
        $listData = $this->profileData;
        unset($listData['profile_pic_url']);
        $listData['profile_pic_url'] = 'http://example.com/photo.jpg';
        UserProfile::create($listData);

        $response = $this->actingAs($this->user)
            ->getJson('/api/user-profiles');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'user_id',
                        'user' => [
                            'id',
                            'fullname',
                            'email',
                            'phone',
                            'role',
                            'branch_id',
                        ],
                        'address',
                        'phone',
                        'vehicle_type',
                        'nin',
                        'guarantors_name',
                        'guarantors_address',
                        'guarantors_phone',
                        'profile_pic_url',
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

    public function test_can_list_user_profiles_with_pagination(): void
    {
        UserProfile::factory()->count(15)->create();

        $response = $this->actingAs($this->user)
            ->getJson('/api/user-profiles');

        $response->assertOk()
            ->assertJsonCount(15, 'data') // Default pagination is 10
            ->assertJsonStructure([
                'data',
                'links',
                'meta',
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
                    'user' => [
                        'id',
                        'fullname',
                        'email',
                        'phone',
                        'role',
                        'branch_id',
                    ],
                    'address',
                    'phone',
                    'vehicle_type',
                    'nin',
                    'guarantors_name',
                    'guarantors_address',
                    'guarantors_phone',
                    'profile_pic_url',
                    'created_at',
                    'updated_at',
                ],
            ]);

        $dbData = $this->profileData;
        unset($dbData['profile_pic_url']);
        $this->assertDatabaseHas('user_profiles', $dbData);
    }

    public function test_can_show_user_profile(): void
    {
        $showData = $this->profileData;
        unset($showData['profile_pic_url']);
        $showData['profile_pic_url'] = 'http://example.com/photo.jpg';
        $profile = UserProfile::create($showData);

        $response = $this->actingAs($this->user)
            ->getJson("/api/user-profiles/{$profile->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'user_id',
                    'user' => [
                        'id',
                        'fullname',
                        'email',
                        'phone',
                        'role',
                        'branch_id',
                    ],
                    'address',
                    'phone',
                    'vehicle_type',
                    'nin',
                    'guarantors_name',
                    'guarantors_address',
                    'guarantors_phone',
                    'profile_pic_url',
                    'created_at',
                    'updated_at',
                ],
            ]);
    }

    public function test_can_update_user_profile(): void
    {
        $initialData = $this->profileData;
        unset($initialData['profile_pic_url']);
        $initialData['profile_pic_url'] = 'http://example.com/photo.jpg';
        $profile = UserProfile::create($initialData);

        $updatedData = [
            'phone' => '0987654321',
            'address' => '456 Updated Street',
            'profile_pic_url' => UploadedFile::fake()->image('new_avatar.png'),
        ];

        $response = $this->actingAs($this->user)
            ->putJson("/api/user-profiles/{$profile->id}", $updatedData);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'user_id',
                    'user' => [
                        'id',
                        'fullname',
                        'email',
                        'phone',
                        'role',
                        'branch_id',
                    ],
                    'address',
                    'phone',
                    'vehicle_type',
                    'nin',
                    'guarantors_name',
                    'guarantors_address',
                    'guarantors_phone',
                    'profile_pic_url',
                    'created_at',
                    'updated_at',
                ],
            ]);

        $dbCheckData = [
            'id' => $profile->id,
            'phone' => '0987654321',
            'address' => '456 Updated Street',
        ];
        $this->assertDatabaseHas('user_profiles', $dbCheckData);
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