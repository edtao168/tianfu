<?php

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
        Schema::table('transaction_templates', function (Blueprint $table) {
            // 將 name 調整到前面
			$table->string('name', 50)->after('user_id')->change();
            
            // 是否為定期收支項目
            $table->boolean('is_recurring')->default(false)->after('memo');
            
            // 週期頻率 (例如: monthly, yearly)
            $table->string('frequency', 20)->nullable()->after('is_recurring');
            
            // 每 N (天/月/年) 觸發一次
            $table->unsignedInteger('interval')->default(1)->after('frequency');
            
            // 下次預計發生日
            $table->date('next_due_date')->nullable()->after('interval');
            
            // 結束日期 (若無限期則 NULL)
            $table->date('end_date')->nullable()->after('next_due_date');
            
            // 是否啟用此定期收支
            $table->boolean('is_active')->default(true)->after('end_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transaction_templates', function (Blueprint $table) {
            $table->dropColumn([
                'is_recurring',
                'frequency',
                'interval',
                'next_due_date',
                'end_date',
                'is_active',
            ]);
        });
    }
};
