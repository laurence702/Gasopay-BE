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
            $table->uuid('id')->primary();
            $table->foreignUuid('order_id')->constrained('orders')->onDelete('cascade');
            $table->foreignUlid('user_id')->constrained('users')->onDelete('restrict'); // Rider, Regular User
            $table->decimal('amount', 10, 2);
            $table->enum('payment_method', ['cash', 'bank', 'bank_transfer', 'mobile_money', 'wallet']);
            $table->enum('status', ['pending', 'approved', 'rejected', 'paid', 'completed', 'failed'])->default('pending');
            $table->string('reference')->nullable();
            $table->foreignUlid('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_histories');
    }
};
