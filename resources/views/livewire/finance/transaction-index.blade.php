<!-- resources/views/livewire/finance/transaction-index.blade.php -->

<div class="h-screen overflow-y-auto text-base-content">
    <div class="sticky top-0 z-10 backdrop-blur-sm">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-base-content tracking-wide">記帳流水明細</h1>
                <p class="text-sm opacity-70 mt-1">支出是撬動未來的槓桿，負債是抵禦通膨的盾牌，收入是維持生存的戰果。</p>
            </div>
            <div class="flex items-center gap-2">
                <x-button label="資產帳戶" icon="o-wallet" class="btn-ghost border border-base-300" link="{{ route('finance.accounts') }}" />
                <x-button label="篩選明細" icon="o-funnel" class="{{ $showFilters ? 'btn-neutral' : 'btn-ghost border border-base-300' }}" wire:click="$toggle('showFilters')" />
            </div>
        </div>

        {{-- 月份導航 + 本月收支 --}}
        <div class="mt-4 space-y-3">
            <div class="bg-base-100/95 flex items-center justify-between bg-base-200/60 p-1.5 rounded-2xl border border-base-200 shadow-inner">
                <x-button 
                    icon="o-chevron-left" 
                    class="btn-xs btn-ghost text-base-content opacity-70 hover:opacity-100 px-2.5 h-7 min-h-0 rounded-xl transition-all shadow-sm" 
                    wire:click="previousMonth" 
                />
                
                <div class="flex items-center gap-1.5 font-mono font-black text-sm text-base-content tracking-wider select-none">
                    <x-heroicon-o-calendar class="w-4 h-4 opacity-50" />
                    <span>{{ $monthDisplay }}</span>
                    @if($isCurrentMonth)
                        <span class="px-1.5 py-0.5 text-[9px] font-extrabold rounded-full bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">本月</span>
                    @endif
                    <span class="text-xs font-normal opacity-50 ml-1">({{ $transactionCount }} 筆)</span>
                </div>
                
                <x-button 
                    icon="o-chevron-right" 
                    class="btn-xs btn-ghost text-base-content opacity-70 hover:opacity-100 px-2.5 h-7 min-h-0 rounded-xl transition-all shadow-sm" 
                    wire:click="nextMonth" 
                    :disabled="$isCurrentMonth" 
                />
            </div>

            {{-- 本月收支數據卡片 (1:1 雙欄) --}}
            <div class="grid grid-cols-2 gap-2">
                <div class="flex items-center justify-between px-3 py-2 rounded-xl bg-emerald-500/10 border border-emerald-500/20">
                    <div class="flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        <span class="text-xs font-semibold text-emerald-600 dark:text-emerald-400">收入</span>
                    </div>
                    <span class="font-mono font-extrabold text-xs sm:text-sm text-emerald-600 dark:text-emerald-400 truncate ml-1">
                        +{{ $baseSymbol }}{{ $totalIncome }}
                    </span>
                </div>

                <div class="flex items-center justify-between px-3 py-2 rounded-xl bg-rose-500/10 border border-rose-500/20">
                    <div class="flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                        <span class="text-xs font-semibold text-rose-600 dark:text-rose-400">支出</span>
                    </div>
                    <span class="font-mono font-extrabold text-xs sm:text-sm text-rose-600 dark:text-rose-400 truncate ml-1">
                        -{{ $baseSymbol }}{{ $totalExpense }}
                    </span>
                </div>
            </div>
        </div>

        <!-- 動態展開的進階篩選面板 -->
        @if($showFilters)
            <div class="mt-4 bg-base-200/50 p-5 rounded-2xl border border-base-200 shadow-sm grid grid-cols-1 sm:grid-cols-4 gap-4 transition-all duration-300 animate-fadeIn">
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
                <div class="text-xs font-bold opacity-70 tracking-wider flex items-center gap-2 px-2">
                    <span class="w-1.5 h-1.5 rounded-full bg-teal-500/70"></span>
                    <span>{{ date('Y 年 m 月 d 日', strtotime($date)) }}</span>
                    <span class="opacity-70">週{{ ['日','一','二','三','四','五','六'][date('w', strtotime($date))] }}</span>
                    <span class="text-[10px] font-normal opacity-50 ml-1">({{ count($dayTransactions) }} 筆)</span>
                </div>

                <!-- 當天交易卡片群組 -->
                <div class="bg-base-100 rounded-2xl border border-base-200 shadow-sm divide-y divide-base-200/60 overflow-hidden">
                    @foreach($dayTransactions as $tx)
						@php
							$acc = $tx->fromAccount ?? $tx->toAccount;
							
							$typeConfig = config("business.account_types.{$acc?->type}") ?? config("business.account_types.cash");
							$typeTheme = $typeConfig['theme'] ?? 'orange';
							
							$color = $acc?->color ?? 'default';
							
							$currencyCode = $acc?->currency ?? 'TWD';
							$currencySymbol = $currencies[$currencyCode]['symbol'] ?? 'NT$';
							
							$isIncome = $tx->type === 'income';
							$isExpense = $tx->type === 'expense';
							$isTransfer = $tx->type === 'transfer';
							
							if ($isTransfer) {
								$iconColor = 'text-sky-600 dark:text-sky-400';
								$bgColor = 'bg-sky-500/10';
								$amountClass = 'text-sky-600 dark:text-sky-400';
								$amountPrefix = '↕';
							} elseif ($isIncome) {
								$iconColor = 'text-emerald-600 dark:text-emerald-400';
								$bgColor = 'bg-emerald-500/10';
								$amountClass = 'text-emerald-600 dark:text-emerald-400';
								$amountPrefix = '+';
							} elseif ($isExpense) {
								$iconColor = 'text-rose-600 dark:text-rose-400';
								$bgColor = 'bg-rose-500/10';
								$amountClass = 'text-rose-600 dark:text-rose-400';
								$amountPrefix = '-';
							} else {
								$iconColor = 'opacity-50';
								$bgColor = 'bg-base-200';
								$amountClass = 'text-base-content';
								$amountPrefix = '';
							}
							
							$displayTitle = '未分類';
							$iconName = 'o-hashtag';
							
							if ($isTransfer) {
								$fromName = $tx->fromAccount->name ?? '?';
								$toName = $tx->toAccount->name ?? '?';
								$displayTitle = $fromName . ' → ' . $toName;
								$iconName = 'o-arrow-path';
							} elseif ($tx->category) {
								
									$displayTitle = $tx->category->name;
								
								$iconName = $tx->category->icon ?? 'o-hashtag';
							} else {
								$displayTitle = $acc?->name ?? '未分類';
								$iconName = $isExpense ? 'o-credit-card' : 'o-wallet';
							}
							
							if (!str_starts_with($iconName, 'heroicon')) {
								$iconName = 'heroicon-o-' . ltrim($iconName, 'o-');
							}
						@endphp
						
						<!-- 單筆交易卡片 -->
						<div class="card-{{ $typeTheme }} relative flex items-center py-3 px-4 transition-all duration-150 cursor-pointer group hover:bg-base-200/50"
							 wire:click="$dispatch('open-transaction-modal', { transactionId: {{ $tx->id }} })">
							
							{{-- 左側主題側邊條 --}}
							<span class="account-left-bar absolute left-0 top-1 bottom-1 w-1 rounded-r-full z-10"></span>
							
							{{-- 圖標 --}}
							<div class="rounded-xl p-2.5 flex-shrink-0 {{ $bgColor }}">
								<x-dynamic-component :component="$iconName" class="w-5 h-5 {{ $iconColor }}" />
							</div>
							
							{{-- 內容區域 --}}
							<div class="flex-1 min-w-0 ml-3 md:ml-4">
								<div class="flex items-center justify-between gap-2">
									<div class="flex items-center gap-2 min-w-0">
										<span class="text-base md:text-base font-semibold text-base-content flex-shrink-0">
											{{ $displayTitle }}
										</span>
										
										@if($acc)
											<span class="text-sm opacity-60 font-normal truncate">
												（{{ $acc->name }}）
											</span>
										@endif
										
										@if($isTransfer)
											<span class="px-1.5 py-0.5 text-[10px] font-extrabold rounded-md bg-sky-500/10 text-sky-600 dark:text-sky-400 flex-shrink-0">
												轉帳
											</span>
										@endif
									</div>
									
									{{-- 金額 --}}
									<span class="font-mono font-bold text-base {{ $amountClass }} flex-shrink-0">
										<span class="font-normal opacity-50 text-sm">{{ $currencySymbol }}</span>
										{{ $amountPrefix }}{{ number_format((float)$tx->amount, 2) }}
									</span>
								</div>
								
								{{-- 備註 + 時間 --}}
								<div class="flex items-center justify-between gap-2 mt-0.5">
									<span class="text-sm opacity-60 truncate {{ $tx->memo ? '' : 'italic' }}">
										{{ $tx->memo ?: '無備註' }}
									</span>
									<span class="text-sm font-mono opacity-50 flex-shrink-0">
										{{ date('H:i', strtotime($tx->recorded_at)) }}
									</span>
								</div>
							</div>
						</div>
					@endforeach
                </div>
            </div>
        @empty
            <div class="bg-base-100 rounded-2xl p-16 border border-dashed border-base-200 text-center opacity-60 text-sm">
                <x-heroicon-o-document-magnifying-glass class="w-10 h-10 mx-auto mb-3 opacity-40" />
                <p class="font-medium">{{ $monthDisplay }} 沒有符合篩選條件的紀錄</p>
                <p class="text-xs opacity-60 mt-1">試試調整篩選條件或切換其他月份</p>
            </div>
        @endforelse
    </div>

    {{-- 底部統計資訊 --}}
    @if($transactionCount > 0)
        <div class="mt-6 pt-4 border-t border-base-200 flex justify-between items-center text-xs opacity-50 pb-10">
            <span>共 {{ $transactionCount }} 筆交易</span>
            <span>{{ $monthDisplay }}</span>
        </div>
    @endif

</div>