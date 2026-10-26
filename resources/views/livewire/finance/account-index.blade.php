<!-- filepath: resources/views/livewire/finance/account-index.blade.php -->
<div class="p-4 sm:p-6 lg:p-8 max-w-7xl mx-auto space-y-8 text-base-content animate-fadeIn">
    
    {{-- ============================================================ --}}
    {{-- 1. 頂部收支統計卡片 --}}
    {{-- ============================================================ --}}
    <div class="space-y-3">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            @php
                $periodThemeMap = [
                    'today' => 'blue',
                    'month' => 'green',
                    'year' => 'purple',
                ];
            @endphp
            @foreach(['today' => '本日收支摘要', 'month' => '本月收支累計', 'year' => '本年年度統計'] as $period => $title)
                @php
                    $stats = $this->periodStats[$period] ?? ['income' => 0, 'expense' => 0, 'base_symbol' => 'NT$'];
                    $baseSymbol = $stats['base_symbol'] ?? 'NT$';
                    $currencyCount = count($stats['details'] ?? []);
                    $income = (string) ($stats['income'] ?? '0');
                    $expense = (string) ($stats['expense'] ?? '0');
                    
                    $incomeClean = str_replace(',', '', $income);
                    $expenseClean = str_replace(',', '', $expense);
                    $profitValue = bcsub($incomeClean, $expenseClean, 2);
                    $isProfitNegative = bccomp($profitValue, '0', 2) < 0;
                    $profitFormatted = number_format((float)$profitValue, 2);
                    
                    $theme = $periodThemeMap[$period] ?? 'blue';
                @endphp
            
                <div class="clickable-card stats shadow-sm bg-base-100 border border-base-300 rounded-2xl hover:border-base-content/25 hover:shadow-md transition-all duration-300 cursor-pointer overflow-hidden group" 
     wire:click="showPeriodDetail('{{ $period }}')">
					<div class="stat p-0">

						{{-- 斜切緞帶標題層 --}}
						<div class="relative h-12">

							{{-- 斜切色塊 --}}
							<div class="absolute inset-0 stat-ribbon stat-ribbon-{{ $theme }} stat-ribbon-clip"></div>

							{{-- 髮絲下緣線 --}}
							<div class="absolute inset-x-0 top-12 h-px stat-ribbon-line"></div>

							{{-- 標題文字層(浮在緞帶上,不吃斜切遮罩) --}}
							<div class="relative flex items-center justify-between h-12 pl-5 pr-5">
								<div class="flex items-center gap-1.5 min-w-0">
									
									<span class="text-sm font-bold tracking-wide
												 text-{{ $theme }}-700 dark:text-{{ $theme }}-300
												 truncate">
										{{ $title }}
									</span>
								</div>
								<div class="flex items-center gap-2 flex-shrink-0">
									@if($currencyCount > 1)
										<span class="text-[10px] font-mono
													 px-1.5 py-0.5 rounded
													 bg-{{ $theme }}-500/10
													 text-{{ $theme }}-700 dark:text-{{ $theme }}-300">
											{{ $currencyCount }}種
										</span>
									@endif
									{{-- 【新增】可點擊箭頭指示 --}}
									<svg class="click-arrow w-4 h-4 opacity-40 group-hover:opacity-80 group-hover:translate-x-0.5 transition-all"
										 fill="none" stroke="currentColor" viewBox="0 0 24 24">
										<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
									</svg>
								</div>
							</div>
						</div>

						{{-- 收支數字區(保持原樣) --}}
						<div class="px-5 pb-5 pt-3 space-y-2.5">
							<div class="flex justify-between items-center text-sm">
								<span class="text-tx-income font-medium">收入</span>
								<span class="font-bold font-mono text-tx-income">+{{ $baseSymbol }} {{ $income }}</span>
							</div>
							<div class="flex justify-between items-center text-sm">
								<span class="text-tx-expense font-medium">支出</span>
								<span class="font-bold font-mono text-tx-expense">-{{ $baseSymbol }} {{ $expense }}</span>
							</div>
							<div class="flex justify-between items-center pt-2.5 border-t border-base-200/60">
								<span class="text-sm font-bold opacity-80">差額</span>
								<span class="text-base font-black font-mono {{ $isProfitNegative ? 'text-tx-expense' : 'text-base-content' }}">
									{{ $isProfitNegative ? '' : '+' }}{{ $baseSymbol }} {{ $profitFormatted }}
								</span>
							</div>
						</div>
					</div>
				</div>
            @endforeach
        </div>
    </div>

	{{-- ============================================================ --}}
	{{-- 2. 總資產卡片 --}}
	{{-- ============================================================ --}}
	<div class="relative" wire:key="total-assets-card">
		{{-- Loading 狀態 --}}
		<div wire:loading wire:target="refreshTotalAssets, saveAccount, deleteAccount, switchShop" 
			 class="absolute inset-0 z-10 assets-card rounded-2xl flex items-center justify-center border border-base-content/10 backdrop-blur-sm">
			<div class="flex flex-col items-center gap-3 text-base-content">
				<div class="loading loading-spinner loading-lg opacity-80"></div>
				<span class="text-sm font-medium tracking-wide opacity-80">計算總資產中...</span>
			</div>
		</div>
		
		{{-- 總資產卡片本體 --}}
		<div wire:loading.remove wire:target="refreshTotalAssets, saveAccount, deleteAccount, switchShop"
			 class="relative overflow-hidden assets-card border border-base-content/10 rounded-2xl p-6 md:p-8 text-base-content shadow-xl">
			
			{{-- 背景裝飾：極淡暖光暈 --}}
			<div class="absolute -top-24 -right-24 w-72 h-72 rounded-full pointer-events-none"
				 style="background: radial-gradient(circle, rgba(214,197,166,0.10) 0%, transparent 70%);"></div>
			
			{{-- 背景裝飾 2：大尺寸古銅錢浮水印 --}}
			<svg class="absolute -top-28 -right-30 w-72 h-72 md:w-96 md:h-96 opacity-[0.05] dark:opacity-[0.15] pointer-events-none select-none text-base-content transition-all" 
				 viewBox="0 0 100 100" fill="none" stroke="currentColor">
				
				{{-- 寬而厚實的外緣圓框（模擬真實古錢厚重邊道） --}}
				<circle cx="50" cy="50" r="44" stroke-width="6" />
				
				{{-- 中間方孔（帶有適度厚度的內郭框） --}}
				<rect x="36" y="36" width="28" height="28" rx="1" stroke-width="2" />

				{{-- 「開元通寶」四字佈局：上開、下元、右通、左寶 --}}
				<g fill="currentColor" stroke="none" class="font-serif font-black select-none opacity-95 text-[20px]">
					{{-- 上：開 --}}
					<text x="50" y="24" text-anchor="middle" dominant-baseline="central">開</text>
					{{-- 下：元 --}}
					<text x="50" y="76" text-anchor="middle" dominant-baseline="central">元</text>
					{{-- 右：通 --}}
					<text x="76" y="50" text-anchor="middle" dominant-baseline="central">通</text>
					{{-- 左：寶 --}}
					<text x="24" y="50" text-anchor="middle" dominant-baseline="central">寶</text>
				</g>
			</svg>
			
			<div class="relative z-10">
				{{-- 標題列 --}}
				<div class="flex items-center justify-between">
					<div class="flex items-center gap-2 text-base-content font-semibold tracking-wider text-sm">
						{{-- 小銅錢 SVG --}}
						<svg class="w-4 h-4 opacity-70" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.25">
							<circle cx="12" cy="12" r="8"/>
							<rect x="9.5" y="9.5" width="5" height="5" rx="0.5"/>
						</svg>
						<span>總資產（{{ $this->totalAssets['base_code'] }}）</span>
						<span class="hidden sm:inline text-xs opacity-50 font-normal ml-1">所有幣別換算後加總</span>
					</div>
					
					{{-- 自動刷新控制 --}}
					<div class="flex items-center gap-2.5 bg-base-content/2 backdrop-blur-sm px-3 py-1.5 rounded-full border border-base-content/10">
						<span class="text-[10px] opacity-70 flex items-center gap-1.5">
							<span class="inline-block w-2 h-2 rounded-full {{ $autoRefresh ? 'bg-emerald-400 animate-pulse' : 'bg-base-content/30' }}"></span>
							{{ $autoRefresh ? '自動更新中' : '已暫停' }}
						</span>
						
						{{-- 重新整理 SVG --}}
						<button wire:click="refreshTotalAssets" 
								class="opacity-60 hover:opacity-100 transition-colors p-1 rounded hover:bg-base-content/10"
								title="立即重新整理">
							<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
								<path d="M4 12a8 8 0 0 1 13.7-5.7L20 8M20 4v4h-4M20 12a8 8 0 0 1-13.7 5.7L4 16M4 20v-4h4"/>
							</svg>
						</button>
						
						{{-- 播放／暫停 SVG --}}
						<button wire:click="$toggle('autoRefresh')" 
								class="opacity-60 hover:opacity-100 transition-colors p-1 rounded hover:bg-base-content/10 border border-base-content/10 hover:border-base-content/30"
								title="{{ $autoRefresh ? '暫停自動更新' : '開啟自動更新' }}">
							@if($autoRefresh)
								<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
									<path d="M9 5v14M15 5v14"/>
								</svg>
							@else
								<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
									<path d="M8 5v14l11-7z"/>
								</svg>
							@endif
						</button>
					</div>
				</div>
				
				{{-- 總金額 --}}
				<div class="mt-4">
					<div class="text-3xl md:text-4xl font-black font-mono tracking-tight drop-shadow-sm">
						{{ $this->totalAssets['base_symbol'] }} {{ number_format($this->totalAssets['total'], 2) }}
					</div>
					@if(!$this->totalAssets['has_assets'])
						<div class="mt-1 text-sm opacity-60">尚無資產記錄</div>
					@endif
				</div>
				
				{{-- 幣別明細 --}}
				@if(count($this->totalAssets['breakdown']) > 0)
					<div class="mt-6 pt-5 border-t border-base-content/15">
						<div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-2.5">
							@foreach($this->totalAssets['breakdown'] as $item)
								<div class="flex flex-col gap-1 bg-base-content/[0.03] backdrop-blur-md rounded-xl px-3 py-2 border border-base-content/10 hover:bg-base-content/[0.06] transition-all shadow-sm min-w-0">
									<div class="flex items-center justify-between gap-1">
										<span class="text-xs font-bold">{{ $item['code'] }}</span>
										@if($item['is_base'])
											{{-- 小朱印 --}}
											<span class="text-[10px] px-1 py-0.5 rounded-sm bg-[#9E2B25]/80 text-white font-medium flex-shrink-0 tracking-wider">本幣</span>
										@endif
									</div>
									<span class="text-sm font-mono font-bold truncate opacity-95">
										{{ $item['symbol'] }} {{ number_format($item['balance'], 0) }}
									</span>
									@if(!$item['is_base'])
										<span class="text-[10px] opacity-55 font-mono truncate">
											→ {{ $this->totalAssets['base_symbol'] }} {{ number_format($item['converted'], 0) }}
										</span>
									@endif
								</div>
							@endforeach
						</div>
					</div>
				@else
					<div class="mt-5 pt-4 border-t border-white/20 text-sm opacity-60">
						尚無任何資產，請先新增您的第一個帳戶。
					</div>
				@endif
				
				@if($autoRefresh)
					<div wire:poll.visible.30s="refreshTotalAssets" class="hidden"></div>
				@endif
			</div>
		</div>
	</div>

    {{-- ============================================================ --}}
    {{-- 3. 操作標題列 --}}
    {{-- ============================================================ --}}
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between border-b border-base-300/60 pb-5">
        <div>
            <h1 class="text-2xl font-black tracking-tight text-base-content">資產帳戶總覽</h1>
            <p class="text-sm opacity-70 mt-1">即時追蹤您的多幣別資產配置與資金流向</p>
        </div>
        <div class="flex items-center gap-3">
            <x-button label="新增帳戶" icon="o-plus" class="btn-purple" wire:click="openCreateModal" />
            <x-button label="記一筆" icon="o-pencil-square" class="btn-dark text-white" wire:click="$dispatch('open-transaction-modal')" />
        </div>
    </div>

    {{-- ============================================================ --}}
    {{-- 4. 帳戶列表（含分幣別標題列） --}}
    {{-- ============================================================ --}}
    @foreach($this->currencyGroups as $group)
        @php
            $currencyTheme = $group['theme'] ?? 'blue';
            $currencyColorMap = [
                'blue'   => 'sky',
                'red'    => 'rose',
                'green'  => 'emerald',
                'purple' => 'violet',
                'orange' => 'orange',
            ];
            $dotColor = $currencyColorMap[$currencyTheme] ?? 'sky';
        @endphp

        <div class="space-y-3.5 pt-2 animate-fadeIn">
            {{-- 分幣別標題列：幣別 + 代碼 + 點線 + 合計 --}}
			<div class="flex items-center gap-2">
				<span class="flex items-center gap-2 min-w-0">
					<span class="w-1.5 h-4 rounded-full bg-{{ $dotColor }}-500 flex-shrink-0"></span>
					<span class="text-sm font-bold text-base-content truncate">
						{{ $group['currency_name'] }}帳戶資產
					</span>
					<span class="text-xs opacity-50 font-mono font-normal flex-shrink-0">{{ $group['currency'] }}</span>
				</span>

				{{-- 引導點線 --}}
				<span class="flex-1 border-b border-dotted border-base-content/20 min-w-[1rem]"></span>

				<span class="text-sm font-bold text-base-content flex-shrink-0 whitespace-nowrap">
					<span class="opacity-50">{{ $group['currency_symbol'] }}</span>{{ number_format($group['total_balance'], 2) }}
				</span>
			</div>

            {{-- 帳戶卡片 --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($group['accounts'] as $account)
                    @php
                        $typeConfig = config("business.account_types.{$account->type}") ?? config("business.account_types.cash");
                        $icon = $typeConfig['icon'] ?? 'heroicon-o-currency-dollar';
                        $typeTheme = $typeConfig['theme'] ?? 'orange';
                    @endphp

                    <div wire:click="viewAccountTransactions({{ $account->id }})" 
                         class="clickable-card group card-{{ $typeTheme }} account-card relative overflow-hidden rounded-2xl border border-base-300/70 bg-base-100 shadow-sm cursor-pointer transition-all duration-200 hover:shadow-md hover:border-base-content/25 active:scale-[0.98] p-5 pl-6 flex flex-col justify-between h-28">
                        
                        <span class="account-left-bar absolute left-0 top-0 bottom-0 w-1.5"></span>

                        <div class="flex justify-between items-start relative z-10">
                            <div class="flex items-center gap-2 min-w-0">
                                <x-dynamic-component :component="$icon" class="w-5 h-5 flex-shrink-0 opacity-60" />
                                <span class="font-bold text-base-content text-base truncate">{{ $account->name }}</span>
                            </div>
                            <span class="account-badge px-2 py-0.5 text-[10px] font-semibold rounded-md font-mono flex-shrink-0 ml-2">
                                {{ $typeConfig['name'] }}
                            </span>
                        </div>
                        
                        <div class="flex justify-between items-end mt-2 relative z-10">
                            <span class="text-xs opacity-60 font-medium">當前餘額</span>
                            <div class="flex items-center gap-1.5 ml-2 min-w-0">
                                <span class="font-mono text-xl font-extrabold text-base-content truncate">
                                    <span class="opacity-50 mr-0.5 text-base font-bold">{{ $group['currency_symbol'] }}</span>{{ number_format($account->calculated_balance, 2) }}
                                </span>
                                {{-- 可點擊箭頭指示 --}}
                                <svg class="click-arrow w-4 h-4 opacity-30 group-hover:opacity-70 group-hover:translate-x-0.5 transition-all flex-shrink-0"
                                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach

{{-- ============================================================ --}}
    {{-- 5. 新增/編輯帳戶 Modal --}}
    {{-- ============================================================ --}}
    <x-modal 
        wire:model="showAccountModal" 
        title="{{ $editingAccountId ? '編輯資產帳戶' : '新增資產帳戶' }}" 
        separator 
        persistent 
        size="2xl"
        box-class="border border-base-300 bg-base-100 shadow-2xl rounded-2xl text-base-content"
        x-on:click.stop
    >
        {{-- 右上角關閉按鈕 --}}
        <button 
            type="button" 
            wire:click="$set('showAccountModal', false)" 
            class="btn btn-ghost btn-sm btn-square absolute right-4 top-4 opacity-50 hover:opacity-100"
        >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>

        {{-- Modal Body --}}
        <div class="py-4">
            <x-form wire:submit="saveAccount" class="space-y-5">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <x-input label="帳戶名稱" wire:model="accountName" placeholder="例如：中信證券、國泰世華..." icon="o-pencil" required />
    <x-select label="帳戶類型" wire:model="accountType" :options="$accountTypeOptions" icon="o-tag" required />
</div>

                <div class="space-y-2">
                    <label class="label p-0 flex items-center gap-1">
                        <span class="label-text font-bold text-xs opacity-80">使用幣別 (Currency)</span>
                    </label>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                        @foreach($availableCurrencies as $code => $name)
                            @php 
                                $isSelected = ($accountCurrency === $code);
                            @endphp
                            <button type="button" 
                                class="flex flex-col items-center justify-center py-2.5 px-3 rounded-xl border transition-all duration-200 {{ $isSelected ? 'bg-stone-800 text-stone-50 border-stone-700 shadow-sm dark:bg-stone-200 dark:text-stone-900' : 'bg-base-200/70 border-base-300 text-base-content hover:bg-base-200' }}" 
                                wire:click="$set('accountCurrency', '{{ $code }}')">
                                <span class="text-xs font-black">{{ $name }}</span>
                                <span class="text-[10px] opacity-70 font-mono mt-0.5">{{ $code }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end bg-base-200/50 p-4 rounded-2xl border border-base-300/80">
                    <div class="md:col-span-2">
                        <x-input 
                            label="初始資產 (以選定幣別計價)" 
                            wire:model="accountBalance" 
                            prefix="{{ $availableCurrencies[$accountCurrency] ?? '$' }}" 
                            type="text" 
                            inputmode="decimal"
                            class="font-mono text-lg text-right font-bold" 
                            required 
                            x-data
                            x-on:input="
                                $event.target.value = $event.target.value.replace(/[^0-9.]/g, '');
                                $wire.set('accountBalance', $event.target.value)
                            "
                        />
                    </div>
                    <div class="text-xs opacity-70 leading-relaxed md:pb-2">
                        💡 初始資產，後續庫存採購與銷售折抵將會連動此餘額進行異動。
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4">
                    <x-textarea 
                        label="備忘 (Memo)" 
                        wire:model="accountMemo" 
                        placeholder="例如：此帳戶為中信銀行信義分行，主要用於官網收款..."
                        rows="2"
                        hint="僅供內部參考，不會顯示於任何單據上" />
                </div>

                {{-- 操作按鈕 --}}
                <x-slot:actions>
                    <div class="grid grid-cols-2 gap-2 w-full pt-3 border-t border-base-300/60">
                        <x-button label="取消" type="button" wire:click="$set('showAccountModal', false)" class="btn-light btn-sm opacity-80" />
                        <x-button label="確認儲存" type="submit" class="btn-dark btn-sm text-white" spinner="saveAccount" />
                    </div>
                </x-slot:actions>
            </x-form>
        </div>
    </x-modal>

    {{-- ============================================================ --}}
    {{-- 6. 交易流水 Modal --}}
    {{-- ============================================================ --}}
    <livewire:finance.transaction-list-modal />
</div>