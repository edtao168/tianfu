<?php //app\Livewire\Finance\ReportStats.php

namespace App\Livewire\Finance;

use App\Models\Transaction;
use App\Models\Category;
use App\Models\Currency;
use App\Models\FinancialAccount;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;

class ReportStats extends Component
{
    public int $shopId = 1;

    // 第一層分頁：category, trend, asset
    public string $tab1 = 'category';
    
    // 第二層分頁：expense, income, balance
    public string $tab2 = 'expense';
    
    // 第三層分頁：year, month, day
    public string $tab3 = 'month';

    public int $selectedYear;
    public int $selectedMonth;

    // ============================================================
    // 總資產趨勢相關屬性
    // ============================================================
    public string $assetTab = 'month';  // month 或 day
    public bool $showAssetTrend = false;

    public function mount()
    {
        $this->selectedYear = (int) date('Y');
        $this->selectedMonth = (int) date('n');
        
        // 從 session 或 user 取得 shopId
        $this->shopId = (int) session('current_shop_id', 1);
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
            $this->tab2 = 'balance';  // 資產趨勢固定用 balance
        } else {
            $this->showAssetTrend = false;
        }
        $this->dispatch('refreshChart', $this->getChartData());
    }

    public function updatedTab2()
    {
        $this->dispatch('refreshChart', $this->getChartData());
    }

    public function updatedTab3()
    {
        $this->dispatch('refreshChart', $this->getChartData());
    }

    public function updatedAssetTab()
    {
        $this->dispatch('refreshChart', $this->getChartData());
    }

    public function changeDate()
    {
        $this->dispatch('refreshChart', $this->getChartData());
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
            'baseDate' => $this->selectedYear . '-' . ($this->selectedMonth ?? 1) . '-01',
        ]);
    }

    // ============================================================
    // 分類報表數據
    // ============================================================

    public function getCategoryDataProperty()
    {
        $query = Transaction::query()
            ->where('shop_id', $this->shopId)
            ->where('type', $this->tab2);

        if ($this->tab3 === 'year') {
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
            if (bccomp($total, '0.0000', 4) > 0) {
                $percentage = bcdiv(bcmul($data['amount'], '100', 4), $total, 2);
            } else {
                $percentage = '0.00';
            }
            $categorySummary[$id]['percentage'] = $percentage;
            $categorySummary[$id]['amount_display'] = number_format((float)$data['amount'], 2);
        }

        uasort($categorySummary, fn($a, $b) => bccomp($b['amount'], $a['amount'], 4));

        return [
            'total' => number_format((float)$total, 2),
            'list' => array_values($categorySummary)
        ];
    }

    // ============================================================
    // 趨勢報表數據
    // ============================================================

    public function getTrendDataProperty()
    {
        $list = [];
        if ($this->tab3 === 'month') {
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

        $result = [];
        foreach ($list as $key => $item) {
            $result[] = [
                'label' => $item['label'],
                'amount' => $item['amount'],
                'amount_display' => number_format((float)$item['amount'], 2)
            ];
        }

        return $result;
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

    // ============================================================
    // 總資產趨勢數據（BCMath 精確計算）
    // ============================================================

    public function getAssetTrendDataProperty()
    {
		$result = [];
		
		$baseCurrency = Currency::where('shop_id', $this->shopId)
			->where('is_base', true)
			->first();
			
		if (!$baseCurrency) {
			return [];
		}

		$baseCode = $baseCurrency->code;
		$baseSymbol = $baseCurrency->symbol;

		if ($this->assetTab === 'month') {
			for ($m = 1; $m <= 12; $m++) {
				$endOfMonth = Carbon::create($this->selectedYear, $m)->endOfMonth();
				// ✅ 正確：用 $result[] 自動遞增索引 (0, 1, 2...)，不要用 $result[$m]
				$result[] = [
					'label' => $m . '月',
					'amount' => $this->calculateTotalAssets($endOfMonth, $baseCode),
					'symbol' => $baseSymbol,
				];
			}
		} else {
			$daysInMonth = Carbon::create($this->selectedYear, $this->selectedMonth)->daysInMonth;
			for ($d = 1; $d <= $daysInMonth; $d++) {
				$endOfDay = Carbon::create($this->selectedYear, $this->selectedMonth, $d)->endOfDay();
				// ✅ 正確：用 $result[]
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
        $accounts = FinancialAccount::where('shop_id', $this->shopId)
            ->where('is_active', true)
            ->get();

        if ($accounts->isEmpty()) {
            return '0.0000';
        }

        $total = '0.0000';

        foreach ($accounts as $account) {
            $balance = $this->getAccountBalanceAt($account->id, $until);
            
            if ($account->currency !== $baseCode) {
                $rate = $this->getExchangeRate($account->currency, $baseCode);
                $balance = bcmul($balance, $rate, 4);
            }
            
            $total = bcadd($total, $balance, 4);
        }

        return $total;
    }

    private function getAccountBalanceAt(int $accountId, Carbon $until): string
    {
        $account = FinancialAccount::where('shop_id', $this->shopId)->find($accountId);
        if (!$account) {
            return '0.0000';
        }

        $initialBalance = (string) ($account->balance ?? '0.0000');
        
        $transactions = Transaction::where('shop_id', $this->shopId)
            ->where(function ($query) use ($accountId) {
                $query->where('from_account_id', $accountId)
                      ->orWhere('to_account_id', $accountId);
            })
            ->where('recorded_at', '<=', $until)
            ->get();

        $netChange = '0.0000';
        foreach ($transactions as $tx) {
            $txAmount = (string) $tx->amount;
            if ($tx->from_account_id == $accountId) {
                $netChange = bcsub($netChange, $txAmount, 4);
            }
            if ($tx->to_account_id == $accountId) {
                $netChange = bcadd($netChange, $txAmount, 4);
            }
        }

        return bcadd($initialBalance, $netChange, 4);
    }

    private function getExchangeRate(string $from, string $to): string
    {
        if ($from === $to) {
            return '1.0000';
        }

        $fromCurrency = Currency::where('shop_id', $this->shopId)
            ->where('code', $from)
            ->where('is_active', true)
            ->first();

        $toCurrency = Currency::where('shop_id', $this->shopId)
            ->where('code', $to)
            ->where('is_active', true)
            ->first();

        if (!$fromCurrency || !$toCurrency || bccomp((string)$toCurrency->rate, '0.0000', 4) === 0) {
            return '1.0000';
        }

        return bcdiv((string)$fromCurrency->rate, (string)$toCurrency->rate, 4);
    }

    // ============================================================
    // 圖表數據與前端進階視覺樣式設定
    // ============================================================

    public function getChartData()
    {
		// 總資產趨勢模式 (折線圖)
		if ($this->showAssetTrend) {
			$data = $this->assetTrendData;

			// ✅ 強制拉出純索引陣列，確保長度與數值完全一對一 (只有 12 個)
			$labels = array_values(collect($data)->pluck('label')->all());
			$values = array_values(collect($data)->map(fn($item) => (float) round((float)$item['amount'], 2))->all());

			return [
				'type' => 'line',
				'labels' => $labels,
				'values' => $values,
				'color' => '#818cf8',
				'centerText' => null,
				'unit' => reset($data)['symbol'] ?? 'NT$',
				'isAssetTrend' => true,
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
		];
	}

    // ============================================================
    // 獲取幣別資訊（供 Blade 使用）
    // ============================================================

    public function getBaseCurrencyProperty()
    {
        return Currency::where('shop_id', $this->shopId)
            ->where('is_base', true)
            ->first();
    }

    // ============================================================
    // Render
    // ============================================================

    public function render()
    {
        return view('livewire.finance.report-stats', [
            'chartData' => $this->getChartData(),
            'baseCurrency' => $this->baseCurrency,
        ])->layout('components.layouts.app');
    }
}