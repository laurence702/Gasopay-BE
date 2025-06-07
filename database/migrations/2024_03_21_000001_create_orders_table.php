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
            $table->string('order_reference', 30)->unique()->nullable();
            $table->foreignUlid('payer_id')->constrained('users')->cascadeOnDelete()->comment('e.g rider, user');
            $table->ulid('created_by')->nullable()->after('payer_id');
            $table->ulid('branch_id')->nullable();
            $table->enum('product', ['keke', 'car', 'cng', 'pms', 'lpg']);
            $table->decimal('amount_due', 10, 2);
            $table->enum('payment_type', ['full', 'part'])->default('full')->after('amount_due');
            $table->enum('payment_method', ['cash', 'bank_transfer', 'wallet'])->default('cash');
            $table->enum('payment_status', ['pending', 'paid', 'failed', 'completed', 'approved', 'rejected'])->default('pending');
            $table->timestamps();
            $table->softDeletes();

            $table->index('payer_id');
            $table->index('order_reference');
            
            $table->foreign('branch_id')
                  ->references('id')
                  ->on('branches')
                  ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
        Schema::dropIfExists('products');
    }
}; 