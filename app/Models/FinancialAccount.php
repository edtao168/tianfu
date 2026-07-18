<?php // app/Models/FinancialAccount.php

namespace App\Models;

use App\Traits\BelongsToTenant;
use App\Traits\HasShop;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinancialAccount extends Model
{
    use HasFactory, HasShop, BelongsToTenant;

    /**
     * 允許批量寫入的欄位白名單
     */
    protected $fillable = [
        'shop_id', // =ledger_id；帳本
		'user_id', // =tenant_id；租戶
        'name',
        'type',
        'balance',
        'credit_limit',
        'currency',
        'memo',
        'is_active',
    ];

    /**
     * 數值嚴謹性：Model 層必須標註欄位型態轉換
     * 這裡我們將金額欄位精確標註為 4 位小數的 decimal 字串，以利配合 BC Math 運算
     */
    protected $casts = [
        'shop_id' => 'integer',
        'balance' => 'decimal:4',
        'credit_limit' => 'decimal:4',
        'is_active' => 'boolean',
    ];
	
	/**
     * 一個帳戶擁有多筆交易明細
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'from_account_id');
    }
}