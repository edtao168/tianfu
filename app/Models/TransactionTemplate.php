<?php
// app/Models/TransactionTemplate.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'shop_id',
        'type',
        'name',
        'amount',
        'from_account_id',
        'to_account_id',
        'category_id',
        'memo',
    ];

    protected $casts = [
        'amount' => 'decimal:4',
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
}