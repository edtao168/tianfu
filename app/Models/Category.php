<?php
// app/Models/Category.php

namespace App\Models;

use App\Traits\HasShop;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

class Category extends Model
{
    use HasFactory, HasShop;

    protected $fillable = [
        'shop_id',
        'user_id',
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
     * Category 專屬的全域租戶 Scope
     * 確保使用者能看到「系統預設」以及「自己建立」的分類
     */
    protected static function bootCategory(): void
    {
        // 寫入自訂分類時，自動綁定當前使用者
        static::creating(function ($model) {
            if (Auth::check() && !$model->user_id && !$model->is_default) {
                $model->user_id = Auth::id();
            }
        });

        // 核心隔離邏輯：WHERE is_default = 1 OR user_id = 當前用戶
        static::addGlobalScope('tenant_categories', function (Builder $builder) {
            if (Auth::check()) {
                $builder->where(function (Builder $query) {
                    $query->where('is_default', true)
                          ->orWhere('user_id', Auth::id());
                });
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