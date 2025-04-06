<?php

use App\Models\User;
use App\Models\Branch;
use App\Enums\RoleEnum;
use Laravel\Sanctum\Sanctum;
use function Pest\Laravel\{getJson, postJson, putJson, deleteJson};

beforeEach(function () {
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
            ]
        ]);
});

// test('only super admin can create branch admin', function () {
//     $branch_admin = User::factory()->create(['role' => RoleEnum::Admin]);
//     $branch_admin = Branch::factory()->create();
//     Sanctum::actingAs($branch_admin);
//     $userData = [
//         'fullname' => 'Test User',
//         'email' => 'test@example.com',
//         'phone' => '1234567890',
//         'password' => 'password123',
//         'password_confirmation' => 'password123',
//         'role' => RoleEnum::Rider->value,
//         'branch_id' => $branch_admin->branch->id
//     ];

//     $response = postJson('/api/create-admin', $userData);

//     $response->assertStatus(403);
// });

test('branch admin can register a rider', function () {
    // Create a branch first
    $branch = Branch::factory()->create();
    
    // Create branch admin and associate with the branch
    $branchAdmin = User::factory()->create([
        'role' => RoleEnum::Admin,
        'branch_id' => $branch->id
    ]);
    
    Sanctum::actingAs($branchAdmin);
    
    $userData = [
        'fullname' => 'Test Rider',
        'email' => 'rider@example.com',
        'phone' => '1234567890',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => RoleEnum::Rider->value,
        'branch_id' => $branch->id
    ];

    $response = postJson('/api/register-rider', $userData);

    $response->assertCreated()
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
});

test('Admin can view a specific user', function () {
    $admin = User::factory()->create(['role' => RoleEnum::Admin]);
    Sanctum::actingAs($admin);

    $targetUser = User::factory()->create();

    $response = getJson("/api/users/{$targetUser->id}", [
        'Accept' => 'application/json',
        'Authorization' => 'Bearer ' . $admin->createToken('test-token')->plainTextToken
    ]);

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
    $admin = User::factory()->create(['role' => RoleEnum::Admin]);
    Sanctum::actingAs($admin);

    $targetUser = User::factory()->create();
    $branch = Branch::factory()->create();

    $updateData = [
        'fullname' => 'Updated User',
        'email' => 'updated@example.com',
        'phone' => '0987654321',
        'role' => RoleEnum::Regular->value,
        'branch_id' => $branch->id
    ];

    $response = putJson("/api/users/{$targetUser->id}", $updateData, [
        'Accept' => 'application/json',
        'Authorization' => 'Bearer ' . $admin->createToken('test-token')->plainTextToken
    ]);

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
    $admin = User::factory()->create(['role' => RoleEnum::Admin]);
    Sanctum::actingAs($admin);

    $targetUser = User::factory()->create();

    $response = deleteJson("/api/users/{$targetUser->id}", [], [
        'Accept' => 'application/json',
        'Authorization' => 'Bearer ' . $admin->createToken('test-token')->plainTextToken
    ]);

    $response->assertOk()
        ->assertJson([
            'message' => 'User deleted successfully'
        ]);

    expect(User::count())->toBe(1); // Only admin remains
});

test('unauthenticated user cannot access user endpoints', function () {  
    $responses = [  
        getJson('/api/users'),  
        getJson('/api/users/1'),  
        putJson('/api/users/1', []),  
        deleteJson('/api/users/1')  
    ];  

    foreach ($responses as $response) {  
        $response->assertStatus(401);  
    }  
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
