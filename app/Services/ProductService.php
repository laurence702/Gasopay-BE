<?php

namespace App\Services;

use App\Enums\ProductTypeEnum;

class ProductService
{
    // Map of product types to their prices
    private const PRODUCT_PRICES = [
        ProductTypeEnum::PMS->value => 100.00,
        ProductTypeEnum::CNG->value => 150.00,
        ProductTypeEnum::LPG->value => 200.00,
    ];
    
    // Map of product types to their descriptions
    private const PRODUCT_DESCRIPTIONS = [
        ProductTypeEnum::PMS->value => 'Premium Motor Spirit',
        ProductTypeEnum::CNG->value => 'Compressed Natural Gas',
        ProductTypeEnum::LPG->value => 'Liquefied Petroleum Gas',
    ];
    
    // Map of product types to their units
    private const PRODUCT_UNITS = [
        ProductTypeEnum::PMS->value => 'liter',
        ProductTypeEnum::CNG->value => 'kg',
        ProductTypeEnum::LPG->value => 'kg',
    ];
    
    /**
     * Get the price for a product type
     */
    public function getPrice(ProductTypeEnum $productType): float
    {
        return self::PRODUCT_PRICES[$productType->value];
    }
    
    /**
     * Get the description for a product type
     */
    public function getDescription(ProductTypeEnum $productType): string
    {
        return self::PRODUCT_DESCRIPTIONS[$productType->value];
    }
    
    /**
     * Get the unit for a product type
     */
    public function getUnit(ProductTypeEnum $productType): string
    {
        return self::PRODUCT_UNITS[$productType->value];
    }
    
    /**
     * Get all product types with their details
     */
    public function getAllProducts(): array
    {
        $products = [];
        foreach (ProductTypeEnum::cases() as $case) {
            $products[] = [
                'type' => $case->value,
                'price' => $this->getPrice($case),
                'description' => $this->getDescription($case),
                'unit' => $this->getUnit($case),
            ];
        }
        return $products;
    }
}
