<?php
// app/Livewire/Finance/AccountIndex.php

namespace App\Livewire\Finance;

use App\Models\FinancialAccount;
use App\Models\Transaction;
use Carbon\Carbon;
use Livewire\Attributes\On;
use Livewire\Component;
use Mary\Traits\Toast;

class AccountIndex extends Component
{
    use Toast;
    
    // ============ Modal 控制 ============
    // public bool $showPeriodDetailModal = false;
    public bool $showAccountModal = false;
    
    // ============ 基本設定 ============
    public $shopId = 1;
    public ?int $editingAccountId = null;
    
    // ============ 帳戶管理表單 ============
    public string $accountName = '';
    public string $accountType = 'cash';
    public string $accountBalance = '0.00';
    public string $accountCurrency = '';
    public string $creditLimit = '0.00';
    public string $accountMemo = ''; 

    // ============ 生命週期與事件 ============
    
    public function mount()
    {
        $this->accountCurrency = $this->getBaseCurrency();
    }
    
    #[On('refresh-data')]
    public function onDataChanged()
    {
        // 重新渲染畫面
    }

    /**
     * 開啟通用交易流水 Modal (呼叫獨立組件)
     */
    public function viewAccountTransactions(int $accountId)
    {
        $this->dispatch('open-transaction-list-modal', params: [
            'accountId' => $accountId,
			'dateMode' => 'month',
        ]);
    }

    /**
     * 從 TransactionListModal 觸發編輯帳戶
     */
    #[On('edit-account-from-transactions')]
    public function handleEditAccountFromTransactions(int $accountId)
    {
        $this->editAccountFromList($accountId);
    }

    // ============ 匯率與 BCMath 工具 ============
    
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
    
    private function getExchangeRate(string $currency): float
    {
        $currencies = config('business.currencies', []);
        return (float)($currencies[$currency]['rate'] ?? 1);
    }
    
    private function convertToBase(string $amount, string $currency): string
    {
        if (!is_numeric($amount) || $amount === '') {
            return '0.0000';
        }
        
        $rate = $this->getExchangeRate($currency);
        return bcmul($amount, (string)$rate, 4) ?: '0.0000';
    }

    // ============ 頂部統計數據計算 ============
    
    public function getPeriodStatsProperty()
    {
        $now = Carbon::now();
        $periods = [
            'today' => ['start' => $now->copy()->startOfDay(), 'end' => $now->copy()->endOfDay()],
            'month' => ['start' => $now->copy()->startOfMonth(), 'end' => $now->copy()->endOfMonth()],
            'year'  => ['start' => $now->copy()->startOfYear(), 'end' => $now->copy()->endOfYear()],
        ];

        $stats = [
            'today' => ['income' => '0.00', 'expense' => '0.00', 'details' => []],
            'month' => ['income' => '0.00', 'expense' => '0.00', 'details' => []],
            'year'  => ['income' => '0.00', 'expense' => '0.00', 'details' => []],
        ];

        $currencies = config('business.currencies', []);
        $baseCurrency = $this->getBaseCurrency();
        $baseSymbol = $this->getBaseCurrencySymbol();

        foreach ($periods as $key => $range) {
            try {
                $transactions = Transaction::query()
                    ->where('shop_id', $this->shopId)
                    ->whereBetween('recorded_at', [$range['start'], $range['end']])
                    ->get();

                $incomeTotal = '0.0000';
                $expenseTotal = '0.0000';
                $currencyDetails = [];

                foreach ($transactions as $tx) {
                    $currency = $tx->currency;
                    $amount = (string)$tx->amount;
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
                        $currencyDetails[$currency]['income'] = bcadd($currencyDetails[$currency]['income'], $amount, 4);
                    } elseif ($tx->type === 'expense') {
                        $currencyDetails[$currency]['expense'] = bcadd($currencyDetails[$currency]['expense'], $amount, 4);
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
                \Log::error('Period stats error: ' . $e->getMessage());
                continue;
            }
        }
        
        return $stats;
    }

    public function showPeriodDetail($period)
    {
        $periodTitles = [
            'today' => '日',
            'month' => '月',
            'year'  => '年'
        ];
        
		$dateMode = $period === 'today' ? 'day' : $period;
		
        $this->dispatch('open-transaction-list-modal', params: [
			'dateMode' => $dateMode, // 'today', 'month', 'year'
			'baseDate' => now()->format('Y-m-d'),
			'title' => $periodTitles[$period] ?? '交易明細',
			'type' => null, // 顯示全部交易
		]);
    }

    // ============ 帳戶新增 / 編輯管理 ============
    
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
        $this->dispatch('refresh-data');
    }

    // ============ Render ============
    
    public function render()
    {
        $accounts = FinancialAccount::where('is_active', true)->get();
        $currencyConfig = config('business.currencies', []);

        $currencyGroups = [];
        foreach ($currencyConfig as $code => $info) {
            $groupAccounts = $accounts->where('currency', $code);
            $totalBalance = $groupAccounts->reduce(function ($carry, $account) {
                return bcadd($carry, (string)$account->balance, 4);
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
        foreach (config('business.account_types', []) as $key => $value) {
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