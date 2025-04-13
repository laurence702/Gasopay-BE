<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('user_id')->constrained('users')->cascadeOnDelete()->comment('e.g rider');
            $table->string('phone');
            $table->text('address');
            $table->string('nin')->nullable();
            $table->string('guarantors_name')->nullable();
            $table->string('vehicle_type')->nullable();
            $table->string('photo')->nullable();
            $table->string('barcode')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_profiles');
    }
};
