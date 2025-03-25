<?php

use App\Models\User;
use App\Enums\RoleEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Set up any necessary test data
});

test('user can register successfully', function () {
    $userData = [
        'fullname' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'phone' => '+2348012345678',
        'address' => '123 Test Street'
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
                'updated_at'
            ],
            'token'
        ]);

    $this->assertDatabaseHas('users', [
        'email' => $userData['email'],
        'fullname' => $userData['fullname'],
        'phone' => $userData['phone'],
        'role' => RoleEnum::Regular->value
    ]);
});

test('user can login with correct credentials', function () {
    $user = User::factory()->create([
        'password' => bcrypt('password123')
    ]);

    $response = $this->postJson('/api/login', [
        'email' => $user->email,
        'password' => 'password123'
    ]);

    $response->assertOk()
        ->assertJsonStructure([
            'message',
            'user',
            'token'
        ]);
});

test('user cannot login with incorrect credentials', function () {
    $user = User::factory()->create([
        'password' => bcrypt('password123')
    ]);

    $response = $this->postJson('/api/login', [
        'email' => $user->email,
        'password' => 'wrongpassword'
    ]);

    $response->assertStatus(422);
});

test('authenticated user can logout', function () {
    $user = User::factory()->create();
    
    Sanctum::actingAs($user);
    
    $response = $this->postJson('/api/logout');

    $response->assertOk()
        ->assertJson(['message' => 'Logged out successfully']);

    $this->assertDatabaseCount('personal_access_tokens', 0);
});

test('authenticated user can get their profile', function () {
    $user = User::factory()->create();
    
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
                'created_at',
                'updated_at'
            ]
        ]);
});
