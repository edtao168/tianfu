<?php // [個人記帳系統] database/migrations/2026_07_09_xxxxxx_create_partners_table.php

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
        Schema::create('partners', function (Blueprint $blueprint) {
            $blueprint->id();
            // 嚴謹關聯：每個家庭成員必須綁定一個登入帳號 (users.id)
            $blueprint->foreignId('user_id')->constrained()->onDelete('cascade');
            
            $blueprint->string('name', 50);             // 成員稱呼 (例如：老婆、大兒子)
            $blueprint->string('photo_path')->nullable(); // 頭像圖片路徑 (完全對齊你 Model 的 photo_path)
            $blueprint->string('phone', 20)->nullable();
            $blueprint->string('role', 30)->default('member'); // 權限角色 (如 admin, member)
            
            // 💡 補齊你參考進銷存所延伸的結構欄位
            $blueprint->json('contacts')->nullable();    // 聯絡資訊 JSON (Line, 載具等多維資料)
            $blueprint->date('joined_at')->nullable();   // 加入日期 / 生日
            
            $blueprint->boolean('is_active')->default(true);
            $blueprint->timestamps();
            $blueprint->softDeletes(); // 虛擬刪除，確保歷史流水對照一致性
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partners');
    }
};