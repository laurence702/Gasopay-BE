<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_types', function (Blueprint $table) {
            $table->mediumIncrements('id');
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('user_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete()->comment('e.g rider');
            $table->unsignedMediumInteger('vehicle_type_id')->nullable();
            $table->string('phone');
            $table->text('address');
            $table->string('nin')->nullable();
            $table->string('guarantors_name')->nullable();
            $table->string('photo')->nullable();
            $table->string('barcode')->nullable();
            $table->timestamps();

            $table->foreign('vehicle_type_id')
            ->references('id')
            ->on('vehicle_types')
            ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_types');
        Schema::dropIfExists('user_profiles');
    }
};
