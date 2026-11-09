<?php
// database/migrations/2026_11_09_140000_rename_frequency_to_cycle_unit_in_transaction_templates_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. 改名
        Schema::table('transaction_templates', function (Blueprint $table) {
            $table->renameColumn('frequency', 'cycle_unit');
        });

        // 2. 只補 comment，其餘屬性照抄 schema
        Schema::table('transaction_templates', function (Blueprint $table) {
            // varchar(20) NULL DEFAULT NULL
            $table->string('cycle_unit', 20)->nullable()->comment('週期單位')->change();
            // int unsigned NOT NULL DEFAULT 1
            $table->integer('interval')->unsigned()->default(1)->comment('週期')->change();
        });
    }

    public function down(): void
    {
        // 還原 comment（原本沒有 comment，給空字串）
        Schema::table('transaction_templates', function (Blueprint $table) {
            $table->string('cycle_unit', 20)->nullable()->comment('')->change();
            $table->integer('interval')->unsigned()->default(1)->comment('')->change();
        });

        // 改回名字
        Schema::table('transaction_templates', function (Blueprint $table) {
            $table->renameColumn('cycle_unit', 'frequency');
        });
    }
};