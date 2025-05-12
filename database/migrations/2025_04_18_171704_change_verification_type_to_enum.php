<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use App\Enums\ProfileVerificationStatusEnum;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
           $table->dropColumn('profile_verified');
            $table->enum('verification_status', ['pending', 'verified', 'rejected'])
            ->default(ProfileVerificationStatusEnum::PENDING->value);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
           $table->boolean('profile_verified')->default(false);
            $table->dropColumn('verification_status');
        });
    }
};
