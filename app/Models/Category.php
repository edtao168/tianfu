<?php
// app/Models/Category.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'shop_id',
        'parent_id',
        'name',
        'type',
        'icon',
        'sort_order',
        'is_active',
        'is_default',
    ];

    protected $casts = [
        'shop_id' => 'integer',
        'parent_id' => 'integer',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id')->orderBy('sort_order', 'asc');
    }

    // 檢查是否有交易記錄
    public function hasTransactions()
    {
        return $this->records()->exists();
    }

    // 關聯交易記錄（假設 records 是關聯名稱，請根據實際情況調整）
    public function records()
    {
        return $this->hasMany(Transaction::class, 'category_id');
    }
}