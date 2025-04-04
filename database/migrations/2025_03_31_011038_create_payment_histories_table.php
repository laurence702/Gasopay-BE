<?php

use App\Enums\PaymentTypeEnum;
use App\Enums\PaymentMethodEnum;
use App\Enums\PaymentStatusEnum;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignUlid('payer_id')->constrained('users')->onDelete('restrict'); // Rider, User
            $table->foreignUlid('approver_id')->constrained('users')->onDelete('restrict'); // Branch Admin
            $table->foreignId('branch_id')->constrained()->onDelete('cascade'); // Branch where transaction occurs
            $table->decimal('amount_due', 10, 2); // Total cost (e.g., 20 liters * price)
            $table->decimal('amount_paid', 10, 2)->default(0.00)->comment('Amount paid so far');
            $table->decimal('outstanding', 10, 2)->nullable(); // Remaining balance
            $table->string('payment_type')->default(PaymentTypeEnum::Part->value); // Enum: Full, Part
            $table->string('payment_method')->default(PaymentMethodEnum::Cash->value); // Enum: Cash, BankTransfer
            $table->string('status')->default(PaymentStatusEnum::Pending->value); // Enum: Pending, Approved, Completed
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_histories');
    }
};
