<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\ProfileVerificationStatusEnum;

return new class extends Migration
{
    public function up(): void
    {
        // Create branches table WITHOUT branch_admin foreign key constraint
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('location');
            $table->ulid('branch_admin')->nullable(); // Just the column, no constraint yet
            $table->string('branch_phone')->nullable();
            $table->timestamps();
        });

        // Create users table WITHOUT branch_id foreign key constraint
        Schema::create('users', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('fullname');
            $table->string('phone')->unique();
            $table->string('email')->unique();
            $table->enum('role', ['admin', 'rider', 'regular', 'super_admin']);
            $table->unsignedBigInteger('branch_id')->nullable(); // Just the column, no constraint yet
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->uuid('profile_id')->nullable()->comment('for rider and non-admin users only');
            $table->enum('verification_status', ['pending', 'verified', 'rejected'])
                  ->default(ProfileVerificationStatusEnum::PENDING->value);
            $table->string('verified_by')->nullable();
            $table->string('balance')->default(0);
            $table->ipAddress('ip_address')->nullable();
            $table->rememberToken();
            $table->timestamp('banned_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Now add the foreign key constraints after both tables exist
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('branch_id')
                  ->references('id')
                  ->on('branches')
                  ->nullOnDelete();
        });

        Schema::table('branches', function (Blueprint $table) {
            $table->foreign('branch_admin')
                  ->references('id')
                  ->on('users')
                  ->cascadeOnDelete();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
        Schema::dropIfExists('branches');
    }
};
