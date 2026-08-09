<?php
// app/Livewire/Finance/AccountIndex.php

namespace App\Livewire\Finance;

use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\Attributes\Computed;
use App\Models\Currency;
use App\Models\FinancialAccount;
use App\Models\Transaction;
use App\Models\Shop;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AccountIndex extends Component
{
    // ============================================================
    // 表單屬性
    // ============================================================
    public $showAccountModal = false;
    public $editingAccountId = null;
    public $accountName = '';
    public $accountType = 'cash';
    public $accountCurrency = 'TWD';
    public $accountBalance = 0;
    public $accountMemo = '';
    public $currentShopId;
    public $autoRefresh = true;
    
    // 統計資料
    public $periodStats = [];
    
    // ============================================================
    // 初始化
    // ============================================================
    public function mount()
    {
        $this->currentShopId = session('current_shop_id', Shop::first()?->id);
        $this->loadPeriodStats();
    }
    
    // ============================================================
    // 計算屬性
    // ============================================================
    
    #[Computed]
    public function shops()
    {
        return Shop::all();
    }
    
    #[Computed]
    public function currencyGroups()
    {
        $groups = [];
        $currencies = Currency::where('is_active', true)
            ->where('shop_id', $this->currentShopId)
            ->get();
        
        foreach ($currencies as $currency) {
            $accounts = FinancialAccount::where('shop_id', $this->currentShopId)
                ->where('currency', $currency->code)
                ->where('is_active', true)
                ->get();
            
            if ($accounts->isNotEmpty()) {
                $groups[] = [
                    'currency' => $currency->code,
                    'currency_name' => $currency->name,
                    'currency_symbol' => $currency->symbol,
                    'total_balance' => $accounts->sum('balance'),
                    'accounts' => $accounts,
                    'theme' => config('business.currency_theme_map.' . $currency->code, 'blue'),
                ];
            }
        }
        
        return $groups;
    }
    
    #[Computed]
    public function totalAssets()
    {
        $baseCurrency = Currency::where('shop_id', $this->currentShopId)
            ->where('is_base', true)
            ->first();
        
        if (!$baseCurrency) {
            return [
                'total' => 0,
                'base_symbol' => '$',
                'base_code' => 'N/A',
                'breakdown' => [],
                'has_assets' => false,
            ];
        }
        
        $total = 0;
        $breakdown = [];
        $currencies = Currency::where('shop_id', $this->currentShopId)
            ->where('is_active', true)
            ->get();
        
        foreach ($currencies as $currency) {
            $balance = FinancialAccount::where('shop_id', $this->currentShopId)
                ->where('currency', $currency->code)
                ->where('is_active', true)
                ->sum('balance');
            
            // 顯示本幣（即使餘額為0）和有餘額的其他幣別
            if ($balance != 0 || $currency->is_base) {
                $convertedValue = $currency->is_base 
                    ? $balance 
                    : $balance * $currency->rate;
                
                $total += $convertedValue;
                
                $breakdown[] = [
                    'code' => $currency->code,
                    'name' => $currency->name,
                    'symbol' => $currency->symbol,
                    'balance' => $balance,
                    'rate' => $currency->rate,
                    'converted' => $convertedValue,
                    'is_base' => $currency->is_base,
                    'theme' => config('business.currency_theme_map.' . $currency->code, 'blue'),
                ];
            }
        }
        
        // 按餘額排序（大的在前面）
        usort($breakdown, function ($a, $b) {
            return $b['balance'] <=> $a['balance'];
        });
        
        return [
            'total' => $total,
            'base_symbol' => $baseCurrency->symbol,
            'base_code' => $baseCurrency->code,
            'breakdown' => $breakdown,
            'has_assets' => $total > 0,
        ];
    }
    
    // ============================================================
    // 期間統計
    // ============================================================
    
    public function loadPeriodStats()
    {
        $baseCurrency = Currency::where('shop_id', $this->currentShopId)
            ->where('is_base', true)
            ->first();
        
        if (!$baseCurrency) {
            $this->periodStats = [];
            return;
        }
        
        $periods = ['today', 'month', 'year'];
        foreach ($periods as $period) {
            $this->periodStats[$period] = $this->calculatePeriodStats($period, $baseCurrency);
        }
    }
    
    private function calculatePeriodStats($period, $baseCurrency)
    {
        $now = now();
        $startDate = match($period) {
            'today' => $now->copy()->startOfDay(),
            'month' => $now->copy()->startOfMonth(),
            'year' => $now->copy()->startOfYear(),
            default => $now->copy()->startOfDay(),
        };
        
        // 取得該期間的所有交易
        $transactions = Transaction::where('shop_id', $this->currentShopId)
            ->where('recorded_at', '>=', $startDate)
            ->where('recorded_at', '<=', $now)
            ->get();
        
        $income = 0;
        $expense = 0;
        $details = [];
        
        foreach ($transactions as $transaction) {
            // 取得交易的幣別
            $currencyCode = $transaction->currency;
            $currency = Currency::where('code', $currencyCode)
                ->where('shop_id', $this->currentShopId)
                ->first();
            
            if (!$currency) {
                continue;
            }
            
            // 換算成本幣
            $amountInBase = $currency->is_base 
                ? $transaction->amount 
                : $transaction->amount * $currency->rate;
            
            if ($transaction->type === 'income') {
                $income += $amountInBase;
            } elseif ($transaction->type === 'expense') {
                $expense += $amountInBase;
            }
            
            // 記錄各幣別明細
            if (!isset($details[$currencyCode])) {
                $details[$currencyCode] = [
                    'code' => $currencyCode,
                    'symbol' => $currency->symbol,
                    'income' => 0,
                    'expense' => 0,
                ];
            }
            
            if ($transaction->type === 'income') {
                $details[$currencyCode]['income'] += $transaction->amount;
            } elseif ($transaction->type === 'expense') {
                $details[$currencyCode]['expense'] += $transaction->amount;
            }
        }
        
        return [
            'income' => number_format($income, 2),
            'expense' => number_format($expense, 2),
            'base_symbol' => $baseCurrency->symbol,
            'base_code' => $baseCurrency->code,
            'details' => $details,
        ];
    }
    
    public function showPeriodDetail($period)
    {
        $this->dispatch('show-period-detail', period: $period);
    }
    
    // ============================================================
    // 總資產刷新
    // ============================================================
    
    #[On('refresh-total-assets')]
    public function refreshTotalAssets()
    {
        // 清除計算屬性快取
        unset($this->totalAssets);
        unset($this->currencyGroups);
        $this->loadPeriodStats();
    }
    
    // ============================================================
    // 帳戶 CRUD
    // ============================================================
    
    public function openCreateModal()
    {
        $this->resetForm();
        $this->showAccountModal = true;
    }
    
    public function editAccount($id)
    {
        $account = FinancialAccount::findOrFail($id);
        $this->editingAccountId = $id;
        $this->accountName = $account->name;
        $this->accountType = $account->type;
        $this->accountCurrency = $account->currency;
        $this->accountBalance = $account->balance;
        $this->accountMemo = $account->memo ?? '';
        $this->showAccountModal = true;
    }
    
    public function saveAccount()
    {
        $this->validate([
            'accountName' => 'required|string|max:255',
            'accountType' => 'required|string|in:cash,bank,e-wallet,securities',
            'accountCurrency' => 'required|string|exists:currencies,code',
            'accountBalance' => 'required|numeric|min:0',
            'accountMemo' => 'nullable|string|max:500',
        ]);
        
        $currency = Currency::where('code', $this->accountCurrency)
            ->where('shop_id', $this->currentShopId)
            ->firstOrFail();
        
        if ($this->editingAccountId) {
            $account = FinancialAccount::findOrFail($this->editingAccountId);
            $account->update([
                'name' => $this->accountName,
                'type' => $this->accountType,
                'currency' => $currency->code,
                'balance' => $this->accountBalance,
                'memo' => $this->accountMemo,
                'shop_id' => $this->currentShopId,
            ]);
            $message = '帳戶已更新！';
        } else {
            FinancialAccount::create([
                'name' => $this->accountName,
                'type' => $this->accountType,
                'currency' => $currency->code,
                'balance' => $this->accountBalance,
                'memo' => $this->accountMemo,
                'shop_id' => $this->currentShopId,
                'user_id' => auth()->id(),
                'is_active' => true,
            ]);
            $message = '帳戶已新增！';
        }
        
        $this->showAccountModal = false;
        $this->resetForm();
        $this->refreshTotalAssets();
        $this->dispatch('notify', message: $message);
    }
    
    public function deleteAccount($id)
    {
        $account = FinancialAccount::findOrFail($id);
        
        // 檢查是否有交易記錄
        $hasTransactions = Transaction::where('from_account_id', $id)
            ->orWhere('to_account_id', $id)
            ->exists();
        
        if ($hasTransactions) {
            $this->dispatch('notify', message: '此帳戶已有交易記錄，無法刪除！');
            return;
        }
        
        $account->delete();
        $this->refreshTotalAssets();
        $this->dispatch('notify', message: '帳戶已刪除！');
    }
    
    private function resetForm()
    {
        $this->editingAccountId = null;
        $this->accountName = '';
        $this->accountType = 'cash';
        $this->accountCurrency = 'TWD';
        $this->accountBalance = 0;
        $this->accountMemo = '';
    }
    
    // ============================================================
    // 其他功能
    // ============================================================
    
    public function viewAccountTransactions($accountId)
    {
        $this->dispatch('open-transaction-modal', accountId: $accountId);
    }
    
    public function switchShop($shopId)
    {
        $this->currentShopId = $shopId;
        session(['current_shop_id' => $shopId]);
        $this->refreshTotalAssets();
    }
    
    // ============================================================
    // 渲染
    // ============================================================
    
    public function render()
    {
        return view('livewire.finance.account-index', [
            'accountTypeOptions' => config('business.account_types', []),
            'availableCurrencies' => Currency::where('shop_id', $this->currentShopId)
                ->where('is_active', true)
                ->pluck('name', 'code')
                ->toArray(),
        ])->layout('layouts.app');
    }
}