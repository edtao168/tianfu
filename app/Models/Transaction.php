<?php // app/Models/Transaction.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'shop_id',
        'user_id',
        'type',
        'category_id',
        'from_account_id',
        'to_account_id',
        'amount',
        'recorded_at',
        'memo',
		'photo_path',
    ];

    protected $casts = [
        'shop_id' => 'integer',
        'user_id' => 'integer',
        'category_id' => 'integer',
        'from_account_id' => 'integer',
        'to_account_id' => 'integer',
        'amount' => 'decimal:4',
        'recorded_at' => 'datetime',
    ];
	
	/**
	 * 取得該交易相關的主要帳戶（用於判斷幣別）
	 */
	public function getRelatedAccountAttribute()
	{
		if ($this->type === 'income' && $this->to_account_id) {
			return $this->toAccount;
		} elseif ($this->type === 'expense' && $this->from_account_id) {
			return $this->fromAccount;
		} elseif ($this->type === 'transfer') {
			return $this->fromAccount ?? $this->toAccount;
		}
		return null;
	}
	
	/**
	 * 獲取交易對應的幣別
	 */
	public function getCurrencyAttribute(): string
	{
		$account = null;
		
		// 收入：錢進入 to_account，幣別應該從 to_account 取得
		if ($this->type === 'income') {
			$account = $this->toAccount;
		} 
		// 支出：錢從 from_account 出去，幣別應該從 from_account 取得
		elseif ($this->type === 'expense') {
			$account = $this->fromAccount;
		} 
		// 轉帳：優先使用 from_account，如果沒有則使用 to_account
		elseif ($this->type === 'transfer') {
			$account = $this->fromAccount ?? $this->toAccount;
		}
		
		// 如果找不到帳戶或帳戶沒有幣別，回傳預設值 TWD
		if (!$account || !isset($account->currency)) {
			\Log::warning('Currency not found for transaction', [
				'transaction_id' => $this->id,
				'type' => $this->type,
				'from_account_id' => $this->from_account_id,
				'to_account_id' => $this->to_account_id,
			]);
			return 'TWD';
		}
		
		return $account->currency;
	}
	
	// 取得照片完整 URL
    public function getPhotoUrlAttribute(): ?string
    {
        if ($this->photo_path) {
            return asset('storage/' . $this->photo_path);
        }
        return null;
    }

	/**
     * 收支分類關聯
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    /**
     * 來源帳戶關聯
     */
    public function fromAccount(): BelongsTo
    {
        return $this->belongsTo(FinancialAccount::class, 'from_account_id');
    }

    /**
     * 目標帳戶關聯
     */
    public function toAccount(): BelongsTo
    {
        return $this->belongsTo(FinancialAccount::class, 'to_account_id');
    }
}