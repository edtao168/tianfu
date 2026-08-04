<?php //app/Livewire/Finance/TransactionListModal.php

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

    // 查詢篩選條件
    public ?int $accountId = null;
    public ?int $categoryId = null;
    public ?string $transactionType = null; // income, expense, transfer

    // 標題與幣別顯示
    public string $title = '交易明細列表';
    public string $subtitle = '';
    public string $currencySymbol = 'NT$';
    public string $targetCurrency = '';

    // 交易資料與日期範圍
    public array $transactionsData = [];
    public string $startDate = '';  // Y-m-d
    public string $endDate = '';    // Y-m-d
    
    // 日期範圍模式: month, day, year
    public string $dateRangeMode = 'month';

    // 四項總體統計（月支出、月收入、年支出、年收入）
    public array $statsSummary = [
        'month_expense' => '0.00',
        'month_income' => '0.00',
        'year_expense' => '0.00',
        'year_income' => '0.00',
    ];

    // 關聯實體快照
    public ?FinancialAccount $currentAccount = null;

    public function mount()
    {
        $this->setDateRange('month');
        $this->resetTransactionsData();
    }

    /**
     * 設定日期範圍
     */
	private function setDateRange(string $mode, ?string $baseDate = null)
	{
		$this->dateRangeMode = $mode;
		$now = $baseDate ? Carbon::parse($baseDate) : Carbon::now();

		switch ($mode) {
			case 'day':
				$this->startDate = $now->copy()->startOfDay()->format('Y-m-d');
				$this->endDate = $now->copy()->endOfDay()->format('Y-m-d');
				break;
			case 'month':
				$this->startDate = $now->copy()->startOfMonth()->format('Y-m-d');
				$this->endDate = $now->copy()->endOfMonth()->format('Y-m-d');
				break;
			case 'year':
				$this->startDate = $now->copy()->startOfYear()->format('Y-m-d');
				$this->endDate = $now->copy()->endOfYear()->format('Y-m-d');
				break;
			default:
				$this->startDate = $now->copy()->startOfMonth()->format('Y-m-d');
				$this->endDate = $now->copy()->endOfMonth()->format('Y-m-d');
				break;
		}
	}

    /**
     * 獲取日期範圍顯示文字
     */
    public function getDateRangeDisplayProperty(): string
    {
        $start = Carbon::parse($this->startDate);
        $end = Carbon::parse($this->endDate);
        
        if ($this->dateRangeMode === 'day') {
            return $start->format('Y 年 m 月 d 日');
        } elseif ($this->dateRangeMode === 'month') {
            return $start->format('Y 年 m 月');
        } elseif ($this->dateRangeMode === 'year') {
            return $start->format('Y 年');
        }
        
        return $start->format('Y/m/d') . ' ~ ' . $end->format('Y/m/d');
    }

    #[On('open-transaction-list-modal')]
    public function openModal(array $params = [])
    {
        $this->resetFilter();

        $this->accountId = $params['accountId'] ?? null;
        $this->categoryId = $params['categoryId'] ?? null;
        $this->transactionType = $params['type'] ?? null;
        
        // 設定日期範圍模式（從參數傳入）
        $dateMode = $params['dateMode'] ?? 'month';
        $baseDate = $params['baseDate'] ?? null;
        $this->setDateRange($dateMode, $baseDate);

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
            $this->title = $params['title'] ?? ($category ? $category->name : '分類交易');
            $this->subtitle = '大類與關聯子類明細';
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

	/**
	 * 載入交易明細
	 */
	public function loadTransactions()
	{
		$start = Carbon::parse($this->startDate)->startOfDay();
		$end = Carbon::parse($this->endDate)->endOfDay();

		// 計算年度範圍（用於年度統計）
		$yearStart = Carbon::parse($this->startDate)->startOfYear();
		$yearEnd = Carbon::parse($this->endDate)->endOfYear();

		// 1. 若有 categoryId，包含該大類及所有子類 ID
		$targetCategoryIds = [];
		if ($this->categoryId) {
			$subCategoryIds = Category::where('parent_id', $this->categoryId)->pluck('id')->toArray();
			$targetCategoryIds = array_merge([$this->categoryId], $subCategoryIds);
		}

		// 2. 構建基底 Query 閉包
		$applyFilter = function ($query) use ($targetCategoryIds) {
			$query->where('shop_id', $this->shopId);

			if ($this->accountId) {
				$query->where(function ($q) {
					$q->where('from_account_id', $this->accountId)
					  ->orWhere('to_account_id', $this->accountId);
				});
			}

			if (!empty($targetCategoryIds)) {
				$query->whereIn('category_id', $targetCategoryIds);
			}

			if ($this->transactionType) {
				$query->where('type', $this->transactionType);
			}
		};

		// 3. 計算統計數據（期間內 + 年度）
		$periodTxs = Transaction::query()->where($applyFilter)->whereBetween('recorded_at', [$start, $end])->get();
		$yearTxs = Transaction::query()->where($applyFilter)->whereBetween('recorded_at', [$yearStart, $yearEnd])->get();

		$periodExpense = '0.0000';
		$periodIncome = '0.0000';
		foreach ($periodTxs as $tx) {
			$amt = (string)$tx->amount;
			if ($tx->type === 'expense') {
				$periodExpense = bcadd($periodExpense, $amt, 4);
			}
			if ($tx->type === 'income') {
				$periodIncome = bcadd($periodIncome, $amt, 4);
			}
		}

		$yearExpense = '0.0000';
		$yearIncome = '0.0000';
		foreach ($yearTxs as $tx) {
			$amt = (string)$tx->amount;
			if ($tx->type === 'expense') {
				$yearExpense = bcadd($yearExpense, $amt, 4);
			}
			if ($tx->type === 'income') {
				$yearIncome = bcadd($yearIncome, $amt, 4);
			}
		}

		// 4. 取得交易流水清單
		$transactions = $periodTxs->sortByDesc('recorded_at');

		$categoryIds = $transactions->pluck('category_id')->filter()->unique()->toArray();
		$categories = Category::whereIn('id', $categoryIds)->get()->keyBy('id');

		$accountIds = [];
		foreach ($transactions as $tx) {
			if ($tx->from_account_id) {
				$accountIds[] = $tx->from_account_id;
			}
			if ($tx->to_account_id) {
				$accountIds[] = $tx->to_account_id;
			}
		}
		$accounts = FinancialAccount::whereIn('id', array_unique($accountIds))->get()->keyBy('id');

		// 獲取幣別配置
		$currencies = config('business.currencies', []);

		$totalIncome = '0.0000';
		$totalExpense = '0.0000';
		$formattedList = [];

		foreach ($transactions as $tx) {
			$isTransfer = ($tx->type === 'transfer');
			$isIncome = false;
			$isExpense = false;

			// 明細清單：保留交易原本金額，不進行匯率換算
			$rawAmount = (string)$tx->amount;

			// 上方統計區：換算為本幣（Base Currency）進行精確加總
			$convertedAmountForStats = $this->convertToBase($rawAmount, $tx->currency);

			// 判斷收入/支出（依據是否有指定帳戶）
			if ($this->accountId) {
				// 帳戶模式：根據與指定帳戶的關係判斷
				if ($tx->type === 'income' && $tx->to_account_id == $this->accountId) {
					$isIncome = true;
				} elseif ($tx->type === 'expense' && $tx->from_account_id == $this->accountId) {
					$isExpense = true;
				} elseif ($isTransfer) {
					if ($tx->to_account_id == $this->accountId) {
						$isIncome = true;
					} elseif ($tx->from_account_id == $this->accountId) {
						$isExpense = true;
					}
				}
			} else {
				// 期間模式：根據交易類型判斷
				if ($tx->type === 'income') {
					$isIncome = true;
				}
				if ($tx->type === 'expense') {
					$isExpense = true;
				}
			}

			// 累計頂部統計金額（使用換算後的本幣）
			if ($isIncome) {
				$totalIncome = bcadd($totalIncome, $convertedAmountForStats, 4);
			} elseif ($isExpense) {
				$totalExpense = bcadd($totalExpense, $convertedAmountForStats, 4);
			}

			// 分類圖示與名稱
			$categoryIcon = 'folder';
			$categoryName = null;

			if ($isTransfer) {
				$categoryIcon = 'arrow-path';
			} elseif ($tx->category_id && isset($categories[$tx->category_id])) {
				$category = $categories[$tx->category_id];
				$categoryIcon = $category->icon ?? 'folder';
				$categoryName = $category->name;
			}

			// 帳戶名稱
			$fromAccountName = $tx->from_account_id && isset($accounts[$tx->from_account_id])
				? $accounts[$tx->from_account_id]->name
				: null;
			$toAccountName = $tx->to_account_id && isset($accounts[$tx->to_account_id])
				? $accounts[$tx->to_account_id]->name
				: null;

			// 獲取顯示用的帳戶名稱
			if ($this->accountId) {
				$accountName = $this->currentAccount->name ?? '';
			} else {
				if ($isTransfer) {
					if ($fromAccountName && $toAccountName) {
						$accountName = $fromAccountName . ' ➔ ' . $toAccountName;
					} else {
						$accountName = $fromAccountName ?? $toAccountName ?? '未知帳戶';
					}
				} elseif ($isIncome) {
					$accountName = $toAccountName ?? $fromAccountName ?? '未知帳戶';
				} elseif ($isExpense) {
					$accountName = $fromAccountName ?? $toAccountName ?? '未知帳戶';
				} else {
					$accountName = $fromAccountName ?? $toAccountName ?? '未知帳戶';
				}
			}

			// 獲取該筆交易原幣別符號
			$txCurrencySymbol = $currencies[$tx->currency]['symbol'] ?? 'NT$';

			// 組裝單筆交易資料 (display_amount 直接帶入原始金額)
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
				'currency_symbol' => $txCurrencySymbol,
				'display_amount' => $rawAmount,
				'is_income' => $isIncome,
				'is_expense' => $isExpense,
				'is_transfer' => $isTransfer,
				'category_icon' => $categoryIcon,
				'category_name' => $categoryName,
				'from_account_name' => $fromAccountName,
				'to_account_name' => $toAccountName,
				'account_name' => $accountName,
			];
		}

		// 計算淨額
		$netAmount = bcsub($totalIncome, $totalExpense, 4);

		// ========== 帳戶模式：計算期初與期末餘額 ==========
		$openingBalance = '0.0000';
		$closingBalance = '0.0000';
		$isAccountMode = !is_null($this->accountId);

		if ($isAccountMode && $this->currentAccount) {
			$currentBalance = (string)$this->currentAccount->balance;
			$closingBalance = $currentBalance;
			$openingBalance = bcsub($closingBalance, $netAmount, 4);
		}

		// ========== 組裝 statsSummary ==========
		$this->statsSummary = [
			'total_income' => $totalIncome,
			'total_expense' => $totalExpense,
			'net_amount' => $netAmount,
			'opening_balance' => $openingBalance,
			'closing_balance' => $closingBalance,
			'is_account_mode' => $isAccountMode,
			'currency_symbol' => $this->currencySymbol,
		];

		// ========== 組裝 transactionsData ==========
		$this->transactionsData = [
			'list' => $formattedList,
			'total_income' => $totalIncome,
			'total_expense' => $totalExpense,
			'net_amount' => $netAmount,
			'total_count' => $transactions->count(),
		];
	}
    
    public function closeModal()
    {
        $this->showModal = false;
        $this->dispatch('modal-closed');
    }

    /**
	 * 上一個時間範圍
	 */
	public function previousRange()
	{
		$start = Carbon::parse($this->startDate);
		
		if ($this->accountId) {
			// 帳戶模式：固定按月跳轉
			$newDate = $start->subMonth();
			$this->setDateRange('month', $newDate->format('Y-m-d'));
		} else {
			// 期間模式：按當前模式跳轉
			switch ($this->dateRangeMode) {
				case 'day':
					$newDate = $start->subDay();
					$this->setDateRange('day', $newDate->format('Y-m-d'));
					break;
				case 'month':
					$newDate = $start->subMonth();
					$this->setDateRange('month', $newDate->format('Y-m-d'));
					break;
				case 'year':
					$newDate = $start->subYear();
					$this->setDateRange('year', $newDate->format('Y-m-d'));
					break;
			}
		}
		$this->loadTransactions();
	}

	/**
	 * 下一個時間範圍
	 */
	public function nextRange()
	{
		$start = Carbon::parse($this->startDate);
		$now = Carbon::now();
		
		if ($this->accountId) {
			// 帳戶模式：固定按月跳轉，不能超過當月
			$newDate = $start->addMonth();
			if ($newDate->startOfMonth()->gt($now->startOfMonth())) {
				$newDate = $now;
			}
			$this->setDateRange('month', $newDate->format('Y-m-d'));
		} else {
			// 期間模式：按當前模式跳轉
			switch ($this->dateRangeMode) {
				case 'day':
					$newDate = $start->addDay();
					if ($newDate->startOfDay()->gt($now->startOfDay())) {
						$newDate = $now;
					}
					$this->setDateRange('day', $newDate->format('Y-m-d'));
					break;
				case 'month':
					$newDate = $start->addMonth();
					if ($newDate->startOfMonth()->gt($now->startOfMonth())) {
						$newDate = $now;
					}
					$this->setDateRange('month', $newDate->format('Y-m-d'));
					break;
				case 'year':
					$newDate = $start->addYear();
					if ($newDate->startOfYear()->gt($now->startOfYear())) {
						$newDate = $now;
					}
					$this->setDateRange('year', $newDate->format('Y-m-d'));
					break;
			}
		}
		$this->loadTransactions();
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

    /**
	 * 獲取匯率
	 */
	private function getExchangeRate(string $currency): float
    {
        $currencies = config('business.currencies', []);
        return (float)($currencies[$currency]['rate'] ?? 1);
    }

    /**
	 * 轉換為基礎幣別
	 */
	private function convertToBase(string $amount, string $currency): string
    {
        if (!is_numeric($amount) || $amount === '') return '0.0000';
        $rate = $this->getExchangeRate($currency);
        return bcmul($amount, (string)$rate, 4) ?: '0.0000';
    }

    /**
	 * 從基礎幣別轉換為目標幣別
	 */
	private function convertFromBase(string $amount, string $currency): string
    {
        if (!is_numeric($amount) || $amount === '') return '0.0000';
        $rate = $this->getExchangeRate($currency);
        if ($rate == 0) return '0.0000';
        return bcdiv($amount, (string)$rate, 4) ?: '0.0000';
    }

    /**
	 * 轉換為目標帳戶幣別
	 */
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