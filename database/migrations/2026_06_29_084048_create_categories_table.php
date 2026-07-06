<?php
// database/migrations/2026_06_29_000003_create_categories_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            
            // 多店/多帳本預留
            $table->unsignedBigInteger('shop_id')->default(1)->index();
            
            // 無限級分類的核心：指向自己的 id
            // 如果 parent_id 為 null，代表它是大分類（如：食品飲料）
            // 如果 parent_id 有值，代表它是子分類（如：外食、咖啡）
            $table->unsignedBigInteger('parent_id')->nullable()->index();
            
            // 分類基本資訊
            $table->string('name'); // 分類名稱
            $table->string('type', 20)->default('expense'); // 適用類型：expense(支出), income(收入)
            $table->string('icon')->nullable(); // 圖標（挖財有很多可愛的小圖標）
            $table->integer('sort_order')->default(0); // 排序，數字越小越靠前
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};