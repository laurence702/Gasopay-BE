<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Order;
use App\Models\Branch;
use App\Enums\PaymentTypeEnum;
use App\Enums\PaymentMethodEnum;
use App\Enums\PaymentStatusEnum;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        $amount_due = $this->faker->randomFloat(2, 1000, 10000);
        
        return [
            'id' => Str::uuid(),
            'payer_id' => User::factory(),
            'created_by' => User::factory(),
            'branch_id' => Branch::factory(),
            'product' => $this->faker->randomElement(['keke', 'car', 'cng', 'pms', 'lpg']),
            'amount_due' => $amount_due,
            'payment_type' => $this->faker->randomElement([PaymentTypeEnum::Full->value, PaymentTypeEnum::Part->value]),
            'payment_method' => $this->faker->randomElement([
                PaymentMethodEnum::Cash->value,
                PaymentMethodEnum::BankTransfer->value
            ]),
            'payment_status' => $this->faker->randomElement([
                PaymentStatusEnum::Pending->value, 
                PaymentStatusEnum::Paid->value, 
                PaymentStatusEnum::Failed->value
            ]),
        ];
    }
} 