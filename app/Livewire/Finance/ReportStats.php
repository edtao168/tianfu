<?php // app/Livewire/Finance/ReportStats.php

namespace App\Livewire\Finance;

use App\Models\Transaction;
use App\Models\Currency;
use App\Models\FinancialAccount;
use App\Services\CurrencyService;
use App\Traits\WithDateNavigation;
use Carbon\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class ReportStats extends Component
{
    use WithDateNavigation;

    public int $shopId = 1;

    public string $tab1 = 'category';
    public string $tab2 = 'expense';
    public string $tab3 = 'month';

    public string $assetTab = 'month';
    public bool $showAssetTrend = false;

    /**
     * 幣別篩選：'all' = 合併（本位幣換算）；其他 = 單一幣別原幣不換算
     */
    public string $currencyFilter = 'all';

    public function mount()
    {
        $this->shopId = (int) session('current_shop_id', 1);
        $this->currentDate = now()->format('Y-m');
        $this->dateMode = 'month';
        $this->updateDateMode();
    }

    protected function onDateChanged()
    {
        $this->dispatch('refreshChart', $this->getChartData());
    }

    // ============================================================
    // 事件監聽
    // ============================================================

    #[On('refresh-data')]
    #[On('modal-closed')]
    public function onDataChanged()
    {
        $this->dispatch('refreshChart', $this->getChartData());
    }

    // ============================================================
    // 分頁切換
    // ============================================================

    public function updatedTab1($value)
    {
        if ($value === 'category') {
            if (!in_array($this->tab2, ['expense', 'income'], true)) {
                $this->tab2 = 'expense';
            }
            if ($this->tab3 === 'day') $this->tab3 = 'month';
            $this->showAssetTrend = false;
        } elseif ($value === 'asset') {
            $this->showAssetTrend = true;
            $this->tab2 = 'balance';
            $this->currencyFilter = 'all';   // 資產趨勢強制合併
        } elseif ($value === 'account') {
            $this->showAssetTrend = false;
            if (!in_array($this->tab2, ['composition', 'trend'], true)) {
                $this->tab2 = 'composition';
            }
            // ⬅️ 賬戶報表預設年度
            $this->tab3 = 'year';
        } else {
            if (!in_array($this->tab2, ['expense', 'income', 'balance'], true)) {
                $this->tab2 = 'expense';
            }
            $this->showAssetTrend = false;
        }

        $this->updateDateMode();
        $this->dispatch('refreshChart', $this->getChartData());
    }

    public function updatedTab2()
    {
        $this->dispatch('refreshChart', $this->getChartData());
    }

    public function updatedTab3()
    {
        $this->updateDateMode();
        $this->dispatch('refreshChart', $this->getChartData());
    }

    public function updatedAssetTab()
    {
        $this->updateDateMode();
        $this->dispatch('refreshChart', $this->getChartData());
    }

    public function updatedCurrencyFilter()
    {
        $this->dispatch('refreshChart', $this->getChartData());
    }

    private function updateDateMode()
    {
        $activeMode = $this->showAssetTrend ? $this->assetTab : $this->tab3;

        if ($activeMode === 'year') {
            $this->dateMode = 'year';
            if (strlen($this->currentDate) > 4) {
                $this->currentDate = substr($this->currentDate, 0, 4);
            }
        } else {
            $this->dateMode = 'month';
            if (strlen($this->currentDate) === 4) {
                $this->currentDate = $this->currentDate . '-' . now()->format('m');
            }
        }
    }

    // ============================================================
    // Modal
    // ============================================================

    public function openCategoryTransactions(int $categoryId, string $categoryName)
    {
        $this->dispatch('open-transaction-list-modal', params: [
            'categoryId' => $categoryId,
            'type' => $this->tab2,
            'title' => $categoryName . ' - 交易明細',
            'dateMode' => $this->tab3 === 'year' ? 'year' : 'month',
            'baseDate' => $this->selectedYear . '-' . sprintf('%02d', $this->selectedMonth) . '-01',
            'currency' => $this->currencyFilter,
        ]);
    }

    // ============================================================
    // 計算屬性
    // ============================================================

    #[Computed]
    public function selectedYear(): int
    {
        if ($this->dateMode === 'year') {
            return (int) $this->currentDate;
        }
        return (int) Carbon::parse($this->currentDate)->year;
    }

    #[Computed]
    public function selectedMonth(): int
    {
        if ($this->dateMode === 'year') return 1;
        return (int) Carbon::parse($this->currentDate)->month;
    }

    #[Computed]
    public function baseCurrency(): ?Currency
    {
        return app(CurrencyService::class)->getBaseCurrency();
    }

    #[Computed]
    public function activeCurrency(): ?Currency
    {
        $service = app(CurrencyService::class);
        if ($this->currencyFilter === 'all') {
            return $this->baseCurrency;
        }
        return $service->findByCode($this->currencyFilter);
    }

    #[Computed]
    public function availableCurrencies(): array
    {
        return app(CurrencyService::class)->getAllCurrencies()->values()->all();
    }

    /**
     * 目前是否為「合併模式」（= 需要匯率換算）
     */
    private function isConsolidated(): bool
    {
        return $this->currencyFilter === 'all';
    }
	
	/**
     * 對 Query 套用幣別篩選（透過 from/to 賬戶）
     *
     * 中文名稱：幣別篩選器
     * 用途：因 transactions 表無 currency 欄位，需透過關聯賬戶的 currency 篩選
     */
    private function applyCurrencyFilter($query): void
    {
        $code = $this->currencyFilter;
        $query->where(function ($q) use ($code) {
            $q->whereHas('fromAccount', fn($qq) => $qq->where('currency', $code))
              ->orWhereHas('toAccount', fn($qq) => $qq->where('currency', $code));
        });
    }

    // ============================================================
    // 分類報表
    // ============================================================

    #[Computed]
    public function categoryData()
    {
        $service = app(CurrencyService::class);
        $baseCode = $this->baseCurrency->code ?? 'TWD';

        $query = Transaction::query()
            ->where('shop_id', $this->shopId)
            ->where('type', $this->tab2);

        // 分幣別模式：只查該幣別，不合併
        if (!$this->isConsolidated()) {
            $this->applyCurrencyFilter($query);
        }

        if ($this->dateMode === 'year') {
            $query->whereYear('recorded_at', $this->selectedYear);
        } else {
            $query->whereYear('recorded_at', $this->selectedYear)
                  ->whereMonth('recorded_at', $this->selectedMonth);
        }

            $transactions = $query->with(['category.parent', 'fromAccount', 'toAccount'])->get();

        $total = '0.0000';
        $categorySummary = [];

        foreach ($transactions as $tx) {
            if (!$tx->category_id) continue;

            $txAmount = (string) $tx->amount;

            // 只在合併模式換算
            if ($this->isConsolidated() && ($tx->currency ?? $baseCode) !== $baseCode) {
                $txAmount = $service->convertToBase($txAmount, $tx->currency);
            }

            $mainCategory = $tx->category->parent_id ? $tx->category->parent : $tx->category;
            $catId = $mainCategory->id;
            $catName = $mainCategory->name;

            if (!isset($categorySummary[$catId])) {
                $categorySummary[$catId] = [
                    'id' => $catId,
                    'name' => $catName,
                    'amount' => '0.0000',
                ];
            }

            $total = bcadd($total, $txAmount, 4);
            $categorySummary[$catId]['amount'] = bcadd($categorySummary[$catId]['amount'], $txAmount, 4);
        }

        $activeCurrency = $this->activeCurrency;

        foreach ($categorySummary as $id => $data) {
            $percentage = bccomp($total, '0.0000', 4) > 0
                ? bcdiv(bcmul($data['amount'], '100', 4), $total, 2)
                : '0.00';

            $categorySummary[$id]['percentage'] = $percentage;
            $categorySummary[$id]['amount_display'] = $service->format($data['amount'], $activeCurrency);
        }

        uasort($categorySummary, fn($a, $b) => bccomp($b['amount'], $a['amount'], 4));

        return [
            'total' => $service->format($total, $activeCurrency),
            'total_raw' => $total,
            'list' => array_values($categorySummary),
        ];
    }

    // ============================================================
    // 收支趨勢
    // ============================================================

    #[Computed]
    public function trendData()
    {
        $service = app(CurrencyService::class);
        $baseCode = $this->baseCurrency->code ?? 'TWD';
        $list = [];

        if ($this->dateMode === 'year') {
            for ($m = 1; $m <= 12; $m++) {
                $list[$m] = ['label' => $m . '月', 'amount' => '0.0000'];
            }

            $query = Transaction::query()
                ->where('shop_id', $this->shopId)
                ->whereYear('recorded_at', $this->selectedYear);

            if (!$this->isConsolidated()) {
                $this->applyCurrencyFilter($query);
            }

            foreach ($query->with(['fromAccount', 'toAccount'])->get(['id', 'type', 'amount', 'recorded_at', 'from_account_id', 'to_account_id']) as $tx) {
                $m = (int) Carbon::parse($tx->recorded_at)->format('n');
                $list[$m]['amount'] = $this->calculateTrendAmount($list[$m]['amount'], $tx, $baseCode, $service);
            }
        } elseif ($this->tab3 === 'month') {
            for ($m = 1; $m <= 12; $m++) {
                $list[$m] = ['label' => $m . '月', 'amount' => '0.0000'];
            }

            $query = Transaction::query()
                ->where('shop_id', $this->shopId)
                ->whereYear('recorded_at', $this->selectedYear);

            if (!$this->isConsolidated()) {
                $this->applyCurrencyFilter($query);
            }

            foreach ($query->with(['fromAccount', 'toAccount'])->get(['id', 'type', 'amount', 'recorded_at', 'from_account_id', 'to_account_id']) as $tx) {
                $m = (int) Carbon::parse($tx->recorded_at)->format('n');
                $list[$m]['amount'] = $this->calculateTrendAmount($list[$m]['amount'], $tx, $baseCode, $service);
            }
        } else {
            $daysInMonth = Carbon::create($this->selectedYear, $this->selectedMonth)->daysInMonth;
            for ($d = 1; $d <= $daysInMonth; $d++) {
                $list[$d] = ['label' => $d . '日', 'amount' => '0.0000'];
            }

            $query = Transaction::query()
                ->where('shop_id', $this->shopId)
                ->whereYear('recorded_at', $this->selectedYear)
                ->whereMonth('recorded_at', $this->selectedMonth);

            if (!$this->isConsolidated()) {
                 $this->applyCurrencyFilter($query);
            }

            foreach ($query->with(['fromAccount', 'toAccount'])->get(['id', 'type', 'amount', 'recorded_at', 'from_account_id', 'to_account_id']) as $tx) {
                $d = (int) Carbon::parse($tx->recorded_at)->format('j');
                $list[$d]['amount'] = $this->calculateTrendAmount($list[$d]['amount'], $tx, $baseCode, $service);
            }
        }

        $activeCurrency = $this->activeCurrency;

        return array_map(fn($item) => [
            'label' => $item['label'],
            'amount' => $item['amount'],
            'amount_display' => $service->format($item['amount'], $activeCurrency),
        ], array_values($list));
    }

    private function calculateTrendAmount(
        string $currentAmount,
        $tx,
        string $baseCode,
        CurrencyService $service
    ): string {
        $txAmount = (string) $tx->amount;

        if ($this->isConsolidated() && ($tx->currency ?? $baseCode) !== $baseCode) {
            $txAmount = $service->convertToBase($txAmount, $tx->currency);
        }

        if ($this->tab2 === 'balance') {
            if ($tx->type === 'income') return bcadd($currentAmount, $txAmount, 4);
            if ($tx->type === 'expense') return bcsub($currentAmount, $txAmount, 4);
            return $currentAmount;
        }

        return $tx->type === $this->tab2 ? bcadd($currentAmount, $txAmount, 4) : $currentAmount;
    }

    // ============================================================
    // 總資產趨勢（合併單線 / 分幣別多線）
    // ============================================================

    #[Computed]
    public function assetTrendData()
    {
        $service = app(CurrencyService::class);
        $baseCurrency = $this->baseCurrency;
        if (!$baseCurrency) return [];

        $baseCode = $baseCurrency->code;
        $baseSymbol = $baseCurrency->symbol;

        $timePoints = [];
        if ($this->assetTab === 'month' || $this->dateMode === 'year') {
            for ($m = 1; $m <= 12; $m++) {
                $timePoints[] = Carbon::create($this->selectedYear, $m)->endOfMonth();
            }
        } else {
            $daysInMonth = Carbon::create($this->selectedYear, $this->selectedMonth)->daysInMonth;
            for ($d = 1; $d <= $daysInMonth; $d++) {
                $timePoints[] = Carbon::create($this->selectedYear, $this->selectedMonth, $d)->endOfDay();
            }
        }

        // ---------- 分幣別多線模式 ----------
        if (!$this->isConsolidated()) {
            $accounts = FinancialAccount::where('shop_id', $this->shopId)
                ->where('is_active', true)
                ->where('currency', $this->currencyFilter)
                ->get();

            $labels = [];
            foreach ($timePoints as $tp) {
                $labels[] = $this->assetTab === 'day'
                    ? $tp->format('j') . '日'
                    : $tp->format('n') . '月';
            }

            $series = [];
            foreach ($accounts as $account) {
                $values = [];
                $hasNonZero = false;
                foreach ($timePoints as $tp) {
                    $balance = $this->getAccountBalanceAt($account->id, $tp);
                    $values[] = (float) round((float) $balance, 2);
                    if (bccomp($balance, '0.0000', 4) !== 0) $hasNonZero = true;
                }
                if (!$hasNonZero) continue;

                $series[] = [
                    'id' => $account->id,
                    'name' => $account->name,
                    'currency' => $account->currency,
                    'values' => $values,
                    'color' => $this->getAccountColor($account->id),
                ];
            }

            // 分幣別下的各時間點「單幣別總額」
            $totals = [];
            foreach ($timePoints as $i => $tp) {
                $sum = '0.0000';
                foreach ($accounts as $account) {
                    $sum = bcadd($sum, $this->getAccountBalanceAt($account->id, $tp), 4);
                }
                $totals[] = (float) round((float) $sum, 2);
            }

            $activeCurrency = $this->activeCurrency;

            return [
                'mode' => 'multi',
                'labels' => $labels,
                'series' => $series,
                'symbol' => $activeCurrency->symbol ?? $baseSymbol,
                'table' => array_map(fn($i) => [
                    'label' => $labels[$i],
                    'amount' => $totals[$i],
                    'amount_display' => $service->format((string) $totals[$i], $activeCurrency),
                    'symbol' => $activeCurrency->symbol ?? $baseSymbol,
                ], array_keys($totals)),
            ];
        }

        // ---------- 合併模式 ----------
        $accounts = FinancialAccount::where('shop_id', $this->shopId)
            ->where('is_active', true)->get();

        $result = [];
        foreach ($timePoints as $tp) {
            $label = $this->assetTab === 'day'
                ? $tp->format('j') . '日'
                : $tp->format('n') . '月';

            $total = '0.0000';
            foreach ($accounts as $account) {
                $balance = $this->getAccountBalanceAt($account->id, $tp);
                if ($account->currency !== $baseCode) {
                    $balance = $service->convertToBase($balance, $account->currency);
                }
                $total = bcadd($total, $balance, 4);
            }

            $result[] = [
                'label' => $label,
                'amount' => $total,
                'symbol' => $baseSymbol,
            ];
        }

        return $result;
    }

    /**
     * 交易效應 vs 匯率效應分解（僅合併模式）
     */
    #[Computed]
    public function assetEffectBreakdown()
    {
        if (!$this->isConsolidated()) return null;

        $service = app(CurrencyService::class);
        $baseCurrency = $this->baseCurrency;
        if (!$baseCurrency) return null;

        $baseCode = $baseCurrency->code;

        if ($this->dateMode === 'year') {
            $start = Carbon::create($this->selectedYear, 1, 1)->startOfDay();
            $end = Carbon::create($this->selectedYear, 12, 31)->endOfDay();
        } else {
            $start = Carbon::create($this->selectedYear, $this->selectedMonth, 1)->startOfDay();
            $end = Carbon::create($this->selectedYear, $this->selectedMonth)->endOfMonth()->endOfDay();
        }

        $accounts = FinancialAccount::where('shop_id', $this->shopId)
            ->where('is_active', true)->get();

        $startTotal = '0.0000';
        foreach ($accounts as $account) {
            $bal = $this->getAccountBalanceAt($account->id, $start);
            if ($account->currency !== $baseCode) {
                $bal = $service->convertToBase($bal, $account->currency);
            }
            $startTotal = bcadd($startTotal, $bal, 4);
        }

        $endTotal = '0.0000';
        foreach ($accounts as $account) {
            $bal = $this->getAccountBalanceAt($account->id, $end);
            if ($account->currency !== $baseCode) {
                $bal = $service->convertToBase($bal, $account->currency);
            }
            $endTotal = bcadd($endTotal, $bal, 4);
        }

        $transactionEffect = '0.0000';
        $txs = Transaction::where('shop_id', $this->shopId)
            ->whereBetween('recorded_at', [$start, $end])
            ->with(['fromAccount', 'toAccount'])
            ->get();

        foreach ($txs as $tx) {
            $txAmount = (string) $tx->amount;
            $txCurrency = $tx->currency;
            if ($txCurrency !== $baseCode) {
                $txAmount = $service->convertToBase($txAmount, $txCurrency);
            }
            if ($tx->type === 'income') $transactionEffect = bcadd($transactionEffect, $txAmount, 4);
            if ($tx->type === 'expense') $transactionEffect = bcsub($transactionEffect, $txAmount, 4);
        }

        $totalChange = bcsub($endTotal, $startTotal, 4);
        $fxEffect = bcsub($totalChange, $transactionEffect, 4);

        return [
            'start_total' => $service->format($startTotal, $baseCurrency),
            'end_total' => $service->format($endTotal, $baseCurrency),
            'total_change' => $service->format($totalChange, $baseCurrency),
            'total_change_raw' => (float) $totalChange,
            'transaction_effect' => $service->format($transactionEffect, $baseCurrency),
            'transaction_effect_raw' => (float) $transactionEffect,
            'fx_effect' => $service->format($fxEffect, $baseCurrency),
            'fx_effect_raw' => (float) $fxEffect,
            'symbol' => $baseCurrency->symbol,
        ];
    }

    // ============================================================
    // 賬戶報表
    // ============================================================

    private function getSnapshotDate(): Carbon
    {
        if ($this->dateMode === 'year') {
            return Carbon::create($this->selectedYear, 12, 31)->endOfDay();
        }
        return Carbon::create($this->selectedYear, $this->selectedMonth)
            ->endOfMonth()->endOfDay();
    }

    private function getAccountColor(int $accountId): string
    {
        $palette = [
            '#818cf8', '#f87171', '#34d399', '#fbbf24',
            '#a78bfa', '#60a5fa', '#fb923c', '#4ade80',
            '#f472b6', '#22d3ee',
        ];
        return $palette[$accountId % count($palette)];
    }

    #[Computed]
    public function accountCompositionData()
    {
        $service = app(CurrencyService::class);
        $baseCurrency = $this->baseCurrency;

        if (!$baseCurrency) {
            return ['total' => '0.00', 'list' => [], 'others' => [], 'symbol' => 'NT$', 'snapshot_date' => null, 'is_consolidated' => true];
        }

        $baseCode = $baseCurrency->code;
        $until = $this->getSnapshotDate();

        $query = FinancialAccount::where('shop_id', $this->shopId)->where('is_active', true);
        if (!$this->isConsolidated()) {
            $query->where('currency', $this->currencyFilter);
        }
        $accounts = $query->get();

        $activeCurrency = $this->activeCurrency;

        if ($accounts->isEmpty()) {
            return [
                'total' => '0.00',
                'list' => [],
                'others' => [],
                'symbol' => $activeCurrency->symbol ?? 'NT$',
                'snapshot_date' => $until->format('Y-m-d'),
                'is_consolidated' => $this->isConsolidated(),
            ];
        }

        $list = [];
        $total = '0.0000';

        foreach ($accounts as $account) {
            $balance = $this->getAccountBalanceAt($account->id, $until);

            if ($this->isConsolidated() && $account->currency !== $baseCode) {
                $balance = $service->convertToBase($balance, $account->currency);
            }

            if (bccomp($balance, '0.0000', 4) === 0) continue;

            $list[] = [
                'id' => $account->id,
                'name' => $account->name,
                'currency' => $account->currency,
                'amount' => $balance,
            ];
            $total = bcadd($total, $balance, 4);
        }

        usort($list, fn($a, $b) => bccomp($b['amount'], $a['amount'], 4));

        $threshold = 3.0;
        $mainList = [];
        $otherList = [];
        $otherTotal = '0.0000';

        foreach ($list as $item) {
            $percentage = bccomp($total, '0.0000', 4) > 0
                ? (float) bcdiv(bcmul($item['amount'], '100', 4), $total, 2)
                : 0.0;

            $item['percentage'] = number_format($percentage, 2);
            $item['amount_display'] = $service->format($item['amount'], $activeCurrency);

            if ($percentage < $threshold) {
                $otherList[] = $item;
                $otherTotal = bcadd($otherTotal, $item['amount'], 4);
            } else {
                $mainList[] = $item;
            }
        }

        if (!empty($otherList)) {
            $otherPercentage = bccomp($total, '0.0000', 4) > 0
                ? (float) bcdiv(bcmul($otherTotal, '100', 4), $total, 2)
                : 0.0;

            $mainList[] = [
                'id' => null,
                'name' => '其它（' . count($otherList) . ' 個賬戶）',
                'currency' => $activeCurrency->code ?? $baseCode,
                'amount' => $otherTotal,
                'percentage' => number_format($otherPercentage, 2),
                'amount_display' => $service->format($otherTotal, $activeCurrency),
                'children' => $otherList,
            ];
        }

        return [
            'total' => $service->format($total, $activeCurrency),
            'total_raw' => $total,
            'list' => $mainList,
            'others' => $otherList,
            'symbol' => $activeCurrency->symbol ?? 'NT$',
            'snapshot_date' => $until->format('Y-m-d'),
            'is_consolidated' => $this->isConsolidated(),
        ];
    }

    #[Computed]
    public function accountTrendData()
    {
        $service = app(CurrencyService::class);
        $baseCurrency = $this->baseCurrency;

        if (!$baseCurrency) {
            return ['labels' => [], 'series' => [], 'symbol' => 'NT$'];
        }

        $baseCode = $baseCurrency->code;

        $query = FinancialAccount::where('shop_id', $this->shopId)->where('is_active', true);
        if (!$this->isConsolidated()) {
            $query->where('currency', $this->currencyFilter);
        }
        $accounts = $query->get();

        $activeCurrency = $this->activeCurrency;

        if ($accounts->isEmpty()) {
            return ['labels' => [], 'series' => [], 'symbol' => $activeCurrency->symbol ?? 'NT$'];
        }

        $labels = [];
        $timePoints = [];

        if ($this->dateMode === 'year') {
            for ($m = 1; $m <= 12; $m++) {
                $labels[] = $m . '月';
                $timePoints[] = Carbon::create($this->selectedYear, $m)->endOfMonth()->endOfDay();
            }
        } else {
            $daysInMonth = Carbon::create($this->selectedYear, $this->selectedMonth)->daysInMonth;
            for ($d = 1; $d <= $daysInMonth; $d++) {
                $labels[] = $d . '日';
                $timePoints[] = Carbon::create($this->selectedYear, $this->selectedMonth, $d)->endOfDay();
            }
        }

        $series = [];
        foreach ($accounts as $account) {
            $values = [];
            $hasNonZero = false;

            foreach ($timePoints as $tp) {
                $balance = $this->getAccountBalanceAt($account->id, $tp);

                if ($this->isConsolidated() && $account->currency !== $baseCode) {
                    $balance = $service->convertToBase($balance, $account->currency);
                }

                $values[] = (float) round((float) $balance, 2);
                if (bccomp($balance, '0.0000', 4) !== 0) $hasNonZero = true;
            }

            if (!$hasNonZero) continue;

            $series[] = [
                'id' => $account->id,
                'name' => $account->name,
                'currency' => $account->currency,
                'values' => $values,
                'color' => $this->getAccountColor($account->id),
            ];
        }

        return [
            'labels' => $labels,
            'series' => $series,
            'symbol' => $activeCurrency->symbol ?? 'NT$',
        ];
    }

    // ============================================================
    // 圖表資料出口
    // ============================================================

    public function getChartData()
    {
        // 總資產趨勢
        if ($this->showAssetTrend) {
            $data = $this->assetTrendData;

            // 分幣別多線模式
            if (is_array($data) && isset($data['mode']) && $data['mode'] === 'multi') {
                return [
                    'type' => 'multiLine',
                    'labels' => $data['labels'] ?? [],
                    'series' => $data['series'] ?? [],
                    'symbol' => $data['symbol'] ?? 'NT$',
                    'color' => null,
                    'centerText' => null,
                    'isAssetTrend' => true,
                    'isSmallMultiples' => false,
                    'isMultiLine' => true,
                ];
            }

            // 合併模式（單線）
            $values = collect($data)->map(fn($item) => (float) $item['amount'])->all();
            $minValue = !empty($values) ? min($values) : 0;
            $suggestedMin = $minValue < 0 ? $minValue * 1.1 : $minValue * 0.9;

            return [
                'type' => 'line',
                'labels' => array_values(collect($data)->pluck('label')->all()),
                'values' => array_values(collect($data)->map(fn($item) => (float) round((float)$item['amount'], 2))->all()),
                'color' => '#818cf8',
                'centerText' => null,
                'unit' => reset($data)['symbol'] ?? 'NT$',
                'isAssetTrend' => true,
                'isSmallMultiples' => false,
                'isMultiLine' => false,
            ];
        }

        // 分類報表
        if ($this->tab1 === 'category') {
            $data = $this->categoryData;
            return [
                'type' => 'pie',
                'labels' => array_values(collect($data['list'])->pluck('name')->all()),
                'values' => array_values(collect($data['list'])->map(fn($item) => (float)$item['amount'])->all()),
                'centerText' => $data['total'] ?? '0.00',
                'color' => null,
                'isAssetTrend' => false,
                'isSmallMultiples' => false,
                'isMultiLine' => false,
            ];
        }

        // 賬戶報表
        if ($this->tab1 === 'account') {
            if ($this->tab2 === 'composition') {
                $data = $this->accountCompositionData;
                return [
                    'type' => 'pie',
                    'labels' => array_values(collect($data['list'])->pluck('name')->all()),
                    'values' => array_values(collect($data['list'])->map(fn($item) => (float)$item['amount'])->all()),
                    'centerText' => $data['total'] ?? '0.00',
                    'color' => null,
                    'isAssetTrend' => false,
                    'isSmallMultiples' => false,
                    'isMultiLine' => false,
                    'symbol' => $data['symbol'] ?? 'NT$',
                ];
            }

            $data = $this->accountTrendData;
            return [
                'type' => 'smallMultiples',
                'labels' => $data['labels'] ?? [],
                'series' => $data['series'] ?? [],
                'color' => null,
                'centerText' => null,
                'isAssetTrend' => false,
                'isSmallMultiples' => true,
                'isMultiLine' => false,
                'symbol' => $data['symbol'] ?? 'NT$',
            ];
        }

        // 收支趨勢
        $data = $this->trendData;
        return [
            'type' => 'bar',
            'labels' => array_values(collect($data)->pluck('label')->all()),
            'values' => array_values(collect($data)->map(fn($item) => (float)$item['amount'])->all()),
            'color' => $this->tab2 === 'expense' ? '#f87171' : ($this->tab2 === 'income' ? '#34d399' : '#60a5fa'),
            'centerText' => null,
            'isAssetTrend' => false,
            'isSmallMultiples' => false,
            'isMultiLine' => false,
        ];
    }

    // ============================================================
    // 渲染
    // ============================================================
    private function getAccountBalanceAt(int $accountId, Carbon $until): string
    {
        $account = FinancialAccount::where('shop_id', $this->shopId)->find($accountId);
        if (!$account) return '0.0000';

        $initialBalance = (string) ($account->balance ?? '0.0000');
        $transactions = Transaction::where('shop_id', $this->shopId)
            ->where(function ($query) use ($accountId) {
                $query->where('from_account_id', $accountId)->orWhere('to_account_id', $accountId);
            })
            ->where('recorded_at', '<=', $until)
            ->get();

        $netChange = '0.0000';
        foreach ($transactions as $tx) {
            $txAmount = (string) $tx->amount;
            if ($tx->from_account_id == $accountId) $netChange = bcsub($netChange, $txAmount, 4);
            if ($tx->to_account_id == $accountId) $netChange = bcadd($netChange, $txAmount, 4);
        }

        return bcadd($initialBalance, $netChange, 4);
    }
	
    public function render()
    {
        $parsedDate = $this->parseCurrentDate();
        $isYearMode = ($this->dateMode === 'year');

        if ($isYearMode) {
            $displayDate = $this->selectedYear . ' 年';
            $isCurrent = ($this->selectedYear === (int) now()->format('Y'));
        } else {
            $displayDate = $parsedDate->format('Y 年 m 月');
            $isCurrent = $parsedDate->isCurrentMonth();
        }

        return view('livewire.finance.report-stats', [
            'displayDate' => $displayDate,
            'isCurrent' => $isCurrent,
            'dateMode' => $this->dateMode,
            'chartData' => $this->getChartData(),
            'selectedYear' => $this->selectedYear,
            'selectedMonth' => $this->selectedMonth,
            'accountCompositionData' => $this->tab1 === 'account' ? $this->accountCompositionData : null,
            'accountTrendData' => $this->tab1 === 'account' ? $this->accountTrendData : null,
            'effectBreakdown' => $this->showAssetTrend ? $this->assetEffectBreakdown : null,
        ])->layout('components.layouts.app');
    }
}