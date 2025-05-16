<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('unit')->nullable()->after('name');
            $table->text('description')->nullable()->after('name');
            $table->decimal('price', 8, 2)->default(0.00)->after('unit');
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUlid('payer_id')->constrained('users')->cascadeOnDelete()->comment('e.g rider, user');
            $table->ulid('created_by')->nullable()->after('payer_id');
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->enum('product', ['keke', 'car', 'cng', 'pms', 'lpg']);
            $table->decimal('amount_due', 10, 2);
            $table->enum('payment_type', ['full', 'part'])->default('full')->after('amount_due');
            $table->enum('payment_method', ['cash', 'bank_transfer', 'wallet'])->default('cash');
            $table->enum('payment_status', ['pending', 'paid', 'failed', 'completed', 'approved', 'rejected'])->default('pending');
            $table->timestamps();
            $table->softDeletes();

            $table->index('payer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
        Schema::dropIfExists('products');
    }
}; 