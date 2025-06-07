<?php

namespace Database\Factories;

use App\Enums\ProofStatusEnum;
use App\Models\Order;
use App\Models\User;
use App\Models\PaymentHistory;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentProofFactory extends Factory
{
    public function definition(): array
    {
        return [
            'payment_history_id' => PaymentHistory::factory(),
            'proof_url' => $this->faker->imageUrl(),
            'status' => ProofStatusEnum::Pending->value,
            'approved_by' => null,
            'approved_at' => null,
        ];
    }

    public function approved(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => ProofStatusEnum::Approved->value,
                'approved_by' => User::factory(),
                'approved_at' => now(),
            ];
        });
    }

    public function rejected(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => ProofStatusEnum::Rejected->value,
                'approved_by' => User::factory(),
                'approved_at' => now(),
            ];
        });
    }
} 