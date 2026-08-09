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
        Schema::create('shops', function (Blueprint $table) {
			$table->id();
			$table->string('name')->comment('帳本名稱，例如：家庭帳本');
			$table->string('description')->nullable()->comment('帳本描述');
			$table->foreignId('owner_id')->constrained('users')->comment('帳本擁有者');
			$table->timestamps();
		});

		// 3. shop_user 表 (中間表，記錄誰屬於哪個帳本)
		Schema::create('shop_user', function (Blueprint $table) {
			$table->id();
			$table->foreignId('shop_id')->constrained()->onDelete('cascade');
			$table->foreignId('user_id')->constrained()->onDelete('cascade');
			$table->string('role')->default('member')->comment('角色: owner, admin, member');
			$table->timestamp('joined_at')->useCurrent();
			$table->unique(['shop_id', 'user_id']);
		});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shops');
		Schema::dropIfExists('shop_user');
    }
};
