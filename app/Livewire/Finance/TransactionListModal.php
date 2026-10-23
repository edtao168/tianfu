<?php // app/Livewire/Finance/TransactionListModal.php

namespace App\Livewire\Finance;

use App\Models\Category;
use App\Models\Currency;
use App\Models\FinancialAccount;
use App\Models\Transaction;
use App\Services\CurrencyService;
use App\Services\MoneyCalculator;
use App\Traits\WithDateNavigation;
use Carbon\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Mary\Traits\Toast;

class TransactionListModal extends Component
{
    use Toast, WithDateNavigation;

    public bool $showModal = false;
    public int $shopId = 1;

    // 查詢篩選條件
    public ?int $accountId = null;
    public ?int $categoryId = null;
    public ?string $transactionType = null; // income, expense, transfer

    // 標題與幣別顯示
    public string $title = '交易明細列表';
    public string $currencySymbol = 'NT$';

    // 交易資料
    public array $transactionsData = [];
    
    // 統計摘要
    public array $statsSummary = [];

    // 關聯實體快照
    public ?FinancialAccount $currentAccount = null;

    // ========== 計算屬性 ==========
    
    #[Computed]
    public function dateDisplay(): string
    {
        $date = $this->parseCurrentDate();
        return match ($this->dateMode) {
            'day'   => $date->format('Y-m-d'),
            'year'  => $date->format('Y 年'),
            default => $date->format('Y 年 m 月'),
        };
    }

    #[Computed]
    public function isCurrentDate(): bool
    {
        $date = $this->parseCurrentDate();
        return match ($this->dateMode) {
            'day'   => $date->isToday(),
            'year'  => $date->isCurrentYear(),
            default => $date->isCurrentMonth(),
        };
    }

    #[Computed]
    public function baseSymbol(): string
    {
        return $this->currencySymbol;
    }

    #[Computed]
    public function totalIncome(): string
    {
        return number_format((float)($this->statsSummary['total_income'] ?? 0), 2);
    }

    #[Computed]
    public function totalExpense(): string
    {
        return number_format((float)($this->statsSummary['total_expense'] ?? 0), 2);
    }

    #[Computed]
    public function transactionCount(): int
    {
        return $this->transactionsData['total_count'] ?? 0;
    }

    // ========== 生命週期方法 ==========

    public function mount()
    {
        $this->currentDate = now()->format('Y-m');
        $this->dateMode = 'month';
        $this->shopId = (int) session('current_shop_id', 1);
        $this->resetTransactionsData();
    }

    protected function onDateChanged(): void
    {
        if ($this->showModal) {
            $this->loadTransactions();
        }
    }

    // ========== 事件監聽 ==========

    #[On('open-transaction-list-modal')]
    public function openModal(array $params = [])
    {
        $this->resetFilter();
        $this->resetTransactionsData();

        // 設定篩選條件
        $this->accountId = $params['accountId'] ?? null;
        $this->categoryId = $params['categoryId'] ?? null;
        $this->transactionType = $params['type'] ?? null;
        $this->dateMode = $params['dateMode'] ?? 'month';
        $this->title = $params['title'] ?? $this->getDefaultTitle();

        // 設定日期
        if (isset($params['baseDate'])) {
            $this->currentDate = $this->formatDateForMode($params['baseDate'], $this->dateMode);
        } else {
            $this->currentDate = now()->format($this->getDateFormat());
        }

        // 設定帳戶與幣別
        if ($this->accountId) {
			$account = FinancialAccount::find($this->accountId);
			if (!$account) {
				$this->toast(type: 'error', title: '找不到該帳戶');
				return;
			}
			$this->currentAccount = $account;

			// 用 Service 明確取該帳戶幣別符號，找不到才 fallback
			$currencySymbol = app(CurrencyService::class)
				->getCurrency($account->currency)?->symbol;

			$this->currencySymbol = $currencySymbol ?? 'NT$';
		} else {
			$this->currencySymbol = app(CurrencyService::class)->getBaseCurrencySymbol();
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

    // ========== 核心方法：載入交易明細 ==========
	
	public function loadTransactions()
	{
		$currentDate = $this->parseCurrentDate();

		// 1. 計算該區間的開頭與結束點 ($start, $end)
		[$start, $end] = $this->getDateRange($currentDate);

		$targetCategoryIds = $this->getCategoryIdsWithChildren();
		$applyFilter = $this->buildFilterClosure($targetCategoryIds);

		// 2. 查詢當月交易明細
		$transactions = Transaction::query()
			->where($applyFilter)
			->whereBetween('recorded_at', [$start, $end])
			->with(['category', 'fromAccount', 'toAccount'])
			->orderBy('recorded_at', 'desc')
			->get();

		$categories = $this->loadCategories($transactions);
		$accounts = $this->loadAccounts($transactions);
		$currencies = $this->loadCurrencies();

		[$formattedList, $totalIncome, $totalExpense] = $this->buildTransactionList(
			$transactions,
			$categories,
			$accounts,
			$currencies
		);

		// 3. 計算當月淨變化量
		$netAmount = bcsub($totalIncome, $totalExpense, 4);

		// 4. 正確計算「歷史期末」與「歷史期初」餘額
		[$openingBalance, $closingBalance] = $this->calculateBalances($netAmount, $end);

		// 組裝統計摘要
		$this->statsSummary = [
			'total_income'      => $totalIncome,
			'total_expense'     => $totalExpense,
			'net_amount'        => $netAmount,
			'opening_balance'   => $openingBalance,
			'closing_balance'   => $closingBalance,
			'is_account_mode'   => !is_null($this->accountId),
			'currency_symbol'   => $this->currencySymbol,
		];

		$this->transactionsData = [
			'list'         => $formattedList,
			'total_income' => $totalIncome,
			'total_expense'=> $totalExpense,
			'net_amount'   => $netAmount,
			'total_count'  => $transactions->count(),
		];
	}

    // ========== 輔助方法 ==========

    private function getDateRange(Carbon $currentDate): array
    {
        $start = match ($this->dateMode) {
            'day'   => $currentDate->copy()->startOfDay(),
            'year'  => $currentDate->copy()->startOfYear(),
            default => $currentDate->copy()->startOfMonth(),
        };
        $end = match ($this->dateMode) {
            'day'   => $currentDate->copy()->endOfDay(),
            'year'  => $currentDate->copy()->endOfYear(),
            default => $currentDate->copy()->endOfMonth(),
        };
        return [$start, $end];
    }

    private function getCategoryIdsWithChildren(): array
    {
        if (!$this->categoryId) {
            return [];
        }

        $subCategoryIds = Category::where('parent_id', $this->categoryId)
            ->pluck('id')
            ->toArray();
        
        return array_merge([$this->categoryId], $subCategoryIds);
    }

    private function buildFilterClosure(array $targetCategoryIds): \Closure
    {
        return function ($query) use ($targetCategoryIds) {
            $query->where('shop_id', $this->shopId);

            // 帳戶篩選
            if ($this->accountId) {
                $query->where(function ($q) {
                    $q->where('from_account_id', $this->accountId)
                      ->orWhere('to_account_id', $this->accountId);
                });
            }

            // 分類篩選（含子分類）
            if (!empty($targetCategoryIds)) {
                $query->whereIn('category_id', $targetCategoryIds);
            }

            // 交易類型篩選
            if ($this->transactionType) {
                $query->where('type', $this->transactionType);
            }
        };
    }

    private function loadCategories($transactions): \Illuminate\Support\Collection
    {
        $categoryIds = $transactions->pluck('category_id')
            ->filter()
            ->unique()
            ->toArray();
        
        return Category::whereIn('id', $categoryIds)->get()->keyBy('id');
    }

    private function loadAccounts($transactions): \Illuminate\Support\Collection
    {
        $accountIds = [];
        foreach ($transactions as $tx) {
            if ($tx->from_account_id) $accountIds[] = $tx->from_account_id;
            if ($tx->to_account_id) $accountIds[] = $tx->to_account_id;
        }
        
        return FinancialAccount::whereIn('id', array_unique($accountIds))
            ->get()
            ->keyBy('id');
    }

    private function loadCurrencies(): array
    {
        $currenciesData = Currency::where('shop_id', $this->shopId)->get();
        $currencies = [];
        foreach ($currenciesData as $c) {
            $currencies[$c->code] = [
                'name'   => $c->name,
                'symbol' => $c->symbol,
            ];
        }
        return $currencies;
    }
	
	/**
	 * 解析顯示幣別
	 *
	 * 帳戶模式：強制跟隨帳戶幣別（單一幣別視圖）
	 * 期間模式：交易 → 來源帳戶 → 目的帳戶 → 本幣
	 */
	private function resolveDisplayCurrency($tx, string $baseCode): string
	{
		if ($this->accountId && $this->currentAccount) {
			return $this->currentAccount->currency;
		}

		return $tx->currency
			?? $tx->fromAccount?->currency
			?? $tx->toAccount?->currency
			?? $baseCode;
	}

    private function buildTransactionList(
		$transactions,
		$categories,
		$accounts,
		array $currencies
	): array {
		$totalIncome   = '0.0000';
		$totalExpense  = '0.0000';
		$formattedList = [];

		// 幣別服務（含快取）
		$currencyService = app(CurrencyService::class);
		$baseCode        = $currencyService->getBaseCurrencyCode();

		// 帳戶模式：單一幣別，不需換算；期間模式：需換算成本幣
		$needsConversion = !$this->accountId;

		foreach ($transactions as $tx) {
			// 判斷交易類型
			$isTransfer = ($tx->type === 'transfer');
			[$isIncome, $isExpense] = $this->determineIncomeExpense($tx);

			// 原始金額（顯示用）
			$rawAmount = (string) $tx->amount;

			// ============================================================
			// 統計用金額：換算成本幣
			// 幣別來源比照 AccountIndex：交易 → 來源帳戶 → 目的帳戶 → 本幣
			// ============================================================
			$code = $tx->currency
				?? $tx->fromAccount?->currency
				?? $tx->toAccount?->currency
				?? $baseCode;

			$amountInBase = null;

			if ($needsConversion) {
				$currency = $currencyService->getCurrency($code);

				// 找不到幣別：照 AccountIndex 語意跳過統計（不 fallback 成 rate=1）
				if ($currency) {
					$amountInBase = $currency->is_base
						? $rawAmount
						: $currencyService->convertToBase($rawAmount, $code);
				}
			} else {
				// 帳戶模式：直接使用原值（該帳戶單一幣別）
				$amountInBase = $rawAmount;
			}

			// 累加統計（使用換算後金額）
			if ($amountInBase !== null) {
				if ($isIncome) {
					$totalIncome = MoneyCalculator::add($totalIncome, $amountInBase, 4);
				} elseif ($isExpense) {
					$totalExpense = MoneyCalculator::add($totalExpense, $amountInBase, 4);
				}
			}

			// ============================================================
			// 以下為顯示用資料（全部使用原始金額 $rawAmount）
			// ============================================================
			$accountTypeTheme = $this->getAccountTheme($tx);
			[$categoryIcon, $categoryName] = $this->getCategoryInfo($tx, $categories, $isTransfer);
			$accountName = $this->getAccountDisplayName($tx, $accounts);

			// 顯示幣別：帳戶模式強制跟隨帳戶幣別，期間模式按 fallback 解析
			$displayCode = $this->resolveDisplayCurrency($tx, $baseCode);
			$txCurrencySymbol = $currencies[$displayCode]['symbol'] ?? 'NT$';

			$formattedList[] = [
				'id'                  => $tx->id,
				'type'                => $tx->type,
				'amount'              => $tx->amount,
				'recorded_at'         => $tx->recorded_at,
				'memo'                => $tx->memo,
				'category_id'         => $tx->category_id,
				'from_account_id'     => $tx->from_account_id,
				'to_account_id'       => $tx->to_account_id,
				'currency'            => $displayCode,
				'currency_symbol'     => $txCurrencySymbol,
				'display_amount'      => $rawAmount,
				'is_income'           => $isIncome,
				'is_expense'          => $isExpense,
				'is_transfer'         => $isTransfer,
				'category_icon'       => $categoryIcon,
				'category_name'       => $categoryName,
				'from_account_name'   => $accounts[$tx->from_account_id]->name ?? null,
				'to_account_name'     => $accounts[$tx->to_account_id]->name ?? null,
				'account_name'        => $accountName,
				'account_type_theme'  => $accountTypeTheme,
			];
		}

		return [$formattedList, $totalIncome, $totalExpense];
	}

    private function determineIncomeExpense($tx): array
    {
        $isIncome = false;
        $isExpense = false;

        if ($this->accountId) {
            // 帳戶模式
            if ($tx->type === 'income' && $tx->to_account_id == $this->accountId) {
                $isIncome = true;
            } elseif ($tx->type === 'expense' && $tx->from_account_id == $this->accountId) {
                $isExpense = true;
            } elseif ($tx->type === 'transfer') {
                if ($tx->to_account_id == $this->accountId) {
                    $isIncome = true;
                } elseif ($tx->from_account_id == $this->accountId) {
                    $isExpense = true;
                }
            }
        } else {
            // 期間模式
            if ($tx->type === 'income') $isIncome = true;
            if ($tx->type === 'expense') $isExpense = true;
        }

        return [$isIncome, $isExpense];
    }

    private function getAccountTheme($tx): string
    {
        $account = null;
        if ($this->accountId && $this->currentAccount) {
            $account = $this->currentAccount;
        } else {
            $account = $tx->fromAccount ?? $tx->toAccount;
        }

        $typeConfig = config("business.account_types.{$account?->type}") 
            ?? config("business.account_types.cash");
        
        return $typeConfig['theme'] ?? 'orange';
    }

    private function getCategoryInfo($tx, $categories, bool $isTransfer): array
    {
        if ($isTransfer) {
            return ['arrow-path', null];
        }

        if ($tx->category_id && isset($categories[$tx->category_id])) {
            $category = $categories[$tx->category_id];
            return [$category->icon ?? 'folder', $category->name];
        }

        return ['folder', null];
    }

    private function getAccountDisplayName($tx, $accounts): string
    {
        $fromAccountName = $accounts[$tx->from_account_id]->name ?? null;
        $toAccountName = $accounts[$tx->to_account_id]->name ?? null;

        if ($this->accountId) {
            return $this->currentAccount?->name ?? '';
        }

        if ($tx->type === 'transfer') {
            if ($fromAccountName && $toAccountName) {
                return $fromAccountName . ' ➔ ' . $toAccountName;
            }
            return $fromAccountName ?? $toAccountName ?? '未知帳戶';
        }

        if ($tx->type === 'income') {
            return $toAccountName ?? $fromAccountName ?? '未知帳戶';
        }

        if ($tx->type === 'expense') {
            return $fromAccountName ?? $toAccountName ?? '未知帳戶';
        }

        return $fromAccountName ?? $toAccountName ?? '未知帳戶';
    }

    /**
	 * 正確導出歷史期初期末餘額
	 */
	private function calculateBalances(string $netAmount, \Carbon\Carbon $endDate): array
	{
		$openingBalance = '0.0000';
		$closingBalance = '0.0000';

		if ($this->accountId && $this->currentAccount) {
			// 期末金額 = 截至該月份最後一天 23:59:59 的累積餘額
			$closingBalance = $this->currentAccount->getBalanceAtDate($endDate);
			
			// 期初金額 = 該月期末金額 - 該月淨收益
			$openingBalance = bcsub($closingBalance, $netAmount, 4);
		}

		return [$openingBalance, $closingBalance];
	}

    private function getDefaultTitle(): string
    {
        return match ($this->dateMode) {
            'day'   => '本日交易明細',
            'year'  => '年度交易統計',
            default => '交易明細列表',
        };
    }

    private function formatDateForMode(string $date, string $mode): string
    {
        $carbon = Carbon::parse($date);
        return match ($mode) {
            'day'   => $carbon->format('Y-m-d'),
            'year'  => $carbon->format('Y'),
            default => $carbon->format('Y-m'),
        };
    }

    private function getDateFormat(): string
    {
        return match ($this->dateMode) {
            'day'   => 'Y-m-d',
            'year'  => 'Y',
            default => 'Y-m',
        };
    }

    private function resetFilter(): void
    {
        $this->accountId = null;
        $this->categoryId = null;
        $this->transactionType = null;
        $this->currentAccount = null;
    }

    private function resetTransactionsData(): void
    {
        $this->transactionsData = [
            'list'          => [],
            'total_income'  => '0.0000',
            'total_expense' => '0.0000',
            'total_count'   => 0,
        ];
        $this->statsSummary = [];
    }

    // ========== 公開方法（供 Blade 呼叫） ==========

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->dispatch('modal-closed');
    }

    public function editAccount(): void
    {
        if (!$this->accountId) return;
        $this->showModal = false;
        $this->dispatch('edit-account-from-transactions', accountId: $this->accountId);
    }

    public function toggleAccountVisibility(): void
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

    // ========== 渲染 ==========
    public function render()
    {
        return view('livewire.finance.transaction-list-modal', [
            'dateDisplay'     => $this->dateDisplay,
            'isCurrentDate'   => $this->isCurrentDate,
            'dateMode'        => $this->dateMode,
            'totalIncome'     => $this->totalIncome,
            'totalExpense'    => $this->totalExpense,
            'baseSymbol'      => $this->baseSymbol,
            'transactionCount'=> $this->transactionCount,
        ]);
    }
}