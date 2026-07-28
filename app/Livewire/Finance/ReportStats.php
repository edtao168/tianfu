<?php
// app/Livewire/Finance/ReportStats.php

namespace App\Livewire\Finance;

use App\Models\Transaction;
use App\Models\Category;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;

class ReportStats extends Component
{
    public int $shopId = 1; // 多店預留

    // 第一層分頁：category(分類報表), trend(收支趨勢)
    public string $tab1 = 'category';
    
    // 第二層分頁：expense(支出), income(收入), balance(結餘 - 僅趨勢頁有)
    public string $tab2 = 'expense';
    
    // 第三層分頁：year(年), month(月), day(日)
    public string $tab3 = 'month';

    // 篩選年份與月份
    public int $selectedYear;
    public int $selectedMonth;

    #[On('refresh-data')]
    public function onDataChanged()
    {
        if (method_exists($this, 'loadStatsData')) {
            $this->loadStatsData();
        }
    }
	
	#[On('modal-closed')]
	public function onModalClosed()
	{
		$this->dispatch('refreshChart', $this->getChartData());
	}

    public function mount()
    {
        $this->selectedYear = (int)date('Y');
        $this->selectedMonth = (int)date('n');
    }

    /**
     * 點擊大類列時觸發：呼叫通用交易明細 Modal
     */
    public function openCategoryTransactions(int $categoryId, string $categoryName)
    {
        $this->dispatch('open-transaction-list-modal', params: [
            'categoryId' => $categoryId,
            'type' => $this->tab2, // 'expense' 或 'income'
            'title' => $categoryName . ' - 交易明細',
			'dateMode' => $this->tab3 === 'year' ? 'year' : 'month',
			'baseDate' => $this->selectedYear . '-' . ($this->selectedMonth ?? 1) . '-01',
        ]);
    }

    /**
     * 當切換第一層分頁時的聯動調整
     */
    public function updatedTab1($value)
    {
        if ($value === 'category') {
            if ($this->tab2 === 'balance') $this->tab2 = 'expense';
            if ($this->tab3 === 'day') $this->tab3 = 'month';
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

    public function changeDate()
    {
        $this->dispatch('refreshChart', $this->getChartData());
    }

    /**
     * 計算分類報表數據 (圓餅圖與列表)
     */
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
            
            // 歸類到一級大類
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
            
            $total = bcadd($total, $tx->amount, 4);
            $categorySummary[$catId]['amount'] = bcadd($categorySummary[$catId]['amount'], $tx->amount, 4);
        }

        // 計算百分比
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

        $list = array_values($categorySummary);

        return [
            'total' => number_format((float)$total, 2),
            'list' => $list
        ];
    }

    /**
     * 計算趨勢報表數據 (長條圖)
     */
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
                $m = (int)Carbon::parse($tx->recorded_at)->format('n');
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
                $d = (int)Carbon::parse($tx->recorded_at)->format('j');
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
        if ($this->tab2 === 'balance') {
            if ($tx->type === 'income') return bcadd($currentAmount, $tx->amount, 4);
            if ($tx->type === 'expense') return bcsub($currentAmount, $tx->amount, 4);
            return $currentAmount;
        }
        
        return $tx->type === $this->tab2 ? bcadd($currentAmount, $tx->amount, 4) : $currentAmount;
    }

    public function getChartData()
    {
        if ($this->tab1 === 'category') {
            $data = $this->categoryData;
            return [
                'type' => 'pie',
                'labels' => collect($data['list'])->pluck('name')->toArray(),
                'values' => collect($data['list'])->map(fn($item) => (float)$item['amount'])->toArray(),
                'centerText' => $data['total'] ?? '0.00'
            ];
        } else {
            $data = $this->trendData;
            return [
                'type' => 'bar',
                'labels' => collect($data)->pluck('label')->toArray(),
                'values' => collect($data)->map(fn($item) => (float)$item['amount'])->toArray(),
                'color' => $this->tab2 === 'expense' ? '#f87171' : ($this->tab2 === 'income' ? '#34d399' : '#60a5fa'),
                'centerText' => null
            ];
        }
    }

    public function render()
    {
        return view('livewire.finance.report-stats', [
            'chartData' => $this->getChartData()
        ])->layout('components.layouts.app');
    }
}