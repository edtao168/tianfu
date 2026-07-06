<?php // database/migrations/2026_06_29_000002_create_transactions_table.php

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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            
            // 多店/多帳本預留
            $table->unsignedBigInteger('shop_id')->default(1)->index();
            
            // 用戶關聯
            $table->unsignedBigInteger('user_id')->index();
            
            // 交易類型：expense(支出), income(收入), transfer(轉帳)
            $table->string('type', 20)->index();
            
            // 分類關聯（支出或收入的分類，轉帳時可為 null）
            $table->unsignedBigInteger('category_id')->nullable()->index();
            
            // 帳戶關聯（核心邏輯）
            // 支出時：記錄在 from_account_id，to_account_id 為 null
            // 收入時：記錄在 to_account_id，from_account_id 為 null
            // 轉帳時：兩者皆有值（從 A 帳戶 轉到 B 帳戶）
            $table->unsignedBigInteger('from_account_id')->nullable()->index();
            $table->unsignedBigInteger('to_account_id')->nullable()->index();
            
            // 金額嚴謹性：DECIMAL(16,4)
            $table->decimal('amount', 16, 4)->default(0.0000);
            
            // 交易時間與備註
            $table->dateTime('recorded_at')->index(); // 用戶選定的記帳日期時間
            $table->string('memo')->nullable(); // 備註（例如：麥當勞大麥克套餐）
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};