<?php

namespace App\Http\Controllers\Api;

use App\Enums\ProductTypeEnum;
use App\Http\Controllers\Controller;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;

class ProductTypeController extends Controller
{
    protected $productService;
    
    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }
    
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => $this->productService->getAllProducts()
        ]);
    }
    
    public function show(string $type): JsonResponse
    {
        try {
            $productType = ProductTypeEnum::from($type);
            return response()->json([
                'data' => [
                    'type' => $productType->value,
                    'price' => $this->productService->getPrice($productType),
                    'description' => $this->productService->getDescription($productType),
                    'unit' => $this->productService->getUnit($productType),
                ]
            ]);
        } catch (\ValueError $e) {
            return response()->json([
                'message' => 'Product type not found'
            ], 404);
        }
    }
}
