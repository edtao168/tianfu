<?php
// app/Models/Shop.php
// 帳本（shop_id=ledger_id）是隔離邊界，user_id 只是「這筆資料是誰建的」的記錄，不是查詢隔離條件。

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Shop extends Model
{
    protected $fillable = [
        'name',
        'description',
        'owner_id',
    ];

    protected $casts = [
        'owner_id' => 'integer',
    ];

    /**
     * 取得帳本的擁有者
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * 取得所有屬於此帳本的使用者
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'shop_user')
                    ->withPivot('role', 'joined_at')
                    ->withTimestamps();
    }

    /**
     * 取得此帳本下的所有幣別
     */
    public function currencies(): HasMany
    {
        return $this->hasMany(Currency::class);
    }

    /**
     * 取得此帳本下的所有財務帳戶
     */
    public function financialAccounts(): HasMany
    {
        return $this->hasMany(FinancialAccount::class);
    }
}