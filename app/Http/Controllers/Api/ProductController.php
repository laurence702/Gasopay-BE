<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductCollection;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ProductController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): ProductCollection
    {
        $this->authorize('viewAny', Product::class);
        $products = Product::query()
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('unit', 'like', "%{$search}%");
            })
            ->paginate();

        return new ProductCollection($products);
    }

    public function store(StoreProductRequest $request): ProductResource
    {
        $this->authorize('create', Product::class);
        $product = Product::create($request->validated());
        return new ProductResource($product);
    }

    public function show(Product $product): ProductResource
    {
        $this->authorize('view', $product);
        return new ProductResource($product);
    }

    public function update(UpdateProductRequest $request, Product $product): ProductResource
    {
        $this->authorize('update', $product);
        $product->update($request->validated());
        return new ProductResource($product);
    }

    public function destroy(Product $product): JsonResponse
    {
        $this->authorize('delete', $product);
        $product->delete();
        return response()->json([
            'message' => 'Product deleted successfully'
        ]);
    }
}
