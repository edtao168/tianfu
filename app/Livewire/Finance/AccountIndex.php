<?php
// app/Livewire/Finance/AccountIndex.php

namespace App\Livewire\Finance;

use App\Models\FinancialAccount;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\TransactionTemplate;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;
use Mary\Traits\Toast;

class AccountIndex extends Component
{
    use Toast;
    
    // ============ Modal 控制 ============
    public bool $showTemplateModal = false;
    public bool $showPeriodDetailModal = false;
    public bool $showAccountTransactionsModal = false;
    public bool $showAccountModal = false;
    public bool $showTransactionModal = false;
    
    // ============ 基本設定 ============
    public $shopId = 1;
    public ?int $editingAccountId = null;
    
    // ============ 記帳表單 ============
    public string $type = 'expense';
    public string $amount = '';
    public ?int $accountId = 5;
    public ?int $toAccountId = null;
    public ?int $categoryId = 2;
    public string $recordedAt = '';
    public string $memo = '';
    public array $quickAmounts = [100, 200, 500, 1000, 2000, 5000];
    
    // ============ 帳戶管理表單 ============
    public string $accountName = '';
    public string $accountType = 'cash';
    public string $accountBalance = '0.00';
    public string $accountCurrency = '';
    public string $creditLimit = '0.00';
    public string $accountMemo = ''; 
    
    // ============ 範本管理表單 ============
    public string $templateType = 'expense';
    public string $templateName = '';
    public string $templateAmount = '';
    public ?int $templateAccountId = null;
    public ?int $templateToAccountId = null;
    public ?int $templateCategoryId = null;
    public string $templateMemo = '';
    public ?int $editingTemplateId = null;
    
    // ============ 分幣別詳情 ============
    public string $periodDetailTitle = '';
    public array $periodDetailData = [];
    public array $periodDetailSummary = [];
    public bool $canDeletePeriodDetail = false;
    
    // ============ 帳戶流水 ============
    public ?int $selectedAccountId = null;
    public string $selectedAccountName = '';
    public string $selectedAccountCurrency = '';
    public string $selectedCurrencySymbol = 'NT$';
    public array $accountTransactions = [];
    public string $transactionMonth = '';
    public ?FinancialAccount $selectedAccount = null;

    // ============ 生命週期 ============
    
    public function mount()
    {
        $this->recordedAt = now()->format('Y-m-d\TH:i');
        $this->transactionMonth = now()->format('Y-m');
        $this->accountCurrency = $this->getBaseCurrency();
        
        $this->accountTransactions = [
            'list' => [],
            'total_income' => '0.0000',
            'total_expense' => '0.0000',
            'total_count' => 0,
        ];
    }
    
    #[On('refresh-data')]
    public function onDataChanged()
    {
        if ($this->selectedAccountId) {
            $this->loadAccountTransactions();
        }
    }

    // ============ 匯率工具方法 ============
    
    private function getBaseCurrency(): string
    {
        return config('business.base_currency', 'TWD');
    }
    
    private function getBaseCurrencySymbol(): string
    {
        $base = $this->getBaseCurrency();
        $currencies = config('business.currencies');
        return $currencies[$base]['symbol'] ?? 'NT$';
    }
    
    private function getExchangeRate(string $currency): float
    {
        $currencies = config('business.currencies');
        
        if (!isset($currencies[$currency])) {
            \Log::warning('Currency not found, using default rate 1', ['currency' => $currency]);
            return 1;
        }
        
        return (float)($currencies[$currency]['rate'] ?? 1);
    }
    
    private function convertToBase(string $amount, string $currency): string
    {
        if (!is_numeric($amount) || $amount === '') {
            return '0.0000';
        }
        
        $rate = $this->getExchangeRate($currency);
        $result = bcmul($amount, (string)$rate, 4);
        
        return $result ?: '0.0000';
    }
    
    private function convertFromBase(string $amount, string $currency): string
    {
        if (!is_numeric($amount) || $amount === '') {
            return '0.0000';
        }
        
        $rate = $this->getExchangeRate($currency);
        if ($rate == 0) {
            return '0.0000';
        }
        
        $result = bcdiv($amount, (string)$rate, 4);
        return $result ?: '0.0000';
    }

    private function getTransactionCurrency($tx): string
    {
        $account = null;
        $currency = $this->getBaseCurrency();

        if ($tx->type === 'income' && $tx->to_account_id) {
            $account = FinancialAccount::find($tx->to_account_id);
        } elseif ($tx->type === 'expense' && $tx->from_account_id) {
            $account = FinancialAccount::find($tx->from_account_id);
        } elseif ($tx->type === 'transfer') {
            $account = $tx->from_account_id 
                ? FinancialAccount::find($tx->from_account_id)
                : FinancialAccount::find($tx->to_account_id);
        }

        if ($account) {
            $currency = $account->currency ?? $this->getBaseCurrency();
        }

        return $currency;
    }

    // ============ 統計功能 ============
    
    public function getPeriodStatsProperty()
    {
        $now = Carbon::now();
        $periods = [
            'today' => [
                'start' => $now->copy()->startOfDay(),
                'end' => $now->copy()->endOfDay()
            ],
            'month' => [
                'start' => $now->copy()->startOfMonth(),
                'end' => $now->copy()->endOfMonth()
            ],
            'year' => [
                'start' => $now->copy()->startOfYear(),
                'end' => $now->copy()->endOfYear()
            ],
        ];

        $stats = [
            'today' => ['income' => '0.00', 'expense' => '0.00', 'details' => []],
            'month' => ['income' => '0.00', 'expense' => '0.00', 'details' => []],
            'year' => ['income' => '0.00', 'expense' => '0.00', 'details' => []],
        ];

        $currencies = config('business.currencies');
        $baseCurrency = $this->getBaseCurrency();
        $baseSymbol = $this->getBaseCurrencySymbol();

        foreach ($periods as $key => $range) {
            try {
                $transactions = Transaction::query()
                    ->with(['fromAccount', 'toAccount'])
                    ->where('shop_id', $this->shopId)
                    ->whereBetween('recorded_at', [$range['start'], $range['end']])
                    ->get();

                $incomeTotal = '0.0000';
                $expenseTotal = '0.0000';
                $currencyDetails = [];

                foreach ($transactions as $tx) {
                    $currency = $tx->currency;
                    $amount = $tx->amount;

                    $amountInBase = $this->convertToBase($amount, $currency);

                    if ($tx->type === 'income') {
                        $incomeTotal = bcadd($incomeTotal, $amountInBase, 4);
                    } elseif ($tx->type === 'expense') {
                        $expenseTotal = bcadd($expenseTotal, $amountInBase, 4);
                    }

                    if (!isset($currencyDetails[$currency])) {
                        $currencyDetails[$currency] = [
                            'income' => '0.0000',
                            'expense' => '0.0000',
                            'currency_symbol' => $currencies[$currency]['symbol'] ?? '',
                            'currency_name' => $currencies[$currency]['name'] ?? $currency,
                            'rate' => $currencies[$currency]['rate'] ?? 1,
                            'bg' => $currencies[$currency]['bg'] ?? '',
                            'symbol_color' => $currencies[$currency]['symbol_color'] ?? '',
                            'tag' => $currencies[$currency]['tag'] ?? '',
                        ];
                    }

                    if ($tx->type === 'income') {
                        $currencyDetails[$currency]['income'] = bcadd(
                            $currencyDetails[$currency]['income'],
                            $amount,
                            4
                        );
                    } elseif ($tx->type === 'expense') {
                        $currencyDetails[$currency]['expense'] = bcadd(
                            $currencyDetails[$currency]['expense'],
                            $amount,
                            4
                        );
                    }
                }

                $stats[$key] = [
                    'income' => number_format((float)$incomeTotal, 2),
                    'expense' => number_format((float)$expenseTotal, 2),
                    'details' => $currencyDetails,
                    'base_currency' => $baseCurrency,
                    'base_symbol' => $baseSymbol,
                ];
            } catch (\Exception $e) {
                \Log::error('Period stats error: ' . $e->getMessage(), [
                    'period' => $key,
                    'trace' => $e->getTraceAsString()
                ]);
                continue;
            }
        }
        
        return $stats;
    }

    public function showPeriodDetail($period)
    {
        $periodTitles = [
            'today' => '本日收支明細（分幣別）',
            'month' => '本月收支明細（分幣別）',
            'year' => '本年收支明細（分幣別）'
        ];
        
        $stats = $this->periodStats;
        $this->periodDetailTitle = $periodTitles[$period] ?? '收支明細';
        $this->periodDetailData = $stats[$period]['details'] ?? [];
        
        $totalIncomeBase = '0.0000';
        $totalExpenseBase = '0.0000';
        foreach ($this->periodDetailData as $currency => $data) {
            $totalIncomeBase = bcadd(
                $totalIncomeBase,
                $this->convertToBase($data['income'], $currency),
                4
            );
            $totalExpenseBase = bcadd(
                $totalExpenseBase,
                $this->convertToBase($data['expense'], $currency),
                4
            );
        }
        
        $this->periodDetailSummary = [
            'total_income' => number_format((float)$totalIncomeBase, 2),
            'total_expense' => number_format((float)$totalExpenseBase, 2),
            'base_currency' => $this->getBaseCurrency(),
            'base_symbol' => $this->getBaseCurrencySymbol(),
        ];
        
        $this->canDeletePeriodDetail = !empty($this->periodDetailData);
        $this->showPeriodDetailModal = true;
    }

    // ============ 帳戶流水功能 ============
    
    public function viewAccountTransactions($accountId)
    {
        $account = FinancialAccount::findOrFail($accountId);
        $this->selectedAccountId = $accountId;
        $this->selectedAccountName = $account->name;
        $this->selectedAccountCurrency = $account->currency;
        $this->selectedAccount = $account;
        
        $currencies = config('business.currencies');
        $this->selectedCurrencySymbol = $currencies[$account->currency]['symbol'] ?? 'NT$';
        
        $this->loadAccountTransactions();
        $this->showAccountTransactionsModal = true;
    }

    public function loadAccountTransactions()
    {
        if (!$this->selectedAccountId) {
            return;
        }
        
        $startDate = Carbon::createFromFormat('Y-m', $this->transactionMonth)->startOfMonth();
        $endDate = Carbon::createFromFormat('Y-m', $this->transactionMonth)->endOfMonth();
        
        $account = FinancialAccount::find($this->selectedAccountId);
        if (!$account) {
            $this->accountTransactions = [
                'list' => [],
                'total_income' => '0.0000',
                'total_expense' => '0.0000',
                'total_count' => 0,
            ];
            return;
        }
        
        $accountCurrency = $account->currency ?? $this->getBaseCurrency();
        
        $transactions = Transaction::query()
            ->where('shop_id', $this->shopId)
            ->whereBetween('recorded_at', [$startDate, $endDate])
            ->where(function ($query) {
                $query->where('from_account_id', $this->selectedAccountId)
                      ->orWhere('to_account_id', $this->selectedAccountId);
            })
            ->orderBy('recorded_at', 'desc')
            ->get();
        
        $categoryIds = $transactions->pluck('category_id')->filter()->unique()->toArray();
        $categories = Category::whereIn('id', $categoryIds)->get()->keyBy('id');
        
        $accountIds = [];
        foreach ($transactions as $tx) {
            if ($tx->type === 'transfer') {
                if ($tx->from_account_id) $accountIds[] = $tx->from_account_id;
                if ($tx->to_account_id) $accountIds[] = $tx->to_account_id;
            }
        }
        $accounts = FinancialAccount::whereIn('id', array_unique($accountIds))->get()->keyBy('id');
        
        $totalIncome = '0.0000';
        $totalExpense = '0.0000';
        $formattedList = [];
        
        foreach ($transactions as $tx) {
            $isTransfer = ($tx->type === 'transfer');
            $isIncome = false;
            $isExpense = false;
            $amountInAccountCurrency = '0.0000';
            
            if ($tx->type === 'income' && $tx->to_account_id == $this->selectedAccountId) {
                $isIncome = true;
                $amountInAccountCurrency = $this->convertToAccountCurrency((string)$tx->amount, $tx->currency, $accountCurrency);
            } elseif ($tx->type === 'expense' && $tx->from_account_id == $this->selectedAccountId) {
                $isExpense = true;
                $amountInAccountCurrency = $this->convertToAccountCurrency((string)$tx->amount, $tx->currency, $accountCurrency);
            } elseif ($isTransfer) {
                if ($tx->to_account_id == $this->selectedAccountId) {
                    $isIncome = true;
                    $amountInAccountCurrency = $this->convertToAccountCurrency((string)$tx->amount, $tx->currency, $accountCurrency);
                } elseif ($tx->from_account_id == $this->selectedAccountId) {
                    $isExpense = true;
                    $amountInAccountCurrency = $this->convertToAccountCurrency((string)$tx->amount, $tx->currency, $accountCurrency);
                }
            }

            if ($isIncome) {
                $totalIncome = bcadd($totalIncome, $amountInAccountCurrency, 4);
            } elseif ($isExpense) {
                $totalExpense = bcadd($totalExpense, $amountInAccountCurrency, 4);
            }
            
            $categoryIcon = 'folder';
            $categoryName = null;
            
            if ($isTransfer) {
                $categoryIcon = 'arrow-path';
            } elseif ($tx->category_id && isset($categories[$tx->category_id])) {
                $category = $categories[$tx->category_id];
                $categoryIcon = $category->icon ?? 'folder';
                $categoryName = $category->name;
            }
            
            $fromAccountName = null;
            $toAccountName = null;
            if ($isTransfer) {
                if ($tx->from_account_id && isset($accounts[$tx->from_account_id])) {
                    $fromAccountName = $accounts[$tx->from_account_id]->name;
                }
                if ($tx->to_account_id && isset($accounts[$tx->to_account_id])) {
                    $toAccountName = $accounts[$tx->to_account_id]->name;
                }
            }
            
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
                'amount_in_account_currency' => $amountInAccountCurrency,
                'is_income' => $isIncome,
                'is_expense' => $isExpense,
                'is_transfer' => $isTransfer,
                'category_icon' => $categoryIcon,
                'category_name' => $categoryName,
                'from_account_name' => $fromAccountName,
                'to_account_name' => $toAccountName,
            ];
        }

        // 強制寫回 component state 屬性
        $this->accountTransactions = [
            'list' => $formattedList,
            'total_income' => $totalIncome,
            'total_expense' => $totalExpense,
            'total_count' => $transactions->count(),
        ];
    }
    
    private function convertToAccountCurrency(string $amount, string $fromCurrency, string $toCurrency): string
    {
        if (!is_numeric($amount) || $amount === '') {
            return '0.0000';
        }
        
        if ($fromCurrency === $toCurrency) {
            return bcadd($amount, '0.0000', 4);
        }
        
        $amountInBase = $this->convertToBase($amount, $fromCurrency);
        $result = $this->convertFromBase($amountInBase, $toCurrency);
        
        return $result ?: '0.0000';
    }

    public function previousMonth()
    {
        $date = Carbon::createFromFormat('Y-m', $this->transactionMonth)->subMonth();
        $this->transactionMonth = $date->format('Y-m');
        $this->loadAccountTransactions();
    }

    public function nextMonth()
    {
        $date = Carbon::createFromFormat('Y-m', $this->transactionMonth)->addMonth();
        if ($date->isFuture()) {
            $date = now();
        }
        $this->transactionMonth = $date->format('Y-m');
        $this->loadAccountTransactions();
    }

    // ============ 帳戶操作（從流水 Modal） ============
    
    public function editAccount()
    {
        if (!$this->selectedAccountId) {
            $this->toast(type: 'error', title: '請先選擇帳戶');
            return;
        }
        
        $this->showAccountTransactionsModal = false;
        $this->editAccountDirect($this->selectedAccountId);
    }

    private function editAccountDirect(int $accountId)
    {
        $account = FinancialAccount::findOrFail($accountId);
        $this->editingAccountId = $account->id;
        $this->accountName = $account->name;
        $this->accountType = $account->type;
        $this->accountBalance = number_format((float)$account->balance, 2, '.', '');
        $this->accountCurrency = $account->currency;
        $this->showAccountModal = true;
    }

    public function deleteAccount()
    {
        if (!$this->selectedAccountId) {
            $this->toast(type: 'error', title: '請先選擇帳戶');
            return;
        }

        try {
            $hasTransactions = Transaction::where('from_account_id', $this->selectedAccountId)
                ->orWhere('to_account_id', $this->selectedAccountId)
                ->exists();

            if ($hasTransactions) {
                $this->toast(type: 'error', title: '此帳戶有交易記錄，無法刪除');
                return;
            }

            $account = FinancialAccount::findOrFail($this->selectedAccountId);
            $accountName = $account->name;
            $account->delete();

            $this->toast(type: 'success', title: "已刪除帳戶：{$accountName}");
            
            $this->showAccountTransactionsModal = false;
            $this->dispatch('refresh-data');
            
        } catch (\Exception $e) {
            \Log::error('Delete account error: ' . $e->getMessage());
            $this->toast(type: 'error', title: '刪除失敗：' . $e->getMessage());
        }
    }

    public function toggleAccountVisibility()
    {
        if (!$this->selectedAccountId) {
            $this->toast(type: 'error', title: '請先選擇帳戶');
            return;
        }

        try {
            $account = FinancialAccount::findOrFail($this->selectedAccountId);
            $newStatus = !$account->is_active;
            $account->update(['is_active' => $newStatus]);

            $statusText = $newStatus ? '顯示' : '隱藏';
            $this->toast(type: 'success', title: "已{$statusText}帳戶：{$account->name}");
            
            $this->selectedAccount = $account->fresh();
            $this->dispatch('refresh-data');
            
        } catch (\Exception $e) {
            \Log::error('Toggle account visibility error: ' . $e->getMessage());
            $this->toast(type: 'error', title: '操作失敗：' . $e->getMessage());
        }
    }

    #[On('edit-transaction')]
    public function editTransaction($transactionId)
    {
        try {
            $transaction = Transaction::find($transactionId);
            if (!$transaction) {
                $this->toast(type: 'error', title: '交易不存在');
                return;
            }

            $this->showAccountTransactionsModal = false;
            $this->dispatch('open-transaction-modal', transactionId: $transactionId);
            
        } catch (\Exception $e) {
            \Log::error('Edit transaction error: ' . $e->getMessage());
            $this->toast(type: 'error', title: '無法編輯交易：' . $e->getMessage());
        }
    }

    public function editTransactionFromCard($transactionId)
    {
        $this->editTransaction($transactionId);
    }

    // ============ 帳戶管理 ============
    
    public function editAccountFromList(int $id)
    {
        $account = FinancialAccount::findOrFail($id);
        $this->editingAccountId = $account->id;
        $this->accountName = $account->name;
        $this->accountType = $account->type;
        $this->accountBalance = number_format((float)$account->balance, 2, '.', '');
        $this->accountCurrency = $account->currency;
        $this->accountMemo = $account->memo ?? '';
        $this->showAccountModal = true;
    }

    public function openCreateModal()
    {
        $this->reset([
            'editingAccountId',
            'accountName',
            'accountType',
            'accountBalance',
            'accountCurrency',
            'creditLimit',
            'accountMemo'
        ]);
        $this->accountCurrency = $this->getBaseCurrency();
        $this->showAccountModal = true;
    }

    public function saveAccount()
    {
        $validTypes = implode(',', array_keys(config('business.account_types')));
        $validCurrencies = implode(',', array_keys(config('business.currencies')));

        $this->validate([
            'accountName' => 'required|string|max:50',
            'accountType' => "required|in:{$validTypes}",
            'accountCurrency' => "required|in:{$validCurrencies}",
            'accountBalance' => 'required|numeric|min:0',
        ]);

        $formattedBalance = bcadd($this->accountBalance, '0.0000', 4);

        if ($this->editingAccountId) {
            $account = FinancialAccount::findOrFail($this->editingAccountId);
            $account->update([
                'name'     => $this->accountName,
                'type'     => $this->accountType,
                'balance'  => $formattedBalance,
                'currency' => $this->accountCurrency,
                'memo'     => $this->accountMemo ?? '',
            ]);
            $this->toast(type: 'success', title: '帳戶更新成功！');
        } else {
            FinancialAccount::create([
                'name'      => $this->accountName,
                'type'      => $this->accountType,
                'balance'   => $formattedBalance,
                'currency'  => $this->accountCurrency,
                'memo'      => $this->accountMemo ?? '',
                'is_active' => true,
            ]);
            $this->toast(type: 'success', title: '帳戶建立成功！');
        }

        $this->reset([
            'editingAccountId',
            'accountName',
            'accountType',
            'accountBalance',
            'accountCurrency',
            'creditLimit'
        ]);
        $this->showAccountModal = false;
    }

    // ============ Render ============
    
    public function render()
    {
        $accounts = FinancialAccount::where('is_active', true)->get();
        $currencyConfig = config('business.currencies');

        $currencyGroups = [];
        foreach ($currencyConfig as $code => $info) {
            $groupAccounts = $accounts->where('currency', $code);
            $totalBalance = $groupAccounts->reduce(function ($carry, $account) {
                return bcadd($carry, $account->balance, 4);
            }, '0.0000');

            $currencyGroups[] = [
                'currency' => $code,
                'currency_name' => $info['name'],
                'currency_symbol' => $info['symbol'],
                'total_balance' => (float)$totalBalance,
                'accounts' => $groupAccounts,
                'bg' => $info['bg'] ?? '',
                'symbol_color' => $info['symbol_color'] ?? '',
                'tag' => $info['tag'] ?? '',
            ];
        }

        $accountTypeOptions = [];
        foreach (config('business.account_types') as $key => $value) {
            $accountTypeOptions[] = ['id' => $key, 'name' => $value['name']];
        }

        return view('livewire.finance.account-index', [
            'currencyGroups' => $currencyGroups,
            'availableCurrencies' => $currencyConfig,
            'accountTypeOptions' => $accountTypeOptions,
            'accountCurrency' => $this->accountCurrency,
        ])->layout('components.layouts.app');
    }
}