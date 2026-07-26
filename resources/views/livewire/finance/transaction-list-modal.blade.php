<!-- filepath: resources/views/livewire/finance/transaction-list-modal.blade.php -->
<div>
	<x-modal 
		wire:model="showModal" 
		backdrop-blur-md 
		max-width="7xl" 
		box-class="border border-base-200 shadow-2xl rounded-t-3xl md:rounded-2xl p-0 overflow-hidden pt-16 pb-24 md:pt-0 md:pb-0"
	>
		<div class="flex flex-col h-[calc(100vh-10rem)] md:h-[85vh] max-h-[85vh]">
			
			{{-- 頂部標題屏風 --}}
			<div class="p-4 md:p-5 bg-stone-50 border-b border-base-200 flex-shrink-0">
				<div class="flex items-center gap-3">
					<div class="p-2 md:p-2.5 rounded-xl bg-base-200 text-stone-700 shadow-inner">
						<x-heroicon-o-document-magnifying-glass class="w-5 h-5 md:w-6 md:h-6" />
					</div>
					<div class="min-w-0 flex-1">
						<h3 class="text-base md:text-lg font-black text-stone-800 tracking-wide font-sans flex items-center gap-2 truncate">
							<span class="truncate">{{ $title }}</span> 
							<span class="text-xs font-medium text-stone-400 font-mono tracking-normal flex-shrink-0">{{ $subtitle }}</span>
						</h3>
						<p class="text-xs text-stone-500 mt-0.5 flex items-center gap-1.5 flex-wrap">
							<span>共計有 <strong class="text-stone-800 font-mono">{{ $transactionsData['total_count'] ?? 0 }}</strong> 筆交易歷史</span>
							@if($currentAccount && !$currentAccount->is_active)
								<span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-700 border border-amber-200">已隱藏帳戶</span>
							@endif
						</p>
					</div>
				</div>
			</div>

			{{-- 內文與數據區塊 --}}
			<div class="p-4 md:p-6 bg-base-100/40 flex-1 overflow-y-auto min-h-0">
				<div class="space-y-4 md:space-y-5 max-w-full">
					
					{{-- 年月導航 + 本月收支 --}}
					<div class="w-full max-w-full space-y-2 flex-shrink-0">
						<div class="flex items-center justify-between bg-stone-100/80 p-1.5 rounded-2xl border border-stone-200/60 w-full shadow-inner">
							<x-button 
								icon="o-chevron-left" 
								class="btn-xs btn-ghost text-stone-500 hover:text-stone-800 hover:bg-white px-2.5 h-7 min-h-0 rounded-xl transition-all shadow-sm" 
								wire:click="previousMonth" 
							/>
							
							<div class="flex items-center gap-1.5 font-mono font-black text-xs md:text-sm text-stone-800 tracking-wider select-none">
								<x-heroicon-o-calendar class="w-4 h-4 text-stone-400" />
								<span>{{ Carbon\Carbon::createFromFormat('Y-m', $transactionMonth)->format('Y 年 m 月') }}</span>
							</div>
							
							<x-button 
								icon="o-chevron-right" 
								class="btn-xs btn-ghost text-stone-500 hover:text-stone-800 hover:bg-white px-2.5 h-7 min-h-0 rounded-xl transition-all shadow-sm" 
								wire:click="nextMonth" 
								:disabled="Carbon\Carbon::createFromFormat('Y-m', $transactionMonth)->isSameMonth(now())" 
							/>
						</div>

						<div class="grid grid-cols-2 gap-2 w-full">
							<div class="flex items-center justify-between px-3 py-2 rounded-xl bg-emerald-500/10 border border-emerald-500/15 min-w-0">
								<div class="flex items-center gap-1 flex-shrink-0">
									<span class="w-2 h-2 rounded-full bg-emerald-500"></span>
									<span class="text-xs font-semibold text-emerald-900">小計收入</span>
								</div>
								<span class="font-mono font-extrabold text-xs sm:text-sm text-emerald-700 truncate min-w-0 ml-1">
									+{{ $currencySymbol }}{{ number_format((float)($transactionsData['total_income'] ?? 0), 2, '.', ',') }}
								</span>
							</div>

							<div class="flex items-center justify-between px-3 py-2 rounded-xl bg-rose-500/10 border border-rose-500/15 min-w-0">
								<div class="flex items-center gap-1 flex-shrink-0">
									<span class="w-2 h-2 rounded-full bg-rose-500"></span>
									<span class="text-xs font-semibold text-rose-900">小計支出</span>
								</div>
								<span class="font-mono font-extrabold text-xs sm:text-sm text-rose-700 truncate min-w-0 ml-1">
									-{{ $currencySymbol }}{{ number_format((float)($transactionsData['total_expense'] ?? 0), 2, '.', ',') }}
								</span>
							</div>
						</div>
					</div>

					{{-- 交易列表 --}}
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
										<span class="text-xs md:text-sm font-medium text-stone-700 truncate">
											{{ $subCategoryName }}
										</span>
										<span class="font-mono font-bold text-xs md:text-sm {{ $amountClass }} flex-shrink-0">
											<span class="font-normal text-stone-400 text-xs">{{ $currencySymbol }}</span>
											{{ $amountPrefix }}{{ number_format((float)$displayAmount, 2) }}
										</span>
									</div>
									
									<div class="flex items-center justify-between gap-2 mt-0.5">
										<span class="text-[11px] md:text-xs text-stone-400 truncate {{ $summary ? '' : 'italic' }}">
											{{ $summary ?: '無備註' }}
										</span>
										<span class="text-[10px] font-mono text-stone-300 flex-shrink-0">
											{{ Carbon\Carbon::parse($tx['recorded_at'])->format('Y-m-d') }}
										</span>
									</div>
								</div>
							</div>
						@empty
							<div class="text-center text-stone-400 py-10 bg-stone-50/20 rounded-xl border border-dashed border-stone-200">
								<x-heroicon-o-document-text class="w-10 h-10 mx-auto mb-2 opacity-25 text-stone-400" />
								<p class="text-xs md:text-sm tracking-wide font-medium">{{ $transactionMonth }} 月份尚無任何流水紀錄</p>
							</div>
						@endforelse
					</div>
				</div>
			</div>

			{{-- 底層動作列 --}}
			<div class="flex flex-wrap items-center justify-between gap-2 p-3 md:p-4 border-t border-base-200 bg-stone-50 flex-shrink-0">
				<div class="flex items-center gap-1.5 flex-wrap">
					@if($accountId)
						<x-button label="帳戶修改" icon="o-pencil" class="btn-xs md:btn-sm bg-base-100 border-base-300 hover:bg-stone-200 text-stone-700" 
								  wire:click="editAccount" />
						
						@if($currentAccount && $currentAccount->is_active)
							<x-button label="隱藏此帳戶" icon="o-eye-slash" class="btn-xs md:btn-sm btn-ghost text-amber-700 hover:bg-amber-50" 
									  wire:click="toggleAccountVisibility" />
						@else
							<x-button label="恢復顯示" icon="o-eye" class="btn-xs md:btn-sm btn-ghost text-emerald-700 hover:bg-emerald-50" 
									  wire:click="toggleAccountVisibility" />
						@endif
					@endif
				</div>

				<x-button label="關閉" @click="$wire.showModal = false" class="btn-xs md:btn-sm btn-ghost text-stone-500 hover:bg-stone-200 px-4" />
			</div>
		</div>
	</x-modal>
</div>