<!-- filepath: resources/views/livewire/finance/transaction-list-modal.blade.php -->
<div>
	<x-modal 
		wire:model="showModal" 
		backdrop-blur-md 
		max-width="5xl" 
		box-class="border border-base-200 shadow-2xl rounded-t-3xl md:rounded-2xl p-0 overflow-hidden pt-16 pb-24 md:pt-0 md:pb-0 bg-base-100 text-base-content"
	>
		<div class="flex flex-col h-[calc(100vh-10rem)] md:h-[80vh] max-h-[80vh]">

			{{-- 頂部標題屏風 --}}
			<div class="p-4 md:p-5 bg-base-200/50 border-b border-base-200 flex-shrink-0">
				<div class="flex flex-col md:flex-row md:items-center justify-between gap-4">

					{{-- 左欄：標題和類型標籤 --}}
					<div class="w-full md:w-1/2">
						<div class="flex items-center gap-3 min-w-0">
							<div class="p-2 md:p-2.5 rounded-xl bg-base-200 text-base-content shadow-inner flex-shrink-0">
								<x-heroicon-o-document-magnifying-glass class="w-6 h-6 md:w-6 md:h-6" />
							</div>
							<div class="min-w-0 flex-1">
								<h3 class="text-lg md:text-lg font-black text-base-content tracking-wide font-sans flex items-center gap-2 flex-wrap">
									<span class="truncate">{{ $title }}</span> 
									<span class="text-xs font-semibold px-2 py-0.5 rounded-md bg-base-200 text-base-content opacity-80 font-mono flex-shrink-0">
										{{ $transactionType === 'expense' ? '支出' : ($transactionType === 'income' ? '收入' : '交易明細') }}
									</span>
								</h3>
								<p class="text-sm opacity-70 mt-0.5 flex items-center gap-2 flex-wrap">
									<span>共 <strong class="text-base-content font-mono">{{ $transactionsData['total_count'] ?? 0 }}</strong> 筆記錄</span>
									@if($currentAccount && !$currentAccount->is_active)
										<span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-warning/20 text-warning border border-warning/30">已隱藏帳戶</span>
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
								<span class="text-sm opacity-60 text-left">期初</span>
								<span class="font-mono font-bold text-base-content text-base opacity-90">
									{{ $currencySymbol }}{{ number_format((float)($statsSummary['opening_balance'] ?? 0), 2) }}
								</span>
								
								<span class="text-sm text-emerald-600 dark:text-emerald-400 text-left">收入</span>
								<span class="font-mono font-bold text-emerald-600 dark:text-emerald-400 text-base">
									+{{ $currencySymbol }}{{ number_format((float)($statsSummary['total_income'] ?? 0), 2) }}
								</span>
								
								<span class="text-sm text-rose-600 dark:text-rose-400 text-left">支出</span>
								<span class="font-mono font-bold text-rose-600 dark:text-rose-400 text-base">
									-{{ $currencySymbol }}{{ number_format((float)($statsSummary['total_expense'] ?? 0), 2) }}
								</span>
								
								<span class="text-sm font-bold opacity-70 text-left border-t border-base-200 pt-0.5">期末</span>
								@php
									$closing = (float)($statsSummary['closing_balance'] ?? 0);
									$closingClass = $closing >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400';
								@endphp
								<span class="font-mono font-black text-base {{ $closingClass }} border-t border-base-200 pt-0.5">
									{{ $currencySymbol }}{{ number_format($closing, 2) }}
								</span>
							</div>
						@else
							{{-- 期間模式：收入 → 支出（差額） --}}
							<div class="grid grid-cols-[60px_1fr] gap-x-4 text-right items-center mr-4 ml-auto">
								<span class="text-sm text-emerald-600 dark:text-emerald-400 text-left">收入</span>
								<span class="font-mono font-bold text-emerald-600 dark:text-emerald-400 text-base">
									+{{ $currencySymbol }}{{ number_format((float)($statsSummary['total_income'] ?? 0), 2) }}
								</span>
								
								<span class="text-sm text-rose-600 dark:text-rose-400 text-left">支出</span>
								<span class="font-mono font-bold text-rose-600 dark:text-rose-400 text-base">
									-{{ $currencySymbol }}{{ number_format((float)($statsSummary['total_expense'] ?? 0), 2) }}
								</span>
								
								<span class="text-sm font-bold opacity-70 text-left border-t border-base-200 pt-0.5">差額</span>
								@php
									$net = (float)($statsSummary['net_amount'] ?? 0);
									$netClass = $net > 0 ? 'text-emerald-600 dark:text-emerald-400' : ($net < 0 ? 'text-rose-600 dark:text-rose-400' : 'opacity-70');
								@endphp
								<span class="font-mono font-black text-base {{ $netClass }} border-t border-base-200 pt-0.5">
									{{ $net > 0 ? '+' : '' }}{{ $currencySymbol }}{{ number_format($net, 2) }}
								</span>
							</div>
						@endif
					</div>
				</div>
			</div>

			{{-- 內文與數據區塊 --}}
			<div class="p-4 md:p-6 bg-base-100 flex-1 overflow-y-auto min-h-0 space-y-4">
				
				<div class="space-y-4 md:space-y-5 max-w-full">

					{{-- 日期導航 (使用 date-nav 元件，根據 mode 動態調整) --}}
					<div class="w-full max-w-full flex-shrink-0">
						<x-date-nav 
							model="currentDate" 
							:type="$dateMode"
							:display="$dateDisplay" 
							:isCurrent="$isCurrentDate" 
							:disableNext="$isCurrentDate" 
						/>
					</div>

					{{-- 交易明細列表 - 按日期分組 --}}
					@php
						$groupedList = [];
						foreach($transactionsData['list'] ?? [] as $tx) {
							$date = substr($tx['recorded_at'], 0, 10);
							if (!isset($groupedList[$date])) {
								$groupedList[$date] = [];
							}
							$groupedList[$date][] = $tx;
						}
						krsort($groupedList);
					@endphp

					<div class="space-y-6">
						@forelse($groupedList as $date => $dayTransactions)
							<div class="space-y-3">
								{{-- 日期標頭 --}}
								<div class="text-sm font-bold opacity-70 tracking-wider flex items-center gap-2 px-2">
									<span class="w-2 h-2 rounded-full bg-teal-500/70"></span>
									<span>{{ date('Y 年 m 月 d 日', strtotime($date)) }}</span>
									<span class="opacity-70">週{{ ['日','一','二','三','四','五','六'][date('w', strtotime($date))] }}</span>
									<span class="text-xs font-normal opacity-50 ml-1">({{ count($dayTransactions) }} 筆)</span>
								</div>

								{{-- 當天交易卡片群組 --}}
								<div class="bg-base-100 rounded-2xl border border-base-200 shadow-sm divide-y divide-base-200/60 overflow-hidden">
									@foreach($dayTransactions as $tx)
										@php
											$typeTheme = $tx['account_type_theme'] ?? 'orange';
											$isIncome = $tx['is_income'] ?? false;
											$isExpense = $tx['is_expense'] ?? false;
											$isTransfer = $tx['is_transfer'] ?? false;
											$categoryIcon = $tx['category_icon'] ?? 'folder';
											$categoryName = $tx['category_name'] ?? null;
											if ($isTransfer) {
												$amountClass = 'text-sky-600 dark:text-sky-400';
												$iconColor = 'text-sky-600 dark:text-sky-400';
												$amountPrefix = '';
												$bgColor = 'bg-sky-500/10';
											} elseif ($isIncome) {
												$amountClass = 'text-emerald-600 dark:text-emerald-400';
												$iconColor = 'text-emerald-600 dark:text-emerald-400';
												$amountPrefix = '+';
												$bgColor = 'bg-emerald-500/10';
											} elseif ($isExpense) {
												$amountClass = 'text-rose-600 dark:text-rose-400';
												$iconColor = 'text-rose-600 dark:text-rose-400';
												$amountPrefix = '-';
												$bgColor = 'bg-rose-500/10';
											} else {
												$amountClass = 'text-base-content';
												$iconColor = 'opacity-50';
												$amountPrefix = '';
												$bgColor = 'bg-base-200';
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
										
										<!-- 單筆交易卡片 -->
										<div class="card-{{ $typeTheme }} relative flex items-center py-3 px-4 transition-all duration-150 cursor-pointer group hover:bg-base-200/50"
											 wire:click="$dispatch('open-transaction-modal', { transactionId: {{ $tx['id'] }} })">
											
											{{-- 左側主題側邊條 --}}
											<span class="account-left-bar absolute left-0 top-1 bottom-1 w-1 rounded-r-full z-10"></span>
											
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
														<span class="text-base md:text-base font-semibold text-base-content flex-shrink-0">
															{{ $subCategoryName }}
														</span>
														
														@if($accountName)
															<span class="text-sm opacity-60 font-normal truncate">
																（{{ $accountName }}）
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
														<span class="font-normal opacity-50 text-sm">{{ $tx['currency_symbol'] ?? $currencySymbol }}</span>
														{{ $amountPrefix }}{{ number_format((float)$displayAmount, 2) }}
													</span>
												</div>
												
												{{-- 備註 + 時間 --}}
												<div class="flex items-center justify-between gap-2 mt-0.5">
													<span class="text-sm opacity-60 truncate {{ $summary ? '' : 'italic' }}">
														{{ $summary ?: '無備註' }}
													</span>
													<span class="text-sm font-mono opacity-50 flex-shrink-0">
														{{ Carbon\Carbon::parse($tx['recorded_at'])->format('H:i') }}
													</span>
												</div>
											</div>
										</div>
									@endforeach
								</div>
							</div>
						@empty
							<div class="text-center opacity-60 py-10 bg-base-200/30 rounded-xl border border-dashed border-base-200">
								<x-heroicon-o-document-text class="w-12 h-12 mx-auto mb-2 opacity-30" />
								<p class="text-base md:text-sm tracking-wide font-medium">{{ $dateDisplay }} 尚無任何流水紀錄</p>
							</div>
						@endforelse
					</div>
				</div>
			</div>

			{{-- 底層動作列 --}}
			<div class="flex flex-wrap items-center justify-between gap-2 p-3 md:p-4 border-t border-base-200 bg-base-200/50 flex-shrink-0">
				<div class="flex items-center gap-2 flex-wrap">
					@if($accountId)
						<x-button label="帳戶修改" icon="o-pencil" class="btn-orange btn-sm" 
								  wire:click="editAccount" />
						
						@if($currentAccount && $currentAccount->is_active)
							<x-button label="隱藏此帳戶" icon="o-eye-slash" class="btn-purple btn-sm" 
								  wire:click="toggleAccountVisibility" />
						@else
							<x-button label="恢復顯示" icon="o-eye" class="btn-dark btn-sm" 
								  wire:click="toggleAccountVisibility" />
						@endif
					@endif
				</div>

				<x-button label="關閉" @click="$wire.showModal = false" class="btn-light btn-sm" />
			</div>
		</div>
	</x-modal>
</div>