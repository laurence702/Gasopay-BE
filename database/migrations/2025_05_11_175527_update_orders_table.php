<?php

use App\Enums\VehicleTypeEnum;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // First drop foreign key in payment_histories
        Schema::table('payment_histories', function (Blueprint $table) {
            $table->dropForeign('payment_histories_order_id_foreign');
        });

        // Then update orders table
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('id');
            $table->uuid('id')->primary();
            $table->dropColumn('product_id');
            $table->dropColumn('quantity');
            $table->enum('product', ['keke', 'car', 'cng', 'pms', 'lpg']);
            $table->ulid('created_by')->nullable()->after('payer_id'); //admin who created the order
            $table->enum('payment_type', ['full', 'part'])->default('full');
            $table->enum('payment_status', ['pending', 'paid', 'failed'])->default('pending');
            $table->enum('payment_method', ['cash', 'bank', 'mobile_money'])->default('cash');
            $table->integer('amount_paid')->default(0);
        });

        // Update payment_histories order_id column to UUID using raw SQL
        DB::statement('ALTER TABLE payment_histories ALTER COLUMN order_id TYPE uuid USING (uuid_generate_v4())');

        // Add back the foreign key constraint
        Schema::table('payment_histories', function (Blueprint $table) {
            $table->foreign('order_id')
                  ->references('id')
                  ->on('orders')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        // First drop the foreign key in payment_histories
        Schema::table('payment_histories', function (Blueprint $table) {
            $table->dropForeign('payment_histories_order_id_foreign');
        });

        // Then revert orders table changes
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('product');
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->dropColumn('created_by');
            $table->dropColumn('payment_type');
            $table->dropColumn('payment_status');
            $table->dropColumn('payment_method');
            $table->dropColumn('amount_paid');
            $table->dropColumn('id');
            $table->id();
            $table->integer('quantity')->default(0);
        });

        // Convert payment_histories order_id back to bigint using raw SQL
        DB::statement('ALTER TABLE payment_histories ALTER COLUMN order_id TYPE bigint USING (1)');

        // Finally restore payment_histories foreign key
        Schema::table('payment_histories', function (Blueprint $table) {
            $table->foreign('order_id')
                  ->references('id')
                  ->on('orders')
                  ->onDelete('cascade');
        });
    }
};
