<?php // app/Livewire/Finance/ReportStats.php

namespace App\Livewire\Finance;

use App\Models\Transaction;
use App\Models\Category;
use App\Models\Currency;
use App\Models\FinancialAccount;
use App\Traits\WithDateNavigation;
use Carbon\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class ReportStats extends Component
{
    use WithDateNavigation;

    public int $shopId = 1;

    // 第一層分頁：category, trend, asset, account  // ⬅️ 新增 account
    public string $tab1 = 'category';
    
    // 第二層分頁：expense, income, balance, composition, trend  // ⬅️ 新增 composition/trend
    public string $tab2 = 'expense';
    
    // 第三層分頁：year, month, day
    public string $tab3 = 'month';

    // 總資產趨勢相關屬性
    public string $assetTab = 'month';  // month 或 day
    public bool $showAssetTrend = false;

    public function mount()
    {
        $this->shopId = (int) session('current_shop_id', 1);
        $this->currentDate = now()->format('Y-m');
        $this->dateMode = 'month';
        $this->updateDateMode();
    }

    /**
     * 覆寫 Trait 鉤子：日期變動時觸發圖表更新
     */
    protected function onDateChanged()
    {
        $this->dispatch('refreshChart', $this->getChartData());
    }

    // ============================================================
    // 計算屬性：解析當前年份與月份 (修正年份模式)
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
        if ($this->dateMode === 'year') {
            // 年份模式下，為了查詢整年數據，月份不應被使用
            // 但為了相容性，回傳 1（實際查詢時會用 whereYear）
            return 1;
        }
        return (int) Carbon::parse($this->currentDate)->month;
    }

    // ============================================================
    // 事件監聽
    // ============================================================
    
    #[On('refresh-data')]
    public function onDataChanged()
    {
        $this->dispatch('refreshChart', $this->getChartData());
    }
    
    #[On('modal-closed')]
    public function onModalClosed()
    {
        $this->dispatch('refreshChart', $this->getChartData());
    }

    // ============================================================
    // 分頁切換邏輯
    // ============================================================

    public function updatedTab1($value)
    {
        // ⬅️ 修改：加入 account 分支，並清理 tab2 值域污染
        if ($value === 'category') {
            // 分類報表只接受 expense / income
            if (!in_array($this->tab2, ['expense', 'income'], true)) {
                $this->tab2 = 'expense';
            }
            if ($this->tab3 === 'day') $this->tab3 = 'month';
            $this->showAssetTrend = false;
        } elseif ($value === 'asset') {
            $this->showAssetTrend = true;
            $this->tab2 = 'balance';
        } elseif ($value === 'account') {
            // 賬戶報表只接受 composition / trend
            $this->showAssetTrend = false;
            if (!in_array($this->tab2, ['composition', 'trend'], true)) {
                $this->tab2 = 'composition';
            }
        } else {
            // trend 報表：expense / income / balance
            if (!in_array($this->tab2, ['expense', 'income', 'balance'], true)) {
                $this->tab2 = 'expense';
            }
            $this->showAssetTrend = false;
        }
        $this->updateDateMode();
        $this->dispatch('refreshChart', $this->getChartData());
    }

    public function updatedTab2($value)
    {
        $this->dispatch('refreshChart', $this->getChartData());
    }

    public function updatedTab3()
    {
        $this->updateDateMode();
        $this->dispatch('refreshChart', $this->getChartData());
    }

    public function updatedAssetTab($value)
    {
        $this->updateDateMode();
        $this->dispatch('refreshChart', $this->getChartData());
    }
    
    /**
     * 更新日期模式，並確保 currentDate 格式正確
     */
    private function updateDateMode()
    {
        $activeMode = $this->showAssetTrend ? $this->assetTab : $this->tab3;
        
        if ($activeMode === 'year') {
            $this->dateMode = 'year';
            // 將 currentDate 轉為 YYYY 格式
            if (strlen($this->currentDate) > 4) {
                $this->currentDate = substr($this->currentDate, 0, 4);
            }
        } else {
            $this->dateMode = 'month';
            // 若原本為 YYYY 格式，補回當前月份成 YYYY-MM 格式
            if (strlen($this->currentDate) === 4) {
                $this->currentDate = $this->currentDate . '-' . now()->format('m');
            }
            // 確保格式為 YYYY-MM
            if (strlen($this->currentDate) === 7 && strpos($this->currentDate, '-') !== false) {
                // 已經是 YYYY-MM 格式，不需要處理
            }
        }
    }

    // ============================================================
    // 點擊大類 → 開啟 Modal
    // ============================================================

    public function openCategoryTransactions(int $categoryId, string $categoryName)
    {
        $this->dispatch('open-transaction-list-modal', params: [
            'categoryId' => $categoryId,
            'type' => $this->tab2,
            'title' => $categoryName . ' - 交易明細',
            'dateMode' => $this->tab3 === 'year' ? 'year' : 'month',
            'baseDate' => $this->selectedYear . '-' . sprintf('%02d', $this->selectedMonth) . '-01',
        ]);
    }

    // ============================================================
    // 報表數據計算 (分類/趨勢/資產)
    // ============================================================

    #[Computed]
    public function categoryData()
    {
        $query = Transaction::query()
            ->where('shop_id', $this->shopId)
            ->where('type', $this->tab2);

        // 修正：根據 dateMode 決定查詢條件
        if ($this->dateMode === 'year') {
            $query->whereYear('recorded_at', $this->selectedYear);
        } else {
            $query->whereYear('recorded_at', $this->selectedYear)
                  ->whereMonth('recorded_at', $this->selectedMonth);
        }

        $transactions = $query->with('category.parent')->get();

        $total = '0.0000';
        $categorySummary = [];

        foreach ($transactions as $tx) {
            if (!$tx->category_id) continue;
            
            $mainCategory = $tx->category->parent_id ? $tx->category->parent : $tx->category;
            $catId = $mainCategory->id;
            $catName = $mainCategory->name;

            if (!isset($categorySummary[$catId])) {
                $categorySummary[$catId] = [
                    'id' => $catId,
                    'name' => $catName,
                    'amount' => '0.0000'
                ];
            }
            
            $total = bcadd($total, (string)$tx->amount, 4);
            $categorySummary[$catId]['amount'] = bcadd($categorySummary[$catId]['amount'], (string)$tx->amount, 4);
        }

        foreach ($categorySummary as $id => $data) {
            $percentage = bccomp($total, '0.0000', 4) > 0 
                ? bcdiv(bcmul($data['amount'], '100', 4), $total, 2) 
                : '0.00';
            
            $categorySummary[$id]['percentage'] = $percentage;
            $categorySummary[$id]['amount_display'] = number_format((float)$data['amount'], 2);
        }

        uasort($categorySummary, fn($a, $b) => bccomp($b['amount'], $a['amount'], 4));

        return [
            'total' => number_format((float)$total, 2),
            'list' => array_values($categorySummary)
        ];
    }

    #[Computed]
    public function trendData()
    {
        $list = [];
        
        // 修正：根據 dateMode 決定趨勢顯示的粒度
        if ($this->dateMode === 'year') {
            // 年份模式：顯示各月趨勢
            for ($m = 1; $m <= 12; $m++) {
                $list[$m] = ['label' => $m . '月', 'amount' => '0.0000'];
            }

            $transactions = Transaction::query()
                ->where('shop_id', $this->shopId)
                ->whereYear('recorded_at', $this->selectedYear)
                ->get(['type', 'amount', 'recorded_at']);

            foreach ($transactions as $tx) {
                $m = (int) Carbon::parse($tx->recorded_at)->format('n');
                $list[$m]['amount'] = $this->calculateTrendAmount($list[$m]['amount'], $tx);
            }
        } elseif ($this->tab3 === 'month') {
            // 月度模式：顯示各月趨勢
            for ($m = 1; $m <= 12; $m++) {
                $list[$m] = ['label' => $m . '月', 'amount' => '0.0000'];
            }

            $transactions = Transaction::query()
                ->where('shop_id', $this->shopId)
                ->whereYear('recorded_at', $this->selectedYear)
                ->get(['type', 'amount', 'recorded_at']);

            foreach ($transactions as $tx) {
                $m = (int) Carbon::parse($tx->recorded_at)->format('n');
                $list[$m]['amount'] = $this->calculateTrendAmount($list[$m]['amount'], $tx);
            }
        } else {
            // 日模式：顯示各日趨勢
            $daysInMonth = Carbon::create($this->selectedYear, $this->selectedMonth)->daysInMonth;
            for ($d = 1; $d <= $daysInMonth; $d++) {
                $list[$d] = ['label' => $d . '日', 'amount' => '0.0000'];
            }

            $transactions = Transaction::query()
                ->where('shop_id', $this->shopId)
                ->whereYear('recorded_at', $this->selectedYear)
                ->whereMonth('recorded_at', $this->selectedMonth)
                ->get(['type', 'amount', 'recorded_at']);

            foreach ($transactions as $tx) {
                $d = (int) Carbon::parse($tx->recorded_at)->format('j');
                $list[$d]['amount'] = $this->calculateTrendAmount($list[$d]['amount'], $tx);
            }
        }

        return array_map(fn($item) => [
            'label' => $item['label'],
            'amount' => $item['amount'],
            'amount_display' => number_format((float)$item['amount'], 2)
        ], array_values($list));
    }

    private function calculateTrendAmount(string $currentAmount, $tx): string
    {
        $txAmount = (string) $tx->amount;
        if ($this->tab2 === 'balance') {
            if ($tx->type === 'income') return bcadd($currentAmount, $txAmount, 4);
            if ($tx->type === 'expense') return bcsub($currentAmount, $txAmount, 4);
            return $currentAmount;
        }
        
        return $tx->type === $this->tab2 ? bcadd($currentAmount, $txAmount, 4) : $currentAmount;
    }

    #[Computed]
    public function assetTrendData()
    {
        $result = [];
        $baseCurrency = $this->baseCurrency;
            
        if (!$baseCurrency) return [];

        $baseCode = $baseCurrency->code;
        $baseSymbol = $baseCurrency->symbol;

        if ($this->assetTab === 'month' || $this->dateMode === 'year') {
            // 月度資產趨勢
            for ($m = 1; $m <= 12; $m++) {
                $endOfMonth = Carbon::create($this->selectedYear, $m)->endOfMonth();
                $result[] = [
                    'label' => $m . '月',
                    'amount' => $this->calculateTotalAssets($endOfMonth, $baseCode),
                    'symbol' => $baseSymbol,
                ];
            }
        } else {
            // 每日資產趨勢
            $daysInMonth = Carbon::create($this->selectedYear, $this->selectedMonth)->daysInMonth;
            for ($d = 1; $d <= $daysInMonth; $d++) {
                $endOfDay = Carbon::create($this->selectedYear, $this->selectedMonth, $d)->endOfDay();
                $result[] = [
                    'label' => $d . '日',
                    'amount' => $this->calculateTotalAssets($endOfDay, $baseCode),
                    'symbol' => $baseSymbol,
                ];
            }
        }

        return $result;
    }

    private function calculateTotalAssets(Carbon $until, string $baseCode): string
    {
        $accounts = FinancialAccount::where('shop_id', $this->shopId)->where('is_active', true)->get();
        if ($accounts->isEmpty()) return '0.0000';

        $total = '0.0000';
        foreach ($accounts as $account) {
            $balance = $this->getAccountBalanceAt($account->id, $until);
            if ($account->currency !== $baseCode) {
                $rate = $this->getExchangeRate($account->currency, $baseCode);
                $balance = bcmul($balance, (string)$rate, 4);
            }
            $total = bcadd($total, $balance, 4);
        }

        return $total;
    }

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

    private function getExchangeRate(string $from, string $to): float
    {
        if ($from === $to) return 1.0;

        $fromCurrency = Currency::where('shop_id', $this->shopId)->where('code', $from)->where('is_active', true)->first();
        $toCurrency = Currency::where('shop_id', $this->shopId)->where('code', $to)->where('is_active', true)->first();

        if (!$fromCurrency || !$toCurrency || $toCurrency->rate <= 0) {
            return 1.0;
        }

        return $fromCurrency->rate / $toCurrency->rate;
    }

    // ============================================================
    // ⬅️ 新增：賬戶報表相關計算
    // ============================================================

    /**
     * 根據當前 dateMode 與 tab3，取得期末快照日期
     */
    private function getSnapshotDate(): Carbon
    {
        if ($this->dateMode === 'year') {
            return Carbon::create($this->selectedYear, 12, 31)->endOfDay();
        }

        // month / day 模式：取當月最後一天（若有 selectedDay 再擴充）
        return Carbon::create($this->selectedYear, $this->selectedMonth)
            ->endOfMonth()->endOfDay();
    }

    /**
     * 為每個賬戶產生固定顏色（依 id 取樣）
     */
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
        $baseCurrency = $this->baseCurrency;
        if (!$baseCurrency) {
            return [
                'total' => '0.00',
                'list' => [],
                'others' => [],
                'symbol' => 'NT$',
                'snapshot_date' => null,
            ];
        }

        $baseCode = $baseCurrency->code;
        $until = $this->getSnapshotDate();

        $accounts = FinancialAccount::where('shop_id', $this->shopId)
            ->where('is_active', true)
            ->get();

        if ($accounts->isEmpty()) {
            return [
                'total' => '0.00',
                'list' => [],
                'others' => [],
                'symbol' => $baseCurrency->symbol ?? 'NT$',
                'snapshot_date' => $until->format('Y-m-d'),
            ];
        }

        $list = [];
        $total = '0.0000';

        foreach ($accounts as $account) {
            $balance = $this->getAccountBalanceAt($account->id, $until);

            if ($account->currency !== $baseCode) {
                $rate = $this->getExchangeRate($account->currency, $baseCode);
                $balance = bcmul($balance, (string)$rate, 4);
            }

            // 餘額為 0 的賬戶不顯示
            if (bccomp($balance, '0.0000', 4) === 0) {
                continue;
            }

            $list[] = [
                'id' => $account->id,
                'name' => $account->name,
                'currency' => $account->currency,
                'amount' => $balance,
            ];
            $total = bcadd($total, $balance, 4);
        }

        // 依金額降序
        usort($list, fn($a, $b) => bccomp($b['amount'], $a['amount'], 4));

        // 計算百分比 + Top N + 其它（佔比 < 3%）
        $threshold = 3.0;
        $mainList = [];
        $otherList = [];
        $otherTotal = '0.0000';

        foreach ($list as $item) {
            $percentage = bccomp($total, '0.0000', 4) > 0
                ? (float) bcdiv(bcmul($item['amount'], '100', 4), $total, 2)
                : 0.0;

            $item['percentage'] = number_format($percentage, 2);
            $item['amount_display'] = number_format((float)$item['amount'], 2);

            if ($percentage < $threshold) {
                $otherList[] = $item;
                $otherTotal = bcadd($otherTotal, $item['amount'], 4);
            } else {
                $mainList[] = $item;
            }
        }

        // 加入「其它」項目
        if (!empty($otherList)) {
            $otherPercentage = bccomp($total, '0.0000', 4) > 0
                ? (float) bcdiv(bcmul($otherTotal, '100', 4), $total, 2)
                : 0.0;

            $mainList[] = [
                'id' => null,
                'name' => '其它（' . count($otherList) . ' 個賬戶）',
                'currency' => $baseCode,
                'amount' => $otherTotal,
                'percentage' => number_format($otherPercentage, 2),
                'amount_display' => number_format((float)$otherTotal, 2),
                'children' => $otherList,
            ];
        }

        return [
            'total' => number_format((float)$total, 2),
            'list' => $mainList,
            'others' => $otherList,
            'symbol' => $baseCurrency->symbol ?? 'NT$',
            'snapshot_date' => $until->format('Y-m-d'),
        ];
    }

    #[Computed]
    public function accountTrendData()
    {
        $baseCurrency = $this->baseCurrency;
        if (!$baseCurrency) {
            return ['labels' => [], 'series' => [], 'symbol' => 'NT$'];
        }

        $baseCode = $baseCurrency->code;
        $baseSymbol = $baseCurrency->symbol;

        $accounts = FinancialAccount::where('shop_id', $this->shopId)
            ->where('is_active', true)
            ->get();

        if ($accounts->isEmpty()) {
            return ['labels' => [], 'series' => [], 'symbol' => $baseSymbol];
        }

        // 決定時間點
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

        // 計算每個賬戶的序列
        $series = [];
        foreach ($accounts as $account) {
            $values = [];
            $hasNonZero = false;

            foreach ($timePoints as $tp) {
                $balance = $this->getAccountBalanceAt($account->id, $tp);

                if ($account->currency !== $baseCode) {
                    $rate = $this->getExchangeRate($account->currency, $baseCode);
                    $balance = bcmul($balance, (string)$rate, 4);
                }

                $values[] = (float) round((float)$balance, 2);
                if (bccomp($balance, '0.0000', 4) !== 0) {
                    $hasNonZero = true;
                }
            }

            // 全為 0 的賬戶不顯示
            if (!$hasNonZero) {
                continue;
            }

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
            'symbol' => $baseSymbol,
        ];
    }

    // ============================================================
    // 圖表資料出口
    // ============================================================

    public function getChartData()
    {
        if ($this->showAssetTrend) {
            $data = $this->assetTrendData;
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
                'isSmallMultiples' => false, // ⬅️ 新增
            ];
        }

        if ($this->tab1 === 'category') {
            $data = $this->categoryData;
            return [
                'type' => 'pie',
                'labels' => array_values(collect($data['list'])->pluck('name')->all()),
                'values' => array_values(collect($data['list'])->map(fn($item) => (float)$item['amount'])->all()),
                'centerText' => $data['total'] ?? '0.00',
                'color' => null,
                'isAssetTrend' => false,
                'isSmallMultiples' => false, // ⬅️ 新增
            ];
        }

        // ⬅️ 新增：賬戶報表分支
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
                    'symbol' => $data['symbol'] ?? 'NT$',
                ];
            }

            // trend
            $data = $this->accountTrendData;
            return [
                'type' => 'smallMultiples',
                'labels' => $data['labels'] ?? [],
                'series' => $data['series'] ?? [],
                'color' => null,
                'centerText' => null,
                'isAssetTrend' => false,
                'isSmallMultiples' => true,
                'symbol' => $data['symbol'] ?? 'NT$',
            ];
        }

        // trend 報表（expense/income/balance）
        $data = $this->trendData;
        return [
            'type' => 'bar',
            'labels' => array_values(collect($data)->pluck('label')->all()),
            'values' => array_values(collect($data)->map(fn($item) => (float)$item['amount'])->all()),
            'color' => $this->tab2 === 'expense' ? '#f87171' : ($this->tab2 === 'income' ? '#34d399' : '#60a5fa'),
            'centerText' => null,
            'isAssetTrend' => false,
            'isSmallMultiples' => false, // ⬅️ 新增
        ];
    }

    #[Computed]
    public function baseCurrency()
    {
        return Currency::where('shop_id', $this->shopId)->where('is_base', true)->first();
    }

    // ============================================================
    // 渲染
    // ============================================================
    
    public function render()
    {
        $parsedDate = $this->parseCurrentDate();
        $isYearMode = ($this->dateMode === 'year');

        // 統一格式化顯示日期與判斷是否為當前時間
        if ($isYearMode) {
            $displayDate = $this->selectedYear . ' 年';
            $isCurrent = ($this->selectedYear === (int) now()->format('Y'));
        } else {
            $displayDate = $parsedDate->format('Y 年 m 月');
            $isCurrent = $parsedDate->isCurrentMonth();
        }

        return view('livewire.finance.report-stats', [
            'displayDate' => $displayDate,
            'isCurrent'   => $isCurrent,
            'dateMode'    => $this->dateMode,
            'chartData'   => $this->getChartData(),
            'selectedYear' => $this->selectedYear,
            'selectedMonth' => $this->selectedMonth,
            // ⬅️ 新增：僅在 account tab 才計算，避免無謂開銷
            'accountCompositionData' => $this->tab1 === 'account' ? $this->accountCompositionData : null,
            'accountTrendData'       => $this->tab1 === 'account' ? $this->accountTrendData : null,
        ])->layout('components.layouts.app');
    }
}