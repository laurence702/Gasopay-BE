<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\User;
use App\Models\PaymentHistory;
use App\Enums\PaymentMethodEnum;
use App\Enums\PaymentStatusEnum;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PaymentHistory>
 */
class PaymentHistoryFactory extends Factory
{
    protected $model = PaymentHistory::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Safe payment methods present in both Enum and migration
        $safePaymentMethods = [
            PaymentMethodEnum::Cash->value,
            PaymentMethodEnum::BankTransfer->value,
            PaymentMethodEnum::Wallet->value,
        ];

        return [
            'order_id' => Order::factory(),
            'user_id' => User::factory(), // user_id is the payer
            'amount' => $this->faker->randomFloat(2, 50, 1000), // Sensible default amount
            'payment_method' => $this->faker->randomElement($safePaymentMethods),
            'status' => $this->faker->randomElement(PaymentStatusEnum::cases())->value,
            'reference' => $this->faker->optional()->bothify('REF-##########'),
            'approved_by' => null, // Default to not approved
            'approved_at' => null, // Default to not approved
        ];
    }
}
