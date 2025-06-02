<?php

namespace Tests\Feature\Controllers\Api;

use App\Models\User;
use App\Enums\RoleEnum;
use App\Enums\VehicleTypeEnum;
use Illuminate\Support\Facades\Mail;
use App\Mail\WelcomeEmail;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use App\Models\Branch;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $branch1;
    protected $branch2;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $this->branch1 = Branch::factory()->create();
        $this->branch2 = Branch::factory()->create();
    }

    public function test_regular_user_can_register_with_profile()
    {
        $userData = [
            'fullname' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '1234567890',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => RoleEnum::Regular->value,
            'branch_id' => $this->branch1->id,
            'address' => '123 Test St',
            'nin' => '123456789',
            'guarantors_name' => 'Test Guarantor',
            'guarantors_address' => '456 Guarantor Ave',
            'guarantors_phone' => '5551234567',
        ];

        $response = $this->postJson('/api/register', $userData);

        $response->assertCreated()
            ->assertJson([
                'status' => 'success',
                'message' => 'User created successfully',
            ])
            ->assertJsonStructure([
                'status',
                'message',
                'user' => [
                    'id',
                    'fullname',
                    'email',
                    'phone',
                    'role',
                    'branch_id',
                    'user_profile' => [
                        'address',
                        'nin',
                        'guarantors_name',
                        'guarantors_address',
                        'guarantors_phone',
                    ],
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'phone' => '1234567890',
            'role' => RoleEnum::Regular->value,
            'branch_id' => $this->branch1->id,
        ]);

        $this->assertDatabaseHas('user_profiles', [
            'address' => '123 Test St',
            'nin' => '123456789',
            'guarantors_name' => 'Test Guarantor',
            'guarantors_address' => '456 Guarantor Ave',
            'guarantors_phone' => '5551234567',
        ]);
    }

    public function test_rider_can_register_with_vehicle_type()
    {
        $userData = [
            'fullname' => 'Test Rider',
            'email' => 'rider@example.com',
            'phone' => '0987654321',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => RoleEnum::Rider->value,
            'branch_id' => $this->branch2->id,
            'address' => '456 Rider St',
            'nin' => '987654321',
            'guarantors_name' => 'Rider Guarantor',
            'guarantors_address' => '789 Guarantor St',
            'guarantors_phone' => '5559876543',
            'vehicle_type' => VehicleTypeEnum::Car->value,
            'profilePicUrl' => 'http://example.com/pic.jpg',
        ];

        $response = $this->postJson('/api/register', $userData);

        $response->assertCreated()
            ->assertJson([
                'status' => 'success',
                'message' => 'User created successfully',
            ])
            ->assertJsonStructure([
                'status',
                'message',
                'user' => [
                    'id',
                    'fullname',
                    'email',
                    'phone',
                    'role',
                    'branch_id',
                    'user_profile' => [
                        'address',
                        'nin',
                        'guarantors_name',
                        'guarantors_address',
                        'guarantors_phone',
                        'vehicle_type',
                        'profile_pic_url',
                    ],
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'rider@example.com',
            'phone' => '0987654321',
            'role' => RoleEnum::Rider->value,
            'branch_id' => $this->branch2->id,
        ]);

        $this->assertDatabaseHas('user_profiles', [
            'address' => '456 Rider St',
            'nin' => '987654321',
            'guarantors_name' => 'Rider Guarantor',
            'guarantors_address' => '789 Guarantor St',
            'guarantors_phone' => '5559876543',
            'vehicle_type' => VehicleTypeEnum::Car->value,
            'profile_pic_url' => 'http://example.com/pic.jpg',
        ]);
    }

    public function test_admin_can_register_without_profile()
    {
        $userData = [
            'fullname' => 'Test Admin',
            'email' => 'admin@example.com',
            'phone' => '1112223333',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => RoleEnum::Admin->value,
            'branch_id' => $this->branch1->id,
        ];

        $response = $this->postJson('/api/register', $userData);

        $response->assertCreated()
            ->assertJson([
                'status' => 'success',
                'message' => 'User created successfully',
            ])
            ->assertJsonStructure([
                'status',
                'message',
                'user' => [
                    'id',
                    'fullname',
                    'email',
                    'phone',
                    'role',
                    'branch_id',
                    'user_profile',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'admin@example.com',
            'role' => RoleEnum::Admin->value,
        ]);

        $this->assertDatabaseMissing('user_profiles', [
            'user_id' => User::where('email', 'admin@example.com')->first()->id,
        ]);
    }

    public function test_registration_fails_without_required_profile_fields()
    {
        $userData = [
            'fullname' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '1234567890',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => RoleEnum::Rider->value,
        ];

        $response = $this->postJson('/api/register', $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'address',
                'vehicle_type',
                'nin',
                'guarantors_name',
                'guarantors_address',
                'guarantors_phone',
            ]);

        $this->assertDatabaseMissing('users', [
            'email' => 'test@example.com',
        ]);

        Mail::assertNotSent(WelcomeEmail::class);
    }

    public function test_user_can_login_with_correct_email_credential()
    {
        $user = User::factory()->create([
            'fullname' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '1234567890',
            'password' => Hash::make('password123'),
            'role' => RoleEnum::Regular->value,
            'branch_id' => $this->branch1->id,
        ]);

        $response = $this->postJson('/api/login', [
            'login_identifier' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertOk()
            ->assertJson([
                'status' => 'success',
                'message' => 'Login successful',
            ])
            ->assertJsonStructure([
                'status',
                'message',
                'user',
                'token',
            ]);
    }

    public function test_user_can_login_with_correct_phone_credential()
    {
        $user = User::factory()->create([
            'fullname' => 'Test User Phone',
            'email' => 'testphone@example.com',
            'phone' => '1234567890',
            'password' => Hash::make('password123'),
            'role' => RoleEnum::Regular->value,
            'branch_id' => $this->branch2->id,
        ]);

        $response = $this->postJson('/api/login', [
            'login_identifier' => '1234567890',
            'password' => 'password123',
        ]);

        $response->assertOk()
            ->assertJson([
                'status' => 'success',
                'message' => 'Login successful',
            ])
            ->assertJsonStructure([
                'status',
                'message',
                'user',
                'token',
            ]);
    }

    public function test_user_cannot_login_with_incorrect_password()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'phone' => '1234567890',
            'password' => Hash::make('password123'),
            'branch_id' => $this->branch1->id,
        ]);

        $response = $this->postJson('/api/login', [
            'login_identifier' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertUnauthorized()
            ->assertJson([
                'status' => 'error',
                'message' => 'Invalid credentials',
            ]);
    }

    public function test_user_cannot_login_with_non_existent_identifier()
    {
        // No user created, attempt login with fake identifier
        $response = $this->postJson('/api/login', [
            'login_identifier' => 'nonexistent@example.com',
            'password' => 'password123',
        ]);

        // Expect 401 Unauthorized because Auth::attempt will fail
        $response->assertUnauthorized()
                 ->assertJson([
                    'status' => 'error',
                    'message' => 'Invalid credentials',
                 ]);
    }

    public function test_authenticated_user_can_get_their_information()
    {
        $user = User::factory()->create([
            'branch_id' => $this->branch1->id,
        ]);
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/me');

        $response->assertOk()
            ->assertJsonStructure([
                'user' => [
                    'id',
                    'fullname',
                    'email',
                    'phone',
                    'role',
                    'branch_id',
                ]
            ]);
    }

    public function test_authenticated_user_can_logout()
    {
        $user = User::factory()->create([
            'branch_id' => $this->branch2->id,
        ]);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/logout');

        $response->assertOk()
            ->assertJson([
                'message' => 'Logged out successfully'
            ]);
    }

    public function test_unauthenticated_user_cannot_access_protected_routes()
    {
        $response = $this->getJson('/api/me');
        $response->assertUnauthorized();

        $response = $this->postJson('/api/logout');
        $response->assertUnauthorized();
    }
} 