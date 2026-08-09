<?php
// database/migrations/2026_08_09_085544_create_currencies_table.php

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
        // 檢查資料表是否已存在
        if (!Schema::hasTable('currencies')) {
            // 如果不存在，建立完整的新表
            Schema::create('currencies', function (Blueprint $table) {
                $table->id();
                $table->foreignId('shop_id')
                      ->constrained()
                      ->onDelete('cascade')
                      ->comment('帳本/租戶 ID');
                $table->string('code', 3)->comment('幣別代碼');
                $table->string('name')->comment('幣別名稱');
                $table->string('symbol', 10)->comment('幣別符號');
                $table->decimal('rate', 15, 6)->default(1.000000)->comment('對基準貨幣的匯率');
                $table->boolean('is_base')->default(false)->comment('是否為基準貨幣');
                $table->boolean('is_active')->default(true)->comment('是否啟用');
                $table->timestamps();
                
                $table->unique(['shop_id', 'code']);
                $table->index(['shop_id', 'is_base', 'is_active']);
                $table->index(['shop_id', 'is_active']);
            });
        } else {
            // 如果已存在，只追加缺失的索引和約束
            $this->addMissingConstraints();
        }
    }

    /**
     * 追加缺失的約束和索引
     */
    protected function addMissingConstraints(): void
    {
        // 使用原生 SQL 檢查和新增
        $connection = DB::connection();
        $database = $connection->getDatabaseName();
        $table = 'currencies';

        // 1. 檢查並新增唯一約束
        $indexes = $connection->select("
            SELECT INDEX_NAME 
            FROM INFORMATION_SCHEMA.STATISTICS 
            WHERE TABLE_SCHEMA = ? 
            AND TABLE_NAME = ? 
            AND INDEX_NAME = 'currencies_shop_id_code_unique'
        ", [$database, $table]);

        if (empty($indexes)) {
            Schema::table('currencies', function (Blueprint $table) {
                $table->unique(['shop_id', 'code']);
            });
        }

        // 2. 檢查並新增一般索引
        $indexes = $connection->select("
            SELECT INDEX_NAME 
            FROM INFORMATION_SCHEMA.STATISTICS 
            WHERE TABLE_SCHEMA = ? 
            AND TABLE_NAME = ? 
            AND INDEX_NAME = 'currencies_shop_id_is_base_is_active_index'
        ", [$database, $table]);

        if (empty($indexes)) {
            Schema::table('currencies', function (Blueprint $table) {
                $table->index(['shop_id', 'is_base', 'is_active']);
            });
        }

        $indexes = $connection->select("
            SELECT INDEX_NAME 
            FROM INFORMATION_SCHEMA.STATISTICS 
            WHERE TABLE_SCHEMA = ? 
            AND TABLE_NAME = ? 
            AND INDEX_NAME = 'currencies_shop_id_is_active_index'
        ", [$database, $table]);

        if (empty($indexes)) {
            Schema::table('currencies', function (Blueprint $table) {
                $table->index(['shop_id', 'is_active']);
            });
        }

        // 3. 檢查並新增外鍵約束（如果 shops 表存在）
        if (Schema::hasTable('shops')) {
            $foreignKeys = $connection->select("
                SELECT CONSTRAINT_NAME 
                FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = ? 
                AND TABLE_NAME = ? 
                AND COLUMN_NAME = 'shop_id' 
                AND REFERENCED_TABLE_NAME IS NOT NULL
            ", [$database, $table]);

            if (empty($foreignKeys)) {
                // 先檢查並刪除可能存在的舊外鍵
                // 然後新增外鍵
                Schema::table('currencies', function (Blueprint $table) {
                    $table->foreign('shop_id')
                          ->references('id')
                          ->on('shops')
                          ->onDelete('cascade');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('currencies');
    }
};