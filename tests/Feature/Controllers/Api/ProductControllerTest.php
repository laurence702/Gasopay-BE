<?php

use App\Models\User;
use App\Models\Product;
use App\Enums\RoleEnum;
use Laravel\Sanctum\Sanctum;
use function Pest\Laravel\{getJson, postJson, putJson, deleteJson};

beforeEach(function () {
    // Clear database
    Product::query()->delete();
    User::query()->delete();
});

test('authenticated user can list products', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);
    
    $products = Product::factory()->count(3)->create();

    $response = getJson('/api/products');

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'unit',
                    'created_at',
                    'updated_at'
                ]
            ],
            'links',
            'meta'
        ]);
});

test('super admin user can create a product', function () {
    $superAdmin = User::factory()->create(['role' => RoleEnum::SuperAdmin]);
    Sanctum::actingAs($superAdmin);

    $productData = [
        'name' => 'Test Product',
        'unit' => 'kg',
        'price' => 100.00
    ];

    $response = postJson('/api/products', $productData);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'unit',
                'created_at',
                'updated_at'
            ]
        ]);

    expect(Product::count())->toBe(1);
});

test('any authenticated user can view a specific product', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $product = Product::factory()->create();

    $response = getJson("/api/products/{$product->id}");

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'unit',
                'created_at',
                'updated_at'
            ]
        ]);
});

test('super_admin can update a product', function () {
    $superAdmin = User::factory()->create(['role' => RoleEnum::SuperAdmin]);
    Sanctum::actingAs($superAdmin);

    $product = Product::factory()->create();
    $updateData = [
        'name' => 'Updated Product',
        'unit' => 'liter'
    ];

    $response = putJson("/api/products/{$product->id}", $updateData);

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'unit',
                'created_at',
                'updated_at'
            ]
        ]);

    expect(Product::first())
        ->name->toBe('Updated Product')
        ->unit->toBe('liter');
});

test('authenticated user can delete a product', function () {
    $superAdmin = User::factory()->create(['role' => RoleEnum::SuperAdmin]);
    Sanctum::actingAs($superAdmin);

    $product = Product::factory()->create();

    $response = deleteJson("/api/products/{$product->id}");

    $response->assertOk()
        ->assertJson([
            'message' => 'Product deleted successfully'
        ]);

    expect(Product::count())->toBe(0);
});

test('unauthenticated user cannot access product endpoints', function () {
    $responses = [
        getJson('/api/products'),
        postJson('/api/products', []),
        getJson('/api/products/1'),
        putJson('/api/products/1', []),
        deleteJson('/api/products/1')
    ];

    foreach ($responses as $response) {
        $response->assertStatus(401);
    }
});

test('products can be searched', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $product1 = Product::factory()->create(['name' => 'Petrol']);
    $product2 = Product::factory()->create(['name' => 'Diesel']);

    $response = getJson('/api/products?search=Petrol');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Petrol');
}); 