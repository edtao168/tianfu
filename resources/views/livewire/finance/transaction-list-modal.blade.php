<!-- filepath: resources/views/livewire/finance/transaction-list-modal.blade.php -->
<div>
	<x-modal 
		wire:model="showModal" 
		backdrop-blur-md 
		max-width="5xl" 
		box-class="border border-base-200 shadow-2xl rounded-t-3xl md:rounded-2xl p-0 overflow-hidden pt-16 pb-24 md:pt-0 md:pb-0"
	>
		<div class="flex flex-col h-[calc(100vh-10rem)] md:h-[80vh] max-h-[80vh]">

			{{-- 頂部標題屏風 --}}
			<div class="p-4 md:p-5 bg-stone-50 border-b border-base-200 flex-shrink-0">
				<div class="flex flex-col md:flex-row md:items-center justify-between gap-4">

					{{-- 左欄：標題和類型標籤 --}}
					<div class="w-full md:w-1/2">
						<div class="flex items-center gap-3 min-w-0">
							<div class="p-2 md:p-2.5 rounded-xl bg-base-200 text-stone-700 shadow-inner flex-shrink-0">
								<x-heroicon-o-document-magnifying-glass class="w-5 h-5 md:w-6 md:h-6" />
							</div>
							<div class="min-w-0 flex-1">
								<h3 class="text-base md:text-lg font-black text-stone-800 tracking-wide font-sans flex items-center gap-2 flex-wrap">
									<span class="truncate">{{ $title }}</span> 
									<span class="text-xs font-semibold px-2 py-0.5 rounded-md bg-stone-200/60 text-stone-600 font-mono flex-shrink-0">
										{{ $transactionType === 'expense' ? '支出' : ($transactionType === 'income' ? '收入' : '交易明細') }}
									</span>
								</h3>
								<p class="text-xs text-stone-500 mt-0.5 flex items-center gap-2 flex-wrap">
									<span>共 <strong class="text-stone-800 font-mono">{{ $transactionsData['total_count'] ?? 0 }}</strong> 筆記錄</span>
									@if($currentAccount && !$currentAccount->is_active)
										<span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-700 border border-amber-200">已隱藏帳戶</span>
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
								<span class="text-xs text-stone-400 text-left">期初</span>
								<span class="font-mono font-bold text-stone-600 text-sm">
									{{ $currencySymbol }}{{ number_format((float)($statsSummary['opening_balance'] ?? 0), 2) }}
								</span>
								
								<span class="text-xs text-emerald-400 text-left">收入</span>
								<span class="font-mono font-bold text-emerald-500 text-sm">
									+{{ $currencySymbol }}{{ number_format((float)($statsSummary['total_income'] ?? 0), 2) }}
								</span>
								
								<span class="text-xs text-rose-400 text-left">支出</span>
								<span class="font-mono font-bold text-rose-500 text-sm">
									-{{ $currencySymbol }}{{ number_format((float)($statsSummary['total_expense'] ?? 0), 2) }}
								</span>
								
								<span class="text-xs font-bold text-stone-500 text-left border-t border-stone-200 pt-0.5">期末</span>
								@php
									$closing = (float)($statsSummary['closing_balance'] ?? 0);
									$closingClass = $closing >= 0 ? 'text-emerald-600' : 'text-rose-600';
								@endphp
								<span class="font-mono font-black text-sm {{ $closingClass }} border-t border-stone-200 pt-0.5">
									{{ $currencySymbol }}{{ number_format($closing, 2) }}
								</span>
							</div>
						@else
							{{-- 期間模式：收入 → 支出（差額） --}}
							<div class="grid grid-cols-[60px_1fr] gap-x-4 text-right items-center mr-4 ml-auto">
								<span class="text-xs text-emerald-400 text-left">收入</span>
								<span class="font-mono font-bold text-emerald-500 text-sm">
									+{{ $currencySymbol }}{{ number_format((float)($statsSummary['total_income'] ?? 0), 2) }}
								</span>
								
								<span class="text-xs text-rose-400 text-left">支出</span>
								<span class="font-mono font-bold text-rose-500 text-sm">
									-{{ $currencySymbol }}{{ number_format((float)($statsSummary['total_expense'] ?? 0), 2) }}
								</span>
								
								<span class="text-xs font-bold text-stone-500 text-left border-t border-stone-200 pt-0.5">差額</span>
								@php
									$net = (float)($statsSummary['net_amount'] ?? 0);
									$netClass = $net > 0 ? 'text-emerald-600' : ($net < 0 ? 'text-rose-600' : 'text-stone-500');
								@endphp
								<span class="font-mono font-black text-sm {{ $netClass }} border-t border-stone-200 pt-0.5">
									{{ $net > 0 ? '+' : '' }}{{ $currencySymbol }}{{ number_format($net, 2) }}
								</span>
							</div>
						@endif
					</div>
				</div>
			</div>

			{{-- 內文與數據區塊 --}}
			<div class="p-4 md:p-6 bg-base-100/40 flex-1 overflow-y-auto min-h-0 space-y-4">
				
				<div class="space-y-4 md:space-y-5 max-w-full">

					{{-- 日期範圍導航 --}}
					<div class="w-full max-w-full flex-shrink-0">
						<div class="flex items-center justify-between bg-stone-100/80 p-1.5 rounded-2xl border border-stone-200/60 w-full shadow-inner">
							<x-button 
								icon="o-chevron-left" 
								class="btn-xs btn-ghost text-stone-500 hover:text-stone-800 hover:bg-white px-2.5 h-7 min-h-0 rounded-xl transition-all shadow-sm" 
								wire:click="previousRange" 
							/>
							
							<div class="flex items-center gap-1.5 font-mono font-black text-xs md:text-sm text-stone-800 tracking-wider select-none">
								<x-heroicon-o-calendar class="w-4 h-4 text-stone-400" />
								<span>{{ $this->dateRangeDisplay }}</span>
							</div>
							
							<x-button 
								icon="o-chevron-right" 
								class="btn-xs btn-ghost text-stone-500 hover:text-stone-800 hover:bg-white px-2.5 h-7 min-h-0 rounded-xl transition-all shadow-sm" 
								wire:click="nextRange" 
							/>
						</div>
					</div>

					{{-- 交易明細列表 --}}
					<div class="space-y-1.5">
						@forelse($transactionsData['list'] ?? [] as $tx)
							@php
								$isIncome = $tx['is_income'] ?? false;
								$isExpense = $tx['is_expense'] ?? false;
								$isTransfer = $tx['is_transfer'] ?? false;
								
								$categoryIcon = $tx['category_icon'] ?? 'folder';
								$categoryName = $tx['category_name'] ?? null;
								
								if ($isTransfer) {
									$amountClass = 'text-sky-600';
									$iconColor = 'text-sky-500';
									$borderColor = 'border-sky-200';
									$hoverBorderColor = 'hover:border-sky-300';
									$amountPrefix = '';
								} elseif ($isIncome) {
									$amountClass = 'text-emerald-600';
									$iconColor = 'text-emerald-500';
									$borderColor = 'border-emerald-200';
									$hoverBorderColor = 'hover:border-emerald-300';
									$amountPrefix = '+';
								} elseif ($isExpense) {
									$amountClass = 'text-rose-600';
									$iconColor = 'text-rose-500';
									$borderColor = 'border-rose-200';
									$hoverBorderColor = 'hover:border-rose-300';
									$amountPrefix = '-';
								} else {
									$amountClass = 'text-stone-600';
									$iconColor = 'text-stone-500';
									$borderColor = 'border-stone-200';
									$hoverBorderColor = 'hover:border-stone-300';
									$amountPrefix = '';
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
							
							<div class="relative flex items-center py-2 px-3 hover:bg-stone-50/80 rounded-lg transition-all duration-150 cursor-pointer group border border-gray-200 {{ $hoverBorderColor }}"
								 wire:click="$dispatch('open-transaction-modal', { transactionId: {{ $tx['id'] }} })">
								
								<div class="border-2 {{ $borderColor }} rounded-full p-1.5 md:p-2 flex-shrink-0">
									<x-dynamic-component 
										:component="'heroicon-o-' . $categoryIcon" 
										class="w-4 h-4 md:w-5 md:h-5 {{ $iconColor }}" />
								</div>
								
								<div class="flex-1 min-w-0 ml-2">
									<div class="flex items-center justify-between gap-2">
										<div class="flex items-center gap-1 min-w-0 truncate">
											<span class="text-sm md:text-sm font-medium text-stone-700 flex-shrink-0">
												{{ $subCategoryName }}
											</span>
											
											@if($accountName)
												<span class="text-xs text-stone-500 font-normal truncate">
													（{{ $accountName }}）
												</span>
											@endif
										</div>
										<span class="font-mono font-bold text-xs md:text-sm {{ $amountClass }} flex-shrink-0">
											<span class="font-normal text-stone-400 text-sm">{{ $tx['currency_symbol'] ?? $currencySymbol }}</span>
											{{ $amountPrefix }}{{ number_format((float)$displayAmount, 2) }}
										</span>
									</div>
									
									<div class="flex items-center justify-between gap-2 mt-0.5">
										<span class="text-xs text-stone-500 truncate {{ $summary ? '' : 'italic' }}">
											{{ $summary ?: '無備註' }}
										</span>
										<span class="text-xs font-mono text-stone-500 flex-shrink-0">
											{{ Carbon\Carbon::parse($tx['recorded_at'])->format('Y-m-d') }}
										</span>
									</div>
								</div>
							</div>
						@empty
							<div class="text-center text-stone-400 py-10 bg-stone-50/20 rounded-xl border border-dashed border-stone-200">
								<x-heroicon-o-document-text class="w-10 h-10 mx-auto mb-2 opacity-25 text-stone-400" />
								<p class="text-xs md:text-sm tracking-wide font-medium">{{ $this->dateRangeDisplay }} 尚無任何流水紀錄</p>
							</div>
						@endforelse
					</div>
				</div>
			</div>

			{{-- 底層動作列 --}}
			<div class="flex flex-wrap items-center justify-between gap-2 p-3 md:p-4 border-t border-base-200 bg-stone-50 flex-shrink-0">
				<div class="flex items-center gap-1.5 flex-wrap">
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