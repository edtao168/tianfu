// database/migrations/2026_07_17_add_is_default_to_categories.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('categories', function (Blueprint $table) {
            // 1. 新增欄位，預設值為 false
            $table->boolean('is_default')->default(false)->after('is_active')->comment('是否為系統預設分類');
        });

        // 2. 將所有已存在的分類設定為預設 (is_default = true)
        //    這樣既有的預設資料就會被正確標記
        \App\Models\Category::query()->update(['is_default' => true]);
    }

    public function down()
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('is_default');
        });
    }
};