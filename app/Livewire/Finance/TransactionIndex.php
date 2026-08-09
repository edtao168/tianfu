<?php
// app/Livewire/Finance/TransactionIndex.php

namespace App\Livewire\Finance;

use App\Models\Category;
use App\Models\FinancialAccount;
use App\Models\Transaction;
use App\Services\CurrencyService;
use App\Services\MoneyCalculator;
use Carbon\Carbon;
use Livewire\Component;
use Mary\Traits\Toast;

class TransactionIndex extends Component
{
    use Toast;

    // 篩選器綁定變數
    public string $searchCurrency = '';
    public ?int $searchAccountId = null;
    public ?int $searchCategoryId = null;
    public string $searchType = '';

    // 控制是否顯示進階篩選抽屜
    public bool $showFilters = false;

    // 年月選擇器
    public string $transactionMonth = '';

    // 預留多店店別，預設為 1
    public int $shopId = 1;
	
	protected CurrencyService $currencyService;

    public function boot(CurrencyService $currencyService)
    {
        $this->currencyService = $currencyService;
    }

    public function mount()
    {
        $this->transactionMonth = now()->format('Y-m');
    }

    /**
     * 當篩選條件變更時，重新載入數據
     */
    public function updatedSearchCurrency() { $this->loadTransactions(); }
    public function updatedSearchAccountId() { $this->loadTransactions(); }
    public function updatedSearchCategoryId() { $this->loadTransactions(); }
    public function updatedSearchType() { $this->loadTransactions(); }

    /**
     * 監聽數據刷新事件
     */
    #[On('refresh-data')]
    public function onDataChanged()
    {
        $this->loadTransactions();
    }

    /**
     * 監聽點擊編輯交易
     */
    #[On('edit-transaction')]
    public function editTransaction($transactionId)
    {
        try {
            $transaction = Transaction::find($transactionId);
            if (!$transaction) {
                $this->toast(type: 'error', title: '交易不存在');
                return;
            }

            $this->dispatch('open-transaction-modal', transactionId: $transactionId);
            
        } catch (\Exception $e) {
            \Log::error('Edit transaction error: ' . $e->getMessage());
            $this->toast(type: 'error', title: '無法編輯交易：' . $e->getMessage());
        }
    }

    /**
     * 上一個月
     */
    public function previousMonth()
    {
        $date = Carbon::createFromFormat('Y-m', $this->transactionMonth)->subMonth();
        $this->transactionMonth = $date->format('Y-m');
        $this->loadTransactions();
    }

    /**
     * 下一個月
     */
    public function nextMonth()
    {
        $date = Carbon::createFromFormat('Y-m', $this->transactionMonth)->addMonth();
        if ($date->isFuture()) {
            $date = now();
        }
        $this->transactionMonth = $date->format('Y-m');
        $this->loadTransactions();
    }

    /**
     * 載入交易數據（無分頁）
     */
    private function loadTransactions()
    {
        // 這個方法會在 render 中被重新執行，不需要額外操作
        // 保留此方法以維持一致性
    }

    /**
     * 刪除單筆記帳紀錄
     */
    public function deleteTransaction(int $id)
    {
        try {
            DB::transaction(function () use ($id) {
                $transaction = Transaction::where('id', $id)
                    ->where('shop_id', $this->shopId)
                    ->firstOrFail();

                $accountId = $transaction->from_account_id ?? $transaction->to_account_id;
                $account = FinancialAccount::where('id', $accountId)->lockForUpdate()->firstOrFail();

                if ($transaction->type === 'expense') {
                    $newBalance = bcadd($account->balance, $transaction->amount, 4);
                } else {
                    $newBalance = bcsub($account->balance, $transaction->amount, 4);
                }

                $account->update(['balance' => $newBalance]);
                $transaction->delete();
            });

            $this->toast(type: 'success', title: '刪除成功', description: '該筆紀錄已移除，帳戶餘額已精確沖正回滾。');
            $this->dispatch('refresh-data');
            
        } catch (\Exception $e) {
            $this->toast(type: 'error', title: '刪除失敗', description: $e->getMessage());
        }
    }

    /**
     * 後端渲染主邏輯
     */
    public function render()
    {
        // 解析月份範圍
        $startDate = Carbon::createFromFormat('Y-m', $this->transactionMonth)->startOfMonth();
        $endDate = Carbon::createFromFormat('Y-m', $this->transactionMonth)->endOfMonth();

        // 建立基本查詢器
        $query = Transaction::query()
            ->with(['category', 'category.parent', 'fromAccount', 'toAccount'])
            ->where('shop_id', $this->shopId)
            ->whereBetween('recorded_at', [$startDate, $endDate])
            ->orderBy('recorded_at', 'desc');

        // 套用動態條件過濾
        if ($this->searchType) {
            $query->where('type', $this->searchType);
        }
        if ($this->searchCategoryId) {
            $query->where('category_id', $this->searchCategoryId);
        }
        if ($this->searchAccountId) {
            $query->where(function($q) {
                $q->where('from_account_id', $this->searchAccountId)
                  ->orWhere('to_account_id', $this->searchAccountId);
            });
        }
        if ($this->searchCurrency) {
            $query->where(function($q) {
                $q->whereHas('fromAccount', fn($a) => $a->where('currency', $this->searchCurrency))
                  ->orWhereHas('toAccount', fn($a) => $a->where('currency', $this->searchCurrency));
            });
        }

        // 取得所有交易（無分頁）
        $transactions = $query->get();
		
		 // ✅ 為每個交易附加幣別符號
		$transactions->each(function ($tx) {
			$acc = $tx->fromAccount ?? $tx->toAccount;
			$currencyCode = $acc->currency ?? 'TWD';
			$currency = $this->currencyService->getCurrency($currencyCode);
			$tx->currency_symbol = $currency?->symbol ?? 'NT$';
			$tx->currency_code = $currencyCode;
		});

        // 依日期分組
        $groupedTransactions = $transactions->groupBy(function ($item) {
            return Carbon::parse($item->recorded_at)->format('Y-m-d');
        });

        // 計算本月收支統計
        $totalIncome = '0.0000';
        $totalExpense = '0.0000';
		
        foreach ($transactions as $tx) {
            $currency = $tx->currency ?? $this->currencyService->getBaseCurrencyCode();
            $amount = $tx->amount;
            $amountInBase = $this->currencyService->convertToBase((string)$amount, $currency);

            if ($tx->type === 'income') {
                // 使用 MoneyCalculator
                $totalIncome = MoneyCalculator::add($totalIncome, $amountInBase, 4);
            } elseif ($tx->type === 'expense') {
                // 使用 MoneyCalculator
                $totalExpense = MoneyCalculator::add($totalExpense, $amountInBase, 4);
            }
        }

        // 撈取篩選選單所需資料
        $accounts = FinancialAccount::where('is_active', true)->get();
        $filteredAccounts = $this->searchCurrency ? $accounts->where('currency', $this->searchCurrency) : $accounts;
        $categories = Category::whereNotNull('parent_id')->orderBy('sort_order')->get();

        // 計算當前月份顯示文字
        $monthDisplay = Carbon::createFromFormat('Y-m', $this->transactionMonth)->format('Y 年 m 月');
        $isCurrentMonth = Carbon::createFromFormat('Y-m', $this->transactionMonth)->isSameMonth(now());
		
		// 從資料庫取得所有幣別
        $currencies = $this->currencyService->getAllCurrencies();
		// 獲取基準貨幣符號
		$baseSymbol = $this->currencyService->getBaseCurrencySymbol();


        return view('livewire.finance.transaction-index', [
            'groupedTransactions' => $groupedTransactions,
            'transactions' => $transactions,
            'currencies' => $currencies->toArray(),
            'accounts' => $filteredAccounts,
            'categories' => $categories,
            'totalIncome' => number_format((float)$totalIncome, 2),
            'totalExpense' => number_format((float)$totalExpense, 2),
            'baseSymbol' => $baseSymbol,
            'monthDisplay' => $monthDisplay,
            'isCurrentMonth' => $isCurrentMonth,
            'transactionCount' => $transactions->count(),
        ])->layout('components.layouts.app');
    }
}