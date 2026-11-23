<?php // app/Models/Transaction.php
// user_id = 建立者記錄（重構後為 created_by），不用於查詢隔離
// shop_id = 帳本 (Ledger/Shop)
// 一個使用者可以擁有多個帳本，多個使用者可以共用一個帳本
// 所有財務資料 都透過 shop_id 隔離（由 HasShop global scope 提供）

namespace App\Models;

use App\Traits\HasShop;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use HasFactory, HasShop;

    /**
     * 註：user_id 語意為「建立者記錄」（重構後為 created_by），
     *     不用於查詢隔離。查詢隔離以 shop_id（帳本）為邊界，由 HasShop 提供。
     */
    protected $fillable = [
        'shop_id',
        'user_id', // 建立者記錄，不用於查詢隔離
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
     *
     * 註：關聯查詢會套用 Category 的 HasShop global scope（shop_id = 1），
     *     單帳本下等價；多帳本啟用時，需確認當前帳本一致。
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    /**
     * 來源帳戶關聯
     *
     * 註：關聯查詢會套用 FinancialAccount 的 HasShop global scope（shop_id = 1）。
     */
    public function fromAccount(): BelongsTo
    {
        return $this->belongsTo(FinancialAccount::class, 'from_account_id');
    }

    /**
     * 目標帳戶關聯
     *
     * 註：關聯查詢會套用 FinancialAccount 的 HasShop global scope（shop_id = 1）。
     */
    public function toAccount(): BelongsTo
    {
        return $this->belongsTo(FinancialAccount::class, 'to_account_id');
    }
}