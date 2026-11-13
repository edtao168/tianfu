<?php // app/Livewire/Finance/TransactionIndex.php

namespace App\Livewire\Finance;

use App\Models\Category;
use App\Models\Currency;
use App\Models\FinancialAccount;
use App\Models\Transaction;
use App\Services\CurrencyService;
use App\Services\MoneyCalculator;
use App\Traits\WithDateNavigation;
use Carbon\Carbon;
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
        // 預設月模式；gotoCurrentDate 依 dateMode 產生對應格式
        $this->dateMode = 'month';
        $this->gotoCurrentDate();
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

    /**
     * 切換時間粒度（day | month | year）
     * 切換時保留「當前時間點」的語意，例如：
     *   月 2026-11 → 年 2026 → 日 2026-11-13（取今天）
     */
    public function updatedDateMode($value)
    {
        if (!in_array($value, ['day', 'month', 'year'], true)) {
            $this->dateMode = 'month';
            $value = 'month';
        }

        $parsed = $this->parseCurrentDate();

        $this->currentDate = match ($value) {
            'year'  => $parsed->format('Y'),
            'day'   => $parsed->format('Y-m-d'),
            default => $parsed->format('Y-m'),
        };

        $this->resetPage();
    }

    public function getBaseCurrencyProperty()
    {
        return Currency::where('shop_id', $this->shopId)->where('is_base', true)->first();
    }

    /**
     * 渲染組件視圖
     */
    public function render()
    {
        $parsedDate = $this->parseCurrentDate();

        // ---------- 日期顯示與狀態判斷（三態） ----------
        $dateDisplay = match ($this->dateMode) {
            'day'   => $parsedDate->format('Y 年 m 月 d 日'),
            'year'  => $parsedDate->format('Y 年'),
            default => $parsedDate->format('Y 年 m 月'),
        };

        $isCurrent = match ($this->dateMode) {
            'day'   => $parsedDate->isToday(),
            'year'  => $parsedDate->isCurrentYear(),
            default => $parsedDate->isCurrentMonth(),
        };

        // ---------- 期間區間（三態） ----------
        [$start, $end] = match ($this->dateMode) {
            'day'   => [$parsedDate->copy()->startOfDay(),   $parsedDate->copy()->endOfDay()],
            'year'  => [$parsedDate->copy()->startOfYear(),  $parsedDate->copy()->endOfYear()],
            default => [$parsedDate->copy()->startOfMonth(), $parsedDate->copy()->endOfMonth()],
        };

        $query = Transaction::query()
            ->where('shop_id', $this->shopId)
            ->whereBetween('recorded_at', [$start, $end])
            ->with(['category', 'fromAccount', 'toAccount']);

        // ---------- 條件篩選 ----------
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

        // 關鍵字搜索：同時支援 備註 (memo) 與 類別名稱 (category->name)
        if (!empty($this->search)) {
            $searchTerm = '%' . $this->search . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('memo', 'like', $searchTerm)
                  ->orWhereHas('category', function ($catQuery) use ($searchTerm) {
                      $catQuery->where('name', 'like', $searchTerm);
                  });
            });
        }

        // ---------- 期間交易明細 ----------
        $periodTransactions = (clone $query)->orderBy('recorded_at', 'desc')->get();

        // 預先載入匯率表（避免 N+1）
        $currencyService = app(CurrencyService::class);
        $baseCode        = $currencyService->getBaseCurrencyCode();

        $totalIncome     = '0.0000';
        $totalExpense    = '0.0000';
        $needsConversion = !$this->searchAccountId;

        foreach ($periodTransactions as $tx) {
            $amount = (string) $tx->amount;

            $code = $tx->currency
                ?? $tx->fromAccount?->currency
                ?? $tx->toAccount?->currency
                ?? $baseCode;

            if ($needsConversion) {
                $currency = $currencyService->getCurrency($code);
                if (!$currency) {
                    continue;
                }
                $amountInBase = $currency->is_base
                    ? $amount
                    : $currencyService->convertToBase($amount, $code);
            } else {
                $amountInBase = $amount;
            }

            if ($tx->type === 'income') {
                $totalIncome = MoneyCalculator::add($totalIncome, $amountInBase, 4);
            } elseif ($tx->type === 'expense') {
                $totalExpense = MoneyCalculator::add($totalExpense, $amountInBase, 4);
            }
        }

        // ---------- 分組策略（依模式不同） ----------
        // year  : 按「月」分組（Y-m），避免 365 個日期群組
        // day/month : 按「日」分組（Y-m-d）
        $groupFormat = $this->dateMode === 'year' ? 'Y-m' : 'Y-m-d';
        $groupedTransactions = $periodTransactions->groupBy(function ($item) use ($groupFormat) {
            return Carbon::parse($item->recorded_at)->format($groupFormat);
        });

        // ---------- 基礎選單與貨幣資料 ----------
        $categories = Category::where('shop_id', $this->shopId)->get();
        $accounts   = FinancialAccount::where('shop_id', $this->shopId)->where('is_active', true)->get();

        $currenciesData = Currency::where('shop_id', $this->shopId)->get();
        $currencies = [];
        foreach ($currenciesData as $c) {
            $currencies[$c->code] = [
                'name'   => $c->name,
                'symbol' => $c->symbol,
            ];
        }

        return view('livewire.finance.transaction-index', [
            'groupedTransactions' => $groupedTransactions,
            'transactionCount'    => $periodTransactions->count(),
            'categories'          => $categories,
            'accounts'            => $accounts,
            'currencies'          => $currencies,

            // 三態顯示
            'dateDisplay'         => $dateDisplay,
            'isCurrent'           => $isCurrent,
            'dateMode'            => $this->dateMode,
            'groupFormat'         => $groupFormat,

            'totalIncome'         => number_format((float) $totalIncome, 2),
            'totalExpense'        => number_format((float) $totalExpense, 2),
            'baseSymbol'          => $this->baseCurrency?->symbol ?? 'NT$',
        ])->layout('components.layouts.app');
    }
}