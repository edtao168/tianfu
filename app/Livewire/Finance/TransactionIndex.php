<?php // app/Livewire/Finance/TransactionIndex.php

namespace App\Livewire\Finance;

use App\Models\Category;
use App\Models\Currency;
use App\Models\FinancialAccount;
use App\Models\Transaction;
use App\Traits\WithDateNavigation;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class TransactionIndex extends Component
{
    use WithPagination, WithDateNavigation;

    public int $shopId = 1;

    // UI 開關與篩選屬性
    public bool $showFilters = false;
    public ?string $searchCurrency = null;
    public ?int $searchAccountId = null;
    public ?int $searchCategoryId = null;
    public ?string $searchType = null;
    public string $search = '';

    public function mount()
    {
        // 1. 預設以「月」為單位
        $this->currentDate = now()->format('Y-m');
        $this->dateMode = 'month';
        $this->shopId = (int) session('current_shop_id', 1);
    }

    #[On('refresh-data')]
    public function refreshData()
    {
        $this->resetPage();
    }

    protected function onDateChanged()
    {
        $this->resetPage();
    }

    public function getBaseCurrencyProperty()
    {
        return Currency::where('shop_id', $this->shopId)->where('is_base', true)->first();
    }

    public function render()
    {
        $parsedDate = $this->parseCurrentDate();

        // 日期顯示與當月狀態判斷
        $monthDisplay = $parsedDate->format('Y 年 m 月');
        $isCurrentMonth = $parsedDate->isCurrentMonth();

        // 按「月」進行交易紀錄查詢
        $query = Transaction::query()
            ->where('shop_id', $this->shopId)
            ->whereYear('recorded_at', $parsedDate->year)
            ->whereMonth('recorded_at', $parsedDate->month)
            ->with(['category', 'fromAccount', 'toAccount']);

        // 條件篩選
        if ($this->searchCategoryId) {
            $query->where('category_id', $this->searchCategoryId);
        }

        if ($this->searchAccountId) {
            $query->where(function ($q) {
                $q->where('from_account_id', $this->searchAccountId)
                  ->orWhere('to_account_id', $this->searchAccountId);
            });
        }

        if ($this->searchType) {
            $query->where('type', $this->searchType);
        }

        if ($this->searchCurrency) {
            $query->where(function ($q) {
                $q->whereHas('fromAccount', fn($a) => $a->where('currency', $this->searchCurrency))
                  ->orWhereHas('toAccount', fn($a) => $a->where('currency', $this->searchCurrency));
            });
        }

        if (!empty($this->search)) {
            $query->where('note', 'like', '%' . $this->search . '%');
        }

        // 高精度當月金額累加 (bcadd / bcsub)
        $monthTransactions = (clone $query)->orderBy('recorded_at', 'desc')->get();
        $totalIncome = '0.0000';
        $totalExpense = '0.0000';

        foreach ($monthTransactions as $tx) {
            $amount = (string) $tx->amount;
            if ($tx->type === 'income') {
                $totalIncome = bcadd($totalIncome, $amount, 4);
            } elseif ($tx->type === 'expense') {
                $totalExpense = bcadd($totalExpense, $amount, 4);
            }
        }

        // 按日期（Y-m-d）分組
        $groupedTransactions = $monthTransactions->groupBy(function ($item) {
            return \Carbon\Carbon::parse($item->recorded_at)->format('Y-m-d');
        });

        // 基礎選單與貨幣資料
        $categories = Category::where('shop_id', $this->shopId)->get();
        $accounts = FinancialAccount::where('shop_id', $this->shopId)->where('is_active', true)->get();
        
        $currenciesData = Currency::where('shop_id', $this->shopId)->get();
        $currencies = [];
        foreach ($currenciesData as $c) {
            $currencies[$c->code] = [
                'name' => $c->name,
                'symbol' => $c->symbol,
            ];
        }

        return view('livewire.finance.transaction-index', [
            'groupedTransactions' => $groupedTransactions,
            'transactionCount' => $monthTransactions->count(),
            'categories' => $categories,
            'accounts' => $accounts,
            'currencies' => $currencies,
            'monthDisplay' => $monthDisplay,
            'isCurrentMonth' => $isCurrentMonth,
            'totalIncome' => number_format((float)$totalIncome, 2),
            'totalExpense' => number_format((float)$totalExpense, 2),
            'baseSymbol' => $this->baseCurrency?->symbol ?? 'NT$',
        ])->layout('components.layouts.app');
    }
}