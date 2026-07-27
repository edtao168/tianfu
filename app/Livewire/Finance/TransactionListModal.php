<?php
// app/Livewire/Finance/TransactionListModal.php

namespace App\Livewire\Finance;

use App\Models\Category;
use App\Models\FinancialAccount;
use App\Models\Transaction;
use Carbon\Carbon;
use Livewire\Attributes\On;
use Livewire\Component;
use Mary\Traits\Toast;

class TransactionListModal extends Component
{
    use Toast;

    public bool $showModal = false;
    public $shopId = 1;

    // 查詢篩選條件
    public ?int $accountId = null;
    public ?int $categoryId = null;
    public ?string $transactionType = null; // income, expense, transfer

    // 標題與幣別顯示
    public string $title = '交易明細列表';
    public string $subtitle = '';
    public string $currencySymbol = 'NT$';
    public string $targetCurrency = '';

    // 交易資料與月份
    public array $transactionsData = [];
    public string $transactionMonth = '';

    // 四項總體統計（月支出、月收入、年支出、年收入）
    public array $statsSummary = [
        'month_expense' => '0.00',
        'month_income' => '0.00',
        'year_expense' => '0.00',
        'year_income' => '0.00',
    ];

    // 關聯實體快照
    public ?FinancialAccount $currentAccount = null;

    public function mount()
    {
        $this->transactionMonth = now()->format('Y-m');
        $this->resetTransactionsData();
    }

    #[On('open-transaction-list-modal')]
    public function openModal(array $params = [])
    {
        $this->resetFilter();

        $this->accountId = $params['accountId'] ?? null;
        $this->categoryId = $params['categoryId'] ?? null;
        $this->transactionType = $params['type'] ?? null;

        // 設定標題與幣別邏輯
        if ($this->accountId) {
            $account = FinancialAccount::find($this->accountId);
            if (!$account) {
                $this->toast(type: 'error', title: '找不到該帳戶');
                return;
            }
            $this->currentAccount = $account;
            $this->title = $account->name;
            $this->subtitle = '帳戶歷史交易';
            $this->targetCurrency = $account->currency;

            $currencies = config('business.currencies', []);
            $this->currencySymbol = $currencies[$account->currency]['symbol'] ?? 'NT$';
        } elseif ($this->categoryId) {
            $category = Category::find($this->categoryId);
            $this->title = $params['title'] ?? ($category ? $category->name : '分類交易');
            $this->subtitle = '大類與關聯子類明細';
            $this->targetCurrency = $this->getBaseCurrency();
            $this->currencySymbol = $this->getBaseCurrencySymbol();
        } else {
            $this->title = $params['title'] ?? '交易明細列表';
            $this->subtitle = '流水記錄';
            $this->targetCurrency = $this->getBaseCurrency();
            $this->currencySymbol = $this->getBaseCurrencySymbol();
        }

        $this->loadTransactions();
        $this->showModal = true;
    }

    #[On('refresh-transaction-list')]
    public function refreshTransactions()
    {
        if ($this->showModal) {
            $this->loadTransactions();
        }
    }

    public function loadTransactions()
    {
        $currentDate = Carbon::createFromFormat('Y-m', $this->transactionMonth);
        $monthStart = $currentDate->copy()->startOfMonth();
        $monthEnd = $currentDate->copy()->endOfMonth();
        $yearStart = $currentDate->copy()->startOfYear();
        $yearEnd = $currentDate->copy()->endOfYear();

        // 1. 若有 categoryId，包含該大類及所有子類 ID
        $targetCategoryIds = [];
        if ($this->categoryId) {
            $subCategoryIds = Category::where('parent_id', $this->categoryId)->pluck('id')->toArray();
            $targetCategoryIds = array_merge([$this->categoryId], $subCategoryIds);
        }

        // 2. 構建基底 Query 閉包
        $applyFilter = function ($query) use ($targetCategoryIds) {
            $query->where('shop_id', $this->shopId);

            if ($this->accountId) {
                $query->where(function ($q) {
                    $q->where('from_account_id', $this->accountId)
                      ->orWhere('to_account_id', $this->accountId);
                });
            }

            if (!empty($targetCategoryIds)) {
                $query->whereIn('category_id', $targetCategoryIds);
            }

            if ($this->transactionType) {
                $query->where('type', $this->transactionType);
            }
        };

        // 3. 計算（月支出、月收入、年支出、年收入）數據
        $monthTxs = Transaction::query()->where($applyFilter)->whereBetween('recorded_at', [$monthStart, $monthEnd])->get();
        $yearTxs = Transaction::query()->where($applyFilter)->whereBetween('recorded_at', [$yearStart, $yearEnd])->get();

        $monthExpense = '0.0000';
        $monthIncome = '0.0000';
        foreach ($monthTxs as $tx) {
            $amt = (string)$tx->amount;
            if ($tx->type === 'expense') $monthExpense = bcadd($monthExpense, $amt, 4);
            if ($tx->type === 'income') $monthIncome = bcadd($monthIncome, $amt, 4);
        }

        $yearExpense = '0.0000';
        $yearIncome = '0.0000';
        foreach ($yearTxs as $tx) {
            $amt = (string)$tx->amount;
            if ($tx->type === 'expense') $yearExpense = bcadd($yearExpense, $amt, 4);
            if ($tx->type === 'income') $yearIncome = bcadd($yearIncome, $amt, 4);
        }

        $this->statsSummary = [
            'month_expense' => number_format((float)$monthExpense, 2),
            'month_income' => number_format((float)$monthIncome, 2),
            'year_expense' => number_format((float)$yearExpense, 2),
            'year_income' => number_format((float)$yearIncome, 2),
        ];

        // 4. 取得當月交易流水清單
        $transactions = $monthTxs->sortByDesc('recorded_at');

        $categoryIds = $transactions->pluck('category_id')->filter()->unique()->toArray();
        $categories = Category::whereIn('id', $categoryIds)->get()->keyBy('id');

        $accountIds = [];
        foreach ($transactions as $tx) {
            if ($tx->from_account_id) $accountIds[] = $tx->from_account_id;
            if ($tx->to_account_id) $accountIds[] = $tx->to_account_id;
        }
        $accounts = FinancialAccount::whereIn('id', array_unique($accountIds))->get()->keyBy('id');

        $totalIncome = '0.0000';
        $totalExpense = '0.0000';
        $formattedList = [];

        foreach ($transactions as $tx) {
            $isTransfer = ($tx->type === 'transfer');
            $isIncome = false;
            $isExpense = false;
            $displayAmount = (string)$tx->amount;

            if ($this->accountId) {
                if ($tx->type === 'income' && $tx->to_account_id == $this->accountId) {
                    $isIncome = true;
                    $displayAmount = $this->convertToAccountCurrency((string)$tx->amount, $tx->currency, $this->targetCurrency);
                } elseif ($tx->type === 'expense' && $tx->from_account_id == $this->accountId) {
                    $isExpense = true;
                    $displayAmount = $this->convertToAccountCurrency((string)$tx->amount, $tx->currency, $this->targetCurrency);
                } elseif ($isTransfer) {
                    if ($tx->to_account_id == $this->accountId) {
                        $isIncome = true;
                        $displayAmount = $this->convertToAccountCurrency((string)$tx->amount, $tx->currency, $this->targetCurrency);
                    } elseif ($tx->from_account_id == $this->accountId) {
                        $isExpense = true;
                        $displayAmount = $this->convertToAccountCurrency((string)$tx->amount, $tx->currency, $this->targetCurrency);
                    }
                }
            } else {
                if ($tx->type === 'income') $isIncome = true;
                if ($tx->type === 'expense') $isExpense = true;
            }

            if ($isIncome) {
                $totalIncome = bcadd($totalIncome, $displayAmount, 4);
            } elseif ($isExpense) {
                $totalExpense = bcadd($totalExpense, $displayAmount, 4);
            }

            $categoryIcon = 'folder';
            $categoryName = null;

            if ($isTransfer) {
                $categoryIcon = 'arrow-path';
            } elseif ($tx->category_id && isset($categories[$tx->category_id])) {
                $category = $categories[$tx->category_id];
                $categoryIcon = $category->icon ?? 'folder';
                $categoryName = $category->name;
            }

            $fromAccountName = $tx->from_account_id && isset($accounts[$tx->from_account_id]) ? $accounts[$tx->from_account_id]->name : null;
            $toAccountName = $tx->to_account_id && isset($accounts[$tx->to_account_id]) ? $accounts[$tx->to_account_id]->name : null;

            $formattedList[] = [
                'id' => $tx->id,
                'type' => $tx->type,
                'amount' => $tx->amount,
                'recorded_at' => $tx->recorded_at,
                'memo' => $tx->memo,
                'category_id' => $tx->category_id,
                'from_account_id' => $tx->from_account_id,
                'to_account_id' => $tx->to_account_id,
                'currency' => $tx->currency,
                'display_amount' => $displayAmount,
                'is_income' => $isIncome,
                'is_expense' => $isExpense,
                'is_transfer' => $isTransfer,
                'category_icon' => $categoryIcon,
                'category_name' => $categoryName,
                'from_account_name' => $fromAccountName,
                'to_account_name' => $toAccountName,
            ];
        }

        $this->transactionsData = [
            'list' => $formattedList,
            'total_income' => $totalIncome,
            'total_expense' => $totalExpense,
            'total_count' => $transactions->count(),
        ];
    }
	
	public function closeModal()
	{
		$this->showModal = false;
		$this->dispatch('modal-closed');
	}

    private function resetFilter()
    {
        $this->accountId = null;
        $this->categoryId = null;
        $this->transactionType = null;
        $this->currentAccount = null;
    }

    private function resetTransactionsData()
    {
        $this->transactionsData = [
            'list' => [],
            'total_income' => '0.0000',
            'total_expense' => '0.0000',
            'total_count' => 0,
        ];
    }

    public function previousMonth()
    {
        $date = Carbon::createFromFormat('Y-m', $this->transactionMonth)->subMonth();
        $this->transactionMonth = $date->format('Y-m');
        $this->loadTransactions();
    }

    public function nextMonth()
    {
        $date = Carbon::createFromFormat('Y-m', $this->transactionMonth)->addMonth();
        if ($date->isFuture()) {
            $date = now();
        }
        $this->transactionMonth = $date->format('Y-m');
        $this->loadTransactions();
    }

    public function editAccount()
    {
        if (!$this->accountId) return;
        $this->showModal = false;
        $this->dispatch('edit-account-from-transactions', accountId: $this->accountId);
    }

    public function toggleAccountVisibility()
    {
        if (!$this->accountId) return;

        try {
            $account = FinancialAccount::findOrFail($this->accountId);
            $newStatus = !$account->is_active;
            $account->update(['is_active' => $newStatus]);

            $statusText = $newStatus ? '顯示' : '隱藏';
            $this->toast(type: 'success', title: "已{$statusText}帳戶：{$account->name}");

            $this->currentAccount = $account->fresh();
            $this->dispatch('refresh-data');

        } catch (\Exception $e) {
            $this->toast(type: 'error', title: '操作失敗：' . $e->getMessage());
        }
    }

    private function getBaseCurrency(): string
    {
        return config('business.base_currency', 'TWD');
    }

    private function getBaseCurrencySymbol(): string
    {
        $base = $this->getBaseCurrency();
        $currencies = config('business.currencies', []);
        return $currencies[$base]['symbol'] ?? 'NT$';
    }

    private function getExchangeRate(string $currency): float
    {
        $currencies = config('business.currencies', []);
        return (float)($currencies[$currency]['rate'] ?? 1);
    }

    private function convertToBase(string $amount, string $currency): string
    {
        if (!is_numeric($amount) || $amount === '') return '0.0000';
        $rate = $this->getExchangeRate($currency);
        return bcmul($amount, (string)$rate, 4) ?: '0.0000';
    }

    private function convertFromBase(string $amount, string $currency): string
    {
        if (!is_numeric($amount) || $amount === '') return '0.0000';
        $rate = $this->getExchangeRate($currency);
        if ($rate == 0) return '0.0000';
        return bcdiv($amount, (string)$rate, 4) ?: '0.0000';
    }

    private function convertToAccountCurrency(string $amount, string $fromCurrency, string $toCurrency): string
    {
        if (!is_numeric($amount) || $amount === '') return '0.0000';
        if ($fromCurrency === $toCurrency) return bcadd($amount, '0.0000', 4);

        $amountInBase = $this->convertToBase($amount, $fromCurrency);
        return $this->convertFromBase($amountInBase, $toCurrency);
    }

    public function render()
    {
        return view('livewire.finance.transaction-list-modal');
    }
}