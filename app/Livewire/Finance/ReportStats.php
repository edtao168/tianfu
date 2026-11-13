<?php // app/Livewire/Finance/ReportStats.php

namespace App\Livewire\Finance;

use App\Models\Transaction;
use App\Models\Currency;
use App\Models\FinancialAccount;
use App\Services\CurrencyService;
use App\Services\MoneyCalculator;
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
	
	/**
     * 時間粒度：僅類別分頁（year|month|day）與賬戶占比分頁（year|month）使用
     * 收支分頁仍用 $tab3；總資產仍用 $assetTab
     */
    public string $granularity = 'month';
	public bool $hasUserChangedGranularity = false;

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
            // 類別：允許 year|month|day，保留使用者上次選擇；預設 month
            if (!in_array($this->granularity, ['year', 'month', 'day'], true)) {
                $this->granularity = 'month';
            }
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
            // 賬戶占比：只允許 year|month；若上次是 day 則退回 month
            if (!in_array($this->granularity, ['year', 'month'], true)) {
                $this->granularity = 'month';
            }
            // 首次進入賬戶分頁時預設年（保留原習慣）
            if ($this->tab2 === 'composition' && $this->granularity === 'month'
                && !$this->hasUserChangedGranularity) {
                $this->granularity = 'year';
            }

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
        // 決定目前分頁的「時間模式」來源
        if ($this->showAssetTrend) {
            $activeMode = $this->assetTab;                      // month | day
        } elseif ($this->usesGranularity()) {
            $activeMode = $this->granularity;                   // year | month | day
        } else {
            $activeMode = $this->tab3;                          // month | day（收支）
        }

        if ($activeMode === 'year') {
            $this->dateMode = 'year';
            if (strlen($this->currentDate) > 4) {
                $this->currentDate = substr($this->currentDate, 0, 4);
            }
        } elseif ($activeMode === 'day') {
            $this->dateMode = 'day';
            if (strlen($this->currentDate) === 4) {
                $this->currentDate = $this->currentDate . '-' . now()->format('m-d');
            } elseif (strlen($this->currentDate) === 7) {
                $this->currentDate = $this->currentDate . '-' . now()->format('d');
            }
        } else {
            $this->dateMode = 'month';
            if (strlen($this->currentDate) === 4) {
                $this->currentDate = $this->currentDate . '-' . now()->format('m');
            } elseif (strlen($this->currentDate) === 10) {
                $this->currentDate = substr($this->currentDate, 0, 7);
            }
        }
    }
	
	public function updatedGranularity()
    {
        $this->hasUserChangedGranularity = true;
        $this->updateDateMode();
        $this->dispatch('refreshChart', $this->getChartData());
    }

    /**
     * 目前分頁允許的時間粒度
     * - 類別：year|month|day（流量報表，期間越短越接近單筆歸類）
     * - 賬戶占比：year|month（存量報表，日粒度無新洞察且有空圖陷阱）
     * - 其他分頁：回傳空陣列（不使用 granularity）
     */
    public function allowedGranularities(): array
    {
        if ($this->tab1 === 'category') {
            return ['year', 'month', 'day'];
        }
        if ($this->tab1 === 'account' && $this->tab2 === 'composition') {
            return ['year', 'month'];
        }
        return [];
    }

    /**
     * 目前是否使用 granularity（類別 / 賬戶占比）
     */
    public function usesGranularity(): bool
    {
        return !empty($this->allowedGranularities());
    }

    // ============================================================
    // Modal
    // ============================================================

    public function openCategoryTransactions(int $categoryId, string $categoryName)
    {
        $currencyParam = $this->currencyFilter !== 'all' ? $this->currencyFilter : null;

        $this->dispatch('open-transaction-list-modal', params: [
            'categoryId' => $categoryId,
            'type' => $this->tab2,
            'title' => $categoryName . ' - 交易明細',
            'dateMode' => $this->dateMode,   // year | month | day
            'baseDate' => $this->currentDate,
            'currency' => $currencyParam,
        ]);
    }

    // ============================================================
    // 計算屬性
    // ============================================================

    #[Computed]
    public function selectedYear(): int
    {
        if (strlen($this->currentDate) >= 4) {
            return (int) substr($this->currentDate, 0, 4);
        }
        return (int) now()->format('Y');
    }

    #[Computed]
    public function selectedMonth(): int
    {
        if (strlen($this->currentDate) >= 7) {
            return (int) substr($this->currentDate, 5, 2);
        }
        return (int) now()->format('m');
    }

    #[Computed]
    public function selectedDay(): int
    {
        if (strlen($this->currentDate) >= 10) {
            return (int) substr($this->currentDate, 8, 2);
        }
        return (int) now()->format('d');
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

    /**
	 * 解析交易幣別：交易 → 來源帳戶 → 目的帳戶 → 本幣
	 */
	private function resolveTransactionCurrency($tx, string $baseCode): string
	{
		return $tx->currency
			?? $tx->fromAccount?->currency
			?? $tx->toAccount?->currency
			?? $baseCode;
	}

	#[Computed]
	public function categoryData()
	{
		$service = app(CurrencyService::class);
		$baseCode = $this->baseCurrency->code ?? 'TWD';

		$query = Transaction::query()
			->where('shop_id', $this->shopId)
			->where('type', $this->tab2)
			->with(['category.parent', 'fromAccount:id,currency', 'toAccount:id,currency']);

		if (!$this->isConsolidated()) {
			$this->applyCurrencyFilter($query);
		}

		if ($this->dateMode === 'year') {
			$query->whereYear('recorded_at', $this->selectedYear);
		} elseif ($this->dateMode === 'day') {
			$query->whereDate('recorded_at', Carbon::create(
				$this->selectedYear, $this->selectedMonth, $this->selectedDay
			));
		} else {
			$query->whereYear('recorded_at', $this->selectedYear)
				  ->whereMonth('recorded_at', $this->selectedMonth);
		}

		$transactions = $query->get();

		$total = '0.0000';
		$categorySummary = [];

		foreach ($transactions as $tx) {
			if (!$tx->category_id) continue;

			$txAmount = (string) $tx->amount;
			$txCurrency = $this->resolveTransactionCurrency($tx, $baseCode);

			if ($this->isConsolidated() && $txCurrency !== $baseCode) {
				$txAmount = $service->convertToBase($txAmount, $txCurrency);
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

			$total = MoneyCalculator::add($total, $txAmount, 4);
			$categorySummary[$catId]['amount'] = MoneyCalculator::add(
				$categorySummary[$catId]['amount'], $txAmount, 4
			);
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

		if ($this->dateMode === 'year' || $this->tab3 === 'month') {
			for ($m = 1; $m <= 12; $m++) {
				$list[$m] = ['label' => $m . '月', 'amount' => '0.0000'];
			}

			$query = Transaction::query()
				->where('shop_id', $this->shopId)
				->whereYear('recorded_at', $this->selectedYear)
				->with(['fromAccount:id,currency', 'toAccount:id,currency']);

			if (!$this->isConsolidated()) {
				$this->applyCurrencyFilter($query);
			}

			foreach ($query->get(['id', 'type', 'amount', 'recorded_at', 'from_account_id', 'to_account_id']) as $tx) {
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
				->whereMonth('recorded_at', $this->selectedMonth)
				->with(['fromAccount:id,currency', 'toAccount:id,currency']);

			if (!$this->isConsolidated()) {
				$this->applyCurrencyFilter($query);
			}

			foreach ($query->get(['id', 'type', 'amount', 'recorded_at', 'from_account_id', 'to_account_id']) as $tx) {
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
		$txCurrency = $this->resolveTransactionCurrency($tx, $baseCode);

		if ($this->isConsolidated() && $txCurrency !== $baseCode) {
			$txAmount = $service->convertToBase($txAmount, $txCurrency);
		}

		if ($this->tab2 === 'balance') {
			if ($tx->type === 'income') return MoneyCalculator::add($currentAmount, $txAmount, 4);
			if ($tx->type === 'expense') return MoneyCalculator::sub($currentAmount, $txAmount, 4);
			return $currentAmount;
		}

		return $tx->type === $this->tab2
			? MoneyCalculator::add($currentAmount, $txAmount, 4)
			: $currentAmount;
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
			$txCurrency = $this->resolveTransactionCurrency($tx, $baseCode);

			if ($txCurrency !== $baseCode) {
				$txAmount = $service->convertToBase($txAmount, $txCurrency);
			}

			if ($tx->type === 'income') $transactionEffect = MoneyCalculator::add($transactionEffect, $txAmount, 4);
			if ($tx->type === 'expense') $transactionEffect = MoneyCalculator::sub($transactionEffect, $txAmount, 4);
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
    /**
	 * 取得某帳戶在指定時間點的餘額（優化版）
	 *
	 * 保留原語意：初始 balance + 期間內 from 為支出、to 為收入的淨額。
	 * 注意：此方法被多次呼叫（時序圖），呼叫端應避免在迴圈內反覆 find()。
	 */
	private function getAccountBalanceAt(int $accountId, Carbon $until): string
	{
		static $accountCache = [];
		static $balanceCache = [];

		if (!isset($accountCache[$accountId])) {
			$accountCache[$accountId] = FinancialAccount::where('shop_id', $this->shopId)
				->select(['id', 'balance'])
				->find($accountId);
		}
		$account = $accountCache[$accountId];
		if (!$account) return '0.0000';

		$cacheKey = $accountId . '|' . $until->timestamp;
		if (isset($balanceCache[$cacheKey])) {
			return $balanceCache[$cacheKey];
		}

		$initialBalance = (string) ($account->balance ?? '0.0000');

		$netChange = '0.0000';
		$transactions = Transaction::where('shop_id', $this->shopId)
			->where(function ($q) use ($accountId) {
				$q->where('from_account_id', $accountId)
				  ->orWhere('to_account_id', $accountId);
			})
			->where('recorded_at', '<=', $until)
			->get(['from_account_id', 'to_account_id', 'amount']);

		foreach ($transactions as $tx) {
			$txAmount = (string) $tx->amount;
			if ($tx->from_account_id == $accountId) $netChange = MoneyCalculator::sub($netChange, $txAmount, 4);
			if ($tx->to_account_id == $accountId)   $netChange = MoneyCalculator::add($netChange, $txAmount, 4);
		}

		$result = MoneyCalculator::add($initialBalance, $netChange, 4);
		$balanceCache[$cacheKey] = $result;
		return $result;
	}
	
    public function render()
    {
        $parsedDate = $this->parseCurrentDate();

        if ($this->dateMode === 'year') {
            $displayDate = $this->selectedYear . ' 年';
            $isCurrent = ($this->selectedYear === (int) now()->format('Y'));
        } elseif ($this->dateMode === 'day') {
            $displayDate = $parsedDate->format('Y 年 m 月 d 日');
            $isCurrent = $parsedDate->isToday();
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