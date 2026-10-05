<?php // database/migrations/xxxx_xx_xx_xxxxxx_add_decimal_places_to_currencies_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('currencies', function (Blueprint $table) {
            // 中文名稱：幣別小數位數
            // 用途：控制金額顯示精度，例如 TWD=2、JPY=0、BTC=8
            $table->unsignedTinyInteger('decimal_places')
                  ->default(2)
                  ->after('symbol')
                  ->comment('幣別小數位數');
        });

        // 為常見幣別設定合理預設值
        DB::table('currencies')->whereIn('code', ['JPY', 'KRW', 'VND', 'IDR'])->update(['decimal_places' => 0]);
        DB::table('currencies')->whereIn('code', ['BTC', 'ETH', 'USDT'])->update(['decimal_places' => 8]);
        // TWD/CNY/HKD/USD/EUR 等維持 default 2，不需 update
    }

    public function down(): void
    {
        Schema::table('currencies', function (Blueprint $table) {
            $table->dropColumn('decimal_places');
        });
    }
};