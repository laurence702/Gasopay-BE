<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_proofs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('payment_history_id')->constrained('payment_histories')->onDelete('cascade');
            $table->string('proof_url');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignUlid('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->index('payment_history_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_proofs');
    }
};
