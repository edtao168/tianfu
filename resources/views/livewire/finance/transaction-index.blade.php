{{-- resources/views/livewire/finance/transaction-index.blade.php --}}

<div class="p-6 max-w-4xl mx-auto space-y-6">
    
    <!-- 頂部標題與篩選開關 -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between border-b border-base-200 pb-5">
        <div>
            <h1 class="text-2xl font-bold text-base-content tracking-wide">記帳流水明細</h1>
            <p class="text-sm text-gray-500 mt-1">支出是撬動未來的槓桿，負債是抵禦通膨的盾牌，收入是維持生存的戰果。</p>
        </div>
        <div class="flex items-center gap-2">
            <x-button label="資產帳戶" icon="o-wallet" class="btn-outline btn-sm" link="{{ route('finance.accounts') }}" />
            <x-button label="篩選明細" icon="o-funnel" class="btn-sm {{ $showFilters ? 'btn-primary' : 'btn-ghost border-base-200' }}" wire:click="$toggle('showFilters')" />
        </div>
    </div>

    <!-- 動態展開的進階篩選面板 -->
    @if($showFilters)
        <div class="bg-stone-50/50 dark:bg-stone-900/10 p-5 rounded-2xl border border-stone-200/60 shadow-sm grid grid-cols-1 sm:grid-cols-4 gap-4 transition-all duration-300 animate-fadeIn">
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

    <!-- 流水時間線主體 -->
    <div class="space-y-6">
        @forelse($groupedTransactions as $date => $dayTransactions)
            <div class="space-y-3">
                
                <!-- 日期標頭 -->
                <div class="text-xs font-bold text-gray-400 tracking-wider flex items-center gap-2 px-2">
                    <span class="w-1.5 h-1.5 rounded-full bg-teal-500/70"></span>
                    <span>{{ date('Y 年 m 月 d 日', strtotime($date)) }}</span>
                    <span class="opacity-70">週{{ ['日','一','二','三','四','五','六'][date('w', strtotime($date))] }}</span>
                </div>

                <!-- 當天所有交易明細卡片群 -->
                <div class="bg-base-100 rounded-2xl border border-stone-200/50 shadow-sm divide-y divide-stone-100 dark:divide-stone-900/40 overflow-hidden">
                    @foreach($dayTransactions as $tx)
                        @php
                            // 獲取該筆交易所屬的資產帳戶
                            $acc = $tx->fromAccount ?? $tx->toAccount;
                            
                            // 讀取該幣別對應的色卡配置
                            $curStyle = config("business.currencies.{$acc->currency}") ?? config("business.currencies.TWD");
                            
                            // 讀取該帳戶類型的色卡配置
                            $typeStyle = config("business.account_types.{$acc->type}") ?? config("business.account_types.cash");
                            
                            // ✅ 決定顯示的標題
                            $displayTitle = '未分類';
                            $iconName = 'o-hashtag';
                            
                            if ($tx->type === 'transfer') {
                                // ✅ 轉帳：顯示 from → to
                                $fromName = $tx->fromAccount->name ?? '?';
                                $toName = $tx->toAccount->name ?? '?';
                                $displayTitle = $fromName . ' → ' . $toName;
                                $iconName = 'o-arrow-path';
                            } elseif ($tx->category) {
                                // ✅ 有類別：顯示類別名稱（含父類別）
                                if ($tx->category->parent) {
                                    $displayTitle = $tx->category->parent->name . ' › ' . $tx->category->name;
                                } else {
                                    $displayTitle = $tx->category->name;
                                }
                                $iconName = $tx->category->icon ?? 'o-hashtag';
                            } else {
                                // ✅ 無類別的一般收支：顯示帳戶名稱
                                $displayTitle = $acc->name ?? '未分類';
                                $iconName = $tx->type === 'expense' ? 'o-credit-card' : 'o-wallet';
                            }
                            
                            // 處理圖示名稱
                            if (!str_starts_with($iconName, 'heroicon')) {
                                $iconName = 'heroicon-o-' . ltrim($iconName, 'o-');
                            }
                        @endphp
                        
                        <div wire:click="$dispatch('open-transaction-modal', { transaction_id: {{ $tx->id }} })" class="p-4 flex items-center justify-between hover:bg-stone-50/40 dark:hover:bg-stone-900/10 transition-all duration-200 group relative pl-6 cursor-pointer">
                            
                            <!-- 帳戶類型的左側垂直條 -->
                            <span class="absolute left-0 top-0 bottom-0 w-1 {{ $typeStyle['left_bar'] }} opacity-70"></span>

                            <div class="flex items-center gap-3.5 flex-1 min-w-0">
                                <!-- 分類/轉帳 圓圈圖示 -->
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center text-lg flex-shrink-0 
                                    {{ $tx->type === 'expense' ? 'bg-stone-100 text-stone-600' : 
                                       ($tx->type === 'transfer' ? 'bg-sky-100 text-sky-600 dark:bg-sky-950/30' : 
                                       'bg-emerald-50/50 text-emerald-600 dark:bg-emerald-950/20') }}">
                                    <x-dynamic-component :component="$iconName" class="w-5 h-5 opacity-80" />
                                </div>
                                
                                <div class="flex-1 min-w-0">
                                    <!-- 標題 -->
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="font-bold text-sm text-base-content truncate">
                                            {{ $displayTitle }}
                                        </span>
                                        @if($tx->memo)
                                            <span class="text-xs text-gray-400 max-w-[180px] sm:max-w-xs truncate font-medium">({{ $tx->memo }})</span>
                                        @endif
                                        {{-- 轉帳標籤 --}}
                                        @if($tx->type === 'transfer')
                                            <span class="px-1.5 py-0.5 text-[9px] font-extrabold rounded-md bg-sky-100 text-sky-600 dark:bg-sky-950/30 dark:text-sky-400 flex-shrink-0">
                                                轉帳
                                            </span>
                                        @endif
                                    </div>
                                    
                                    <!-- 帳戶名稱與類型徽章 -->
                                    <div class="text-[11px] text-gray-400 mt-1 flex items-center gap-1.5">
                                        <span class="font-bold truncate">{{ $acc->name }}</span>
                                        <span class="px-1.5 py-0.5 text-[9px] font-extrabold rounded-md {{ $typeStyle['badge'] }} flex-shrink-0">
                                            {{ $typeStyle['name'] }}
                                        </span>
                                        <span class="text-[10px] font-mono opacity-60 flex-shrink-0">
                                            {{ $acc->currency }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- 右側金額與刪除按鍵 -->
                            <div class="flex items-center gap-4 flex-shrink-0">
                                <div class="text-right">
                                    <span class="font-mono text-base font-black 
                                        {{ $tx->type === 'expense' ? 'text-base-content' : 
                                           ($tx->type === 'transfer' ? 'text-sky-600' : 'text-emerald-600') }}">
                                        {{ $tx->type === 'expense' ? '-' : 
                                           ($tx->type === 'transfer' ? '↕' : '+') }} 
                                        <span class="{{ $curStyle['symbol_color'] }} text-xs font-bold mr-0.5">{{ $curStyle['symbol'] }}</span>{{ number_format($tx->amount, 2) }}
                                    </span>
                                    <div class="text-[10px] text-gray-400 font-mono mt-0.5 opacity-80">{{ date('H:i', strtotime($tx->recorded_at)) }}</div>
                                </div>

                                <!-- 刪除按鍵 -->
                                <button type="button" 
                                        wire:click.stop="deleteTransaction({{ $tx->id }})"
                                        wire:confirm="確定要刪除這筆記帳明細嗎？對應帳戶的餘額將會使用高精度運算自動退回沖正。"
                                        class="text-stone-300 hover:text-error transition-all p-1.5 md:opacity-0 group-hover:opacity-100">
                                    <x-heroicon-o-trash class="w-4 h-4" />
                                </button>
                            </div>

                        </div>
                    @endforeach
                </div>
            </div>
        @empty
            <div class="bg-base-100 rounded-2xl p-16 border border-dashed border-stone-200 text-center text-gray-400 text-sm">
                <x-heroicon-o-document-magnifying-glass class="w-10 h-10 mx-auto mb-3 opacity-40 text-stone-400" />
                沒有找到符合篩選條件的大宋美學記帳紀錄
            </div>
        @endforelse
    </div>

    <!-- 分頁導航列 -->
    <div class="pt-2">
        {{ $transactionsPaginator->links() }}
    </div>

</div>