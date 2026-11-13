{{-- resources/views/livewire/finance/report-stats.blade.php --}}
<div class="p-4 md:p-6 max-w-7xl mx-auto space-y-6 text-base-content animate-fadeIn"
     x-data="{ filterDrawer: false }">
    
    {{-- ============================================================ --}}
    {{-- 手機端頂部佈局：左側 2/3 (4個分頁 2x2 排列) + 右側 1/3 (篩選按鈕)，下方日期導覽獨占一列 w-full --}}
    {{-- ============================================================ --}}
    @php
        $primaryTabs = [
            ['key' => 'category', 'label' => '類別', 'icon' => 'heroicon-m-chart-pie', 'isAsset' => false],
            ['key' => 'trend',    'label' => '收支', 'icon' => 'heroicon-m-chart-bar', 'isAsset' => false],
            ['key' => 'account',  'label' => '賬戶', 'icon' => 'heroicon-m-wallet', 'isAsset' => false],
            ['key' => 'asset',    'label' => '總資產', 'icon' => 'heroicon-m-building-library', 'isAsset' => true],
        ];
    @endphp

    <div class="block md:hidden space-y-3 pb-3 border-b border-base-300/60">
        {{-- 上方：左邊 2/3 (4個分頁 2x2) + 右邊 1/3 (篩選按鈕) --}}
        <div class="grid grid-cols-3 gap-2 items-stretch">
            <div class="col-span-2 grid grid-cols-2 gap-1.5 p-1 rounded-xl bg-base-200/80 border border-base-300/70 backdrop-blur-md shadow-sm">
                @foreach($primaryTabs as $tab)
                    @php
                        $isActive = $tab['isAsset']
                            ? $showAssetTrend
                            : ($tab1 === $tab['key'] && !$showAssetTrend);
                        $activeClass = $tab['isAsset']
                            ? 'bg-stone-800 text-stone-50 shadow-sm dark:bg-stone-200 dark:text-stone-900 font-bold'
                            : 'bg-base-100 text-base-content shadow-sm font-bold';
                    @endphp
                    <button class="px-2.5 py-2 rounded-lg text-xs transition-all duration-200 flex items-center justify-center gap-1 whitespace-nowrap song-interactive {{ $isActive ? $activeClass : 'text-base-content/60 hover:text-base-content font-medium' }}"
                            wire:click="$set('tab1', '{{ $tab['key'] }}')">
                        <x-dynamic-component :component="$tab['icon']" class="w-3.5 h-3.5 shrink-0" />
                        <span>{{ $tab['label'] }}</span>
                    </button>
                @endforeach
            </div>
            <div class="col-span-1 flex">
                <button @click="filterDrawer = !filterDrawer" 
                        class="w-full btn btn-purple h-full min-h-[44px] px-2 rounded-xl flex flex-col items-center justify-center gap-1 shrink-0 song-interactive shadow-sm">
                    <x-heroicon-m-adjustments-horizontal class="w-4 h-4" />
                    <span class="text-[11px] font-bold tracking-wide">篩選</span>
                </button>
            </div>
        </div>
		{{-- 手機端狀態條：顯示當前篩選維度 --}}
		<div class="flex items-center gap-2 px-1 text-xs">
			{{-- 收支方向（類別/收支分頁才顯示） --}}
			@if($tab1 === 'category' || ($tab1 === 'trend' && !$showAssetTrend))
				<span class="inline-flex items-center gap-1 px-2 py-1 rounded-md
					{{ $tab2 === 'expense' ? 'bg-error/10 text-error' :
					   ($tab2 === 'income' ? 'bg-success/10 text-success' : 'bg-base-200 text-base-content/70') }}">
					<span class="w-1.5 h-1.5 rounded-full
						{{ $tab2 === 'expense' ? 'bg-error' :
						   ($tab2 === 'income' ? 'bg-success' : 'bg-base-content/50') }}"></span>
					{{ ['expense' => '支出', 'income' => '收入', 'balance' => '結餘'][$tab2] ?? $tab2 }}
				</span>
			@endif
			
			{{-- 時間粒度（類別 / 賬戶占比） --}}
			@if($this->usesGranularity())
				<span class="px-2 py-1 rounded-md bg-base-200 text-base-content/70">
					按{{ ['year' => '年', 'month' => '月', 'day' => '日'][$granularity] }}
				</span>
			@endif

			{{-- 賬戶模式 --}}
			@if($tab1 === 'account')
				<span class="px-2 py-1 rounded-md bg-base-200 text-base-content/70">
					{{ $tab2 === 'composition' ? '佔比' : '趨勢' }}
				</span>
			@endif

			{{-- 總資產模式 --}}
			{{-- 幣別狀態：合併 / 單幣別 / 總資產本位幣 --}}
			@php
				$isConsolidated = $currencyFilter === 'all';
				$baseCode = $baseCurrency->code ?? 'TWD';
			@endphp

			@if($showAssetTrend)
				{{-- 總資產：固定本位幣換算 --}}
				<span class="px-2 py-1 rounded-md bg-primary/10 text-primary font-mono text-[11px]">
					{{ $baseCode }} 本位幣
				</span>
			@elseif($isConsolidated)
				{{-- 合併模式 --}}
				<span class="px-2 py-1 rounded-md bg-base-200 text-base-content/60 font-mono text-[11px]">
					🌐 全部合併
				</span>
			@else
				{{-- 單一幣別原幣 --}}
				<span class="px-2 py-1 rounded-md bg-base-200 text-base-content/70 font-mono text-[11px]">
					{{ $currencyFilter }}
				</span>
			@endif

			{{-- 右側：點擊開啟抽屜的提示 --}}
			<button @click="filterDrawer = true"
					class="ml-auto text-base-content/40 hover:text-base-content/70">
				<x-heroicon-m-chevron-down class="w-3.5 h-3.5" />
			</button>
		</div>

        {{-- 下方：日期導覽獨占一列 w-full --}}
        <div class="w-full">
            <x-date-nav 
                model="currentDate" 
                :type="$dateMode"
                :display="$displayDate" 
                :isCurrent="$isCurrent" 
                :disableNext="$isCurrent"
            />
        </div>
    </div>

    {{-- ============================================================ --}}
    {{-- 電腦端頂部佈局（維持現狀） --}}
    {{-- ============================================================ --}}
    <div class="hidden md:flex flex-col lg:flex-row lg:items-center lg:justify-between border-b border-base-300/60 pb-5">
        <div class="flex p-1.5 rounded-xl bg-base-200/80 border border-base-300/70 backdrop-blur-md shadow-sm overflow-x-auto">
            @foreach($primaryTabs as $tab)
                @php
                    $isActive = $tab['isAsset']
                        ? $showAssetTrend
                        : ($tab1 === $tab['key'] && !$showAssetTrend);
                    $activeClass = $tab['isAsset']
                        ? 'bg-stone-800 text-stone-50 shadow-sm dark:bg-stone-200 dark:text-stone-900 font-bold'
                        : 'bg-base-100 text-base-content shadow-sm font-bold';
                @endphp
                <button class="px-4 py-2 rounded-lg text-sm transition-all duration-200 flex items-center gap-1.5 whitespace-nowrap song-interactive {{ $isActive ? $activeClass : 'text-base-content/60 hover:text-base-content font-medium' }}"
                        wire:click="$set('tab1', '{{ $tab['key'] }}')">
                    <x-dynamic-component :component="$tab['icon']" class="w-4 h-4 shrink-0" />
                    <span>{{ $tab['label'] }}</span>
                </button>
            @endforeach
        </div>
        <div class="flex items-center justify-end gap-2">
            <x-date-nav 
                model="currentDate" 
                :type="$dateMode"
                :display="$displayDate" 
                :isCurrent="$isCurrent" 
                :disableNext="$isCurrent"
            />
        </div>
    </div>

    {{-- ============================================================ --}}
    {{-- 電腦端專屬過濾工具列（加深外框） --}}
    {{-- ============================================================ --}}
    <div class="hidden md:flex flex-wrap items-center gap-3 bg-base-200/50 p-3.5 rounded-2xl border-2 border-base-300 shadow-sm backdrop-blur-sm">
        @if($this->usesGranularity())
            <div class="h-4 w-[1px] bg-base-300 mx-1"></div>
            <div class="flex items-center gap-2">
                <span class="text-xs font-bold text-base-content/70 tracking-wider mr-1">時間：</span>
                <div class="join border border-base-300 rounded-lg overflow-hidden shadow-sm">
                    @foreach($this->allowedGranularities() as $g)
                        <button class="btn btn-xs join-item song-interactive
                                       {{ $granularity === $g ? 'btn-neutral' : 'btn-ghost' }}"
                                wire:click="$set('granularity', '{{ $g }}')">
                            {{ ['year' => '年', 'month' => '月', 'day' => '日'][$g] }}
                        </button>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="flex items-center gap-2">
            <span class="text-xs font-bold text-base-content/70 tracking-wider mr-1">檢視：</span>
            @if(!$showAssetTrend)
                @if($tab1 === 'account')
                    <div class="join border border-base-300 rounded-lg overflow-hidden shadow-sm">
                        <button class="btn btn-xs join-item song-interactive {{ $tab2 === 'composition' ? 'btn-neutral' : 'btn-ghost' }}" wire:click="$set('tab2', 'composition')">佔比</button>
                        <button class="btn btn-xs join-item song-interactive {{ $tab2 === 'trend' ? 'btn-neutral' : 'btn-ghost' }}" wire:click="$set('tab2', 'trend')">趨勢</button>
                    </div>
                @else
                    <div class="join border border-base-300 rounded-lg overflow-hidden shadow-sm">
                        <button class="btn btn-xs join-item song-interactive {{ $tab2 === 'expense' ? 'btn-error' : 'btn-ghost' }}" wire:click="$set('tab2', 'expense')">支出</button>
                        <button class="btn btn-xs join-item song-interactive {{ $tab2 === 'income' ? 'btn-success' : 'btn-ghost' }}" wire:click="$set('tab2', 'income')">收入</button>
                        @if($tab1 === 'trend')
                            <button class="btn btn-xs join-item song-interactive {{ $tab2 === 'balance' ? 'btn-neutral' : 'btn-ghost' }}" wire:click="$set('tab2', 'balance')">結餘</button>
                        @endif
                    </div>
                @endif
            @else
                <span class="text-xs font-bold text-primary">總資產本位幣：{{ $baseCurrency->code ?? 'TWD' }}</span>
            @endif
        </div>
    </div>

    {{-- ============================================================ --}}
    {{-- 手機專屬抽屜 --}}
    {{-- ============================================================ --}}
	<div x-show="filterDrawer" 
		 style="display: none;"
		 class="fixed inset-0 z-50 lg:hidden"
		 role="dialog" aria-modal="true">

		{{-- 遮罩：鋪滿全螢幕（含 header 下方），點擊關閉 --}}
		<div x-show="filterDrawer"
			 x-transition.opacity.duration.200ms
			 @click="filterDrawer = false"
			 class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>

		{{-- 面板容器：left 留白，right 貼齊 --}}
		<div class="absolute inset-y-0 right-0 max-w-full flex pl-10 pointer-events-none">
			<div x-show="filterDrawer"
				 x-transition:enter="transform transition ease-in-out duration-300"
				 x-transition:enter-start="translate-x-full"
				 x-transition:enter-end="translate-x-0"
				 x-transition:leave="transform transition ease-in-out duration-300"
				 x-transition:leave-start="translate-x-0"
				 x-transition:leave-end="translate-x-full"
				 {{-- 🔥 關鍵：top 往下推，避開 sticky header（header 高度約 64~72px） --}}
				 class="fixed top-20 bottom-0 right-0 w-3/4 max-w-sm pointer-events-auto
						bg-base-100 border-l border-base-300 shadow-2xl
						flex flex-col rounded-tl-2xl">
				
				{{-- 抽屜標題列：現在不會被 header 蓋住 --}}
				<div class="flex items-center justify-between p-4 border-b border-base-300 flex-shrink-0">
					<h3 class="font-serif font-bold text-base text-base-content">報表檢視與篩選設定</h3>
					<button @click="filterDrawer = false" class="btn btn-sm btn-ghost btn-square song-interactive">✕</button>
				</div>

                <div class="flex-1 overflow-y-auto space-y-6 p-4">
                    @if(!$showAssetTrend)
                        <div class="space-y-2">
                            <label class="text-xs font-bold text-base-content/60 tracking-wider">幣別</label>
                            <div class="grid grid-cols-2 gap-2">
                                <button class="p-2.5 rounded-xl text-xs font-bold border transition-all text-center song-interactive {{ $currencyFilter === 'all' ? 'bg-stone-800 text-stone-50 border-stone-800 dark:bg-stone-200 dark:text-stone-900' : 'bg-base-100 border-base-300 text-base-content' }}"
                                        wire:click="$set('currencyFilter', 'all')">
                                    🌐 全部（合併）
                                </button>
                                @foreach($this->availableCurrencies as $currency)
                                    <button class="p-2.5 rounded-xl text-xs font-bold border transition-all text-center song-interactive {{ $currencyFilter === $currency->code ? 'bg-stone-800 text-stone-50 border-stone-800 dark:bg-stone-200 dark:text-stone-900' : 'bg-base-100 border-base-300 text-base-content' }}"
                                            wire:click="$set('currencyFilter', '{{ $currency->code }}')">
                                        {{ $currency->code }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                        <div class="border-t border-base-300/60"></div>
                    @endif

                    <div class="space-y-2">
                        <label class="text-xs font-bold text-base-content/60 tracking-wider">檢視</label>
                        <div class="flex flex-col gap-2">
                            @if(!$showAssetTrend)
                                @if($tab1 === 'account')
                                    <div class="grid grid-cols-2 gap-2">
                                        <button class="p-2.5 rounded-xl text-xs font-bold border text-center song-interactive {{ $tab2 === 'composition' ? 'bg-stone-800 text-stone-50 border-stone-800 dark:bg-stone-200 dark:text-stone-900' : 'bg-base-100 border-base-300' }}" wire:click="$set('tab2', 'composition')">佔比分佈</button>
                                        <button class="p-2.5 rounded-xl text-xs font-bold border text-center song-interactive {{ $tab2 === 'trend' ? 'bg-stone-800 text-stone-50 border-stone-800 dark:bg-stone-200 dark:text-stone-900' : 'bg-base-100 border-base-300' }}" wire:click="$set('tab2', 'trend')">賬戶趨勢</button>
                                    </div>
                                @else
                                    <div class="grid grid-cols-2 gap-2">
                                        <button class="p-2.5 rounded-xl text-xs font-bold border text-center song-interactive {{ $tab2 === 'expense' ? 'bg-rose-500/20 text-rose-800 dark:text-rose-300 border-rose-500/30' : 'bg-base-100 border-base-300' }}" wire:click="$set('tab2', 'expense')">支出</button>
                                        <button class="p-2.5 rounded-xl text-xs font-bold border text-center song-interactive {{ $tab2 === 'income' ? 'bg-emerald-500/20 text-emerald-800 dark:text-emerald-300 border-emerald-500/30' : 'bg-base-100 border-base-300' }}" wire:click="$set('tab2', 'income')">收入</button>
                                    </div>
                                @endif
                            @else
                                <div class="grid grid-cols-2 gap-2">
                                    <button class="p-2.5 rounded-xl text-xs font-bold border text-center song-interactive {{ $assetTab === 'month' ? 'bg-stone-800 text-stone-50 border-stone-800 dark:bg-stone-200 dark:text-stone-900' : 'bg-base-100 border-base-300' }}" wire:click="$set('assetTab', 'month')">月度總資產</button>
                                    <button class="p-2.5 rounded-xl text-xs font-bold border text-center song-interactive {{ $assetTab === 'day' ? 'bg-stone-800 text-stone-50 border-stone-800 dark:bg-stone-200 dark:text-stone-900' : 'bg-base-100 border-base-300' }}" wire:click="$set('assetTab', 'day')">每日總資產</button>
                                </div>
                            @endif
                        </div>
                    </div>
					
					@if($this->usesGranularity())
                        <div class="border-t border-base-300/60"></div>
                        <div class="space-y-2">
                            <label class="text-xs font-bold text-base-content/60 tracking-wider">時間範圍</label>
                            <div class="grid grid-cols-{{ count($this->allowedGranularities()) }} gap-2">
                                @foreach($this->allowedGranularities() as $g)
                                    <button wire:click="$set('granularity', '{{ $g }}')"
                                            class="p-2.5 rounded-xl text-xs font-bold border text-center song-interactive
                                                   {{ $granularity === $g
                                                        ? 'bg-stone-800 text-stone-50 border-stone-800 dark:bg-stone-200 dark:text-stone-900'
                                                        : 'bg-base-100 border-base-300' }}">
                                        {{ ['year' => '年', 'month' => '月', 'day' => '日'][$g] }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endif
					
					{{-- 底部按鈕 --}}
					<div class="modal-action border-base-300 p-4">
						<button @click="filterDrawer = false" class="w-full btn btn-dark py-2.5 rounded-xl font-bold song-interactive">
							確認並套用
						</button>
					</div>
                </div>                
            </div>
        </div>
    </div>

    {{-- ============================================================ --}}
    {{-- 主內容區 --}}
    {{-- ============================================================ --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        
        {{-- 圖表容器（含局部 loading 遮罩） --}}
        <div x-data="{ chartRendering: false }"
             x-on:chart-rendering-start.window="chartRendering = true"
             x-on:chart-rendering-end.window="chartRendering = false"
             class="lg:col-span-5 bg-base-100 border border-base-300/70 p-5 rounded-2xl backdrop-blur-md shadow-sm flex flex-col justify-center items-center relative min-h-[350px] transition-all duration-300">

            {{-- 後端重算期間：wire:loading 遮罩 --}}
            <div wire:loading.flex
                 wire:target="tab1, tab2, tab3, assetTab, currencyFilter, currentDate"
                 class="absolute inset-0 z-20 items-center justify-center bg-base-100/70 backdrop-blur-[2px] rounded-2xl">
                <div class="flex flex-col items-center gap-2">
                    <span class="loading loading-spinner loading-md text-base-content/70"></span>
                    <span class="text-xs font-medium text-base-content/60">資料計算中...</span>
                </div>
            </div>

            {{-- 前端重繪期間：chartRendering 遮罩（與上者接力） --}}
            <div x-show="chartRendering"
                 x-transition.opacity.duration.150ms
                 class="absolute inset-0 z-20 flex items-center justify-center bg-base-100/70 backdrop-blur-[2px] rounded-2xl">
                <div class="flex flex-col items-center gap-2">
                    <span class="loading loading-spinner loading-md text-base-content/70"></span>
                    <span class="text-xs font-medium text-base-content/60">圖表繪製中...</span>
                </div>
            </div>

            @if($tab1 === 'account' && $tab2 === 'trend')
                {{-- small multiples：固定高度，JS 會複用 canvas --}}
                <div id="smallMultiplesContainer"
                     class="w-full grid grid-cols-1 gap-3 relative z-10"
                     style="max-height: 520px; overflow-y: auto;"></div>
            @else
                <div class="w-full max-w-[320px] relative z-10" style="min-height: 300px;">
                    <canvas id="financeChart" style="width: 100%; height: 100%; min-height: 300px;"></canvas>
                    @if($tab1 === 'category' && !$showAssetTrend)
                        <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none mt-4">
                            <span class="text-xs text-base-content/50 font-bold tracking-widest">總額</span>
                            <span class="text-lg font-black text-base-content font-mono" id="chartCenterTotal">{{ $this->categoryData['total'] ?? '0.00' }}</span>
                        </div>
                    @endif
                    @if($showAssetTrend)
                        <div class="absolute top-2 right-2 text-[10px] text-base-content/40 font-mono">
                            {{ $currencyFilter === 'all' ? ($baseCurrency->code ?? 'TWD') : $currencyFilter }}
                        </div>
                    @endif
                    @if($tab1 === 'account' && $tab2 === 'composition')
                        <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none mt-4">
                            <span class="text-xs text-base-content/50 font-bold tracking-widest">總資產</span>
                            <span class="text-lg font-black text-base-content font-mono" id="chartCenterTotal">
                                {{ $accountCompositionData['total'] ?? '0.00' }}
                            </span>
                        </div>
                    @endif
                </div>
            @endif
        </div>

		{{--
			交易效應 vs 匯率效應分解
			期間變動 = 期末總資產（本位幣） - 期初總資產（本位幣）
			交易貢獻 = 期間內實際收入總額 - 期間內實際支出總額（皆換算為本位幣）
			匯率重估 = 期間變動 - 交易貢獻
					（即扣除真實現金流後的剩餘資產差異，反映外幣匯率波動影響）
		--}}
		@if($showAssetTrend && $effectBreakdown)
			<div class="grid grid-cols-1 md:grid-cols-3 gap-3">
				@php
					$effects = [						
						['label' => '📆 期間變動',     'raw' => $effectBreakdown['total_change_raw'],       'display' => $effectBreakdown['total_change']],						
						['label' => '💼 交易貢獻', 'raw' => $effectBreakdown['transaction_effect_raw'], 'display' => $effectBreakdown['transaction_effect']],					
						['label' => '💱 匯率重估', 'raw' => $effectBreakdown['fx_effect_raw'],          'display' => $effectBreakdown['fx_effect']],
					];
				@endphp
				@foreach($effects as $effect)
					<div class="bg-base-100 border border-base-300/70 p-4 rounded-2xl shadow-sm">
						<div class="text-xs font-bold text-base-content/50 tracking-widest mb-1">{{ $effect['label'] }}</div>
						<div class="text-lg font-black font-mono {{ $effect['raw'] < 0 ? 'text-error' : 'text-base-content' }}">
							{{ $effect['display'] }}
						</div>
					</div>
				@endforeach
			</div>
		@endif

        {{-- 明細表格區 --}}
        <div class="lg:col-span-7 space-y-4">
            
            @if($tab1 === 'account')
                @if($tab2 === 'composition')
                    <div class="bg-base-100 border border-base-300/70 rounded-2xl backdrop-blur-md shadow-sm overflow-hidden">
                        <div class="p-4 bg-base-200/60 border-b border-base-300/60 font-bold text-xs tracking-widest text-base-content/70 flex justify-between items-center">
                            <span>💼 賬戶佔比明細</span>
                            <span class="text-[10px] font-normal text-base-content/50">
                                快照日：{{ $accountCompositionData['snapshot_date'] ?? '—' }}
                            </span>
                        </div>
                        <div class="hidden md:block overflow-x-auto">
                            <table class="table w-full text-sm">
                                <thead>
                                    <tr class="border-b border-base-300/60 text-base-content/50">
                                        <th class="bg-transparent font-bold">賬戶</th>
                                        <th class="bg-transparent text-right font-bold">餘額</th>
                                        <th class="bg-transparent text-right font-bold">佔比</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-base-200">
                                    @forelse($accountCompositionData['list'] as $item)
                                        <tr class="hover:bg-base-200/50 transition-colors border-0">
                                            <td class="font-bold text-base-content bg-transparent">
                                                {{ $item['name'] }}
                                                @if(!empty($item['children']))
                                                    <div class="text-[10px] text-base-content/50 font-normal mt-0.5">
                                                        {{ collect($item['children'])->pluck('name')->join('、') }}
                                                    </div>
                                                @endif
                                            </td>
                                            <td class="text-right font-mono font-bold text-base-content bg-transparent">{{ $item['amount_display'] }}</td>
                                            <td class="text-right font-mono text-base-content/60 bg-transparent">{{ $item['percentage'] }}%</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="3" class="text-center py-8 text-base-content/40 bg-transparent">暫無賬戶數據</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="block md:hidden divide-y divide-base-200">
                            @forelse($accountCompositionData['list'] as $item)
                                <div class="p-4 flex justify-between items-center">
                                    <div>
                                        <div class="font-bold text-base-content text-sm">{{ $item['name'] }}</div>
                                        <div class="text-xs text-base-content/60 mt-0.5">佔比 {{ $item['percentage'] }}%</div>
                                    </div>
                                    <div class="font-mono font-extrabold text-md text-base-content">{{ $item['amount_display'] }}</div>
                                </div>
                            @empty
                                <div class="p-8 text-center text-sm text-base-content/40">暫無賬戶數據</div>
                            @endforelse
                        </div>
                    </div>
                @else
                    <div class="bg-base-100 border border-base-300/70 rounded-2xl backdrop-blur-md shadow-sm overflow-hidden">
                        <div class="p-4 bg-base-200/60 border-b border-base-300/60 font-bold text-xs tracking-widest text-base-content/70 flex justify-between items-center">
                            <span>📈 各賬戶餘額明細</span>
                            <span class="text-[10px] font-normal text-base-content/50">
                                {{ $selectedYear }} 年 @if($tab3 === 'day') {{ $selectedMonth }} 月 @endif
                            </span>
                        </div>
                        <div class="hidden md:block overflow-x-auto max-h-[400px] overflow-y-auto">
                            <table class="table table-pin-rows w-full text-sm">
                                <thead>
                                    <tr class="border-b border-base-300/60 text-base-content/50">
                                        <th class="bg-base-100 font-bold">賬戶</th>
                                        <th class="bg-base-100 text-right font-bold">期末餘額</th>
                                        <th class="bg-base-100 text-right font-bold">變動</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-base-200">
                                    @foreach($accountTrendData['series'] as $s)
                                        @php
                                            $first = $s['values'][0] ?? 0;
                                            $last = !empty($s['values']) ? end($s['values']) : 0;
                                            $change = $last - $first;
                                            $changeClass = $change > 0 ? 'text-success' : ($change < 0 ? 'text-error' : 'text-base-content/40');
                                            $changeSymbol = $change > 0 ? '↑' : ($change < 0 ? '↓' : '—');
                                        @endphp
                                        <tr class="hover:bg-base-200/50 transition-colors border-0">
                                            <td class="font-medium text-base-content/80 bg-transparent">
                                                <span class="inline-flex items-center gap-2">
                                                    <span class="inline-block w-2 h-2 rounded-full" style="background: {{ $s['color'] }}"></span>
                                                    {{ $s['name'] }}
                                                </span>
                                            </td>
                                            <td class="text-right font-mono font-bold text-base-content bg-transparent">
                                                {{ $accountTrendData['symbol'] }}{{ number_format($last, 2) }}
                                            </td>
                                            <td class="text-right font-mono text-sm {{ $changeClass }} bg-transparent">
                                                @if($change != 0)
                                                    {{ $changeSymbol }} {{ $accountTrendData['symbol'] }}{{ number_format(abs($change), 2) }}
                                                @else
                                                    —
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="block md:hidden divide-y divide-base-200 max-h-[400px] overflow-y-auto">
                            @foreach($accountTrendData['series'] as $s)
                                @php
                                    $first = $s['values'][0] ?? 0;
                                    $last = !empty($s['values']) ? end($s['values']) : 0;
                                    $change = $last - $first;
                                    $changeClass = $change > 0 ? 'text-success' : ($change < 0 ? 'text-error' : 'text-base-content/40');
                                    $changeSymbol = $change > 0 ? '↑' : ($change < 0 ? '↓' : '—');
                                @endphp
                                <div class="p-3.5 flex justify-between items-center">
                                    <div>
                                        <div class="text-sm font-medium text-base-content/80 flex items-center gap-2">
                                            <span class="inline-block w-2 h-2 rounded-full" style="background: {{ $s['color'] }}"></span>
                                            {{ $s['name'] }}
                                        </div>
                                        @if($change != 0)
                                            <span class="text-xs ml-4 {{ $changeClass }}">{{ $changeSymbol }} {{ number_format(abs($change), 2) }}</span>
                                        @endif
                                    </div>
                                    <span class="font-mono font-bold text-sm text-base-content">
                                        {{ $accountTrendData['symbol'] }}{{ number_format($last, 2) }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

            @elseif($showAssetTrend)
                {{-- 總資產趨勢列表 --}}
                <div class="bg-base-100 border border-base-300/70 rounded-2xl backdrop-blur-md shadow-sm overflow-hidden">
                    <div class="p-4 bg-base-200/60 border-b border-base-300/60 font-bold text-xs tracking-widest text-base-content/70 flex justify-between items-center">
                        <span>📈 總資產趨勢明細</span>
                        <span class="text-[10px] font-normal text-base-content/50">
                            {{ $selectedYear }} 年 @if($assetTab === 'day') {{ $selectedMonth }} 月 @endif
                        </span>
                    </div>
                    
                    @php
                        $assetTable = [];
                        $assetSymbol = $baseCurrency->symbol ?? 'NT$';
                        if (isset($this->assetTrendData['mode']) && $this->assetTrendData['mode'] === 'multi') {
                            $assetTable = $this->assetTrendData['table'] ?? [];
                            $assetSymbol = $this->assetTrendData['symbol'] ?? 'NT$';
                        } elseif (is_iterable($this->assetTrendData)) {
                            $assetTable = collect($this->assetTrendData)->map(fn($item) => [
                                'label' => $item['label'] ?? '',
                                'amount' => $item['amount'] ?? 0,
                                'amount_display' => ($item['symbol'] ?? 'NT$') . number_format($item['amount'] ?? 0, 0),
                                'symbol' => $item['symbol'] ?? 'NT$',
                            ])->all();
                        }
                    @endphp

                    <div class="hidden md:block overflow-x-auto max-h-[400px] overflow-y-auto">
                        <table class="table table-pin-rows w-full text-sm">
                            <thead>
                                <tr class="border-b border-base-300/60 text-base-content/50">
                                    <th class="bg-base-100 font-bold">時間</th>
                                    <th class="bg-base-100 text-right font-bold">總資產</th>
                                    <th class="bg-base-100 text-right font-bold">變動</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-base-200">
                                @php $prevAmount = null; @endphp
                                @forelse($assetTable as $item)
                                    @php
                                        $currentAmount = $item['amount'] ?? 0;
                                        $change = $prevAmount !== null ? $currentAmount - $prevAmount : 0;
                                        $changeClass = $change > 0 ? 'text-success' : ($change < 0 ? 'text-error' : 'text-base-content/40');
                                        $changeSymbol = $change > 0 ? '↑' : ($change < 0 ? '↓' : '—');
                                        $prevAmount = $currentAmount;
                                    @endphp
                                    <tr class="hover:bg-base-200/50 transition-colors border-0">
                                        <td class="font-medium text-base-content/80 bg-transparent">{{ $item['label'] ?? '' }}</td>
                                        <td class="text-right font-mono font-bold text-primary bg-transparent">{{ $item['amount_display'] ?? '0.00' }}</td>
                                        <td class="text-right font-mono text-sm {{ $changeClass }} bg-transparent">
                                            @if($change != 0)
                                                {{ $changeSymbol }} {{ $assetSymbol }}{{ number_format(abs($change), 0) }}
                                            @else
                                                —
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-center py-8 text-base-content/40 bg-transparent">暫無總資產趨勢數據</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="block md:hidden divide-y divide-base-200 max-h-[400px] overflow-y-auto">
                        @php $prevAmount = null; @endphp
                        @forelse($assetTable as $item)
                            @php
                                $currentAmount = $item['amount'] ?? 0;
                                $change = $prevAmount !== null ? $currentAmount - $prevAmount : 0;
                                $changeClass = $change > 0 ? 'text-success' : ($change < 0 ? 'text-error' : 'text-base-content/40');
                                $changeSymbol = $change > 0 ? '↑' : ($change < 0 ? '↓' : '—');
                                $prevAmount = $currentAmount;
                            @endphp
                            <div class="p-3.5 flex justify-between items-center bg-transparent">
                                <div>
                                    <span class="text-sm font-medium text-base-content/80">{{ $item['label'] ?? '' }}</span>
                                    @if($change != 0)
                                        <span class="text-xs ml-2 {{ $changeClass }}">{{ $changeSymbol }} {{ $assetSymbol }}{{ number_format(abs($change), 0) }}</span>
                                    @endif
                                </div>
                                <span class="font-mono font-bold text-sm text-primary">{{ $item['amount_display'] ?? '0.00' }}</span>
                            </div>
                        @empty
                            <div class="p-8 text-center text-sm text-base-content/40">暫無總資產趨勢數據</div>
                        @endforelse
                    </div>
                </div>

            @elseif($tab1 === 'category')
                <div class="bg-base-100 border border-base-300/70 rounded-2xl backdrop-blur-md shadow-sm overflow-hidden">
                    <div class="p-4 bg-base-200/60 border-b border-base-300/60 font-bold text-xs tracking-widest text-base-content/70 flex justify-between items-center">
                        <span>📋 分類數據明細 (點擊大類查看明細流水)</span>
                    </div>
                    <div class="hidden md:block overflow-x-auto">
                        <table class="table w-full text-sm">
                            <thead>
                                <tr class="border-b border-base-300/60 text-base-content/50">
                                    <th class="bg-transparent font-bold">
										{{ ['expense' => '支出', 'income' => '收入'][$tab2] ?? '' }}分類
									</th>
                                    <th class="bg-transparent text-right font-bold">總計金額</th>
                                    <th class="bg-transparent text-right font-bold">佔比</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-base-200">
                                @forelse($this->categoryData['list'] as $item)
                                    <tr class="clickable-card hover:bg-base-200/50 cursor-pointer transition-colors border-0"
                                        wire:click="openCategoryTransactions({{ $item['id'] }}, '{{ addslashes($item['name']) }}')">
                                        <td class="font-bold text-base-content bg-transparent flex items-center gap-2">
                                            <span>{{ $item['name'] }}</span>
                                            <x-heroicon-m-chevron-right class="w-4 h-4 text-base-content/40" />
                                        </td>
                                        <td class="text-right font-mono font-bold text-base-content bg-transparent">{{ $item['amount_display'] }}</td>
                                        <td class="text-right font-mono text-base-content/60 bg-transparent">{{ $item['percentage'] }}%</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-center py-8 text-base-content/40 bg-transparent">暫無相關財務交易數據</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="block md:hidden divide-y divide-base-200">
                        @forelse($this->categoryData['list'] as $item)
                            <div class="p-4 flex justify-between items-center bg-transparent hover:bg-base-200/50 active:bg-base-300 cursor-pointer transition-all"
                                 wire:click="openCategoryTransactions({{ $item['id'] }}, '{{ addslashes($item['name']) }}')">
                                <div>
                                    <div class="font-bold text-base-content text-sm flex items-center gap-1">
                                        {{ $item['name'] }}
                                        <x-heroicon-m-chevron-right class="w-3.5 h-3.5 text-base-content/40" />
                                    </div>
                                    <div class="text-xs text-base-content/60 mt-0.5">佔比 {{ $item['percentage'] }}%</div>
                                </div>
                                <div class="font-mono font-extrabold text-md text-base-content">{{ $item['amount_display'] }}</div>
                            </div>
                        @empty
                            <div class="p-8 text-center text-sm text-base-content/40">暫無相關財務交易數據</div>
                        @endforelse
                    </div>
                </div>

            @else
                <div class="bg-base-100 border border-base-300/70 rounded-2xl backdrop-blur-md shadow-sm overflow-hidden">
                    <div class="p-4 bg-base-200/60 border-b border-base-300/60 font-bold text-xs tracking-widest text-base-content/70">
                        📅 週期趨勢明細 (時間 + 金額)
                    </div>
                    <div class="hidden md:block overflow-x-auto max-h-[400px] overflow-y-auto">
                        <table class="table table-pin-rows w-full text-sm">
                            <thead>
                                <tr class="border-b border-base-300/60 text-base-content/50">
                                    <th class="bg-base-100 font-bold">時間區間</th>
                                    <th class="bg-base-100 text-right font-bold">金額</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-base-200">
                                @foreach($this->trendData as $item)
                                    <tr class="hover:bg-base-200/50 transition-colors border-0">
                                        <td class="font-medium text-base-content/80 bg-transparent">{{ $item['label'] }}</td>
                                        <td class="text-right font-mono font-bold text-base-content bg-transparent">{{ $item['amount_display'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="block md:hidden divide-y divide-base-200 max-h-[400px] overflow-y-auto">
                        @foreach($this->trendData as $item)
                            <div class="p-3.5 flex justify-between items-center bg-transparent">
                                <span class="text-sm font-medium text-base-content/80">{{ $item['label'] }}</span>
                                <span class="font-mono font-bold text-sm text-base-content">{{ $item['amount_display'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

        </div>
    </div>

    <livewire:finance.transaction-list-modal />
</div>

{{-- ============================================================ --}}
{{-- Chart.js 渲染（優化版：保留實例、關閉小圖動畫） --}}
{{-- ============================================================ --}}
@push('scripts')
<script>
document.addEventListener('livewire:init', function () {
    const getSongPalette = () => {
        const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
        return {
            text: isDark ? '#E7E5E4' : '#292524',
            grid: isDark ? 'rgba(68, 64, 60, 0.25)' : 'rgba(231, 229, 228, 0.5)',
            pies: isDark
                ? ['#8fb4cf', '#b57a7a', '#5b8c7a', '#c29a63', '#8972a3', '#577180', '#415a44', '#555555']
                : ['#a2bddb', '#b57a7a', '#5b8c7a', '#d4af37', '#9b84b3', '#6a8a9a', '#739072', '#a8a29e'],
            primary: '#a2bddb',
            assetLine: '#818cf8',
        };
    };

    // ===== 狀態管理 =====
    let chartInstance = null;
    // key = account name，value = Chart 實例；用 Map 方便差異比對
    const smallMultiplesMap = new Map();
    let currentRenderMode = null; // 'single' | 'multi'

    const emitRenderingStart = () => window.dispatchEvent(new CustomEvent('chart-rendering-start'));
    const emitRenderingEnd   = () => window.dispatchEvent(new CustomEvent('chart-rendering-end'));

    // ===== 共用工具 =====
    function formatAxisTick(value) {
        if (Math.abs(value) >= 1e6) return (value / 1e6).toFixed(1) + 'M';
        if (Math.abs(value) >= 1e3) return (value / 1e3).toFixed(0) + 'K';
        return value;
    }

    function buildTooltipConfig(data, isPie) {
        return {
            backgroundColor: 'rgba(20, 18, 18, 0.9)',
            titleColor: '#FAF9F6',
            bodyColor: '#E7E5E4',
            borderColor: 'rgba(231, 229, 228, 0.1)',
            borderWidth: 1,
            callbacks: {
                label: function(context) {
                    const label = context.dataset.label || context.label || '';
                    const value = context.parsed.y ?? context.parsed.r ?? context.parsed;
                    if (isPie) {
                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                        const percentage = total > 0 ? ((value / total) * 100).toFixed(2) : 0;
                        return ` ${label}: ${data.symbol || ''}${value.toLocaleString()} (${percentage}%)`;
                    }
                    return ` ${label}: ${data.symbol || ''}${value.toLocaleString()}`;
                }
            }
        };
    }

    // ===== 單圖渲染（保留實例，只更新資料） =====
    function renderChart(data) {
        // 若從 multi 切回 single，先清掉 small multiples
        if (currentRenderMode === 'multi') {
            destroySmallMultiples();
            currentRenderMode = null;
        }

        const canvasEl = document.getElementById('financeChart');
        if (!canvasEl) return;

        const ctx = canvasEl.getContext('2d');
        const palette = getSongPalette();

        // 更新中心文字
        const centerTotalEl = document.getElementById('chartCenterTotal');
        if (centerTotalEl && data.centerText !== undefined && data.centerText !== null) {
            centerTotalEl.textContent = data.centerText;
        }

        // 無資料狀態
        if (!data.labels || data.labels.length === 0 || (!data.values && !data.series)) {
            if (chartInstance) chartInstance.destroy();
            chartInstance = new Chart(ctx, {
                type: 'doughnut',
                data: { labels: ['無數據'], datasets: [{ data: [1], backgroundColor: [palette.grid], borderWidth: 0 }] },
                options: { responsive: true, maintainAspectRatio: true, plugins: { legend: { display: false }, tooltip: { enabled: false } }, cutout: '75%' }
            });
            return;
        }

        const isPie = data.type === 'pie';
        const isLine = data.type === 'line';
        const chartType = isPie ? 'doughnut' : (data.type === 'bar' ? 'bar' : 'line');

        // 組 datasets
        let datasets;
        if (isLine) {
            datasets = [{
                data: data.values,
                backgroundColor: 'rgba(129, 140, 248, 0.1)',
                borderColor: '#818cf8',
                borderWidth: 2,
                fill: true,
                tension: 0.4,
                pointRadius: 3,
                pointBackgroundColor: '#818cf8',
            }];
        } else if (isPie) {
            datasets = [{
                data: data.values,
                backgroundColor: palette.pies,
                borderColor: document.documentElement.getAttribute('data-theme') === 'dark' ? '#141212' : '#FAF9F6',
                borderWidth: 1.5,
            }];
        } else {
            datasets = [{
                data: data.values,
                backgroundColor: data.color || palette.primary,
                borderRadius: 4,
            }];
        }

        // 若已有實例且「類型相同」，走 update 路徑（快很多）
        if (chartInstance && chartInstance.config.type === chartType) {
            chartInstance.data.labels = data.labels;
            chartInstance.data.datasets = datasets;
            if (isPie) {
                chartInstance.options.cutout = '75%';
                delete chartInstance.options.scales;
            } else {
                chartInstance.options.scales = buildScales(palette);
            }
            chartInstance.update('none'); // 關動畫，更新極快
            return;
        }

        // 否則重建（切換圖表類型時才重建）
        if (chartInstance) { chartInstance.destroy(); chartInstance = null; }

        const config = {
            type: chartType,
            data: { labels: data.labels, datasets },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                aspectRatio: isPie ? 1 : 1.5,
                animation: { duration: 200 }, // 主圖保留輕量動畫
                plugins: {
                    legend: {
                        display: isPie,
                        position: 'bottom',
                        labels: { boxWidth: 10, font: { size: 11 }, color: palette.text, padding: 12 }
                    },
                    tooltip: buildTooltipConfig(data, isPie)
                },
                cutout: isPie ? '75%' : undefined
            }
        };

        if (!isPie) {
            config.options.scales = buildScales(palette);
        }

        chartInstance = new Chart(ctx, config);
    }

    function buildScales(palette) {
        return {
            y: {
                beginAtZero: false,
                grid: { color: palette.grid },
                ticks: { color: palette.text + '90', font: { size: 10, family: 'mono' }, callback: formatAxisTick }
            },
            x: {
                grid: { display: false },
                ticks: { color: palette.text + '90', font: { size: 10 }, maxTicksLimit: 15 }
            }
        };
    }

    // ===== Small Multiples 渲染（差異更新） =====
    function destroySmallMultiples() {
        smallMultiplesMap.forEach(c => c.destroy());
        smallMultiplesMap.clear();
        const container = document.getElementById('smallMultiplesContainer');
        if (container) container.innerHTML = '';
    }

    function renderSmallMultiples(data) {
        const container = document.getElementById('smallMultiplesContainer');
        if (!container) return;

        // 若從 single 切到 multi，先清掉主圖
        if (currentRenderMode === 'single') {
            if (chartInstance) { chartInstance.destroy(); chartInstance = null; }
            currentRenderMode = null;
        }
        currentRenderMode = 'multi';

        const palette = getSongPalette();
        const symbol = data.symbol || '';
        const series = data.series || [];

        // 空資料
        if (series.length === 0) {
            destroySmallMultiples();
            container.innerHTML = `
                <div class="flex flex-col items-center justify-center py-16 text-base-content/40">
                    <span class="text-3xl mb-2">📭</span>
                    <span class="text-sm">暫無賬戶數據</span>
                </div>`;
            return;
        }

        // 記錄本次要保留的 key
        const nextKeys = new Set(series.map(s => s.name));

        // 移除「本次不存在」的舊圖（帳戶被刪除）
        for (const [name, chart] of smallMultiplesMap.entries()) {
            if (!nextKeys.has(name)) {
                chart.destroy();
                smallMultiplesMap.delete(name);
                const card = container.querySelector(`[data-account-key="${CSS.escape(name)}"]`);
                if (card) card.remove();
            }
        }

        // 依序處理每個 series
        series.forEach((s) => {
            let chart = smallMultiplesMap.get(s.name);
            let canvasEl = chart ? chart.canvas : null;
            const lastValue = s.values[s.values.length - 1] || 0;

            // 若帳戶是新的，建立卡片 + canvas
            if (!chart) {
                const card = document.createElement('div');
                card.className = 'bg-base-200/50 border border-base-300/60 rounded-xl p-3';
                card.dataset.accountKey = s.name;
                card.innerHTML = `
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-xs font-bold text-base-content/80 flex items-center gap-1.5">
                            <span class="inline-block w-2 h-2 rounded-full" style="background: ${s.color}"></span>
                            <span class="sm-name"></span>
                        </span>
                        <span class="text-[10px] text-base-content/60 font-mono sm-value"></span>
                    </div>
                    <div style="height: 110px; position: relative;">
                        <canvas></canvas>
                    </div>
                `;
                card.querySelector('.sm-name').textContent = s.name;
                card.querySelector('.sm-value').textContent = `${symbol}${lastValue.toLocaleString()}`;
                container.appendChild(card);

                canvasEl = card.querySelector('canvas');
                const ctx = canvasEl.getContext('2d');

                chart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: data.labels,
                        datasets: [{
                            data: s.values,
                            borderColor: s.color,
                            backgroundColor: s.color + '20',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.4,
                            pointRadius: 2,
                            pointBackgroundColor: s.color,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        animation: false, // small multiples 關閉動畫，初始化更快
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                backgroundColor: 'rgba(20, 18, 18, 0.9)',
                                titleColor: '#FAF9F6',
                                bodyColor: '#E7E5E4',
                                callbacks: {
                                    label: (context) => ` ${symbol}${context.parsed.y.toLocaleString()}`
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: false,
                                grid: { color: palette.grid },
                                ticks: {
                                    color: palette.text + '60',
                                    font: { size: 9, family: 'mono' },
                                    maxTicksLimit: 4,
                                    callback: formatAxisTick
                                }
                            },
                            x: {
                                grid: { display: false },
                                ticks: { color: palette.text + '60', font: { size: 9 }, maxTicksLimit: 6 }
                            }
                        }
                    }
                });
                smallMultiplesMap.set(s.name, chart);
            } else {
                // 既有圖：只更新資料
                chart.data.labels = data.labels;
                chart.data.datasets[0].data = s.values;
                chart.data.datasets[0].borderColor = s.color;
                chart.data.datasets[0].backgroundColor = s.color + '20';
                chart.data.datasets[0].pointBackgroundColor = s.color;
                chart.update('none'); // 關動畫

                // 更新文字
                const card = chart.canvas.closest('[data-account-key]');
                if (card) {
                    card.querySelector('.sm-value').textContent = `${symbol}${lastValue.toLocaleString()}`;
                }
            }
        });

        // 依 series 順序重排卡片（避免順序錯亂）
        series.forEach((s) => {
            const card = container.querySelector(`[data-account-key="${CSS.escape(s.name)}"]`);
            if (card) container.appendChild(card);
        });
    }

    // ===== 入口 =====
    function renderByData(data) {
        if (!data) return;
        emitRenderingStart();
        // 用 rAF 讓遮罩先渲染，再執行可能耗時的重繪
        requestAnimationFrame(() => {
            try {
                if (data.isSmallMultiples) renderSmallMultiples(data);
                else renderChart(data);
            } finally {
                // 再一個 rAF 確保 chart 已繪製到畫布
                requestAnimationFrame(() => emitRenderingEnd());
            }
        });
    }

    // ===== 初始化 =====
    setTimeout(function() {
        const initialData = @json($chartData ?? null);
        renderByData(initialData);

        Livewire.on('refreshChart', (data) => {
            if (data && data[0]) renderByData(data[0]);
        });

        // 主題切換：重建（顏色需重算），但保留結構
        const observer = new MutationObserver(() => {
            const fresh = @json($chartData ?? null);
            if (fresh) {
                // 主題變更需重建 chart（顏色寫在 config 內）
                destroySmallMultiples();
                if (chartInstance) { chartInstance.destroy(); chartInstance = null; }
                currentRenderMode = null;
                renderByData(fresh);
            }
        });
        observer.observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });

        // resize：只 resize，不重建
        let resizeTimer;
        window.addEventListener('resize', function() {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(() => {
                if (chartInstance) chartInstance.resize();
                smallMultiplesMap.forEach(c => c.resize());
            }, 250);
        });
    }, 100);
});
</script>
@endpush