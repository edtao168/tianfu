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

    // 查詢篩選條件 (支援多元維度)
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

    // 關聯實體快照 (例如目前選中的帳戶)
    public ?FinancialAccount $currentAccount = null;

    public function mount()
    {
        $this->transactionMonth = now()->format('Y-m');
        $this->resetTransactionsData();
    }

    /**
     * 通用開啟 Modal 事件
     * 支援參數:
     * - accountId: 帳戶 ID
     * - categoryId: 分類 ID
     * - type: 交易類型 (income/expense/transfer)
     * - title: 自訂標題
     */
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
            $this->title = $category ? $category->name : '分類交易';
            $this->subtitle = '分類明細';
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
        $startDate = Carbon::createFromFormat('Y-m', $this->transactionMonth)->startOfMonth();
        $endDate = Carbon::createFromFormat('Y-m', $this->transactionMonth)->endOfMonth();

        $query = Transaction::query()
            ->where('shop_id', $this->shopId)
            ->whereBetween('recorded_at', [$startDate, $endDate]);

        // 依據傳入條件彈性組裝 Query
        if ($this->accountId) {
            $query->where(function ($q) {
                $q->where('from_account_id', $this->accountId)
                  ->orWhere('to_account_id', $this->accountId);
            });
        }

        if ($this->categoryId) {
            $query->where('category_id', $this->categoryId);
        }

        if ($this->transactionType) {
            $query->where('type', $this->transactionType);
        }

        $transactions = $query->orderBy('recorded_at', 'desc')->get();

        // 預載入 Category 與 Account 資訊
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
                // 如果是指定帳戶視角，按該帳戶幣別換算
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
                // 一般全局視角
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

    // 專屬帳戶管理的延伸操作 (僅在 accountId 存在時有效)
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

    // BCMath 工具
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