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
use App\Services\CurrencyService;
use App\Services\MoneyCalculator;
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
		$currencyService = app(CurrencyService::class);
		$groups = [];

		$currencies = Currency::where('is_active', true)
			->where('shop_id', $this->currentShopId)
			->orderByRaw('is_base DESC')
			->orderBy('code')
			->get();

		foreach ($currencies as $currency) {
			$accounts = FinancialAccount::where('shop_id', $this->currentShopId)
				->where('user_id', auth()->id())
				->where('currency', $currency->code)
				->where('is_active', true)
				->get()
				->map(function ($account) {
					$account->calculated_balance = (float) $account->calculated_balance;
					return $account;
				});

			if ($accounts->isNotEmpty()) {
				$groups[] = [
					'currency' => $currency->code,
					'currency_name' => $currency->name,
					'currency_symbol' => $currency->symbol,
					'total_balance' => $accounts->sum('calculated_balance'),
					'accounts' => $accounts,
					'theme' => $currencyService->getThemeColor($currency->code),
				];
			}
		}

		return $groups;
	}
    
	#[Computed]
	public function totalAssets()
	{
		$currencyService = app(CurrencyService::class);
		$baseCurrency = $currencyService->getBaseCurrency();

		if (!$baseCurrency) {
			return [
				'total' => 0,
				'base_symbol' => '$',
				'base_code' => 'N/A',
				'breakdown' => [],
				'has_assets' => false,
			];
		}

		$total = '0.0000';
		$breakdown = [];

		foreach ($this->currencyGroups as $group) {
			$currency = $currencyService->findByCode($group['currency']);
			if (!$currency) {
				continue;
			}

			$balance = (string) $group['total_balance'];

			if (bccomp($balance, '0.0000', 4) === 0 && !$currency->is_base) {
				continue;
			}

			$convertedValue = $currency->is_base
				? $balance
				: $currencyService->convertToBase($balance, $currency->code);

			$total = MoneyCalculator::add($total, $convertedValue, 4);

			$breakdown[] = [
				'code' => $currency->code,
				'name' => $currency->name,
				'symbol' => $currency->symbol,
				'balance' => (float) $balance,
				'rate' => $currency->rate,
				'converted' => (float) $convertedValue,
				'is_base' => $currency->is_base,
				'theme' => config('business.currency_theme_map.' . $currency->code, 'blue'),
			];
		}

		usort($breakdown, fn($a, $b) => $b['balance'] <=> $a['balance']);

		return [
			'total' => (float) $total,
			'base_symbol' => $baseCurrency->symbol,
			'base_code' => $baseCurrency->code,
			'breakdown' => $breakdown,
			'has_assets' => bccomp($total, '0.0000', 4) > 0,
		];
	}
    
    // ============================================================
    // 期間統計
    // ============================================================
    
    public function loadPeriodStats()
	{
		$currencyService = app(CurrencyService::class);
		$baseCurrency = $currencyService->getBaseCurrency();

		if (!$baseCurrency) {
			$this->periodStats = [];
			return;
		}

		foreach (['today', 'month', 'year'] as $period) {
			$this->periodStats[$period] = $this->calculatePeriodStats($period, $baseCurrency);
		}
	}
    
    private function calculatePeriodStats($period, $baseCurrency)
	{
		$currencyService = app(CurrencyService::class);
		$baseCode = $baseCurrency->code;

		$now = now();
		$startDate = match ($period) {
			'today' => $now->copy()->startOfDay(),
			'month' => $now->copy()->startOfMonth(),
			'year'  => $now->copy()->startOfYear(),
			default => $now->copy()->startOfDay(),
		};

		$transactions = Transaction::where('shop_id', $this->currentShopId)
			->where('user_id', auth()->id())
			->where('recorded_at', '>=', $startDate)
			->where('recorded_at', '<=', $now)
			->with(['fromAccount:id,currency', 'toAccount:id,currency'])
			->get();

		$income = '0.0000';
		$expense = '0.0000';
		$details = [];

		foreach ($transactions as $transaction) {
			// 幣別來源：交易 → 來源帳戶 → 目的帳戶 → 本幣
			$currencyCode = $transaction->currency
				?? $transaction->fromAccount?->currency
				?? $transaction->toAccount?->currency
				?? $baseCode;

			$currency = $currencyService->getCurrency($currencyCode);

			// 找不到幣別：照原邏輯跳過
			if (!$currency) {
				continue;
			}

			$amountRaw = (string) $transaction->amount;

			// 換算成本幣
			$amountInBase = $currency->is_base
				? $amountRaw
				: $currencyService->convertToBase($amountRaw, $currencyCode);

			if ($transaction->type === 'income') {
				$income = MoneyCalculator::add($income, $amountInBase, 4);
			} elseif ($transaction->type === 'expense') {
				$expense = MoneyCalculator::add($expense, $amountInBase, 4);
			}

			// 各幣別原幣明細
			if (!isset($details[$currencyCode])) {
				$details[$currencyCode] = [
					'code' => $currencyCode,
					'symbol' => $currency->symbol,
					'income' => '0.0000',
					'expense' => '0.0000',
				];
			}

			if ($transaction->type === 'income') {
				$details[$currencyCode]['income'] = MoneyCalculator::add(
					$details[$currencyCode]['income'], $amountRaw, 4
				);
			} elseif ($transaction->type === 'expense') {
				$details[$currencyCode]['expense'] = MoneyCalculator::add(
					$details[$currencyCode]['expense'], $amountRaw, 4
				);
			}
		}

		return [
			'income'      => number_format((float) $income, 2),
			'expense'     => number_format((float) $expense, 2),
			'base_symbol' => $baseCurrency->symbol,
			'base_code'   => $baseCode,
			'details'     => $details,
		];
	}
    
    public function showPeriodDetail($period)
    {
		$dateMode = match($period) {
			'today' => 'day',
			'month' => 'month',
			'year'  => 'year',
			default => 'month',
		};

		$titleMap = [
			'today' => '本日交易明細',
			'month' => '本月交易明細',
			'year'  => '本年度交易明細',
		];

		// 加上 params: 具名參數
		$this->dispatch('open-transaction-list-modal', params: [
			'dateMode' => $dateMode,
			'title'    => $titleMap[$period] ?? '交易明細',
		]);
	}
    
    // ============================================================
    // 總資產刷新
    // ============================================================

	#[On('refresh-data')] //TransactionModal
    public function refreshData()
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
    
    /**
     * 監聽來自 TransactionListModal 的編輯帳戶請求
     */
    #[On('edit-account-from-transactions')]
    public function editAccount($accountId = null)
    {
		if (!$accountId) {
            return;
        }

        $account = FinancialAccount::findOrFail($accountId);
		$this->editingAccountId = $accountId;
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
        $this->refreshData();
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
        $this->refreshData();
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
		// 建議此處也同步加上 title 與 params: 具名參數，確保帳戶明細標題正常
		$account = \App\Models\FinancialAccount::find($accountId);

		$this->dispatch('open-transaction-list-modal', params: [
			'accountId' => $accountId,
			'dateMode'  => 'month',
			'title'     => ($account ? $account->name . ' - ' : '') . '交易明細',
		]);
	}
    
    public function switchShop($shopId)
    {
        $this->currentShopId = $shopId;
        session(['current_shop_id' => $shopId]);
        $this->refreshData();
    }
    
    // ============================================================
    // 渲染
    // ============================================================
    
    public function render()
	{
		// 轉成 MaryUI <x-select> 能正確解析的格式
		$accountTypeOptions = collect(config('business.account_types', []))
			->map(fn ($item, $key) => ['id' => $key, 'name' => $item['name']])
			->values()
			->all();

		return view('livewire.finance.account-index', [
			'accountTypeOptions'  => $accountTypeOptions,
			'availableCurrencies' => Currency::where('shop_id', $this->currentShopId)
				->where('is_active', true)
				->pluck('name', 'code')
				->toArray(),
		])->layout('layouts.app');
	}
}