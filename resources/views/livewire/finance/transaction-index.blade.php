<!-- filepath: resources/views/livewire/finance/transaction-index.blade.php -->

<div class="h-screen overflow-y-auto">
    <div class="sticky top-0 z-10 bg-base-100/95 backdrop-blur-sm">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-base-content tracking-wide">記帳流水明細</h1>
                <p class="text-sm text-stone-500 dark:text-stone-400 mt-1">支出是撬動未來的槓桿，負債是抵禦通膨的盾牌，收入是維持生存的戰果。</p>
            </div>
            <div class="flex items-center gap-2">
                <x-button label="資產帳戶" icon="o-wallet" class="btn-light" link="{{ route('finance.accounts') }}" />
                <x-button label="篩選明細" icon="o-funnel" class="{{ $showFilters ? 'btn-dark' : 'btn-light border-base-200 dark:border-stone-800' }}" wire:click="$toggle('showFilters')" />
            </div>
        </div>

        {{-- 月份導航 + 本月收支 --}}
        <div class="mt-4 space-y-3">
            <div class="flex items-center justify-between bg-stone-100/80 dark:bg-stone-800/80 p-1.5 rounded-2xl border border-stone-200/60 dark:border-stone-700/60 shadow-inner">
                <x-button 
                    icon="o-chevron-left" 
                    class="btn-xs btn-light text-stone-500 dark:text-stone-400 hover:text-stone-800 dark:hover:text-stone-100 px-2.5 h-7 min-h-0 rounded-xl transition-all shadow-sm" 
                    wire:click="previousMonth" 
                />
                
                <div class="flex items-center gap-1.5 font-mono font-black text-sm text-stone-800 dark:text-stone-100 tracking-wider select-none">
                    <x-heroicon-o-calendar class="w-4 h-4 text-stone-400 dark:text-stone-500" />
                    <span>{{ $monthDisplay }}</span>
                    @if($isCurrentMonth)
                        <span class="px-1.5 py-0.5 text-[9px] font-extrabold rounded-full bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800">本月</span>
                    @endif
                    <span class="text-xs font-normal text-stone-400 dark:text-stone-500 ml-1">({{ $transactionCount }} 筆)</span>
                </div>
                
                <x-button 
                    icon="o-chevron-right" 
                    class="btn-xs btn-light text-stone-500 dark:text-stone-400 hover:text-stone-800 dark:hover:text-stone-100 px-2.5 h-7 min-h-0 rounded-xl transition-all shadow-sm" 
                    wire:click="nextMonth" 
                    :disabled="$isCurrentMonth" 
                />
            </div>

            {{-- 本月收支數據卡片 (1:1 雙欄) --}}
            <div class="grid grid-cols-2 gap-2">
                <div class="flex items-center justify-between px-3 py-2 rounded-xl bg-emerald-500/10 dark:bg-emerald-500/20 border border-emerald-500/15 dark:border-emerald-500/30">
                    <div class="flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        <span class="text-xs font-semibold text-emerald-900 dark:text-emerald-300">收入</span>
                    </div>
                    <span class="font-mono font-extrabold text-xs sm:text-sm text-emerald-700 dark:text-emerald-400 truncate ml-1">
                        +{{ $baseSymbol }}{{ $totalIncome }}
                    </span>
                </div>

                <div class="flex items-center justify-between px-3 py-2 rounded-xl bg-rose-500/10 dark:bg-rose-500/20 border border-rose-500/15 dark:border-rose-500/30">
                    <div class="flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                        <span class="text-xs font-semibold text-rose-900 dark:text-rose-300">支出</span>
                    </div>
                    <span class="font-mono font-extrabold text-xs sm:text-sm text-rose-700 dark:text-rose-400 truncate ml-1">
                        -{{ $baseSymbol }}{{ $totalExpense }}
                    </span>
                </div>
            </div>
        </div>

        <!-- 動態展開的進階篩選面板 -->
        @if($showFilters)
            <div class="mt-4 bg-stone-50/50 dark:bg-stone-900/60 p-5 rounded-2xl border border-stone-200/60 dark:border-stone-800 shadow-sm grid grid-cols-1 sm:grid-cols-4 gap-4 transition-all duration-300 animate-fadeIn">
                <x-select label="貨幣種類" wire:model.live="searchCurrency" placeholder="全部貨幣" 
                          :options="collect($currencies)->map(fn($info, $code) => ['id' => $code, 'name' => $info['name']])->toArray()" 
                          class="select-sm" inline />
                
                <x-select label="資產帳戶" wire:model.live="searchAccountId" placeholder="全部帳戶" 
                          :options="$accounts->map(fn($a) => ['id' => $a->id, 'name' => $a->name.' ('.$a->currency.')'])->toArray()" 
                          class="select-sm" inline />
                
                <x-select label="收支分類" wire:model.live="searchCategoryId" placeholder="全部分類" 
                          :options="$categories->map(fn($c) => ['id' => $c->id, 'name' => $c->name])->toArray()" 
                          class="select-sm" inline />
                
                <x-select label="收支性質" wire:model.live="searchType" placeholder="全部性質" 
                          :options="[['id' => 'expense', 'name' => '純支出'], ['id' => 'income', 'name' => '純收入']]" 
                          class="select-sm" inline />
            </div>
        @endif
    </div>

    <!-- 流水時間線主體 -->
    <div class="space-y-6 mt-4">
        @forelse($groupedTransactions as $date => $dayTransactions)
            <div class="space-y-3">
                
                <!-- 日期標頭 -->
                <div class="text-xs font-bold text-stone-500 dark:text-stone-400 tracking-wider flex items-center gap-2 px-2">
                    <span class="w-1.5 h-1.5 rounded-full bg-teal-500/70"></span>
                    <span>{{ date('Y 年 m 月 d 日', strtotime($date)) }}</span>
                    <span class="opacity-70">週{{ ['日','一','二','三','四','五','六'][date('w', strtotime($date))] }}</span>
                    <span class="text-[10px] font-normal text-stone-400 dark:text-stone-500 ml-1">({{ count($dayTransactions) }} 筆)</span>
                </div>

                <!-- 當天交易卡片群組 (完全參考 transaction-list-modal 寫法) -->
                <div class="bg-base-100 rounded-2xl border border-stone-200/50 dark:border-stone-800 shadow-sm divide-y divide-stone-100 dark:divide-stone-800/60 overflow-hidden">
                    @foreach($dayTransactions as $tx)
                        @php
                            $acc = $tx->fromAccount ?? $tx->toAccount;
                            $currencyCode = $acc?->currency ?? 'TWD';
                            $currencySymbol = $currencies[$currencyCode]['symbol'] ?? 'NT$';
                            
                            $isIncome = $tx->type === 'income';
                            $isExpense = $tx->type === 'expense';
                            $isTransfer = $tx->type === 'transfer';
                            
                            // 圖標顏色 & 背景
                            if ($isTransfer) {
                                $iconColor = 'text-sky-500 dark:text-sky-400';
                                $bgColor = 'bg-sky-100 dark:bg-sky-950/60';
                                $amountClass = 'text-sky-600 dark:text-sky-400';
                                $amountPrefix = '↕';
                            } elseif ($isIncome) {
                                $iconColor = 'text-emerald-500 dark:text-emerald-400';
                                $bgColor = 'bg-emerald-100 dark:bg-emerald-950/60';
                                $amountClass = 'text-emerald-600 dark:text-emerald-400';
                                $amountPrefix = '+';
                            } elseif ($isExpense) {
                                $iconColor = 'text-rose-500 dark:text-rose-400';
                                $bgColor = 'bg-rose-100 dark:bg-rose-950/60';
                                $amountClass = 'text-rose-600 dark:text-rose-400';
                                $amountPrefix = '-';
                            } else {
                                $iconColor = 'text-stone-500 dark:text-stone-400';
                                $bgColor = 'bg-stone-200 dark:bg-stone-700';
                                $amountClass = 'text-stone-600 dark:text-stone-300';
                                $amountPrefix = '';
                            }
                            
                            // 標題 & 圖標
                            $displayTitle = '未分類';
                            $iconName = 'o-hashtag';
                            
                            if ($isTransfer) {
                                $fromName = $tx->fromAccount->name ?? '?';
                                $toName = $tx->toAccount->name ?? '?';
                                $displayTitle = $fromName . ' → ' . $toName;
                                $iconName = 'o-arrow-path';
                            } elseif ($tx->category) {
                                if ($tx->category->parent) {
                                    $displayTitle = $tx->category->parent->name . ' › ' . $tx->category->name;
                                } else {
                                    $displayTitle = $tx->category->name;
                                }
                                $iconName = $tx->category->icon ?? 'o-hashtag';
                            } else {
                                $displayTitle = $acc?->name ?? '未分類';
                                $iconName = $isExpense ? 'o-credit-card' : 'o-wallet';
                            }
                            
                            if (!str_starts_with($iconName, 'heroicon')) {
                                $iconName = 'heroicon-o-' . ltrim($iconName, 'o-');
                            }
                        @endphp
                        
                        <!-- 直接參考 modal 的寫法，沒有側邊條，沒有多餘 div -->
                        <div class="relative flex items-center py-3 px-4 hover:bg-stone-50/40 dark:hover:bg-stone-800/40 transition-all duration-150 cursor-pointer group border-l-4 border-transparent hover:border-stone-300 dark:hover:border-stone-600"
                             wire:click="$dispatch('open-transaction-modal', { transactionId: {{ $tx->id }} })">
                            
                            {{-- 圖標 --}}
                            <div class="rounded-xl p-2.5 flex-shrink-0 {{ $bgColor }}">
                                <x-dynamic-component :component="$iconName" class="w-5 h-5 {{ $iconColor }}" />
                            </div>
                            
                            {{-- 內容區域 --}}
                            <div class="flex-1 min-w-0 ml-3 md:ml-4">
                                <div class="flex items-center justify-between gap-2">
                                    <div class="flex items-center gap-2 min-w-0">
                                        <span class="text-base md:text-base font-semibold text-stone-800 dark:text-stone-200 flex-shrink-0">
                                            {{ $displayTitle }}
                                        </span>
                                        
                                        @if($acc)
                                            <span class="text-sm text-stone-500 dark:text-stone-400 font-normal truncate">
                                                （{{ $acc->name }}）
                                            </span>
                                        @endif
                                        
                                        @if($isTransfer)
                                            <span class="px-1.5 py-0.5 text-[10px] font-extrabold rounded-md bg-sky-100 text-sky-600 dark:bg-sky-950/60 dark:text-sky-300 flex-shrink-0">
                                                轉帳
                                            </span>
                                        @endif
                                    </div>
                                    
                                    {{-- 金額 --}}
                                    <span class="font-mono font-bold text-base {{ $amountClass }} flex-shrink-0">
                                        <span class="font-normal text-stone-400 dark:text-stone-500 text-sm">{{ $currencySymbol }}</span>
                                        {{ $amountPrefix }}{{ number_format((float)$tx->amount, 2) }}
                                    </span>
                                </div>
                                
                                {{-- 備註 + 時間 --}}
                                <div class="flex items-center justify-between gap-2 mt-0.5">
                                    <span class="text-sm text-stone-500 dark:text-stone-400 truncate {{ $tx->memo ? '' : 'italic' }}">
                                        {{ $tx->memo ?: '無備註' }}
                                    </span>
                                    <span class="text-sm font-mono text-stone-400 dark:text-stone-500 flex-shrink-0">
                                        {{ date('H:i', strtotime($tx->recorded_at)) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @empty
            <div class="bg-base-100 rounded-2xl p-16 border border-dashed border-stone-200 dark:border-stone-800 text-center text-stone-400 dark:text-stone-500 text-sm">
                <x-heroicon-o-document-magnifying-glass class="w-10 h-10 mx-auto mb-3 opacity-40 text-stone-400 dark:text-stone-500" />
                <p class="font-medium">{{ $monthDisplay }} 沒有符合篩選條件的紀錄</p>
                <p class="text-xs text-stone-400 dark:text-stone-500 mt-1">試試調整篩選條件或切換其他月份</p>
            </div>
        @endforelse
    </div>

    {{-- 底部統計資訊 --}}
    @if($transactionCount > 0)
        <div class="mt-6 pt-4 border-t border-stone-200/50 dark:border-stone-800 flex justify-between items-center text-xs text-stone-400 dark:text-stone-500 pb-10">
            <span>共 {{ $transactionCount }} 筆交易</span>
            <span>{{ $monthDisplay }}</span>
        </div>
    @endif

</div>