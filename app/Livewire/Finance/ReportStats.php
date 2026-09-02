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

    // 第一層分頁：category, trend, asset
    public string $tab1 = 'category';
    
    // 第二層分頁：expense, income, balance
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
        if ($value === 'category') {
            if ($this->tab2 === 'balance') $this->tab2 = 'expense';
            if ($this->tab3 === 'day') $this->tab3 = 'month';
            $this->showAssetTrend = false;
        } elseif ($value === 'asset') {
            $this->showAssetTrend = true;
            $this->tab2 = 'balance';
        } else {
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

    public function getChartData()
    {
        if ($this->showAssetTrend) {
            $data = $this->assetTrendData;
            return [
                'type' => 'line',
                'labels' => array_values(collect($data)->pluck('label')->all()),
                'values' => array_values(collect($data)->map(fn($item) => (float) round((float)$item['amount'], 2))->all()),
                'color' => '#818cf8',
                'centerText' => null,
                'unit' => reset($data)['symbol'] ?? 'NT$',
                'isAssetTrend' => true,
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
            ];
        }

        $data = $this->trendData;
        return [
            'type' => 'bar',
            'labels' => array_values(collect($data)->pluck('label')->all()),
            'values' => array_values(collect($data)->map(fn($item) => (float)$item['amount'])->all()),
            'color' => $this->tab2 === 'expense' ? '#f87171' : ($this->tab2 === 'income' ? '#34d399' : '#60a5fa'),
            'centerText' => null,
            'isAssetTrend' => false,
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
        ])->layout('components.layouts.app');
    }
}