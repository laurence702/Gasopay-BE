<?php

use App\Models\User;
use App\Models\Branch;
use App\Enums\RoleEnum;
use Laravel\Sanctum\Sanctum;
use function Pest\Laravel\{getJson, postJson, putJson, deleteJson};

beforeEach(function () {
    // Clear database
    User::query()->delete();
    Branch::query()->delete();
});

test('authenticated user can list users', function () {
    $user = User::factory()->create(['role' => RoleEnum::Admin]);
    Sanctum::actingAs($user);
    
    $users = User::factory()->count(3)->create();

    $response = getJson('/api/users');

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'fullname',
                    'email',
                    'phone',
                    'role',
                    'branch_id',
                    'created_at',
                    'updated_at'
                ]
            ],
            'links',
            'meta'
        ]);
});

test('authenticated user can create a user', function () {
    $user = User::factory()->create(['role' => RoleEnum::Admin]);
    Sanctum::actingAs($user);
    $branch = Branch::factory()->create();

    $userData = [
        'fullname' => 'Test User',
        'email' => 'test@example.com',
        'phone' => '1234567890',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => RoleEnum::Regular->value,
        'branch_id' => $branch->id
    ];

    $response = postJson('/api/users', $userData);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'data' => [
                'id',
                'fullname',
                'email',
                'phone',
                'role',
                'branch_id',
                'created_at',
                'updated_at'
            ]
        ]);

    expect(User::count())->toBe(2); // Including the admin user
});

test('authenticated user can view a specific user', function () {
    $user = User::factory()->create(['role' => RoleEnum::Admin]);
    Sanctum::actingAs($user);

    $targetUser = User::factory()->create();

    $response = getJson("/api/users/{$targetUser->id}");

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                'id',
                'fullname',
                'email',
                'phone',
                'role',
                'branch_id',
                'created_at',
                'updated_at',
                'branch',
                'user_profile'
            ]
        ]);
});

test('authenticated user can update a user', function () {
    $user = User::factory()->create(['role' => RoleEnum::Admin]);
    Sanctum::actingAs($user);

    $targetUser = User::factory()->create();
    $branch = Branch::factory()->create();

    $updateData = [
        'fullname' => 'Updated User',
        'email' => 'updated@example.com',
        'phone' => '0987654321',
        'role' => RoleEnum::Regular->value,
        'branch_id' => $branch->id
    ];

    $response = putJson("/api/users/{$targetUser->id}", $updateData);

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                'id',
                'fullname',
                'email',
                'phone',
                'role',
                'branch_id',
                'created_at',
                'updated_at'
            ]
        ]);

    expect(User::find($targetUser->id))
        ->fullname->toBe('Updated User')
        ->email->toBe('updated@example.com')
        ->phone->toBe('0987654321');
});

test('authenticated user can delete a user', function () {
    $user = User::factory()->create(['role' => RoleEnum::Admin]);
    Sanctum::actingAs($user);

    $targetUser = User::factory()->create();

    $response = deleteJson("/api/users/{$targetUser->id}");

    $response->assertOk()
        ->assertJson([
            'message' => 'User deleted successfully'
        ]);

    expect(User::count())->toBe(1); // Only admin remains
});

test('unauthenticated user cannot access user endpoints', function () {
    $responses = [
        getJson('/api/users'),
        postJson('/api/users', []),
        getJson('/api/users/1'),
        putJson('/api/users/1', []),
        deleteJson('/api/users/1')
    ];

    foreach ($responses as $response) {
        $response->assertStatus(401);
    }
});

test('validation works when creating user', function () {
    $user = User::factory()->create(['role' => RoleEnum::Admin]);
    Sanctum::actingAs($user);

    $response = postJson('/api/users', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['fullname', 'email', 'password', 'role']);
});

test('users can be searched', function () {
    $user = User::factory()->create(['role' => RoleEnum::Admin]);
    Sanctum::actingAs($user);

    $searchUser = User::factory()->create(['fullname' => 'John Doe']);
    User::factory()->create(['fullname' => 'Jane Smith']);

    $response = getJson('/api/users?search=John');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.fullname', 'John Doe');
}); 