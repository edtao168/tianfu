<?php
// database/migrations/2026_03_14_000000_create_transaction_templates_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaction_templates', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('shop_id')->default(1); // 多店預留
            $table->string('type', 20); // expense, income, transfer
            $table->string('name', 50); // 範本名稱
            $table->decimal('amount', 16, 4)->default(0.0000); // 數值嚴謹性
            $table->foreignId('account_id')->nullable()->constrained('financial_accounts')->onDelete('cascade');
            $table->foreignId('to_account_id')->nullable()->constrained('financial_accounts')->onDelete('cascade');
            $table->foreignId('category_id')->nullable()->constrained('categories')->onDelete('cascade');
            $table->string('memo', 255)->nullable();
            $table->timestamps();

            $table->index(['shop_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_templates');
    }
};