{{-- resources/views/livewire/finance/currency-index.blade.php --}}

<div class="p-4 sm:p-6 max-w-7xl mx-auto space-y-4 sm:space-y-6">
    
    {{-- ============================================================ --}}
    {{-- 1. 頁面標題列 --}}
    {{-- ============================================================ --}}
    <div class="flex flex-col gap-3 sm:gap-4 md:flex-row md:items-center md:justify-between border-b border-base-200 pb-4 sm:pb-5">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-base-content">💱 匯率設定</h1>
            <p class="text-xs sm:text-sm text-base-content/70 mt-0.5 sm:mt-1">管理多幣別與換算本幣，所有統計報表將以本幣貨幣顯示</p>
        </div>
        <div class="flex items-center gap-2 sm:gap-3">
            <x-button 
                label="新增幣別" 
                icon="o-plus" 
                class="btn-green btn-sm sm:btn-md w-full sm:w-auto" 
                wire:click="create" 
            />
        </div>
    </div>

    {{-- ============================================================ --}}
    {{-- 2. 帳本切換器（多帳本時顯示） --}}
    {{-- ============================================================ --}}
    @if($shops->count() > 1)
        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-2 sm:gap-3 p-3 sm:p-4 bg-base-200/80 rounded-xl border border-base-200">
            <span class="text-xs sm:text-sm font-medium text-base-content/70 whitespace-nowrap">📒 當前帳本：</span>
            <div class="flex flex-wrap gap-1.5 w-full sm:w-auto">
                @foreach($shops as $shop)
                    <button 
                        wire:click="switchShop({{ $shop->id }})"
                        class="btn btn-xs sm:btn-sm {{ $currentShopId == $shop->id ? 'btn-dark' : 'btn-light' }} flex-1 sm:flex-none"
                    >
                        {{ $shop->name }}
                    </button>
                @endforeach
            </div>
        </div>
    @endif

    {{-- ============================================================ --}}
    {{-- 3. 訊息提示 --}}
    {{-- ============================================================ --}}
    @if(session()->has('message'))
        <div class="alert alert-success shadow-lg rounded-xl border border-emerald-200/60 dark:border-emerald-800/60 bg-emerald-50/60 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-200 text-sm">
            <x-heroicon-o-check-circle class="w-4 h-4 sm:w-5 sm:h-5 text-emerald-500 dark:text-emerald-400 flex-shrink-0" />
            <span class="break-words">{{ session('message') }}</span>
        </div>
    @endif

    @if(session()->has('error'))
        <div class="alert alert-error shadow-lg rounded-xl border border-rose-200/60 dark:border-rose-800/60 bg-rose-50/60 dark:bg-rose-900/30 text-rose-800 dark:text-rose-200 text-sm">
            <x-heroicon-o-exclamation-circle class="w-4 h-4 sm:w-5 sm:h-5 text-rose-500 dark:text-rose-400 flex-shrink-0" />
            <span class="break-words">{{ session('error') }}</span>
        </div>
    @endif

    {{-- ============================================================ --}}
    {{-- 4. 新增/編輯表單 --}}
    {{-- ============================================================ --}}
    @if($showForm)
        <div class="card bg-base-200 shadow-lg border border-base-200 rounded-xl sm:rounded-2xl">
            <div class="card-body p-4 sm:p-6">
                <h3 class="card-title text-base sm:text-lg font-bold text-base-content">
                    {{ $editingId ? '✏️ 編輯幣別' : '➕ 新增幣別' }}
                </h3>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mt-2">
                    <x-input 
                        label="代碼" 
                        wire:model="code" 
                        placeholder="USD" 
                        maxlength="3"
                        hint="3 個大寫英文字母"
                    />
                    
                    <x-input 
                        label="名稱" 
                        wire:model="name" 
                        placeholder="美元"
                    />
                    
                    <x-input 
                        label="符號" 
                        wire:model="symbol" 
                        placeholder="$"
                    />
                    
                    <x-input 
                        label="匯率" 
                        wire:model="rate" 
                        type="number" 
                        step="0.000001"
                        placeholder="32.150000"
                        hint="1 單位該貨幣 = ? 本幣貨幣"
                    />
                </div>
                
                <div class="flex flex-col sm:flex-row flex-wrap items-start sm:items-center gap-3 sm:gap-6 mt-4">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" wire:model="is_base" class="checkbox checkbox-primary checkbox-sm sm:checkbox-md" />
                        <span class="text-xs sm:text-sm text-base-content">👑 設為本幣貨幣</span>
                    </label>
                    
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" wire:model="is_active" class="checkbox checkbox-success checkbox-sm sm:checkbox-md" />
                        <span class="text-xs sm:text-sm text-base-content">✅ 啟用</span>
                    </label>
                    
                    <div class="flex gap-2 w-full sm:w-auto sm:ml-auto mt-2 sm:mt-0">
                        <x-button label="💾 儲存" wire:click="save" class="btn-dark btn-sm sm:btn-md flex-1 sm:flex-none" />
                        <x-button label="取消" wire:click="cancel" class="btn-light btn-sm sm:btn-md flex-1 sm:flex-none" />
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- ============================================================ --}}
    {{-- 5. 工具列（搜尋 + 篩選） --}}
    {{-- ============================================================ --}}
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 sm:gap-4">
        <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto">
            <x-button 
                label="新增" 
                icon="o-plus" 
                class="btn-green btn-sm flex-1 sm:flex-none" 
                wire:click="create" 
            />
            
            <x-input 
                label="" 
                placeholder="🔍 搜尋幣別..." 
                wire:model.live="search"
                class="input-sm flex-1 sm:flex-none sm:w-48"
                icon="o-magnifying-glass"
            />
            
            <select wire:model.live="filterActive" class="select select-sm select-bordered bg-base-100 text-base-content flex-1 sm:flex-none">
                <option value="all">📋 全部</option>
                <option value="active">✅ 啟用</option>
                <option value="inactive">⛔ 停用</option>
            </select>
        </div>
        
        <div class="text-xs sm:text-sm text-base-content/60 whitespace-nowrap">
            共 {{ $currencies->total() }} 個幣別
        </div>
    </div>

    {{-- ============================================================ --}}
    {{-- 6. 卡片顯示區域（使用 btn-{theme} 配色系統） --}}
    {{-- ============================================================ --}}

    @if($currencies->count() > 0)
        
        {{-- 6.1 本幣貨幣卡（醒目展示，樣式保持不變） --}}
        @if($baseCurrency)
            <div class="relative overflow-hidden bg-gradient-to-br from-indigo-600 via-indigo-500 to-purple-600 dark:from-indigo-700 dark:via-indigo-600 dark:to-purple-700 rounded-2xl p-5 sm:p-8 text-white shadow-xl">
                {{-- 背景裝飾 --}}
                <div class="absolute top-0 right-0 w-48 h-48 bg-white/5 rounded-full -translate-y-1/2 translate-x-1/3"></div>
                <div class="absolute bottom-0 left-0 w-32 h-32 bg-white/5 rounded-full translate-y-1/2 -translate-x-1/3"></div>
                
                <div class="relative z-10 flex flex-col sm:flex-row items-center sm:items-center justify-between gap-4">
                    {{-- 左側：圓形貨幣符號 + 幣別資訊 --}}
                    <div class="flex items-center gap-4 sm:gap-6 w-full sm:w-auto">
                        {{-- 圓形貨幣符號 --}}
                        <div class="flex-shrink-0 w-16 h-16 sm:w-20 sm:h-20 rounded-full bg-white/20 backdrop-blur-sm flex items-center justify-center text-3xl sm:text-4xl font-bold border-2 border-white/30 shadow-lg">
                            {{ $baseCurrency->symbol }}
                        </div>
                        
                        {{-- 幣別資訊 --}}
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="text-xs text-white/70 font-medium tracking-wider">本幣（本位幣）</span>
                                @if($baseCurrency->is_active)
                                    <span class="w-1.5 h-1.5 bg-emerald-400 rounded-full animate-pulse"></span>
                                @endif
                            </div>
                            <div class="flex items-end gap-2 sm:gap-3 mt-0.5">
                                <span class="text-2xl sm:text-3xl font-bold tracking-tight">{{ $baseCurrency->code }}</span>
                                <span class="text-sm sm:text-base text-white/80">{{ $baseCurrency->name }}</span>
                            </div>
                            <div class="flex items-center gap-2 mt-1 text-xs sm:text-sm text-white/60">
                                <span>匯率：<span class="font-mono text-white/80 font-semibold">1.000000</span></span>
                            </div>
                        </div>
                    </div>
                    
                    {{-- 右側：操作按鈕 --}}
                    <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto" wire:click.stop>
                        <x-button 
                            label="✏️ 編輯" 
                            size="xs" 
                            wire:click="edit({{ $baseCurrency->id }})"
                            class="bg-white/20 hover:bg-white/30 text-white border-white/20 btn-xs sm:btn-sm"
                        />
                    </div>
                </div>
                
                {{-- 底部提示 --}}
                <div class="relative z-10 mt-4 pt-4 border-t border-white/10 text-xs text-white/50 flex items-center gap-2">
                    <span>💡</span>
                    <span>所有帳戶金額將以此為基準換算</span>
                </div>
            </div>
        @else
            {{-- 沒有本幣時的提示 --}}
            <div class="bg-amber-50 dark:bg-amber-900/30 border border-amber-200 dark:border-amber-800 rounded-xl p-6 text-center">
                <p class="text-amber-700 dark:text-amber-300">⚠️ 尚未設定本幣貨幣，請先新增一個幣別並設為本幣</p>
                <x-button label="➕ 新增幣別" wire:click="create" class="btn-primary mt-3" />
            </div>
        @endif

        {{-- 6.2 外幣卡片牆（使用 btn-{theme} 系統） --}}
        <div>
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-base-content/70 tracking-wider">
                    💱 外幣列表
                </h3>
                <span class="text-xs text-base-content/60">{{ $foreignCurrencies->count() }} 種外幣</span>
            </div>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3 sm:gap-4">
                @foreach($currencies as $currency)
                    @if(!$currency->is_base)
                        @php
                            // 幣別 → btn-{theme} 映射（與 account-index 一致）
                            $theme = config('business.currency_theme_map.' . $currency->code, 'blue');
                            $theme = $currencyThemeMap[$currency->code] ?? 'blue';
                            
                            // 取得帳戶類型配置（用於 icon）
                            $typeConfig = config("business.account_types.cash");
                            $icon = $typeConfig['icon'] ?? 'heroicon-o-currency-dollar';
                        @endphp
                        
                        <div class="btn-{{ $theme }}">
                            <div 
                                class="currency-card p-4 sm:p-5 rounded-xl border border-base-200 shadow-sm hover:shadow-md transition-all duration-200 cursor-pointer {{ $currency->is_active ? 'opacity-100' : 'opacity-60' }}"
                                wire:click="edit({{ $currency->id }})"
                            >
                                {{-- 幣別標題列：圓形符號 + 代碼 + 狀態 --}}
                                <div class="flex items-start justify-between">
                                    <div class="flex items-center gap-3">
                                        {{-- 小圓形貨幣符號 --}}
                                        <div class="flex-shrink-0 w-10 h-10 sm:w-12 sm:h-12 rounded-full bg-base-200 backdrop-blur-sm flex items-center justify-center text-base sm:text-lg font-bold border border-base-200 shadow-sm">
                                            <span class="currency-symbol text-base-content">{{ $currency->symbol }}</span>
                                        </div>
                                        <div>
                                            <div class="flex items-center gap-2">
                                                <span class="text-lg sm:text-xl font-bold text-base-content">{{ $currency->code }}</span>
                                                @if(!$currency->is_active)
                                                    <span class="badge badge-error badge-sm text-[10px]">停用</span>
                                                @endif
                                            </div>
                                            <p class="text-xs sm:text-sm text-base-content/70">{{ $currency->name }}</p>
                                        </div>
                                    </div>
                                </div>
                                
                                {{-- 匯率資訊 --}}
                                <div class="mt-3 pt-3 border-t border-base-200">
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs text-base-content/60">兌換匯率</span>
                                        <span class="text-xs text-base-content/60">1 {{ $currency->code }} =</span>
                                    </div>
                                    <div class="flex items-end justify-between mt-0.5">
                                        <span class="text-lg font-mono font-bold text-base-content">
                                            {{ number_format($currency->rate, 6) }}
                                        </span>
                                        <span class="text-sm font-medium text-base-content/70">
                                            {{ $baseCurrency->code ?? '本幣' }}
                                        </span>
                                    </div>
                                </div>
                                
                                {{-- 操作按鈕 --}}
                                <div class="mt-4 flex flex-wrap gap-1.5" wire:click.stop>
                                    <x-button 
                                        label="👑 設為本幣" 
                                        size="xs" 
                                        wire:click="setBase({{ $currency->id }})"
                                        class="btn-primary btn-xs"
                                        wire:confirm="確定要將 {{ $currency->name }} 設為本幣貨幣嗎？\n所有金額將以此為基準換算！"
                                    />
                                    
                                    <x-button 
                                        label="{{ $currency->is_active ? '⛔ 停用' : '✅ 啟用' }}"
                                        size="xs"
                                        wire:click="toggleActive({{ $currency->id }})"
                                        class="{{ $currency->is_active ? 'btn-orange btn-xs' : 'btn-green btn-xs' }}"
                                    />
                                    
                                    <x-button 
                                        icon="o-trash" 
                                        size="xs"
                                        wire:click="delete({{ $currency->id }})"
                                        class="btn-red btn-xs"
                                        wire:confirm="確定要刪除 {{ $currency->name }} 嗎？\n此操作無法復原！"
                                    />
                                </div>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>

    @else
        {{-- 完全沒有幣別時 --}}
        <div class="bg-base-200/50 backdrop-blur-sm rounded-xl border border-base-200 p-8 sm:p-12 text-center">
            <div class="text-6xl mb-4">💱</div>
            <p class="text-lg font-medium text-base-content">還沒有設定任何幣別</p>
            <p class="text-sm text-base-content/60 mt-1">請先新增一個幣別，並設為本幣貨幣</p>
            <x-button label="➕ 新增第一個幣別" wire:click="create" class="btn-green mt-4" />
        </div>
    @endif

    {{-- ============================================================ --}}
    {{-- 7. 分頁 --}}
    {{-- ============================================================ --}}
    @if($currencies->count() > 0)
        <div class="mt-4 overflow-x-auto">
            {{ $currencies->links() }}
        </div>
    @endif

    {{-- ============================================================ --}}
    {{-- 8. 使用說明 --}}
    {{-- ============================================================ --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mt-4">
        <div class="bg-blue-50/60 dark:bg-blue-900/20 rounded-xl p-4 border border-blue-200/40 dark:border-blue-800/40">
            <div class="flex items-center gap-2 text-blue-700 dark:text-blue-300">
                <span class="text-xl">👑</span>
                <span class="font-semibold text-sm">本幣貨幣</span>
            </div>
            <p class="text-xs text-blue-600/70 dark:text-blue-400/70 mt-1">記帳的主要貨幣，所有金額以此為基礎換算</p>
        </div>
        
        <div class="bg-emerald-50/60 dark:bg-emerald-900/20 rounded-xl p-4 border border-emerald-200/40 dark:border-emerald-800/40">
            <div class="flex items-center gap-2 text-emerald-700 dark:text-emerald-300">
                <span class="text-xl">💱</span>
                <span class="font-semibold text-sm">匯率定義</span>
            </div>
            <p class="text-xs text-emerald-600/70 dark:text-emerald-400/70 mt-1">1 單位該貨幣 = ? 本幣貨幣</p>
        </div>
        
        <div class="bg-amber-50/60 dark:bg-amber-900/20 rounded-xl p-4 border border-amber-200/40 dark:border-amber-800/40">
            <div class="flex items-center gap-2 text-amber-700 dark:text-amber-300">
                <span class="text-xl">⛔</span>
                <span class="font-semibold text-sm">停用</span>
            </div>
            <p class="text-xs text-amber-600/70 dark:text-amber-400/70 mt-1">停用的幣別不會出現在記帳選項中</p>
        </div>
        
        <div class="bg-rose-50/60 dark:bg-rose-900/20 rounded-xl p-4 border border-rose-200/40 dark:border-rose-800/40">
            <div class="flex items-center gap-2 text-rose-700 dark:text-rose-300">
                <span class="text-xl">🗑️</span>
                <span class="font-semibold text-sm">刪除</span>
            </div>
            <p class="text-xs text-rose-600/70 dark:text-rose-400/70 mt-1">只有未被任何帳戶使用的幣別才能刪除</p>
        </div>
    </div>

</div>