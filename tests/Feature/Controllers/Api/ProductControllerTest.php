<?php

use App\Models\User;
use App\Models\Product;
use App\Enums\RoleEnum;
use Laravel\Sanctum\Sanctum;
use Illuminate\Http\Response;
use function Pest\Laravel\{getJson, postJson, putJson, deleteJson};

beforeEach(function () {
    Product::query()->delete();
    User::query()->delete();
});

test('authenticated user can list products', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);
    
    $products = Product::factory()->count(3)->create();

    $response = getJson('/api/products');

    $response->assertStatus(Response::HTTP_OK)
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
    Sanctum::actingAs(User::factory()->create(['role' => RoleEnum::SuperAdmin]));
    $productData = [
        'name' => 'Test Product',
        'description' => 'A test product description',
        'unit' => 'kg',
        'price' => 100
    ];

    $response = postJson('/api/products', $productData);

    $response->assertStatus(Response::HTTP_CREATED)
        ->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'unit',
                'price',
                'created_at',
                'updated_at'
            ]
        ]);
});

test('any authenticated user can view a specific product', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $product = Product::factory()->create();

    $response = getJson("/api/products/{$product->id}");

    $response->assertStatus(Response::HTTP_OK)
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
    $admin = User::factory()->create(['role' => RoleEnum::SuperAdmin]);
    Sanctum::actingAs($admin);

    $product = Product::factory()->create();

    $updatedData = [
        'name' => 'Updated Product',
        'description' => 'An updated description',
        'unit' => 'liter',
        'price' => 250.50
    ];

    $response = putJson("/api/products/{$product->id}", $updatedData);

    $response->assertStatus(Response::HTTP_OK)
        ->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'unit',
                'price',
                'created_at',
                'updated_at'
            ]
        ]);
});

test('authenticated user can delete a product', function () {
    $superAdmin = User::factory()->create(['role' => RoleEnum::SuperAdmin]);
    Sanctum::actingAs($superAdmin);

    $product = Product::factory()->create();

    $response = deleteJson("/api/products/{$product->id}");

    $response->assertStatus(Response::HTTP_OK)
        ->assertJson([
            'message' => 'Product deleted successfully',
        ]);

    $this->assertSoftDeleted('products', ['id' => $product->id]);
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
        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }
});

test('products can be searched', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $product1 = Product::factory()->create(['name' => 'Petrol']);
    $product2 = Product::factory()->create(['name' => 'Diesel']);

    $response = getJson('/api/products?search=Petrol');

    $response->assertStatus(Response::HTTP_OK)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Petrol');
}); 