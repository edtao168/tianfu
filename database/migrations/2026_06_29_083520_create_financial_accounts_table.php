<?php // database/migrations/2026_06_29_000001_create_financial_accounts_table.php

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
        Schema::create('financial_accounts', function (Blueprint $table) {
            $table->id();
            
            // 多店/多帳本預留：預設為 1，方便日後擴展
            $table->unsignedBigInteger('shop_id')->default(1)->index();
            
            // 帳戶基本資訊
            $table->string('name'); // 帳戶名稱（例如：我的錢包、國泰世華、富邦J卡）
            $table->string('type')->default('cash'); // 帳戶類型：cash(現金), checking(儲蓄卡/活存), credit(信用卡)
            
            // 嚴謹的數值設計：所有金額欄位在資料庫使用 DECIMAL(16,4)
            $table->decimal('balance', 16, 4)->default(0.0000); // 當前餘額
            $table->decimal('credit_limit', 16, 4)->default(0.0000); // 信用卡額度（非信用卡則為 0）
            
            $table->string('currency', 3)->default('TWD'); // 幣別快照快照，預設台幣
            $table->text('memo')->nullable(); // 備註
            $table->boolean('is_active')->default(true); // 是否啟用
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_accounts');
    }
};