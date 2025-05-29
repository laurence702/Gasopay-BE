<?php

namespace App\Traits;

use App\Models\Order;
use Illuminate\Support\Str;

trait GeneratesOrderReference
{
    /**
     * Generate a unique order reference
     *
     * @return string
     */
    protected function generateOrderReference(): string
    {
        // Safe characters for the random part (excluding similar-looking characters)
        $safeChars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        
        do {
            // Format: ORD-YYMM-XXXXX
            // ORD: Prefix
            // YYMM: Year and month
            // XXXXX: Random string
            $reference = sprintf(
                'ORD-%s-%s',
                date('ym'),
                Str::upper(Str::random(5, $safeChars))
            );
        } while ($this->orderReferenceExists($reference));

        return $reference;
    }

    /**
     * Check if an order reference already exists
     *
     * @param string $reference
     * @return bool
     */
    protected function orderReferenceExists(string $reference): bool
    {
        return Order::where('order_reference', $reference)->exists();
    }
} 