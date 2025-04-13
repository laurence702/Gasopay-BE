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

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
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
            'address' => '123 Test Street',
            'nin' => 'NIN123456',
            'guarantors_name' => 'John Doe',
        ];

        $response = $this->postJson('/api/register', $userData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'user' => [
                    'id',
                    'fullname',
                    'email',
                    'phone',
                    'role',
                    'created_at',
                    'updated_at',
                    'user_profile' => [
                        'id',
                        'user_id',
                        'phone',
                        'address',
                        'nin',
                        'guarantors_name',
                    ]
                ],
                'token'
            ]);
        
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'role' => RoleEnum::Regular->value,
        ]);

        $this->assertDatabaseHas('user_profiles', [
            'phone' => '1234567890',
            'address' => '123 Test Street',
            'nin' => 'NIN123456',
            'guarantors_name' => 'John Doe',
        ]);

        Mail::assertSent(WelcomeEmail::class, function ($mail) use ($userData) {
            return $mail->user->email === $userData['email'];
        });
    }

    public function test_rider_can_register_with_vehicle_type()
    {
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

        $response = $this->postJson('/api/register', $userData);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'fullname',
                    'email',
                    'phone',
                    'role',
                    'user_profile' => [
                        'address',
                        'nin',
                        'guarantors_name',
                        'vehicle_type',
                    ],
                ]
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'rider@example.com',
            'role' => RoleEnum::Rider->value,
        ]);

        $this->assertDatabaseHas('user_profiles', [
            'vehicle_type' => VehicleTypeEnum::Car->value,
        ]);
    }

    public function test_admin_can_register_without_profile()
    {
        $userData = [
            'fullname' => 'Test Admin',
            'email' => 'admin@example.com',
            'phone' => '1234567890',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => RoleEnum::Admin->value,
        ];

        $response = $this->postJson('/api/register', $userData);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'fullname',
                    'email',
                    'phone',
                    'role',
                    'user_profile',
                ]
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'admin@example.com',
            'role' => RoleEnum::Admin->value,
        ]);

        $this->assertDatabaseMissing('user_profiles', [
            'user_id' => $response->json('data.id'),
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
            'role' => RoleEnum::Regular->value,
            // Missing required profile fields
        ];

        $response = $this->postJson('/api/register', $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['address', 'nin', 'guarantors_name']);

        $this->assertDatabaseMissing('users', [
            'email' => 'test@example.com',
        ]);

        Mail::assertNotSent(WelcomeEmail::class);
    }

    public function test_user_can_login_with_correct_credentials()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123')
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'message',
                'user',
                'token'
            ]);
    }

    public function test_user_cannot_login_with_incorrect_credentials()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123')
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword'
        ]);

        $response->assertStatus(422);
    }

    public function test_authenticated_user_can_get_their_information()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/me');

        $response->assertOk()
            ->assertJson([
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email
                ]
            ]);
    }

    public function test_authenticated_user_can_logout()
    {
        $user = User::factory()->create();
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
        $response->assertStatus(401);
    }
} 