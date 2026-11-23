<?php
// app/Models/Category.php

namespace App\Models;

use App\Traits\HasShop;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

class Category extends Model
{
    use HasFactory, HasShop;

    /**
     * 註：user_id 語意為「建立者記錄」（重構後為 created_by），
     *     不用於查詢隔離。查詢隔離以 shop_id（帳本）為邊界，由 HasShop 提供。
     *     is_default 分類為帳本內預設，不可修改、刪除（由 UI / 控制器層阻擋）。
     */
    protected $fillable = [
        'shop_id',
        'user_id', // 建立者記錄，不用於查詢隔離
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
        'user_id' => 'integer',
        'parent_id' => 'integer',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    /**
     * Category 的建立事件
     *
     * 註：已移除原本的 tenant_categories global scope（where is_default OR user_id = Auth::id()），
     *     因 user_id 不再作為查詢隔離條件。查詢隔離完全由 HasShop 的 shop_id global scope 提供。
     *     is_default 分類的可見性：當前帳本下所有分類（含 is_default 與其他成員建立的分類）皆可見。
     */
    protected static function bootCategory(): void
    {
        // 寫入自訂分類時，自動記錄建立者（僅 audit，不用於隔離）
        static::creating(function ($model) {
            if (Auth::check() && !$model->user_id && !$model->is_default) {
                $model->user_id = Auth::id();
            }
        });
    }

    /**
     * 嚴謹強型別關聯
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id')->orderBy('sort_order', 'asc');
    }

    /**
     * 關聯查詢會套用 Transaction 的 HasShop global scope（shop_id = 1），
     * 單帳本下等價；多帳本啟用時，需確認當前帳本一致。
     */
    public function records(): HasMany
    {
        return $this->hasMany(Transaction::class, 'category_id');
    }

    /**
     * 檢查是否有交易記錄
     */
    public function hasTransactions(): bool
    {
        return $this->records()->exists();
    }
}