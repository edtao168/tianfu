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

    // ── 請求內快取（取代 static，避免跨請求殘留）──
    /** @var array<int, \App\Models\FinancialAccount|null> */
    private array $accountCache = [];

    /** @var array<string, string> key = "{shopId}|{accountId}|{timestamp}" */
    private array $balanceCache = [];
	
    public function mount()
    {
        $this->shopId = (int) session('current_shop_id', 1);
        $this->currentDate = now()->format('Y-m');
        $this->dateMode = 'month';

        // R1：確保快取為空（元件重建時屬性已是初始值，此處明示意圖）
        $this->accountCache = [];
        $this->balanceCache = [];
		
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

    /**
     * 決定目前分頁的「時間模式」來源 property。
     *
     * 三個時間狀態各有語意，**不可合併**：
     *  - assetTab    ：總資產（存量）的月度/每日快照頻率
     *  - granularity ：類別（流量）/ 賬戶佔比（存量）的年/月/日粒度
     *  - tab3        ：收支（流量）的月/日統計期間
     * 合併會導致切換分頁時互相污染，故保留三者，僅集中「分流規則」於此。
     *
     * @return string 'year'|'month'|'day'
     */
    private function resolveTimeModeSource(): string
    {
        if ($this->showAssetTrend) {
            return $this->assetTab;          // month | day
        }
        if ($this->usesGranularity()) {
            return $this->granularity;       // year | month | day
        }
        return $this->tab3;                  // month | day（收支）
    }
	
    private function updateDateMode()
    {
        $activeMode = $this->resolveTimeModeSource();

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
	
	/**
	 * 集中定義報表篩選群組：電腦端與手機端共用同一份「顯示條件 + 選項」，
	 * 避免兩端 @if 各寫一份造成選項不一致（例：電腦缺幣別、手機缺結餘）。
	 *
	 * 每組結構：
	 *  - key      : 唯一識別
	 *  - label    : 群組標題
	 *  - visible  : 是否顯示（集中判斷）
	 *  - options  : [{value,label,active,tone?}]
	 *  - wireSet  : 點擊時 $set 的 property 名稱
	 *  - layout   : 手機端欄數提示（'grid-2' 或 'grid-N'）
	 *
	 * @return array<int, array<string, mixed>>
	 */
	#[Computed]
	public function filterGroups(): array
	{
		$groups = [];

		// ── 1. 幣別（總資產以外的所有分頁）──
		if (!$this->showAssetTrend) {
			$options = [[
				'value'  => 'all',
				'label'  => '🌐 全部（合併）',
				'active' => $this->currencyFilter === 'all',
			]];
			foreach ($this->availableCurrencies as $currency) {
				$options[] = [
					'value'  => $currency->code,
					'label'  => $currency->code,
					'active' => $this->currencyFilter === $currency->code,
				];
			}

			$groups[] = [
				'key'     => 'currency',
				'label'   => '幣別',
				'visible' => true,
				'options' => $options,
				'wireSet' => 'currencyFilter',
				'layout'  => 'grid-2',
			];
		}

		// ── 2. 檢視 ──
		if ($this->showAssetTrend) {
			// 總資產：月度 / 每日（原本只在手機端有）
			$groups[] = [
				'key'     => 'assetTab',
				'label'   => '檢視',
				'visible' => true,
				'options' => [
					['value' => 'month', 'label' => '月度總資產', 'active' => $this->assetTab === 'month'],
					['value' => 'day',   'label' => '每日總資產', 'active' => $this->assetTab === 'day'],
				],
				'wireSet' => 'assetTab',
				'layout'  => 'grid-2',
			];
		} elseif ($this->tab1 === 'account') {
			// 賬戶：佔比 / 趨勢
			$groups[] = [
				'key'     => 'accountView',
				'label'   => '檢視',
				'visible' => true,
				'options' => [
					['value' => 'composition', 'label' => '佔比', 'active' => $this->tab2 === 'composition'],
					['value' => 'trend',       'label' => '趨勢', 'active' => $this->tab2 === 'trend'],
				],
				'wireSet' => 'tab2',
				'layout'  => 'grid-2',
			];
		} else {
			// 類別 / 收支：支出 / 收入（收支再加結餘）
			$options = [
				['value' => 'expense', 'label' => '支出', 'active' => $this->tab2 === 'expense', 'tone' => 'error'],
				['value' => 'income',  'label' => '收入', 'active' => $this->tab2 === 'income',  'tone' => 'success'],
			];
			// 結餘只給「收支」分頁（原本只在電腦端，集中後手機端也會有）
			if ($this->tab1 === 'trend') {
				$options[] = ['value' => 'balance', 'label' => '結餘', 'active' => $this->tab2 === 'balance'];
			}

			$groups[] = [
				'key'     => 'direction',
				'label'   => '檢視',
				'visible' => true,
				'options' => $options,
				'wireSet' => 'tab2',
				'layout'  => 'grid-2',
			];
		}

		// ── 3. 時間粒度（沿用既有 usesGranularity / allowedGranularities）──
		if ($this->usesGranularity()) {
			$labels  = ['year' => '年', 'month' => '月', 'day' => '日'];
			$options = [];
			foreach ($this->allowedGranularities() as $g) {
				$options[] = [
					'value'  => $g,
					'label'  => $labels[$g] ?? $g,
					'active' => $this->granularity === $g,
				];
			}

			$groups[] = [
				'key'     => 'granularity',
				'label'   => '時間範圍',
				'visible' => true,
				'options' => $options,
				'wireSet' => 'granularity',
				// 固定列舉，避免 Tailwind purge；目前最多 3 個（年/月/日）
				'layout'  => count($options) >= 3 ? 'grid-3' : 'grid-2',
			];
		}

		return $groups;
	}
	
	/**
	 * 供手機狀態條使用：只回傳 visible 的群組，並標記 active 選項。
	 * 不在這裡做文案轉換（狀態條文案另有特殊規則），只做「資料整理」。
	 *
	 * @return array<int, array<string, mixed>>
	 */
	#[Computed]
	public function visibleFilterGroups(): array
	{
		return collect($this->filterGroups)
			->where('visible', true)
			->map(function ($g) {
				$g['active_option'] = collect($g['options'])->firstWhere('active', true);
				return $g;
			})
			->values()
			->all();
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
	
	/**
	 * 將單筆交易金額換算為本位幣（僅合併模式且非本位幣時換算）。
	 *
	 * 這是三處（categoryData / calculateTrendAmount / assetEffectBreakdown）
	 * 原本重複的邏輯，統一封裝以避免不一致。
	 *
	 * @param  object  $tx        交易模型（需有 amount、currency、fromAccount、toAccount）
	 * @param  string  $baseCode  本位幣代碼
	 * @return string             換算後金額字串（4 位小數精度，未換算則原值）
	 */
	private function toBaseAmount($tx, string $baseCode): string
	{
		$txAmount = (string) $tx->amount;
		$txCurrency = $this->resolveTransactionCurrency($tx, $baseCode);

		if ($this->isConsolidated() && $txCurrency !== $baseCode) {
			return app(CurrencyService::class)->convertToBase($txAmount, $txCurrency);
		}

		return $txAmount;
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

			$txAmount = $this->toBaseAmount($tx, $baseCode);

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
		$txAmount = $this->toBaseAmount($tx, $baseCode);

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

        [$labels, $timePoints] = ($this->assetTab === 'month' || $this->dateMode === 'year')
			? $this->buildMonthlyTimePoints($this->selectedYear, false)
			: $this->buildDailyTimePoints($this->selectedYear, $this->selectedMonth, true);

        // ---------- 分幣別多線模式 ----------
        if (!$this->isConsolidated()) {
            $accounts = FinancialAccount::where('shop_id', $this->shopId)
                ->where('is_active', true)
                ->where('currency', $this->currencyFilter)
                ->get();
				
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
			$txAmount = $this->toBaseAmount($tx, $baseCode);

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
	
	/**
	 * 產生「整年 12 個月」的 labels 與時間點。
	 *
	 * @param  int  $year
	 * @param  bool $endOfDay  月底是否取當日 23:59:59（assetTrendData 傳 false，accountTrendData 傳 true）
	 * @return array{0: string[], 1: \Carbon\Carbon[]}  [labels, timePoints]
	 */
	private function buildMonthlyTimePoints(int $year, bool $endOfDay = true): array
	{
		$labels = [];
		$timePoints = [];

		for ($m = 1; $m <= 12; $m++) {
			$labels[] = $m . '月';
			$tp = Carbon::create($year, $m)->endOfMonth();
			$timePoints[] = $endOfDay ? $tp->endOfDay() : $tp;
		}

		return [$labels, $timePoints];
	}

	/**
	 * 產生「指定月份逐日」的 labels 與時間點。
	 *
	 * @param  int  $year
	 * @param  int  $month
	 * @param  bool $endOfDay  每日是否取當日 23:59:59
	 * @return array{0: string[], 1: \Carbon\Carbon[]}  [labels, timePoints]
	 */
	private function buildDailyTimePoints(int $year, int $month, bool $endOfDay = true): array
	{
		$labels = [];
		$timePoints = [];

		$daysInMonth = Carbon::create($year, $month)->daysInMonth;
		for ($d = 1; $d <= $daysInMonth; $d++) {
			$labels[] = $d . '日';
			$tp = Carbon::create($year, $month, $d);
			$timePoints[] = $endOfDay ? $tp->endOfDay() : $tp;
		}

		return [$labels, $timePoints];
	}

    // ============================================================
    // 圖表資料出口
    // ============================================================

    public function getChartData()
	{
		return match (true) {
			$this->showAssetTrend          => $this->chartDataForAssetTrend(),
			$this->tab1 === 'category'     => $this->chartDataForCategory(),
			$this->tab1 === 'account'      => $this->chartDataForAccount(),
			default                        => $this->chartDataForTrend(),
		};
	}
	
	/**
	 * 總資產趨勢圖表資料（合併單線 / 分幣別多線）。
	 */
	private function chartDataForAssetTrend(): array
	{
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
		$maxValue = !empty($values) ? max($values) : 0;
		$range = $maxValue - $minValue;
		$pad = $range > 0 ? $range * 0.1 : max(abs($maxValue) * 0.02, 1);

		return [
			'type' => 'line',
			'labels' => array_values(collect($data)->pluck('label')->all()),
			'values' => array_values(collect($data)->map(fn($item) => (float) round((float)$item['amount'], 2))->all()),
			'suggestedMin' => $minValue - $pad,
			'suggestedMax' => $maxValue + $pad,
			'color' => '#818cf8',
			'centerText' => null,
			'unit' => reset($data)['symbol'] ?? 'NT$',
			'isAssetTrend' => true,
			'isSmallMultiples' => false,
			'isMultiLine' => false,
		];
	}
	
	/**
	 * 類別分佈圖表資料（環形圖）。
	 */
	private function chartDataForCategory(): array
	{
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
	
	/**
	 * 賬戶圖表資料（佔比環形圖 / 趨勢 small multiples）。
	 */
	private function chartDataForAccount(): array
	{
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
	
	/**
	 * 收支趨勢圖表資料（長條圖）。
	 */
	private function chartDataForTrend(): array
	{
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
     *
     * R1：快取由 static 改為元件屬性，生命週期 = 單次 Livewire 請求，
     *     避免 Octane / 多 tenant 同 process 下的跨請求殘留。
     *     cacheKey 加入 shopId 做防禦性隔離。
     */
    private function getAccountBalanceAt(int $accountId, Carbon $until): string
    {
        // 帳戶基本資料快取
        if (!array_key_exists($accountId, $this->accountCache)) {
            $this->accountCache[$accountId] = FinancialAccount::where('shop_id', $this->shopId)
                ->select(['id', 'balance'])
                ->find($accountId);
        }
        $account = $this->accountCache[$accountId];
        if (!$account) return '0.0000';

        // 餘額快取（含 shopId，防禦性隔離）
        $cacheKey = $this->shopId . '|' . $accountId . '|' . $until->timestamp;
        if (array_key_exists($cacheKey, $this->balanceCache)) {
            return $this->balanceCache[$cacheKey];
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
        $this->balanceCache[$cacheKey] = $result;
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