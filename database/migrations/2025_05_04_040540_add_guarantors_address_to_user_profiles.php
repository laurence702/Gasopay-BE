<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->text('guarantors_address')->nullable();
            $table->string('guarantors_phone')->nullable();
            $table->string('profile_pic_url')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->dropColumn('guarantors_address');
            $table->dropColumn('guarantors_phone');
            $table->dropColumn('profile_pic_url');
        });
    }
};
