<?php
// app/Models/TransactionTemplate.php

namespace App\Models;

use App\Traits\HasShop;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class TransactionTemplate extends Model
{
    use HasFactory, HasShop;

    /**
     * 註：user_id 語意為「建立者記錄」（重構後為 created_by），
     *     不用於查詢隔離。查詢隔離以 shop_id（帳本）為邊界，由 HasShop 提供。
     */
    protected $fillable = [
        'shop_id',
        'user_id', // 建立者記錄，不用於查詢隔離
        'name',
        'type',
        'category_id',
        'from_account_id',
        'to_account_id',
        'amount',
        'memo',
        'is_recurring',
        'cycle_unit',
        'interval',
        'next_due_date',
        'end_date',
        'is_active',
    ];

    protected $casts = [
        'amount' => 'decimal:4',
        'is_recurring' => 'boolean',
        'is_active' => 'boolean',
        'interval' => 'integer',
        'next_due_date' => 'date',
        'end_date' => 'date',
    ];

    // 關聯：來源帳戶
    public function fromAccount()
    {
        return $this->belongsTo(FinancialAccount::class, 'from_account_id');
    }

    // 關聯：目標帳戶
    public function toAccount()
    {
        return $this->belongsTo(FinancialAccount::class, 'to_account_id');
    }

    // 關聯：分類
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * 只抓取日常記帳範本（排除定期收支）
     */
    public function scopeStandard(Builder $query): void
    {
        $query->where('is_recurring', false);
    }

    /**
     * 只抓取定期收支項目
     */
    public function scopeRecurring(Builder $query): void
    {
        $query->where('is_recurring', true);
    }
}