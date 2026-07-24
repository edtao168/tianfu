{{-- resources/views/livewire/finance/account-index.blade.php --}}

<div class="p-6 max-w-7xl mx-auto space-y-8">
    
	{{-- 統計卡片 --}}
	<div class="grid grid-cols-1 md:grid-cols-3 gap-5">
		@foreach(['today' => ['本日收支摘要', 'bg-gradient-to-r from-sky-400 to-sky-500'], 'month' => ['本月收支累計', 'bg-gradient-to-r from-emerald-400 to-emerald-500'], 'year' => ['本年年度統計', 'bg-gradient-to-r from-purple-400 to-purple-500']] as $period => $meta)
			@php
				$stats = $this->periodStats[$period];
				$baseSymbol = $stats['base_symbol'] ?? 'NT$';
				$currencyCount = count($stats['details'] ?? []);
			@endphp			
			<div class="stats shadow bg-base-100 border border-base-200 hover:shadow-lg transition-all duration-300 cursor-pointer" 
				 wire:click="showPeriodDetail('{{ $period }}')">
				<div class="stat">
					<div class="stat-title flex items-center gap-1.5 text-gray-500 font-medium">
						<span class="flex h-2 w-2 rounded-full {{ $meta[1] }}"></span>
						{{ $meta[0] }}
						@if($currencyCount > 1)
							<span class="text-xs text-gray-400 ml-1 flex items-center gap-0.5">
								( {{ $currencyCount }}種幣別 )
							</span>
						@endif
					</div>
					<div class="mt-3 space-y-2">
						<div class="flex justify-between items-center">
							<span class="text-sm text-gray-400">收入</span>
							<span class="text-lg font-bold text-success">+{{ $baseSymbol }} {{ $stats['income'] }}</span>
						</div>
						<div class="flex justify-between items-center">
							<span class="text-sm text-gray-400">支出</span>
							<span class="text-lg font-bold text-error">-{{ $baseSymbol }} {{ $stats['expense'] }}</span>
						</div>
					</div>
				</div>				
			</div>
		@endforeach
	</div>

    {{-- 標題列 --}}
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between border-b border-base-200 pb-5">
        <div>
            <h1 class="text-2xl font-bold text-base-content">淨資產帳戶總覽</h1>
            <p class="text-sm text-gray-500 mt-1">即時追蹤您的多幣別資產配置與資金流向</p>
        </div>
        <div class="flex items-center gap-3">
            <x-button label="新增帳戶" icon="o-plus" class="btn-outline btn-sm" wire:click="openCreateModal" />
            
            {{-- 觸發全域事件，通知 TransactionModal 組件彈出 --}}
            <x-button label="記一筆" icon="o-pencil-square" class="btn-primary btn-sm w-32" wire:click="$dispatch('open-transaction-modal')" />
        </div>
    </div>

    {{-- 貨幣總覽 --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        @foreach($currencyGroups as $group)
            @php $curStyle = config("business.currencies.{$group['currency']}") ?? config("business.currencies.TWD"); @endphp
            <div class="{{ $curStyle['bg'] }} p-5 rounded-2xl border shadow-sm flex justify-between items-center transition-all duration-300 hover:scale-[1.02]">
                <div>
                    <div class="text-[11px] font-bold opacity-60 tracking-wider uppercase">{{ $group['currency_name'] }} ({{ $group['currency'] }})</div>
                    <div class="text-2xl font-black text-base-content mt-1">
                        <span class="{{ $curStyle['symbol_color'] }} mr-0.5">{{ $group['currency_symbol'] }}</span>{{ number_format($group['total_balance'], 2) }}
                    </div>
                </div>
                <div class="text-2xl font-black opacity-10 font-mono tracking-widest">{{ $group['currency'] }}</div>
            </div>
        @endforeach
    </div>

    {{-- 帳戶列表 --}}
    @foreach($currencyGroups as $group)
        @php $curStyle = config("business.currencies.{$group['currency']}") ?? config("business.currencies.TWD"); @endphp
        <div class="space-y-3 pt-2 animate-fadeIn">
            <h2 class="text-sm font-bold text-gray-500 tracking-wider flex items-center gap-2">
                <span class="w-1.5 h-4.5 rounded-full {{ $curStyle['tag'] }} bg-current"></span> 
                {{ $group['currency_name'] }}資產明細
            </h2>
            
            <div class="block md:hidden">
                @include('includes._account-card')
            </div>

            <div class="hidden md:grid grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($group['accounts'] as $account)
                    @php $typeStyle = config("business.account_types.{$account->type}") ?? config("business.account_types.cash"); @endphp
                    <div wire:click="viewAccountTransactions({{ $account->id }})" class="{{ $typeStyle['bg'] }} {{ $typeStyle['border'] }} p-4 rounded-xl border shadow-sm cursor-pointer transition-all duration-200 hover:shadow-md flex flex-col justify-between h-28 active:scale-[0.99] relative overflow-hidden pl-5">
                        <span class="absolute left-0 top-0 bottom-0 w-1.5 {{ $typeStyle['left_bar'] }} opacity-80"></span>
                        <div class="flex justify-between items-start">
                            <div class="flex items-center gap-2">
                                <x-dynamic-component 
                    :component="$typeStyle['icon']" 
                    class="w-4 h-4 opacity-40 text-base-content" />
                                <span class="font-bold text-base-content text-md">{{ $account->name }}</span>
                            </div>
                            <span class="px-2 py-0.5 text-[10px] font-extrabold tracking-wider rounded-md {{ $typeStyle['badge'] }}">
                                {{ $typeStyle['name'] }}
                            </span>
                        </div>
                        <div class="flex justify-between items-end mt-2">
                            <span class="text-[11px] text-gray-400 font-medium">當前餘額</span>
                            <span class="font-mono text-xl font-extrabold text-base-content">
                                <span class="{{ $curStyle['symbol_color'] }} mr-0.5 text-base font-bold">{{ $group['currency_symbol'] }}</span>{{ number_format($account->balance, 2) }}
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach

	{{-- 帳戶 Modal --}}
	<x-modal 
		wire:model="showAccountModal" 
		backdrop-blur-md 
		max-width="2xl" 
		box-class="border border-base-200 shadow-2xl rounded-2xl p-0 overflow-hidden"
	>
		{{-- 使用 flex 容器控制高度 --}}
		<div class="flex flex-col" style="height: 85vh; max-height: 85vh;">
			
			{{-- 頂部標題：固定不滾動 --}}
			<div class="p-5 bg-stone-50 border-b border-base-200 flex-shrink-0">
				<div class="flex items-center gap-3">
					<div class="p-2.5 rounded-xl bg-sky-950/5 text-sky-900">
						<x-heroicon-o-wallet class="w-6 h-6" />
					</div>
					<div>
						<h3 class="text-lg font-bold text-stone-800 modal-title">
							{{ $editingAccountId ? '編輯資產帳戶' : '新增資產帳戶' }}
						</h3>
						<p class="text-xs text-stone-500 mt-0.5">請填寫零售門市資產帳戶之基礎參數與初始餘額</p>
					</div>
				</div>
			</div>

			{{-- 表單內容：可滾動區域 --}}
			<div class="p-6 bg-base-100/40 flex-1 overflow-y-auto min-h-0">
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
							@foreach($availableCurrencies as $code => $info)
								@php 
									$isSelected = ($accountCurrency === $code);
									$curConfig = config("business.currencies.{$code}") ?? config("business.currencies.TWD");
								@endphp
								<button type="button" 
									class="flex flex-col items-center justify-center py-2.5 px-3 rounded-xl border transition-all duration-200 {{ $isSelected ? 'btn-ghost active ring-1 ring-sky-300' : 'bg-stone-50' }}" 
									wire:click="$set('accountCurrency', '{{ $code }}')">
									<span class="text-xs font-black">{{ $info['name'] }}</span>
									<span class="text-[10px] opacity-70 font-mono mt-0.5">{{ $code }} ({{ $curConfig['symbol'] ?? '$' }})</span>
								</button>
							@endforeach
						</div>
					</div>

					<div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end bg-stone-50 p-4 rounded-xl border border-sky-100/50">
						<div class="md:col-span-2">
							<x-input label="初始資產 (以選定幣別計價)" wire:model="accountBalance" 
									 prefix="{{ config('business.currencies.'.$accountCurrency.'.symbol') ?? '$' }}" 
									 type="number" step="0.0001" class="font-mono text-lg font-bold" required />
						</div>
						<div class="text-xs text-stone-500 leading-relaxed md:pb-2">
							💡 初始資產，後續庫存採購與銷售折抵將會連動此餘額進行異動。
						</div>
					</div>

					<div class="grid grid-cols-1 gap-4">
						<x-textarea 
							label="備忘 (Memo)" 
							wire:model="accountMemo" 
							placeholder="例如：此帳戶為中信銀行信義分行，主要用於官網收款..."
							rows="2"
							hint="僅供內部參考，不會顯示於任何客戶單據上" />
					</div>

				</x-form>
			</div>

			{{-- 底部按鈕：固定不滾動 --}}
			<div class="flex flex-wrap items-center justify-end gap-3 p-4 border-t border-base-200 bg-stone-50 flex-shrink-0">
				<x-button label="取消" @click="$wire.showAccountModal = false" class="btn-ghost text-stone-700 btn-sm" />
				<x-button label="確認儲存" type="submit" class="btn bg-stone-100 btn-sm px-6" icon="o-check" wire:click="saveAccount" />
			</div>
		</div>
	</x-modal>

	{{-- 分幣別詳情 Modal --}}
	<x-modal wire:model="showPeriodDetailModal" :title="$periodDetailTitle" separator width="90%">
		<div class="space-y-4 p-2">
			@if(empty($periodDetailData))
				<div class="text-center text-gray-400 py-8">
					<x-heroicon-o-information-circle class="w-12 h-12 mx-auto mb-2 opacity-30" />
					<p>此期間無任何交易記錄</p>
				</div>
			@else
				<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
					@foreach($periodDetailData as $currency => $data)
						<div class="rounded-lg p-4 border {{ $data['bg'] ?? 'bg-base-200' }}">
							<div class="flex justify-between items-center mb-3">
								<div>
									<span class="font-bold text-lg">{{ $data['currency_name'] }}</span>
									<span class="text-sm text-gray-400 ml-2">({{ $currency }})</span>
								</div>
								<span class="text-2xl font-bold opacity-20">{{ $currency }}</span>
							</div>
							<div class="grid grid-cols-2 gap-3">
								<div class="bg-success/10 rounded-lg p-3">
									<div class="text-xs text-gray-500">收入</div>
									<div class="text-success font-bold text-lg">
										+{{ $data['currency_symbol'] }}{{ number_format((float)$data['income'], 2) }}
									</div>
									@if($currency !== $this->getBaseCurrency())
										<div class="text-xs text-gray-400 mt-1">
											≈ {{ $this->getBaseCurrencySymbol() }} {{ number_format((float)$this->convertToBase($data['income'], $currency), 2) }}
										</div>
									@endif
								</div>
								<div class="bg-error/10 rounded-lg p-3">
									<div class="text-xs text-gray-500">支出</div>
									<div class="text-error font-bold text-lg">
										-{{ $data['currency_symbol'] }}{{ number_format((float)$data['expense'], 2) }}
									</div>
									@if($currency !== $this->getBaseCurrency())
										<div class="text-xs text-gray-400 mt-1">
											≈ {{ $this->getBaseCurrencySymbol() }} {{ number_format((float)$this->convertToBase($data['expense'], $currency), 2) }}
										</div>
									@endif
								</div>
							</div>
							<div class="mt-3 pt-2 border-t border-base-300 text-xs text-gray-400 flex justify-between">
								<span>匯率: 1 {{ $currency }} = {{ number_format($data['rate'], 4) }} {{ $this->getBaseCurrency() }}</span>
								<span>小計: {{ $data['currency_symbol'] }}{{ number_format((float)bcadd($data['income'], $data['expense'], 4), 2) }}</span>
							</div>
						</div>
					@endforeach
				</div>
				
				<div class="bg-primary/5 rounded-lg p-4 border border-primary/20">
					<div class="flex justify-between items-center">
						<span class="font-bold">總計 ({{ $this->getBaseCurrency() }})</span>
						<div class="flex gap-6">
							<span class="text-success font-bold">
								+{{ $this->getBaseCurrencySymbol() }} {{ $periodDetailSummary['total_income'] ?? '0.00' }}
							</span>
							<span class="text-error font-bold">
								-{{ $this->getBaseCurrencySymbol() }} {{ $periodDetailSummary['total_expense'] ?? '0.00' }}
							</span>
						</div>
					</div>
				</div>
			@endif
		</div>
		<x-slot:actions>
			<x-button label="關閉" @click="$wire.showPeriodDetailModal = false" class="btn-ghost" />
		</x-slot:actions>
	</x-modal>

	{{-- 交易流水 Modal --}}
	<x-modal wire:model="showAccountTransactionsModal" backdrop-blur-md max-width="7xl" box-class="border border-base-200 shadow-2xl rounded-2xl p-0 overflow-hidden">
		
		{{-- 使用 flex 容器控制高度 --}}
		<div class="flex flex-col" style="height: 85vh; max-height: 85vh;">
			
			{{-- 頂部標題屏風：固定不滾動 --}}
			<div class="p-5 bg-stone-50 border-b border-base-200 flex-shrink-0">
				<div class="flex items-center gap-3">
					<div class="p-2.5 rounded-xl bg-base-200 text-stone-700 shadow-inner">
						<x-heroicon-o-document-magnifying-glass class="w-6 h-6" />
					</div>
					<div>
						<h3 class="text-lg font-black text-stone-800 tracking-wide font-sans flex items-center gap-2">
							{{ $selectedAccountName }} 
							<span class="text-xs font-medium text-stone-400 font-mono tracking-normal">明細流水</span>
						</h3>
						<p class="text-xs text-stone-500 mt-0.5 flex items-center gap-1.5">
							<span>共計有 <strong class="text-stone-800 font-mono">{{ $accountTransactions['total_count'] ?? 0 }}</strong> 筆交易歷史</span>
							@if(isset($selectedAccount) && !$selectedAccount->is_active)
								<span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-700 border border-amber-200">已隱藏帳戶</span>
							@endif
						</p>
					</div>
				</div>
			</div>

			{{-- 內文與數據區塊：可滾動區域 --}}
			<div class="p-6 bg-base-100/40 flex-1 overflow-y-auto min-h-0">
				<div class="space-y-5 max-w-full">
					
					{{-- 月份導航 --}}
					{{-- resources/views/livewire/finance/account-index.blade.php (流水 Modal 內的全寬年月與收支統計區塊) --}}

<div class="w-full max-w-full space-y-2 flex-shrink-0">
    
    {{-- 第一行：全佔一行 年月膠囊選擇器 --}}
    <div class="flex items-center justify-between bg-stone-100/80 p-1.5 rounded-2xl border border-stone-200/60 w-full shadow-inner">
        <x-button 
            icon="o-chevron-left" 
            class="btn-xs btn-ghost text-stone-500 hover:text-stone-800 hover:bg-white px-2.5 h-7 min-h-0 rounded-xl transition-all shadow-sm" 
            wire:click="previousMonth" 
        />
        
        <div class="flex items-center gap-1.5 font-mono font-black text-sm text-stone-800 tracking-wider select-none">
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

    {{-- 第二行：本月收支數據卡片 (1:1 雙欄滿寬) --}}
    <div class="grid grid-cols-2 gap-2 w-full">
        
        {{-- 本月收入 --}}
        <div class="flex items-center justify-between px-3 py-2 rounded-xl bg-emerald-500/10 border border-emerald-500/15 min-w-0">
            <div class="flex items-center gap-1.5 flex-shrink-0">
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                <span class="text-xs font-semibold text-emerald-900">收入</span>
            </div>
            <span class="font-mono font-extrabold text-xs sm:text-sm text-emerald-700 truncate min-w-0 ml-1">
                +{{ $selectedCurrencySymbol }}{{ number_format((float)($accountTransactions['total_income'] ?? 0), 2, '.', ',') }}
            </span>
        </div>

        {{-- 本月支出 --}}
        <div class="flex items-center justify-between px-3 py-2 rounded-xl bg-rose-500/10 border border-rose-500/15 min-w-0">
            <div class="flex items-center gap-1.5 flex-shrink-0">
                <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                <span class="text-xs font-semibold text-rose-900">支出</span>
            </div>
            <span class="font-mono font-extrabold text-xs sm:text-sm text-rose-700 truncate min-w-0 ml-1">
                -{{ $selectedCurrencySymbol }}{{ number_format((float)($accountTransactions['total_expense'] ?? 0), 2, '.', ',') }}
            </span>
        </div>

    </div>

</div>

					{{-- 交易列表（緊湊樣式） --}}
					<div class="space-y-1.5">
						@forelse($accountTransactions['list'] ?? [] as $tx)
							@php
								$isIncome = $tx['is_income'] ?? false;
								$isExpense = $tx['is_expense'] ?? false;
								$isTransfer = $tx['is_transfer'] ?? false;
								
								// 使用預先處理好的分類資訊
								$categoryIcon = $tx['category_icon'] ?? 'folder';
								$categoryName = $tx['category_name'] ?? null;
								
								// 根據交易類型設定顏色（優先判斷轉帳）
								if ($isTransfer) {
									// 轉帳：統一使用藍色
									$amountClass = 'text-sky-600';
									$iconColor = 'text-sky-500';
									$borderColor = 'border-sky-200';
									$hoverBorderColor = 'hover:border-sky-300';
									$amountPrefix = '';
									$typeLabel = '轉帳';
								} elseif ($isIncome) {
									// 收入：綠色
									$amountClass = 'text-emerald-600';
									$iconColor = 'text-emerald-500';
									$borderColor = 'border-emerald-200';
									$hoverBorderColor = 'hover:border-emerald-300';
									$amountPrefix = '+';
									$typeLabel = '收入';
								} elseif ($isExpense) {
									// 支出：紅色
									$amountClass = 'text-rose-600';
									$iconColor = 'text-rose-500';
									$borderColor = 'border-rose-200';
									$hoverBorderColor = 'hover:border-rose-300';
									$amountPrefix = '-';
									$typeLabel = '支出';
								} else {
									// 預設
									$amountClass = 'text-stone-600';
									$iconColor = 'text-stone-500';
									$borderColor = 'border-stone-200';
									$hoverBorderColor = 'hover:border-stone-300';
									$amountPrefix = '';
									$typeLabel = '未知';
								}
								
								// 顯示金額
								$displayAmount = $tx['amount_in_account_currency'] ?? $tx['amount'];
								
								// 獲取子類名稱
								$subCategoryName = '';
								if ($isTransfer) {
									if ($tx['from_account_id'] == $selectedAccountId) {
										$subCategoryName = '轉出';
									} else {
										$subCategoryName = '轉入';
									}
								} elseif ($categoryName) {
									$subCategoryName = $categoryName;
								} else {
									$subCategoryName = $isIncome ? '收入' : '支出';
								}
								
								// 摘要內容 - 使用帳戶名稱而非 ID
								$summary = $tx['memo'] ?: '';
								if ($isTransfer && !$summary) {
									if ($tx['from_account_id'] == $selectedAccountId) {
										// 轉出：顯示轉入的帳戶名稱
										$toName = $tx['to_account_name'] ?? '帳戶 #' . $tx['to_account_id'];
										$summary = '轉出至 ' . $toName;
									} else {
										// 轉入：顯示從哪個帳戶轉入
										$fromName = $tx['from_account_name'] ?? '帳戶 #' . $tx['from_account_id'];
										$summary = '從 ' . $fromName . ' 轉入';
									}
								}
							@endphp
							
							{{-- 點擊卡片觸發編輯事件 --}}
							<div class="relative flex items-center py-2 px-3 hover:bg-stone-50/80 rounded-lg transition-all duration-150 cursor-pointer group border border-gray-200 {{ $hoverBorderColor }}"
								 wire:click="$dispatch('edit-transaction', { transactionId: {{ $tx['id'] }} })">
								
								{{-- 圖示（動態組裝 component 名稱，顏色跟隨收支類型） --}}
								<div class="border-2 {{ $borderColor }} rounded-full p-2 flex-shrink-0">
									<x-dynamic-component 
										:component="'heroicon-o-' . $categoryIcon" 
										class="w-5 h-5 {{ $iconColor }}" />
								</div>
								
								{{-- 內容區域（兩行） --}}
								<div class="flex-1 min-w-0 ml-2">
									{{-- 第一行：子類名稱 + 金額 --}}
									<div class="flex items-center justify-between gap-2">
										<span class="text-sm font-medium text-stone-700 truncate">
											{{ $subCategoryName }}
										</span>
										<span class="font-mono font-bold text-sm {{ $amountClass }} flex-shrink-0">
											<span class="font-normal text-stone-400 text-xs">{{ $selectedCurrencySymbol }}</span>
											{{ $amountPrefix }}{{ number_format((float)$displayAmount, 2) }}
										</span>
									</div>
									
									{{-- 第二行：摘要 + 日期 --}}
									<div class="flex items-center justify-between gap-2 mt-0.5">
										<span class="text-xs text-stone-400 truncate {{ $summary ? '' : 'italic' }}">
											{{ $summary ?: '無備註' }}
										</span>
										<span class="text-[10px] font-mono text-stone-300 flex-shrink-0">
											{{ Carbon\Carbon::parse($tx['recorded_at'])->format('Y-m-d') }}
										</span>
									</div>
								</div>
							</div>
						@empty
							<div class="text-center text-stone-400 py-12 bg-stone-50/20 rounded-xl border border-dashed border-stone-200">
								<x-heroicon-o-document-text class="w-12 h-12 mx-auto mb-3 opacity-25 text-stone-400" />
								<p class="text-sm tracking-wide font-medium">{{ $transactionMonth }} 月份尚無任何收支流水紀錄</p>
								<p class="text-xs text-stone-300 mt-1">點擊「記一筆」開始記錄您的第一筆交易</p>
							</div>
						@endforelse
					</div>
					
					{{-- 筆數提示 --}}
					@if(($accountTransactions['total_count'] ?? 0) > 0)
						<div class="text-center text-xs text-stone-400 py-2 border-t border-stone-100 mt-2">
							顯示 {{ count($accountTransactions['list'] ?? []) }} 筆，共 {{ $accountTransactions['total_count'] ?? 0 }} 筆交易
						</div>
					@endif
				</div>
			</div>

			{{-- 底層動作列：固定不滾動 --}}
			<div class="flex flex-wrap items-center justify-between gap-3 p-4 border-t border-base-200 bg-stone-50 flex-shrink-0">
				<div class="flex items-center gap-2 flex-wrap">
					<x-button label="帳戶修改" icon="o-pencil" class="btn-sm bg-base-100 border-base-300 hover:bg-stone-200 text-stone-700" 
							  wire:click="editAccount" />
					
					@if(isset($selectedAccount) && $selectedAccount->is_active)
						<x-button label="隱藏此帳戶" icon="o-eye-slash" class="btn-sm btn-ghost text-amber-700 hover:bg-amber-50" 
								  wire:click="toggleAccountVisibility" />
					@else
						<x-button label="恢復顯示" icon="o-eye" class="btn-sm btn-ghost text-emerald-700 hover:bg-emerald-50" 
								  wire:click="toggleAccountVisibility" />
					@endif

					<x-button label="刪除" icon="o-trash" class="btn-sm btn-ghost text-rose-700 hover:bg-rose-50" 
							  wire:click="deleteAccount" 
							  wire:confirm="確定要刪除此帳戶嗎？此操作無法復原！"
							  :disabled="$accountTransactions['total_count'] > 0" />
				</div>

				<x-button label="關閉對話框" @click="$wire.showAccountTransactionsModal = false" class="btn-sm btn-ghost text-stone-500 hover:bg-stone-200 px-5" />
			</div>
		</div>
	</x-modal>
</div>