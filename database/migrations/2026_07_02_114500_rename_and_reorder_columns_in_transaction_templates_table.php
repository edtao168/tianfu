<?php
// database/migrations/2026_07_02_114500_rename_and_reorder_columns_in_transaction_templates_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. 先執行改名
        Schema::table('transaction_templates', function (Blueprint $table) {
            $table->renameColumn('account_id', 'from_account_id');
        });

        // 2. 使用原生 SQL 調整欄位順序，使其完全對齊 transactions 的結構
        // 目標順序：id -> shop_id -> user_id -> type -> category_id -> from_account_id -> to_account_id -> amount -> memo ...
        DB::statement("ALTER TABLE transaction_templates MODIFY COLUMN user_id BIGINT UNSIGNED NULL AFTER shop_id");
        DB::statement("ALTER TABLE transaction_templates MODIFY COLUMN type VARCHAR(20) NOT NULL AFTER user_id");
        DB::statement("ALTER TABLE transaction_templates MODIFY COLUMN category_id BIGINT UNSIGNED NULL AFTER type");
        DB::statement("ALTER TABLE transaction_templates MODIFY COLUMN from_account_id BIGINT UNSIGNED NULL AFTER category_id");
        DB::statement("ALTER TABLE transaction_templates MODIFY COLUMN to_account_id BIGINT UNSIGNED NULL AFTER from_account_id");
        DB::statement("ALTER TABLE transaction_templates MODIFY COLUMN amount DECIMAL(16,4) NOT NULL DEFAULT '0.0000' AFTER to_account_id");
        DB::statement("ALTER TABLE transaction_templates MODIFY COLUMN name VARCHAR(50) NOT NULL AFTER amount"); // template 獨有欄位，置於金額後
        DB::statement("ALTER TABLE transaction_templates MODIFY COLUMN memo VARCHAR(255) NULL AFTER name");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 還原原本的順序與名稱
        DB::statement("ALTER TABLE transaction_templates MODIFY COLUMN shop_id INT UNSIGNED NOT NULL DEFAULT '1' AFTER id");
        DB::statement("ALTER TABLE transaction_templates MODIFY COLUMN user_id BIGINT UNSIGNED NULL AFTER shop_id");
        DB::statement("ALTER TABLE transaction_templates MODIFY COLUMN type VARCHAR(20) NOT NULL AFTER user_id");
        DB::statement("ALTER TABLE transaction_templates MODIFY COLUMN name VARCHAR(50) NOT NULL AFTER type");
        DB::statement("ALTER TABLE transaction_templates MODIFY COLUMN amount DECIMAL(16,4) NOT NULL DEFAULT '0.0000' AFTER name");
        DB::statement("ALTER TABLE transaction_templates MODIFY COLUMN from_account_id BIGINT UNSIGNED NULL AFTER amount");
        DB::statement("ALTER TABLE transaction_templates MODIFY COLUMN to_account_id BIGINT UNSIGNED NULL AFTER from_account_id");
        DB::statement("ALTER TABLE transaction_templates MODIFY COLUMN category_id BIGINT UNSIGNED NULL AFTER to_account_id");
        DB::statement("ALTER TABLE transaction_templates MODIFY COLUMN memo VARCHAR(255) NULL AFTER category_id");

        Schema::table('transaction_templates', function (Blueprint $table) {
            $table->renameColumn('from_account_id', 'account_id');
        });
    }
};