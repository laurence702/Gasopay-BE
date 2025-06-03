<?php

namespace Tests\Feature\Controllers\Api;

use Tests\TestCase;
use App\Models\User;
use App\Models\Branch;
use App\Models\UserProfile;
use App\Enums\VehicleTypeEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Enums\RoleEnum;
use Illuminate\Contracts\Auth\Authenticatable;
use function Pest\Laravel\{getJson, postJson, putJson, deleteJson};
use Laravel\Sanctum\Sanctum;
use Illuminate\Http\Response;

class UserProfileControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Branch $branch;
    protected array $profileData;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        // Create branch first
        $this->branch = Branch::factory()->create();
        
        // Create user with proper branch assignment
        $this->user = User::factory()->rider()->create([
            'branch_id' => $this->branch->id
        ]);
        
        $this->profileData = [
            'user_id' => $this->user->id,
            'vehicle_type' => VehicleTypeEnum::Car->value,
            'phone' => '1234567890',
            'address' => '123 Test St',
            'nin' => '1234567890',
            'guarantors_name' => 'Test Guarantor',
            'guarantors_address' => '456 Guarantor Ave',
            'guarantors_phone' => '5551234567',
            'profilePicUrl' => UploadedFile::fake()->image('avatar.jpg'),
        ];
    }

    public function test_can_list_user_profiles(): void
    {
        /** @var Authenticatable $user */
        $user = User::factory()->admin()->create(['branch_id' => $this->branch->id]);
        $this->actingAs($user);

        $listData = $this->profileData;
        unset($listData['profilePicUrl']);
        $listData['profile_pic_url'] = 'profile-pics/avatar.jpg';
        UserProfile::create($listData);

        $response = $this->getJson('/api/user-profiles');

        $response->assertStatus(Response::HTTP_OK)
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
                        'profilePicUrl',
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

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(15, 'data') // Default pagination is 10
            ->assertJsonStructure([
                'data',
                'links',
                'meta',
            ]);
    }

    public function test_can_create_user_profile(): void
    {
        /** @var Authenticatable $user */
        $user = User::factory()->rider()->create(['branch_id' => $this->branch->id]);
        $this->actingAs($user);

        $profileData = $this->profileData;
        $profileData['user_id'] = $user->id;

        $response = $this->postJson('/api/user-profiles', $profileData);

        $response->assertStatus(Response::HTTP_CREATED)
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
                    'profilePicUrl',
                    'created_at',
                    'updated_at',
                ],
            ]);

        $dbData = $profileData;
        unset($dbData['profilePicUrl']);
        $this->assertDatabaseHas('user_profiles', $dbData);
    }

    public function test_can_show_user_profile(): void
    {
        /** @var Authenticatable $user */
        $user = User::factory()->admin()->create(['branch_id' => $this->branch->id]);
        $this->actingAs($user);

        $showData = $this->profileData;
        unset($showData['profilePicUrl']);
        $showData['profile_pic_url'] = 'profile-pics/avatar.jpg';
        $profile = UserProfile::create($showData);

        $response = $this->getJson("/api/user-profiles/{$profile->id}");

        $response->assertStatus(Response::HTTP_OK)
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
                    'profilePicUrl',
                    'created_at',
                    'updated_at',
                ],
            ]);
    }

    public function test_can_update_user_profile(): void
    {
        /** @var Authenticatable $user */
        $user = User::factory()->rider()->create(['branch_id' => $this->branch->id]);
        $this->actingAs($user);

        $initialData = $this->profileData;
        $initialData['user_id'] = $user->id;
        unset($initialData['profilePicUrl']);
        $initialData['profile_pic_url'] = 'profile-pics/avatar.jpg';
        $profile = UserProfile::create($initialData);

        $updatedData = [
            'phone' => '0987654321',
            'address' => '456 Updated Street',
            'profilePicUrl' => UploadedFile::fake()->image('new_avatar.png'),
        ];

        $response = $this->putJson("/api/user-profiles/{$profile->id}", $updatedData);

        $response->assertStatus(Response::HTTP_OK)
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
                    'profilePicUrl',
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
        /** @var Authenticatable $user */
        $user = User::factory()->admin()->create(['branch_id' => $this->branch->id]);
        $this->actingAs($user);

        $profile = UserProfile::factory()->for($this->user)->create();

        $response = $this->deleteJson("/api/user-profiles/{$profile->id}");

        $response->assertStatus(Response::HTTP_NO_CONTENT);
        $this->assertDatabaseMissing('user_profiles', ['id' => $profile->id]);
    }
} 