<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('fullname')->after('id');
            $table->string('phone')->unique()->after('email');
            $table->enum('role', ['admin', 'rider', 'regular', 'super_admin'])->after('phone');
            $table->foreignId('branch_id')->nullable()->after('role')->constrained()->nullOnDelete();
            $table->dropColumn('name');
            $table->boolean('profile_verified')->default(false);
        });
    }


    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('name');
            $table->dropForeign(['branch_id']);
            $table->dropColumn(['fullname', 'phone', 'role', 'branch_id']);
        });
    }
};
