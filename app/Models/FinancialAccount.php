<?php // app/Models/FinancialAccount.php

namespace App\Models;

use App\Services\MoneyCalculator;
use App\Traits\HasShop;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinancialAccount extends Model
{
    use HasFactory, HasShop;

    /**
     * 允許批量寫入的欄位白名單
     *
     * 註：user_id 語意為「建立者記錄」（重構後為 created_by），
     *     不用於查詢隔離。查詢隔離以 shop_id（帳本）為邊界，由 HasShop 提供。
     */
    protected $fillable = [
        'shop_id', // =ledger_id；帳本
        'user_id', // 建立者記錄，不用於查詢隔離
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
     * 取得帳戶動態計算後的實際餘額 (Decimal 16,4 嚴謹計算)
     *
     * 註：此處 Transaction::where('shop_id', $this->shop_id) 的顯式條件，
     *     與 Transaction 的 HasShop global scope（shop_id = 1）在單帳本下等價；
     *     多帳本啟用時，HasShop 改動態後，此處可保留（重複但正確）或移除。
     */
    public function getCalculatedBalanceAttribute(): string
    {
        $initialBalance = (string) ($this->balance ?? '0');

        // 流入金額 (Inflow)：收入 或 轉帳進入此帳戶
        $inflow = Transaction::where('shop_id', $this->shop_id)
            ->where('to_account_id', $this->id)
			->where('type', '!=', 'expense')
            ->sum('amount');

        // 流出金額 (Outflow)：支出 或 轉帳從此帳戶扣除
        $outflow = Transaction::where('shop_id', $this->shop_id)
            ->where('from_account_id', $this->id)
			->where('type', '!=', 'income')
            ->sum('amount');

        // 交易後實際動態餘額
        return MoneyCalculator::sub(
			MoneyCalculator::add($initialBalance, (string)$inflow),
			(string)$outflow
		);
    }

	/**
	 * 計算截至指定時間（$endDate）的歷史實際餘額（計算每月期末金額）
	 *
	 * 註：同上，Transaction 的顯式 shop_id 條件與 HasShop global scope 單帳本下等價。
	 */
	public function getBalanceAtDate(?\Carbon\Carbon $endDate = null): string
	{
		$initialBalance = (string) ($this->balance ?? '0');

		$inflowQuery = Transaction::where('shop_id', $this->shop_id)
			->where('to_account_id', $this->id)
			->where('type', '!=', 'expense');

		$outflowQuery = Transaction::where('shop_id', $this->shop_id)
			->where('from_account_id', $this->id)
			->where('type', '!=', 'income');

		if ($endDate) {
			$inflowQuery->where('recorded_at', '<=', $endDate);
			$outflowQuery->where('recorded_at', '<=', $endDate);
		}

		$inflow = $inflowQuery->sum('amount');
		$outflow = $outflowQuery->sum('amount');

		// 歷史餘額 = 初始金額 + 截至時間的總流入 - 截至時間的總流出
	    return MoneyCalculator::sub(
			MoneyCalculator::add($initialBalance, (string)$inflow),
			(string)$outflow
		);
	}
	
	/**
     * 一個帳戶擁有多筆交易明細
     *
     * 註：關聯查詢會套用 Transaction 的 HasShop global scope（shop_id = 1），
     *     單帳本下等價；多帳本啟用時，需確認 $account->shop_id 與當前帳本一致。
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'from_account_id');
    }
}