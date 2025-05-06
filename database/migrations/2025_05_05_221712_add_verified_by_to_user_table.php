<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('verified_by')->nullable();
            $table->string('balance')->default(0); //for debt tracking
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('verified_by');
            $table->dropColumn('balance');
            $table->dropColumn('photo');
        });
    }
};
