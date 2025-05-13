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
            $table->string('phone')->nullable();
            $table->text('address');
            $table->string('nin')->nullable();
            $table->string('guarantors_name')->nullable();
            $table->text('guarantors_address')->nullable()->after('guarantors_name');
            $table->string('guarantors_phone')->nullable()->after('guarantors_address');
            $table->string('vehicle_type')->nullable();
            $table->string('profile_pic_url')->nullable()->after('vehicle_type');
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
