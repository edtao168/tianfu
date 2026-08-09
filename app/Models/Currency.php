<?php
// app/Models/Currency.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

class Currency extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'shop_id',
        'code',
        'name',
        'symbol',
        'rate',
        'is_base',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'rate' => 'decimal:6',
        'is_base' => 'boolean',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        // 通常不需要隱藏任何欄位
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        // 可加入格式化後的匯率顯示
        'formatted_rate',
    ];

    // ============================================================
    // 關聯定義
    // ============================================================

    /**
     * 取得此幣別所屬的帳本（租戶）
     */
    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    /**
     * 取得使用此幣別的財務帳戶
     */
    public function financialAccounts(): HasMany
    {
        return $this->hasMany(FinancialAccount::class, 'currency', 'code');
    }

    /**
     * 取得建立此記錄的使用者
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * 取得最後更新此記錄的使用者
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // ============================================================
    // 查詢作用域 (Scopes)
    // ============================================================

    /**
     * 篩選指定帳本的幣別
     */
    public function scopeForShop($query, $shopId)
    {
        return $query->where('shop_id', $shopId);
    }

    /**
     * 僅查詢啟用的幣別
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * 僅查詢基準貨幣
     */
    public function scopeBaseCurrency($query)
    {
        return $query->where('is_base', true);
    }

    /**
     * 查詢非基準貨幣的幣別
     */
    public function scopeNonBase($query)
    {
        return $query->where('is_base', false);
    }

    // ============================================================
    // 輔助方法 (Helpers)
    // ============================================================

    /**
     * 獲取格式化後的匯率（保留 6 位小數）
     */
    public function getFormattedRateAttribute(): string
    {
        return number_format($this->rate, 6);
    }

    /**
     * 獲取完整的顯示名稱（含符號和代碼）
     */
    public function getDisplayNameAttribute(): string
    {
        return "{$this->symbol} {$this->name} ({$this->code})";
    }

    /**
     * 判斷是否為基準貨幣
     */
    public function isBaseCurrency(): bool
    {
        return $this->is_base === true;
    }

    /**
     * 判斷是否可刪除（檢查是否被帳戶使用）
     */
    public function isDeletable(): bool
    {
        // 基準貨幣不能被刪除
        if ($this->isBaseCurrency()) {
            return false;
        }

        // 檢查是否被財務帳戶使用
        return $this->financialAccounts()->count() === 0;
    }

    /**
     * 獲取不能刪除的原因
     */
    public function getUndeletableReason(): ?string
    {
        if ($this->isBaseCurrency()) {
            return '基準貨幣無法刪除';
        }

        $accountCount = $this->financialAccounts()->count();
        if ($accountCount > 0) {
            return "有 {$accountCount} 個財務帳戶正在使用此幣別，無法刪除";
        }

        return null;
    }

    // ============================================================
    // 模型事件 (Model Events)
    // ============================================================

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        // 確保每個帳本只有一個基準貨幣
		static::saving(function ($currency) {
			if ($currency->is_base) {
				// 如果將此幣別設為基準，取消該帳本下其他幣別的基準狀態
				static::where('shop_id', $currency->shop_id)
					  ->where('id', '!=', $currency->id)
					  ->update(['is_base' => false]);
			}
		});
    }
}