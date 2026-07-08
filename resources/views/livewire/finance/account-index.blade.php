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
            
            {{-- 核心重構：改為觸發全域事件，通知 Layout 中的共用 TransactionModal 組件彈出 --}}
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
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($group['accounts'] as $account)
                    @php $typeStyle = config("business.account_types.{$account->type}") ?? config("business.account_types.cash"); @endphp
                    <div wire:click="viewAccountTransactions({{ $account->id }})" class="{{ $typeStyle['bg'] }} {{ $typeStyle['border'] }} p-4 rounded-xl border shadow-sm cursor-pointer transition-all duration-200 hover:shadow-md flex flex-col justify-between h-28 active:scale-[0.99] relative overflow-hidden pl-5">
                        <span class="absolute left-0 top-0 bottom-0 w-1.5 {{ $typeStyle['left_bar'] }} opacity-80"></span>
                        <div class="flex justify-between items-start">
                            <div class="flex items-center gap-2">
                                {{-- 改用 heroicon --}}
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

    {{-- 帳戶管理 Modal (本頁面專屬 Model，繼續保留) --}}
    <x-modal wire:model="showAccountModal" :title="$editingAccountId ? '編輯資產帳戶' : '新增資產帳戶'" separator progress-indicator>
        <x-form wire:submit="saveAccount">
            <x-input label="帳戶名稱" wire:model="accountName" placeholder="例如：恆生銀行、富途證券" inline required />
            <div class="flex flex-wrap gap-2 p-1 rounded-lg bg-base-200">
                @foreach($availableCurrencies as $code => $info)
                    <button type="button" class="flex-1 min-w-[70px] py-2 text-xs font-bold rounded-md transition-all {{ $accountCurrency === $code ? 'bg-primary text-white shadow' : 'text-gray-500 hover:bg-base-300' }}" wire:click="$set('accountCurrency', '{{ $code }}')">
                        {{ $info['name'] }} ({{ $code }})
                    </button>
                @endforeach
            </div>
            <x-select label="帳戶類型" wire:model="accountType" :options="$accountTypeOptions" inline required />
            <x-input label="當前餘額" wire:model="accountBalance" prefix="$" type="number" step="0.01" inline required />
            <x-slot:actions>
                <x-button label="取消" @click="$wire.showAccountModal = false" />
                <x-button label="確認儲存" type="submit" class="btn-primary" />
            </x-slot:actions>
        </x-form>
    </x-modal>

	{{-- 分幣別詳情 Modal (加寬) --}}
	<x-modal wire:model="showPeriodDetailModal" :title="$periodDetailTitle" separator width="90%">
		<div class="space-y-4 p-2">
			
			@if(empty($periodDetailData))
				<div class="text-center text-gray-400 py-8">
					{{-- 改用 heroicon --}}
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
				
				{{-- 總計 --}}
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

	{{-- 交易流水 Modal (加寬) --}}
	<x-modal wire:model="showAccountTransactionsModal" :title="$selectedAccountName . ' - 交易流水'" separator max-width="7xl">
		<div class="space-y-4 p-2">
			{{-- 操作按鈕列 --}}
			<div class="flex flex-wrap justify-between items-center gap-3 pb-3 border-b border-base-200">
				{{-- 左側：標題資訊 --}}
				<div class="flex items-center gap-3">
					<span class="text-sm text-gray-500">
						共 <span class="font-bold text-base-content">{{ $accountTransactions['total_count'] ?? 0 }}</span> 筆交易
					</span>
					@if(isset($selectedAccount) && !$selectedAccount->is_active)
						<span class="badge badge-warning badge-sm">已隱藏</span>
					@endif
				</div>

				{{-- 右側：操作按鈕 --}}
				<div class="flex flex-wrap items-center gap-2">
					{{-- 修改按鈕 --}}
					<x-button label="帳戶修改" icon="o-pencil" class="btn-sm btn-outline" 
							  wire:click="editAccount" />
					
					{{-- 刪除按鈕 (有交易記錄不允許) --}}
					<x-button label="刪除" icon="o-trash" class="btn-sm btn-outline btn-error" 
							  wire:click="deleteAccount" 
							  wire:confirm="確定要刪除此帳戶嗎？此操作無法復原！"
							  :disabled="$accountTransactions['total_count'] > 0" />
					
					{{-- 隱藏/顯示按鈕 (切換 is_active) --}}
					@if(isset($selectedAccount) && $selectedAccount->is_active)
						<x-button label="隱藏" icon="o-eye-slash" class="btn-sm btn-outline btn-warning" 
								  wire:click="toggleAccountVisibility" />
					@else
						<x-button label="顯示" icon="o-eye" class="btn-sm btn-outline btn-success" 
								  wire:click="toggleAccountVisibility" />
					@endif
				</div>
			</div>

			{{-- 月份導航 --}}
			<div class="flex flex-wrap justify-between items-center gap-3">
				<div class="flex items-center gap-2">
					<x-button label="◀" class="btn-sm btn-outline" wire:click="previousMonth" />
					<span class="font-bold text-lg min-w-[120px] text-center">
						{{ Carbon\Carbon::createFromFormat('Y-m', $transactionMonth)->format('Y年m月') }}
					</span>
					<x-button label="▶" class="btn-sm btn-outline" wire:click="nextMonth" 
							  :disabled="Carbon\Carbon::createFromFormat('Y-m', $transactionMonth)->isSameMonth(now())" />
				</div>
				<div class="flex flex-wrap gap-4 text-sm">
					<span class="badge badge-success gap-1">
						{{-- 改用 heroicon --}}
						<x-heroicon-o-arrow-trending-up class="w-3 h-3" />
						收入: +{{ $selectedCurrencySymbol }}{{ $accountTransactions['total_income'] ?? '0.00' }}
					</span>
					<span class="badge badge-error gap-1">
						{{-- 改用 heroicon --}}
						<x-heroicon-o-arrow-trending-down class="w-3 h-3" />
						支出: -{{ $selectedCurrencySymbol }}{{ $accountTransactions['total_expense'] ?? '0.00' }}
					</span>
					<span class="badge badge-ghost gap-1">
						{{-- 改用 heroicon --}}
						<x-heroicon-o-document-text class="w-3 h-3" />
						共 {{ $accountTransactions['total_count'] ?? 0 }} 筆
					</span>
				</div>
			</div>

			{{-- 交易列表 --}}
			<div class="overflow-x-auto rounded-lg border border-base-200">
				<table class="table table-sm table-zebra">
					<thead>
						<tr>
							<th class="w-32">日期</th>
							<th class="w-20">類型</th>
							<th class="w-28 text-right">金額</th>
							<th>分類</th>
							<th>備註</th>
						</tr>
					</thead>
					<tbody>
						@forelse($accountTransactions['list'] ?? [] as $tx)
							<tr>
								<td class="text-xs font-mono">
									{{ Carbon\Carbon::parse($tx['recorded_at'])->format('Y-m-d H:i') }}
								</td>
								<td>
									@if($tx['type'] === 'income')
										<span class="badge badge-success badge-sm">收入</span>
									@elseif($tx['type'] === 'expense')
										<span class="badge badge-error badge-sm">支出</span>
									@else
										<span class="badge badge-info badge-sm">轉帳</span>
									@endif
								</td>
								<td class="text-right font-mono font-bold {{ $tx['type'] === 'income' ? 'text-success' : 'text-error' }}">
									{{ $tx['type'] === 'income' ? '+' : '-' }}{{ $selectedCurrencySymbol }}{{ number_format($tx['amount'], 2) }}
								</td>
								<td>
									@if($tx['category_id'])
										@php
											$category = App\Models\Category::find($tx['category_id']);
										@endphp
										@if($category)
											<span class="text-sm">{{ $category->name }}</span>
										@endif
									@else
										<span class="text-gray-400 text-xs">未分類</span>
									@endif
								</td>
								<td class="text-sm text-gray-500 max-w-xs truncate">
									{{ $tx['memo'] ?? '-' }}
								</td>
							</tr>
						@empty
							<tr>
								<td colspan="5" class="text-center text-gray-400 py-8">
									{{-- 改用 heroicon --}}
									<x-heroicon-o-document-text class="w-10 h-10 mx-auto mb-2 opacity-30" />
									<p>{{ $transactionMonth }} 月份無交易記錄</p>
								</td>
							</tr>
						@endforelse
					</tbody>
				</table>
			</div>
		</div>
		<x-slot:actions>
			<x-button label="關閉" @click="$wire.showAccountTransactionsModal = false" class="btn-ghost" />
		</x-slot:actions>
	</x-modal>
</div>