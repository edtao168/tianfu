{{-- resources/views/livewire/finance/transaction-index.blade.php --}}

<div class="h-screen overflow-y-auto">
    <div class="sticky top-0 z-10 bg-base-100/95 backdrop-blur-sm">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-base-content tracking-wide">記帳流水明細</h1>
                <p class="text-sm text-gray-500 mt-1">支出是撬動未來的槓桿，負債是抵禦通膨的盾牌，收入是維持生存的戰果。</p>
            </div>
            <div class="flex items-center gap-2">
                <x-button label="資產帳戶" icon="o-wallet" class="btn-light" link="{{ route('finance.accounts') }}" />
                <x-button label="篩選明細" icon="o-funnel" class="{{ $showFilters ? 'btn-dark' : 'btn-light border-base-200' }}" wire:click="$toggle('showFilters')" />
            </div>
        </div>

        {{-- 月份導航 + 本月收支 --}}
        <div class="mt-4 space-y-3">
            <div class="flex items-center justify-between bg-stone-100/80 p-1.5 rounded-2xl border border-stone-200/60 shadow-inner">
                <x-button 
                    icon="o-chevron-left" 
                    class="btn-xs btn-light text-stone-500 hover:text-stone-800 hover:bg-white px-2.5 h-7 min-h-0 rounded-xl transition-all shadow-sm" 
                    wire:click="previousMonth" 
                />
                
                <div class="flex items-center gap-1.5 font-mono font-black text-sm text-stone-800 tracking-wider select-none">
                    <x-heroicon-o-calendar class="w-4 h-4 text-stone-400" />
                    <span>{{ $monthDisplay }}</span>
                    @if($isCurrentMonth)
                        <span class="px-1.5 py-0.5 text-[9px] font-extrabold rounded-full bg-emerald-100 text-emerald-700 border border-emerald-200">本月</span>
                    @endif
                    <span class="text-xs font-normal text-stone-400 ml-1">({{ $transactionCount }} 筆)</span>
                </div>
                
                <x-button 
                    icon="o-chevron-right" 
                    class="btn-xs btn-light text-stone-500 hover:text-stone-800 hover:bg-white px-2.5 h-7 min-h-0 rounded-xl transition-all shadow-sm" 
                    wire:click="nextMonth" 
                    :disabled="$isCurrentMonth" 
                />
            </div>

            {{-- 本月收支數據卡片 (1:1 雙欄) --}}
            <div class="grid grid-cols-2 gap-2">
                <div class="flex items-center justify-between px-3 py-2 rounded-xl bg-emerald-500/10 border border-emerald-500/15">
                    <div class="flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        <span class="text-xs font-semibold text-emerald-900">收入</span>
                    </div>
                    <span class="font-mono font-extrabold text-xs sm:text-sm text-emerald-700 truncate ml-1">
                        +{{ $baseSymbol }}{{ $totalIncome }}
                    </span>
                </div>

                <div class="flex items-center justify-between px-3 py-2 rounded-xl bg-rose-500/10 border border-rose-500/15">
                    <div class="flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                        <span class="text-xs font-semibold text-rose-900">支出</span>
                    </div>
                    <span class="font-mono font-extrabold text-xs sm:text-sm text-rose-700 truncate ml-1">
                        -{{ $baseSymbol }}{{ $totalExpense }}
                    </span>
                </div>
            </div>
        </div>

        <!-- 動態展開的進階篩選面板 -->
        @if($showFilters)
            <div class="mt-4 bg-stone-50/50 dark:bg-stone-900/10 p-5 rounded-2xl border border-stone-200/60 shadow-sm grid grid-cols-1 sm:grid-cols-4 gap-4 transition-all duration-300 animate-fadeIn">
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
                <div class="text-xs font-bold text-gray-400 tracking-wider flex items-center gap-2 px-2">
                    <span class="w-1.5 h-1.5 rounded-full bg-teal-500/70"></span>
                    <span>{{ date('Y 年 m 月 d 日', strtotime($date)) }}</span>
                    <span class="opacity-70">週{{ ['日','一','二','三','四','五','六'][date('w', strtotime($date))] }}</span>
                    <span class="text-[10px] font-normal text-gray-300 ml-1">({{ count($dayTransactions) }} 筆)</span>
                </div>

                <!-- 當天所有交易明細卡片群 -->
                <div class="bg-base-100 rounded-2xl border border-stone-200/50 shadow-sm divide-y divide-stone-100 dark:divide-stone-900/40 overflow-hidden">
                    @foreach($dayTransactions as $tx)
                        @php
                            $acc = $tx->fromAccount ?? $tx->toAccount;
                            $curStyle = config("business.currencies.{$acc->currency}") ?? config("business.currencies.TWD");
                            $typeStyle = config("business.account_types.{$acc->type}") ?? config("business.account_types.cash");
                            
                            // 帳戶類型 → btn-* 主題映射
                            $accountThemeMap = [
                                'cash' => 'orange',
                                'bank' => 'blue',
                                'e-wallet' => 'green',
                                'securities' => 'purple',
                            ];
                            $accountTheme = $accountThemeMap[$acc->type] ?? 'blue';
                            
                            $displayTitle = '未分類';
                            $iconName = 'o-hashtag';
                            
                            if ($tx->type === 'transfer') {
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
                                $displayTitle = $acc->name ?? '未分類';
                                $iconName = $tx->type === 'expense' ? 'o-credit-card' : 'o-wallet';
                            }
                            
                            if (!str_starts_with($iconName, 'heroicon')) {
                                $iconName = 'heroicon-o-' . ltrim($iconName, 'o-');
                            }
                        @endphp
                        
                        {{-- 關鍵：每個交易卡片都用 btn-* 包裹，讓 account-badge / currency-symbol / account-left-bar 繼承樣式 --}}
                        <div class="btn-{{ $accountTheme }}">
                            <div wire:click="$dispatch('edit-transaction', { transactionId: {{ $tx->id }} })" 
                                 class="p-4 flex items-center justify-between hover:bg-stone-50/40 dark:hover:bg-stone-900/10 transition-all duration-200 group relative pl-6 cursor-pointer">
                                
                                {{-- 左側色條：使用 account-left-bar --}}
                                <span class="account-left-bar absolute left-0 top-0 bottom-0 w-1 opacity-70"></span>

                                <div class="flex items-center gap-3.5 flex-1 min-w-0">
                                    <div class="w-10 h-10 rounded-xl flex items-center justify-center text-lg flex-shrink-0 
                                        {{ $tx->type === 'expense' ? 'bg-stone-100 text-stone-600' : 
                                           ($tx->type === 'transfer' ? 'bg-sky-100 text-sky-600 dark:bg-sky-950/30' : 
                                           'bg-emerald-50/50 text-emerald-600 dark:bg-emerald-950/20') }}">
                                        <x-dynamic-component :component="$iconName" class="w-5 h-5 opacity-80" />
                                    </div>
                                    
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <span class="font-bold text-sm text-base-content truncate">
                                                {{ $displayTitle }}
                                            </span>
                                            @if($tx->memo)
                                                <span class="text-xs text-gray-400 max-w-[180px] sm:max-w-xs truncate font-medium">({{ $tx->memo }})</span>
                                            @endif
                                            @if($tx->type === 'transfer')
                                                <span class="px-1.5 py-0.5 text-[9px] font-extrabold rounded-md bg-sky-100 text-sky-600 dark:bg-sky-950/30 dark:text-sky-400 flex-shrink-0">
                                                    轉帳
                                                </span>
                                            @endif
                                        </div>
                                        
                                        {{-- 帳戶名稱 + Badge：使用 account-badge --}}
                                        <div class="text-[11px] text-gray-400 mt-1 flex items-center gap-1.5">
                                            <span class="font-bold truncate">{{ $acc->name }}</span>
                                            <span class="account-badge px-1.5 py-0.5 text-[9px] font-extrabold rounded-md flex-shrink-0">
                                                {{ $typeStyle['name'] }}
                                            </span>
                                            <span class="text-[10px] font-mono opacity-60 flex-shrink-0">
                                                {{ $acc->currency }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-center gap-4 flex-shrink-0">
                                    <div class="text-right">
                                        <span class="font-mono text-base font-black 
                                            {{ $tx->type === 'expense' ? 'text-base-content' : 
                                               ($tx->type === 'transfer' ? 'text-sky-600' : 'text-emerald-600') }}">
                                            {{ $tx->type === 'expense' ? '-' : 
                                               ($tx->type === 'transfer' ? '↕' : '+') }} 
                                            {{-- 貨幣符號：使用 currency-symbol --}}
                                            <span class="currency-symbol text-xs font-bold mr-0.5">{{ $curStyle['symbol'] }}</span>{{ number_format($tx->amount, 2) }}
                                        </span>
                                        <div class="text-[10px] text-gray-400 font-mono mt-0.5 opacity-80">{{ date('H:i', strtotime($tx->recorded_at)) }}</div>
                                    </div>

                                    <button type="button" 
                                            wire:click.stop="deleteTransaction({{ $tx->id }})"
                                            wire:confirm="確定要刪除這筆記帳明細嗎？對應帳戶的餘額將會使用高精度運算自動退回沖正。"
                                            class="text-stone-300 hover:text-error transition-all p-1.5 md:opacity-0 group-hover:opacity-100">
                                        <x-heroicon-o-trash class="w-4 h-4" />
                                    </button>
                                </div>

                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @empty
            <div class="bg-base-100 rounded-2xl p-16 border border-dashed border-stone-200 text-center text-gray-400 text-sm">
                <x-heroicon-o-document-magnifying-glass class="w-10 h-10 mx-auto mb-3 opacity-40 text-stone-400" />
                <p class="font-medium">{{ $monthDisplay }} 沒有符合篩選條件的大宋美學記帳紀錄</p>
                <p class="text-xs text-stone-300 mt-1">試試調整篩選條件或切換其他月份</p>
            </div>
        @endforelse
    </div>

    {{-- 底部統計資訊 --}}
    @if($transactionCount > 0)
        <div class="mt-6 pt-4 border-t border-stone-200/50 flex justify-between items-center text-xs text-stone-400">
            <span>共 {{ $transactionCount }} 筆交易</span>
            <span>{{ $monthDisplay }}</span>
        </div>
    @endif

</div>