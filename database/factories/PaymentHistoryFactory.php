<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PaymentHistory>
 */
class PaymentHistoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => '',
            'payer_id' => '',
            'approver_id' => '',
            'branch_id' => '',
            'amount_due' => '',
            'amount_paid' => '',
            'outstanding' => '',
            'payment_type' => '',
            'payment_method' => '',
             'status' => '',
            'quantity' => '',
        ];
    }
}
