<?php

use App\Models\User;
use App\Enums\RoleEnum;
use Laravel\Sanctum\Sanctum;
use function Pest\Laravel\{postJson, getJson};

beforeEach(function () {
    // Clear database
    User::query()->delete();
});

test('user can register successfully', function () {
    $userData = [
        'fullname' => 'Test User',
        'email' => 'test@example.com',
        'phone' => '1234567890',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => RoleEnum::Regular->value,
    ];

    $response = postJson('/api/register', $userData);

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
    
    expect(User::count())->toBe(1);
});

test('user cannot register with invalid data', function () {
    $response = postJson('/api/register', [
        'email' => 'invalid-email',
        'password' => 'short'
    ]);

    $response->assertStatus(422);
    expect(User::count())->toBe(0);
});

test('user can login with correct credentials', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password123')
    ]);

    $response = postJson('/api/login', [
        'email' => 'test@example.com',
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
        'email' => 'test@example.com',
        'password' => bcrypt('password123')
    ]);

    $response = postJson('/api/login', [
        'email' => 'test@example.com',
        'password' => 'wrongpassword'
    ]);

    $response->assertStatus(422);
});

test('authenticated user can get their information', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = getJson('/api/me');

    $response->assertOk()
        ->assertJson([
            'user' => [
                'id' => $user->id,
                'email' => $user->email
            ]
        ]);
});

test('authenticated user can logout', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = postJson('/api/logout');

    $response->assertOk()
        ->assertJson([
            'message' => 'Logged out successfully'
        ]);
});

test('unauthenticated user cannot access protected routes', function () {
    $response = getJson('/api/me');
    $response->assertStatus(401);
}); 