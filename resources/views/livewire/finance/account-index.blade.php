<!-- resources/views/livewire/finance/account-index.blade.php -->
<div class="p-6 max-w-7xl mx-auto space-y-8">
    
    {{-- ============================================================ --}}
    {{-- 1. 頂部收支統計卡片 --}}
    {{-- ============================================================ --}}
    <div class="space-y-3">
        <div class="flex items-center justify-between">
            <h2 class="text-sm font-bold text-gray-500 tracking-wider flex items-center gap-2">
                <span class="w-1.5 h-4.5 rounded-full bg-sky-500"></span>
                期間資產統計
                <span class="text-xs text-gray-400">點擊卡片看明細</span>
            </h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            @php
                $periodThemeMap = [
                    'today' => 'blue',
                    'month' => 'green',
                    'year' => 'purple',
                ];
            @endphp
            @foreach(['today' => '本日收支摘要', 'month' => '本月收支累計', 'year' => '本年年度統計'] as $period => $title)
                @php
                    $stats = $this->periodStats[$period] ?? ['income' => 0, 'expense' => 0, 'base_symbol' => 'NT$'];
                    $baseSymbol = $stats['base_symbol'] ?? 'NT$';
                    $currencyCount = count($stats['details'] ?? []);
                    $income = (string) ($stats['income'] ?? '0');
                    $expense = (string) ($stats['expense'] ?? '0');
                    
                    $incomeClean = str_replace(',', '', $income);
                    $expenseClean = str_replace(',', '', $expense);
                    $profitValue = bcsub($incomeClean, $expenseClean, 2);
                    $isProfitNegative = bccomp($profitValue, '0', 2) < 0;
                    $profitFormatted = number_format((float)$profitValue, 2);
                    
                    $theme = $periodThemeMap[$period] ?? 'blue';
                @endphp
            
                <div class="btn-{{ $theme }} stats shadow bg-base-100 border border-base-200 hover:shadow-lg transition-all duration-300 cursor-pointer" 
                     wire:click="showPeriodDetail('{{ $period }}')">
                    <div class="stat">
                        <div class="stat-title flex items-center gap-1.5 text-gray-500 font-medium">
                            <span class="flex h-2 w-2 rounded-full currency-tag bg-current opacity-60"></span>
                            {{ $title }}
                            @if($currencyCount > 1)
                                <span class="text-xs text-gray-400 ml-1 flex items-center gap-0.5">
                                    ( {{ $currencyCount }}種幣別 )
                                </span>
                            @endif
                        </div>
                        <div class="mt-3 space-y-2">
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-emerald-400">收入</span>
                                <span class="text-lg font-bold text-emerald-500">+{{ $baseSymbol }} {{ $income }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-rose-400">支出</span>
                                <span class="text-lg font-bold text-rose-600">-{{ $baseSymbol }} {{ $expense }}</span>
                            </div>
                            <div class="flex justify-between items-center pt-2 border-t border-base-200">
                                <span class="text-sm font-medium text-stone-500">結餘</span>
                                <span class="text-lg font-extrabold {{ $isProfitNegative ? 'text-stone-600' : 'text-base-content' }}">
                                    {{ $isProfitNegative ? '' : '+' }}{{ $baseSymbol }} {{ $profitFormatted }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- ============================================================ --}}
    {{-- 2. 總資產卡片 --}}
    {{-- ============================================================ --}}
    <div class="relative" wire:key="total-assets-card">
        {{-- Loading 狀態 --}}
        <div wire:loading wire:target="refreshTotalAssets, saveAccount, deleteAccount, switchShop" 
             class="absolute inset-0 z-10 bg-gradient-to-br from-indigo-600 via-indigo-500 to-purple-600 rounded-2xl flex items-center justify-center">
            <div class="flex flex-col items-center gap-3">
                <div class="loading loading-spinner loading-lg text-white/70"></div>
                <span class="text-white/60 text-sm">計算總資產中...</span>
            </div>
        </div>
        
        {{-- 總資產卡片本體 --}}
        <div wire:loading.remove wire:target="refreshTotalAssets, saveAccount, deleteAccount, switchShop"
             class="relative overflow-hidden bg-gradient-to-br from-indigo-600 via-indigo-500 to-purple-600 rounded-2xl p-6 text-white shadow-xl">
            
            {{-- 背景裝飾 --}}
            <div class="absolute top-0 right-0 w-64 h-64 bg-white/5 rounded-full -translate-y-1/2 translate-x-1/3"></div>
            <div class="absolute bottom-0 left-0 w-48 h-48 bg-white/5 rounded-full translate-y-1/2 -translate-x-1/3"></div>
            
            <div class="relative z-10">
                {{-- 標題列 --}}
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2 text-white/80 text-sm font-medium">
                        <span>📊</span>
                        <span>總資產（{{ $this->totalAssets['base_code'] }}）</span>
                        <span class="text-xs text-white/50 ml-2">所有幣別換算後加總</span>
                    </div>
                    
                    {{-- 自動刷新控制 --}}
                    <div class="flex items-center gap-3">
                        <span class="text-[10px] text-white/40 flex items-center gap-1.5">
                            <span class="inline-block w-1.5 h-1.5 rounded-full {{ $autoRefresh ? 'bg-emerald-400 animate-pulse' : 'bg-white/20' }}"></span>
                            {{ $autoRefresh ? '自動更新中' : '已暫停' }}
                        </span>
                        
                        <button wire:click="refreshTotalAssets" 
                                class="text-white/40 hover:text-white/80 transition-colors text-sm p-1 rounded-lg hover:bg-white/10">
                            ⟳
                        </button>
                        
                        <button wire:click="$toggle('autoRefresh')" 
                                class="text-white/40 hover:text-white/80 transition-colors text-xs px-2 py-0.5 rounded border border-white/20 hover:border-white/40">
                            {{ $autoRefresh ? '⏸' : '▶' }}
                        </button>
                    </div>
                </div>
                
                {{-- 總金額 --}}
                <div class="mt-2">
                    <span class="text-4xl font-black tracking-tight">
                        {{ $this->totalAssets['base_symbol'] }} {{ number_format($this->totalAssets['total'], 2) }}
                    </span>
                    @if(!$this->totalAssets['has_assets'])
                        <span class="ml-3 text-sm text-white/50">尚無資產</span>
                    @endif
                </div>
                
                {{-- 幣別明細 --}}
                @if(count($this->totalAssets['breakdown']) > 0)
                    <div class="mt-4 pt-4 border-t border-white/20">
                        <div class="flex flex-wrap gap-3">
                            @foreach($this->totalAssets['breakdown'] as $item)
                                <div class="flex items-center gap-2 bg-white/10 backdrop-blur-sm rounded-lg px-3 py-1.5 hover:bg-white/20 transition-colors">
                                    <span class="text-xs font-bold">{{ $item['code'] }}</span>
                                    <span class="text-xs text-white/80">
                                        {{ $item['symbol'] }} {{ number_format($item['balance'], 2) }}
                                    </span>
                                    @if(!$item['is_base'])
                                        <span class="text-[10px] text-white/50">
                                            → {{ $this->totalAssets['base_symbol'] }} {{ number_format($item['converted'], 2) }}
                                        </span>
                                    @endif
                                    @if($item['is_base'])
                                        <span class="text-[10px] text-emerald-300">👑 本幣</span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @else
                    <div class="mt-4 pt-4 border-t border-white/20 text-white/50 text-sm">
                        尚無任何資產，請先新增帳戶
                    </div>
                @endif
                
                {{-- 自動刷新計時器（僅在頁面可見時觸發） --}}
                @if($autoRefresh)
                    <div wire:poll.visible.30s="refreshTotalAssets" class="hidden"></div>
                @endif
            </div>
        </div>
    </div>

    {{-- ============================================================ --}}
    {{-- 3. 操作標題列 --}}
    {{-- ============================================================ --}}
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between border-b border-base-200 pb-5">
        <div>
            <h1 class="text-2xl font-bold text-base-content">淨資產帳戶總覽</h1>
            <p class="text-sm text-gray-500 mt-1">即時追蹤您的多幣別資產配置與資金流向</p>
        </div>
        <div class="flex items-center gap-3">
            <x-button label="新增帳戶" icon="o-plus" class="btn-dark" wire:click="openCreateModal" />
            <x-button label="記一筆" icon="o-pencil-square" class="btn-green" wire:click="$dispatch('open-transaction-modal')" />
        </div>
    </div>

    {{-- ============================================================ --}}
    {{-- 4. 分幣別資產總計卡片 --}}
    {{-- ============================================================ --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        @foreach($this->currencyGroups as $group)
            @php
                $theme = $group['theme'] ?? 'blue';
            @endphp
            <div class="btn-{{ $theme }}">
                <div class="currency-card flex justify-between items-center p-5 rounded-2xl border shadow-sm transition-all duration-300 hover:scale-[1.02]">
                    <div>
                        <div class="text-[11px] font-bold opacity-60 tracking-wider uppercase">{{ $group['currency_name'] }} ({{ $group['currency'] }})</div>
                        <div class="text-2xl font-black text-base-content mt-1">
                            <span class="currency-symbol mr-0.5">{{ $group['currency_symbol'] }}</span>{{ number_format($group['total_balance'], 2) }}
                        </div>
                    </div>
                    <div class="text-2xl font-black opacity-10 font-mono tracking-widest">{{ $group['currency'] }}</div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- ============================================================ --}}
    {{-- 5. 帳戶列表 --}}
    {{-- ============================================================ --}}
    @php
        $accountThemeMap = config('business.account_theme_map', []);
    @endphp
    
    @foreach($this->currencyGroups as $group)
        @php
            $currencyTheme = $group['theme'] ?? 'blue';
            $currencyColorMap = [
                'blue' => 'sky',
                'red' => 'rose',
                'green' => 'emerald',
                'purple' => 'violet',
                'orange' => 'orange',
            ];
            $dotColor = $currencyColorMap[$currencyTheme] ?? 'sky';
        @endphp
        
        <div class="space-y-3 pt-2 animate-fadeIn">
            {{-- 標題列 --}}
            <h2 class="text-sm font-bold text-gray-500 tracking-wider flex items-center gap-2">
                <span class="w-1.5 h-4.5 rounded-full bg-{{ $dotColor }}-500"></span> 
                {{ $group['currency_name'] }}資產明細
                <span class="text-xs text-gray-400">點擊卡片看明細</span>
            </h2>
            
            {{-- 手機端卡片式 --}}
            <div class="block md:hidden">
                <div class="grid grid-cols-1 gap-4">
                    @foreach($group['accounts'] as $account)
                        @php
                            $accountTheme = $accountThemeMap[$account->type] ?? 'blue';
                            $typeConfig = config("business.account_types.{$account->type}") ?? config("business.account_types.cash");
                            $icon = $typeConfig['icon'] ?? 'heroicon-o-currency-dollar';
                        @endphp

                        <div class="btn-{{ $accountTheme }}">
                            <div wire:click="viewAccountTransactions({{ $account->id }})" 
                                 class="account-card p-5 rounded-xl border shadow-sm cursor-pointer transition-all duration-200 active:scale-[0.98] relative overflow-hidden pl-5 flex flex-col justify-between h-28">
                                
                                <span class="account-left-bar absolute left-0 top-0 bottom-0 w-1.5 opacity-80"></span>
                                
                                <div class="flex justify-between items-start">
                                    <div class="flex items-center gap-2">
                                        <x-dynamic-component :component="$icon" class="w-4 h-4 opacity-40 text-base-content" />
                                        <span class="font-bold text-base-content text-md">{{ $account->name }}</span>
                                    </div>
                                    <span class="account-badge px-2 py-0.5 text-[10px] font-extrabold tracking-wider rounded-md">
                                        {{ $typeConfig['name'] }}
                                    </span>
                                </div>
                                
                                <div class="flex justify-between items-end mt-2">
                                    <span class="text-[11px] text-gray-400 font-medium">當前餘額</span>
                                    <span class="font-mono text-xl font-extrabold text-base-content">
                                        <span class="currency-symbol mr-0.5 text-base font-bold">{{ $group['currency_symbol'] }}</span>{{ number_format($account->balance, 2) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- PC端網格 --}}
            <div class="hidden md:grid grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($group['accounts'] as $account)
                    @php
                        $accountTheme = $accountThemeMap[$account->type] ?? 'blue';
                        $typeConfig = config("business.account_types.{$account->type}") ?? config("business.account_types.cash");
                        $icon = $typeConfig['icon'] ?? 'heroicon-o-currency-dollar';
                    @endphp

                    <div class="btn-{{ $accountTheme }}">
                        <div wire:click="viewAccountTransactions({{ $account->id }})" 
                             class="account-card p-4 rounded-xl border shadow-sm cursor-pointer transition-all duration-200 hover:shadow-md flex flex-col justify-between h-28 active:scale-[0.99] relative overflow-hidden pl-5">
                            
                            <span class="account-left-bar absolute left-0 top-0 bottom-0 w-1.5 opacity-80"></span>
                            
                            <div class="flex justify-between items-start">
                                <div class="flex items-center gap-2">
                                    <x-dynamic-component :component="$icon" class="w-4 h-4 opacity-40 text-base-content" />
                                    <span class="font-bold text-base-content text-md">{{ $account->name }}</span>
                                </div>
                                <span class="account-badge px-2 py-0.5 text-[10px] font-extrabold tracking-wider rounded-md">
                                    {{ $typeConfig['name'] }}
                                </span>
                            </div>
                            
                            <div class="flex justify-between items-end mt-2">
                                <span class="text-[11px] text-gray-400 font-medium">當前餘額</span>
                                <span class="font-mono text-xl font-extrabold text-base-content">
                                    <span class="currency-symbol mr-0.5 text-base font-bold">{{ $group['currency_symbol'] }}</span>{{ number_format($account->balance, 2) }}
                                </span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach

{{-- 6. 新增/編輯帳戶 Modal --}}
<x-modal 
    wire:model="showAccountModal" 
    title="{{ $editingAccountId ? '編輯資產帳戶' : '新增資產帳戶' }}" 
    separator 
    persistent 
    size="2xl"
    x-on:click.stop
>
    {{-- 添加右上角關閉按鈕 --}}
    <button 
        type="button" 
        wire:click="$set('showAccountModal', false)" 
        class="btn btn-light btn-sm btn-square absolute right-4 top-4 text-base-content/60 hover:text-base-content"
    >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
        </svg>
    </button>

    {{-- Modal Body --}}
    <div class="py-4">
        <x-form wire:submit="saveAccount" class="space-y-5">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-input label="帳戶名稱" wire:model="accountName" placeholder="例如：中信零售現金、官網 Stripe" icon="o-pencil" required />
                <x-select label="帳戶類型" wire:model="accountType" :options="$accountTypeOptions" icon="o-tag" required />
            </div>

            <div class="space-y-2">
                <label class="label p-0 flex items-center gap-1">
                    <span class="label-text font-bold text-stone-700 text-xs">使用幣別 (Currency)</span>
                </label>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                    @foreach($availableCurrencies as $code => $name)
                        @php 
                            $isSelected = ($accountCurrency === $code);
                            $btnThemeMap = [
                                'TWD' => 'blue',
                                'CNY' => 'red',
                                'HKD' => 'green',
                                'USD' => 'purple',
                            ];
                            $btnClass = $btnThemeMap[$code] ?? 'blue';
                        @endphp
                        <button type="button" 
                            class="flex flex-col items-center justify-center py-2.5 px-3 rounded-xl border transition-all duration-200 {{ $isSelected ? 'btn-' . $btnClass . ' ring-1 ring-offset-1' : 'bg-stone-50 dark:bg-stone-800' }}" 
                            wire:click="$set('accountCurrency', '{{ $code }}')">
                            <span class="text-xs font-black">{{ $name }}</span>
                            <span class="text-[10px] opacity-70 font-mono mt-0.5">{{ $code }}</span>
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end bg-stone-50 dark:bg-stone-800/50 p-4 rounded-xl border border-sky-100/50 dark:border-stone-700">
                <div class="md:col-span-2">
                    <x-input label="初始資產 (以選定幣別計價)" wire:model="accountBalance" 
                             prefix="{{ $availableCurrencies[$accountCurrency] ?? '$' }}" 
                             type="number" step="0.0001" class="font-mono text-lg font-bold" required />
                </div>
                <div class="text-xs text-stone-500 dark:text-stone-400 leading-relaxed md:pb-2">
                    💡 初始資產，後續庫存採購與銷售折抵將會連動此餘額進行異動。
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4">
                <x-textarea 
                    label="備忘 (Memo)" 
                    wire:model="accountMemo" 
                    placeholder="例如：此帳戶為中信銀行信義分行，主要用於官網收款..."
                    rows="2"
                    hint="僅供內部參考，不會顯示於任何單據上" />
            </div>

            {{-- 操作按鈕 --}}
            <x-slot:actions>
                <div class="grid grid-cols-2 gap-2 w-full pt-3 border-t border-stone-200 dark:border-stone-700">
                    <x-button label="取消" type="button" wire:click="$set('showAccountModal', false)" class="btn-light btn-sm" />
                    <x-button label="確認儲存" type="submit" class="btn-green btn-sm" spinner="saveAccount" />
                </div>
            </x-slot:actions>
        </x-form>
    </div>
</x-modal>

    {{-- ============================================================ --}}
    {{-- 7. 交易流水 Modal --}}
    {{-- ============================================================ --}}
    <livewire:finance.transaction-list-modal />
</div>