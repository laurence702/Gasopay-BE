<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Branch;
use App\Enums\RoleEnum;
use App\Http\Resources\ProductResource;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class BranchProductController extends Controller
{
    /**
     * Get products for a branch admin to manage
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getProducts(Request $request): JsonResponse
    {
        // Pagination
        $perPage = $request->input('per_page', 15);
        
        // Filtering options
        $search = $request->input('search');
        
        $query = Product::query();
        
        // Branch admins can see all products
        // This could be adjusted if products need to be branch-specific
        
        // Apply search filter if provided
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }
        
        // Sort by most recent first
        $query->orderBy('created_at', 'desc');
        
        // Paginate results
        $products = $query->paginate($perPage);
        
        return response()->json(ProductResource::collection($products));
    }
    
    /**
     * Get a single product
     * 
     * @param string $id
     * @return JsonResponse
     */
    public function getProduct(string $id): JsonResponse
    {
        $product = Product::findOrFail($id);
        
        return response()->json(new ProductResource($product));
    }
    
    /**
     * Create a price quote for a product (branch-specific pricing if applicable)
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function createPriceQuote(Request $request): JsonResponse
    {
        // Validate the request
        $validated = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'quantity' => ['required', 'numeric', 'min:1'],
            'rider_id' => ['nullable', 'exists:users,id']
        ]);
        
        // Get the product
        $product = Product::findOrFail($validated['product_id']);
        
        // Calculate the total price
        $totalPrice = $product->price * $validated['quantity'];
        
        // This is where you could apply branch-specific pricing rules
        // For example, discounts for certain branches or riders
        
        return response()->json([
            'product' => new ProductResource($product),
            'quantity' => $validated['quantity'],
            'unit_price' => $product->price,
            'total_price' => $totalPrice,
            'quote_valid_until' => now()->addDays(1)->toDateTimeString()
        ]);
    }
} 