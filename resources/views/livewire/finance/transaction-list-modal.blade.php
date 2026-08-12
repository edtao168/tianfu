<!-- filepath: resources/views/livewire/finance/transaction-list-modal.blade.php -->
<div>
	<x-modal 
		wire:model="showModal" 
		backdrop-blur-md 
		max-width="5xl" 
		box-class="border border-base-200 dark:border-stone-800 shadow-2xl rounded-t-3xl md:rounded-2xl p-0 overflow-hidden pt-16 pb-24 md:pt-0 md:pb-0"
	>
		<div class="flex flex-col h-[calc(100vh-10rem)] md:h-[80vh] max-h-[80vh]">

			{{-- 頂部標題屏風 --}}
			<div class="p-4 md:p-5 bg-stone-50 dark:bg-stone-900 border-b border-base-200 dark:border-stone-800 flex-shrink-0">
				<div class="flex flex-col md:flex-row md:items-center justify-between gap-4">

					{{-- 左欄：標題和類型標籤 --}}
					<div class="w-full md:w-1/2">
						<div class="flex items-center gap-3 min-w-0">
							<div class="p-2 md:p-2.5 rounded-xl bg-stone-200/60 dark:bg-stone-800 text-stone-700 dark:text-stone-300 shadow-inner flex-shrink-0">
								<x-heroicon-o-document-magnifying-glass class="w-6 h-6 md:w-6 md:h-6" />
							</div>
							<div class="min-w-0 flex-1">
								<h3 class="text-lg md:text-lg font-black text-stone-800 dark:text-stone-100 tracking-wide font-sans flex items-center gap-2 flex-wrap">
									<span class="truncate">{{ $title }}</span> 
									<span class="text-xs font-semibold px-2 py-0.5 rounded-md bg-stone-200/60 dark:bg-stone-800 text-stone-600 dark:text-stone-300 font-mono flex-shrink-0">
										{{ $transactionType === 'expense' ? '支出' : ($transactionType === 'income' ? '收入' : '交易明細') }}
									</span>
								</h3>
								<p class="text-sm text-stone-500 dark:text-stone-400 mt-0.5 flex items-center gap-2 flex-wrap">
									<span>共 <strong class="text-stone-800 dark:text-stone-200 font-mono">{{ $transactionsData['total_count'] ?? 0 }}</strong> 筆記錄</span>
									@if($currentAccount && !$currentAccount->is_active)
										<span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-100 dark:bg-amber-900/50 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800">已隱藏帳戶</span>
									@endif
								</p>
							</div>
						</div>
					</div>

					{{-- 右欄：統計資訊 --}}
					<div class="w-full md:w-1/2 flex md:justify-end">
						@if($accountId)
							{{-- 帳戶模式：期初 → 收入 → 支出 → 期末 --}}
							<div class="grid grid-cols-[60px_1fr] gap-x-4 text-right items-center mr-4 ml-auto">
								<span class="text-sm text-stone-400 dark:text-stone-500 text-left">期初</span>
								<span class="font-mono font-bold text-stone-600 dark:text-stone-300 text-base">
									{{ $currencySymbol }}{{ number_format((float)($statsSummary['opening_balance'] ?? 0), 2) }}
								</span>
								
								<span class="text-sm text-emerald-500 dark:text-emerald-400 text-left">收入</span>
								<span class="font-mono font-bold text-emerald-600 dark:text-emerald-400 text-base">
									+{{ $currencySymbol }}{{ number_format((float)($statsSummary['total_income'] ?? 0), 2) }}
								</span>
								
								<span class="text-sm text-rose-500 dark:text-rose-400 text-left">支出</span>
								<span class="font-mono font-bold text-rose-600 dark:text-rose-400 text-base">
									-{{ $currencySymbol }}{{ number_format((float)($statsSummary['total_expense'] ?? 0), 2) }}
								</span>
								
								<span class="text-sm font-bold text-stone-500 dark:text-stone-400 text-left border-t border-stone-200 dark:border-stone-700 pt-0.5">期末</span>
								@php
									$closing = (float)($statsSummary['closing_balance'] ?? 0);
									$closingClass = $closing >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400';
								@endphp
								<span class="font-mono font-black text-base {{ $closingClass }} border-t border-stone-200 dark:border-stone-700 pt-0.5">
									{{ $currencySymbol }}{{ number_format($closing, 2) }}
								</span>
							</div>
						@else
							{{-- 期間模式：收入 → 支出（差額） --}}
							<div class="grid grid-cols-[60px_1fr] gap-x-4 text-right items-center mr-4 ml-auto">
								<span class="text-sm text-emerald-500 dark:text-emerald-400 text-left">收入</span>
								<span class="font-mono font-bold text-emerald-600 dark:text-emerald-400 text-base">
									+{{ $currencySymbol }}{{ number_format((float)($statsSummary['total_income'] ?? 0), 2) }}
								</span>
								
								<span class="text-sm text-rose-500 dark:text-rose-400 text-left">支出</span>
								<span class="font-mono font-bold text-rose-600 dark:text-rose-400 text-base">
									-{{ $currencySymbol }}{{ number_format((float)($statsSummary['total_expense'] ?? 0), 2) }}
								</span>
								
								<span class="text-sm font-bold text-stone-500 dark:text-stone-400 text-left border-t border-stone-200 dark:border-stone-700 pt-0.5">差額</span>
								@php
									$net = (float)($statsSummary['net_amount'] ?? 0);
									$netClass = $net > 0 ? 'text-emerald-600 dark:text-emerald-400' : ($net < 0 ? 'text-rose-600 dark:text-rose-400' : 'text-stone-500 dark:text-stone-400');
								@endphp
								<span class="font-mono font-black text-base {{ $netClass }} border-t border-stone-200 dark:border-stone-700 pt-0.5">
									{{ $net > 0 ? '+' : '' }}{{ $currencySymbol }}{{ number_format($net, 2) }}
								</span>
							</div>
						@endif
					</div>
				</div>
			</div>

			{{-- 內文與數據區塊 --}}
			<div class="p-4 md:p-6 bg-base-100/40 dark:bg-stone-900/40 flex-1 overflow-y-auto min-h-0 space-y-4">
				
				<div class="space-y-4 md:space-y-5 max-w-full">

					{{-- 日期範圍導航 --}}
					<div class="w-full max-w-full flex-shrink-0">
						<div class="flex items-center justify-between bg-stone-100/80 dark:bg-stone-800/80 p-2 rounded-2xl border border-stone-200/60 dark:border-stone-700/60 w-full shadow-inner">
							<x-button 
								icon="o-chevron-left" 
								class="btn-xs btn-ghost text-stone-500 dark:text-stone-400 hover:text-stone-800 dark:hover:text-stone-100 hover:bg-white dark:hover:bg-stone-700 px-3 h-8 min-h-0 rounded-xl transition-all shadow-sm" 
								wire:click="previousRange" 
							/>
							
							<div class="flex items-center gap-2 font-mono font-black text-sm md:text-sm text-stone-800 dark:text-stone-200 tracking-wider select-none">
								<x-heroicon-o-calendar class="w-5 h-5 text-stone-400 dark:text-stone-500" />
								<span>{{ $this->dateRangeDisplay }}</span>
							</div>
							
							<x-button 
								icon="o-chevron-right" 
								class="btn-xs btn-ghost text-stone-500 dark:text-stone-400 hover:text-stone-800 dark:hover:text-stone-100 hover:bg-white dark:hover:bg-stone-700 px-3 h-8 min-h-0 rounded-xl transition-all shadow-sm" 
								wire:click="nextRange" 
							/>
						</div>
					</div>

					{{-- 交易明細列表 - 按日期分組 (使用原有的 list 資料) --}}
					@php
						// 在 Blade 中將 list 按日期分組
						$groupedList = [];
						foreach($transactionsData['list'] ?? [] as $tx) {
							$date = substr($tx['recorded_at'], 0, 10);
							if (!isset($groupedList[$date])) {
								$groupedList[$date] = [];
							}
							$groupedList[$date][] = $tx;
						}
						// 按日期排序（最新的在前）
						krsort($groupedList);
					@endphp

					<div class="space-y-6">
						@forelse($groupedList as $date => $dayTransactions)
							<div class="space-y-3">
								{{-- 日期標頭 --}}
								<div class="text-sm font-bold text-stone-500 dark:text-stone-400 tracking-wider flex items-center gap-2 px-2">
									<span class="w-2 h-2 rounded-full bg-teal-500/70"></span>
									<span>{{ date('Y 年 m 月 d 日', strtotime($date)) }}</span>
									<span class="opacity-70">週{{ ['日','一','二','三','四','五','六'][date('w', strtotime($date))] }}</span>
									<span class="text-xs font-normal text-stone-400 dark:text-stone-500 ml-1">({{ count($dayTransactions) }} 筆)</span>
								</div>

								{{-- 當天交易卡片群組 --}}
								<div class="bg-base-100 rounded-2xl border border-stone-200/50 dark:border-stone-800 shadow-sm divide-y divide-stone-100 dark:divide-stone-800/60 overflow-hidden">
									@foreach($dayTransactions as $tx)
										@php
											$isIncome = $tx['is_income'] ?? false;
											$isExpense = $tx['is_expense'] ?? false;
											$isTransfer = $tx['is_transfer'] ?? false;
											
											$categoryIcon = $tx['category_icon'] ?? 'folder';
											$categoryName = $tx['category_name'] ?? null;
											
											if ($isTransfer) {
												$amountClass = 'text-sky-600 dark:text-sky-400';
												$iconColor = 'text-sky-500 dark:text-sky-400';
												$amountPrefix = '';
												$bgColor = 'bg-sky-100 dark:bg-sky-950/60';
											} elseif ($isIncome) {
												$amountClass = 'text-emerald-600 dark:text-emerald-400';
												$iconColor = 'text-emerald-500 dark:text-emerald-400';
												$amountPrefix = '+';
												$bgColor = 'bg-emerald-100 dark:bg-emerald-950/60';
											} elseif ($isExpense) {
												$amountClass = 'text-rose-600 dark:text-rose-400';
												$iconColor = 'text-rose-500 dark:text-rose-400';
												$amountPrefix = '-';
												$bgColor = 'bg-rose-100 dark:bg-rose-950/60';
											} else {
												$amountClass = 'text-stone-600 dark:text-stone-300';
												$iconColor = 'text-stone-500 dark:text-stone-400';
												$amountPrefix = '';
												$bgColor = 'bg-stone-200 dark:bg-stone-700';
											}
											
											$displayAmount = $tx['display_amount'] ?? $tx['amount'];
											
											$subCategoryName = '';
											if ($isTransfer) {
												$subCategoryName = ($accountId && $tx['from_account_id'] == $accountId) ? '轉出' : '轉入';
											} elseif ($categoryName) {
												$subCategoryName = $categoryName;
											} else {
												$subCategoryName = $isIncome ? '收入' : '支出';
											}
											
											$accountName = $tx['account_name'] ?? ($currentAccount->name ?? '');
											
											$summary = $tx['memo'] ?: '';
											if ($isTransfer && !$summary) {
												if ($accountId && $tx['from_account_id'] == $accountId) {
													$toName = $tx['to_account_name'] ?? '帳戶 #' . $tx['to_account_id'];
													$summary = '轉出至 ' . $toName;
												} elseif ($accountId && $tx['to_account_id'] == $accountId) {
													$fromName = $tx['from_account_name'] ?? '帳戶 #' . $tx['from_account_id'];
													$summary = '從 ' . $fromName . ' 轉入';
												} else {
													$summary = ($tx['from_account_name'] ?? '帳戶') . ' ➔ ' . ($tx['to_account_name'] ?? '帳戶');
												}
											}
										@endphp
										
										<div class="relative flex items-center py-3 px-4 hover:bg-stone-50/40 dark:hover:bg-stone-800/40 transition-all duration-150 cursor-pointer group border-l-4 border-transparent hover:border-stone-300 dark:hover:border-stone-600"
											 wire:click="$dispatch('open-transaction-modal', { transactionId: {{ $tx['id'] }} })">
											
											{{-- 圖標 --}}
											<div class="rounded-xl p-2.5 flex-shrink-0 {{ $bgColor }}">
												<x-dynamic-component 
													:component="'heroicon-o-' . $categoryIcon" 
													class="w-6 h-6 {{ $iconColor }}" />
											</div>
											
											{{-- 內容區域 --}}
											<div class="flex-1 min-w-0 ml-3 md:ml-4">
												<div class="flex items-center justify-between gap-2">
													<div class="flex items-center gap-2 min-w-0">
														<span class="text-base md:text-base font-semibold text-stone-800 dark:text-stone-200 flex-shrink-0">
															{{ $subCategoryName }}
														</span>
														
														@if($accountName)
															<span class="text-sm text-stone-500 dark:text-stone-400 font-normal truncate">
																（{{ $accountName }}）
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
														<span class="font-normal text-stone-400 dark:text-stone-500 text-sm">{{ $tx['currency_symbol'] ?? $currencySymbol }}</span>
														{{ $amountPrefix }}{{ number_format((float)$displayAmount, 2) }}
													</span>
												</div>
												
												{{-- 備註 + 時間 --}}
												<div class="flex items-center justify-between gap-2 mt-0.5">
													<span class="text-sm text-stone-500 dark:text-stone-400 truncate {{ $summary ? '' : 'italic' }}">
														{{ $summary ?: '無備註' }}
													</span>
													<span class="text-sm font-mono text-stone-400 dark:text-stone-500 flex-shrink-0">
														{{ Carbon\Carbon::parse($tx['recorded_at'])->format('H:i') }}
													</span>
												</div>
											</div>
										</div>
									@endforeach
								</div>
							</div>
						@empty
							<div class="text-center text-stone-400 dark:text-stone-500 py-10 bg-stone-50/20 dark:bg-stone-900/20 rounded-xl border border-dashed border-stone-200 dark:border-stone-800">
								<x-heroicon-o-document-text class="w-12 h-12 mx-auto mb-2 opacity-25 text-stone-400 dark:text-stone-500" />
								<p class="text-base md:text-sm tracking-wide font-medium">{{ $this->dateRangeDisplay }} 尚無任何流水紀錄</p>
							</div>
						@endforelse
					</div>
				</div>
			</div>

			{{-- 底層動作列 --}}
			<div class="flex flex-wrap items-center justify-between gap-2 p-3 md:p-4 border-t border-base-200 dark:border-stone-800 bg-stone-50 dark:bg-stone-900 flex-shrink-0">
				<div class="flex items-center gap-2 flex-wrap">
					@if($accountId)
						<x-button label="帳戶修改" icon="o-pencil" class="btn-orange" 
								  wire:click="editAccount" />
						
						@if($currentAccount && $currentAccount->is_active)
							<x-button label="隱藏此帳戶" icon="o-eye-slash" class="btn-purple" 
								  wire:click="toggleAccountVisibility" />
						@else
							<x-button label="恢復顯示" icon="o-eye" class="btn-purple" 
								  wire:click="toggleAccountVisibility" />
						@endif
					@endif
				</div>

				<x-button label="關閉" @click="$wire.showModal = false" class="btn-blue" />
			</div>
		</div>
	</x-modal>
</div>