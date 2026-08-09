<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shop_user', function (Blueprint $table) {
            $table->timestamps(); // 加上 created_at 和 updated_at
        });
    }

    public function down(): void
    {
        Schema::table('shop_user', function (Blueprint $table) {
            $table->dropTimestamps();
        });
    }
};