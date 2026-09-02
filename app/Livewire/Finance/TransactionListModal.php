<?php // app/Livewire/Finance/TransactionListModal.php

namespace App\Livewire\Finance;

use App\Models\Category;
use App\Models\FinancialAccount;
use App\Models\Transaction;
use App\Services\CurrencyService;
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

    // 交易資料
    public array $transactionsData = [];
    
    // 統計摘要
    public array $statsSummary = [
        'month_expense' => '0.00',
        'month_income' => '0.00',
        'year_expense' => '0.00',
        'year_income' => '0.00',
    ];

    // 關聯實體快照
    public ?FinancialAccount $currentAccount = null;
    
    protected CurrencyService $currencyService;

    // ========== 計算屬性 ==========
    
    #[Computed]
    public function dateDisplay(): string
    {
        $date = $this->parseCurrentDate();
        return match ($this->dateMode) {
            'day' => $date->format('Y 年 m 月 d 日'),
            'year' => $date->format('Y 年'),
            default => $date->format('Y 年 m 月'),
        };
    }

    #[Computed]
    public function isCurrentDate(): bool
    {
        $date = $this->parseCurrentDate();
        return match ($this->dateMode) {
            'day' => $date->isToday(),
            'year' => $date->isCurrentYear(),
            default => $date->isCurrentMonth(),
        };
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
    public function baseSymbol(): string
    {
        return $this->currencySymbol;
    }

    #[Computed]
    public function transactionCount(): int
    {
        return $this->transactionsData['total_count'] ?? 0;
    }

    // ========== 生命週期方法 ==========

    public function boot(CurrencyService $currencyService)
    {
        $this->currencyService = $currencyService;
    }

    public function mount()
    {
        // 預設以「月」為單位
        $this->currentDate = now()->format('Y-m');
        $this->dateMode = 'month';
        $this->shopId = (int) session('current_shop_id', 1);
        $this->resetTransactionsData();
    }

    /**
     * 當日期變更時重新載入資料
     */
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
        if (!is_array($params)) {
            $params = [];
        }

        $this->resetFilter();

        $this->accountId = $params['accountId'] ?? null;
        $this->categoryId = $params['categoryId'] ?? null;
        $this->transactionType = $params['type'] ?? null;
        
        // 設定日期模式（從參數傳入）
        // 支援: 'day', 'month', 'year'
        $this->dateMode = $params['dateMode'] ?? 'month';
        
        // 設定標題
        $this->title = $params['title'] ?? $this->getDefaultTitle();
        
        // 設定 currentDate
        if (isset($params['baseDate'])) {
            $this->currentDate = $this->formatDateForMode($params['baseDate'], $this->dateMode);
        } else {
            $this->currentDate = now()->format($this->getDateFormat());
        }

        // 1. 設定目標幣別
        if ($this->accountId) {
            $account = FinancialAccount::find($this->accountId);
            if (!$account) {
                $this->toast(type: 'error', title: '找不到該帳戶');
                return;
            }
            $this->currentAccount = $account;
            $this->targetCurrency = $account->currency;
        } else {
            $this->targetCurrency = $this->getBaseCurrency();
        }

        // 2. 取得幣別符號
        $currencyData = $this->currencyService->getCurrency($this->targetCurrency);
        $this->currencySymbol = $currencyData ? $currencyData->symbol : 'NT$';

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

    // ========== 輔助方法 ==========

    /**
     * 獲取預設標題
     */
    private function getDefaultTitle(): string
    {
        return match ($this->dateMode) {
            'day' => '本日交易明細',
            'year' => '年度交易統計',
            default => '交易明細列表',
        };
    }

    /**
     * 根據模式格式化日期
     */
    private function formatDateForMode(string $date, string $mode): string
    {
        $carbon = Carbon::parse($date);
        return match ($mode) {
            'day' => $carbon->format('Y-m-d'),
            'year' => $carbon->format('Y'),
            default => $carbon->format('Y-m'),
        };
    }

    /**
     * 獲取日期格式
     */
    private function getDateFormat(): string
    {
        return match ($this->dateMode) {
            'day' => 'Y-m-d',
            'year' => 'Y',
            default => 'Y-m',
        };
    }

    /**
     * 載入交易明細
     */
    public function loadTransactions()
    {
        $currentDate = $this->parseCurrentDate();
        
        // 根據模式計算開始和結束日期
        $start = match ($this->dateMode) {
            'day' => $currentDate->copy()->startOfDay(),
            'year' => $currentDate->copy()->startOfYear(),
            default => $currentDate->copy()->startOfMonth(),
        };
        $end = match ($this->dateMode) {
            'day' => $currentDate->copy()->endOfDay(),
            'year' => $currentDate->copy()->endOfYear(),
            default => $currentDate->copy()->endOfMonth(),
        };

        // 計算年度範圍（用於年度統計）
        $yearStart = $currentDate->copy()->startOfYear();
        $yearEnd = $currentDate->copy()->endOfYear();

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
        $periodTxs = Transaction::query()
            ->where($applyFilter)
            ->whereBetween('recorded_at', [$start, $end])
            ->get();
            
        $yearTxs = Transaction::query()
            ->where($applyFilter)
            ->whereBetween('recorded_at', [$yearStart, $yearEnd])
            ->get();

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
            
            // 獲取帳戶並決定主題色
            $account = null;
            if ($this->accountId && $this->currentAccount) {
                $account = $this->currentAccount;
            } else {
                $account = $tx->fromAccount ?? $tx->toAccount;
            }
                
            $typeConfig = config("business.account_types.{$account?->type}") ?? config("business.account_types.cash");
            $accountTypeTheme = $typeConfig['theme'] ?? 'orange';
    
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

            // 組裝單筆交易資料
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
                'account_type_theme' => $accountTypeTheme,    
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

    // ========== 貨幣轉換方法 ==========

    private function getBaseCurrency(): string
    {
        return $this->currencyService->getBaseCurrencyCode();
    }

    private function getBaseCurrencySymbol(): string
    {
        return $this->currencyService->getBaseCurrencySymbol();
    }

    private function convertToBase(string $amount, string $currency): string
    {
        return $this->currencyService->convertToBase($amount, $currency);
    }

    private function convertFromBase(string $amount, string $currency): string
    {
        return $this->currencyService->convertFromBase($amount, $currency);
    }

    // ========== render 方法 ==========

    public function render()
    {
        return view('livewire.finance.transaction-list-modal', [
            // 日期相關（根據模式顯示不同格式）
            'dateDisplay' => $this->dateDisplay,
            'isCurrentDate' => $this->isCurrentDate,
            'dateMode' => $this->dateMode,
            
            // 統計數據
            'totalIncome' => $this->totalIncome,
            'totalExpense' => $this->totalExpense,
            'baseSymbol' => $this->baseSymbol,
            'transactionCount' => $this->transactionCount,
        ]);
    }
}