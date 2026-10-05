<!-- resources/views/livewire/finance/report-stats.blade.php -->
<div class="p-4 md:p-6 max-w-7xl mx-auto space-y-6 text-base-content">
    
    {{-- ============================================================ --}}
    {{-- 頂部篩選與第一層分頁 --}}
    {{-- ============================================================ --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between border-b border-base-200 pb-4">
        <div class="inline-flex p-1 rounded-xl bg-base-200 border border-base-200 backdrop-blur-sm self-start flex-wrap gap-1">
            <button class="px-4 py-2 rounded-lg text-sm font-bold transition-all duration-200 {{ $tab1 === 'category' && !$showAssetTrend ? 'bg-base-100 text-base-content shadow-sm' : 'text-base-content/60 hover:text-base-content' }}" 
                    wire:click="$set('tab1', 'category')">📊 分類報表</button>
            <button class="px-4 py-2 rounded-lg text-sm font-bold transition-all duration-200 {{ $tab1 === 'trend' && !$showAssetTrend ? 'bg-base-100 text-base-content shadow-sm' : 'text-base-content/60 hover:text-base-content' }}" 
                    wire:click="$set('tab1', 'trend')">📈 收支趨勢</button>
            <button class="px-4 py-2 rounded-lg text-sm font-bold transition-all duration-200 {{ $tab1 === 'account' && !$showAssetTrend ? 'bg-base-100 text-base-content shadow-sm' : 'text-base-content/60 hover:text-base-content' }}" 
                    wire:click="$set('tab1', 'account')">💼 賬戶報表</button>
            <button class="px-4 py-2 rounded-lg text-sm font-bold transition-all duration-200 {{ $showAssetTrend ? 'bg-primary/20 text-primary shadow-sm' : 'text-base-content/60 hover:text-base-content' }}" 
                    wire:click="$set('tab1', 'asset')">🏦 總資產趨勢</button>
        </div>

        <div class="flex items-center gap-2">
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
    {{-- 幣別切換器（總資產趨勢時隱藏，因為強制合併） --}}
    {{-- ============================================================ --}}
    @if(!$showAssetTrend)
        <div class="flex items-center gap-2 flex-wrap">
            <span class="text-xs font-bold text-base-content/50 tracking-widest">幣別</span>
            <div class="flex gap-1 p-1 bg-base-200 rounded-xl border border-base-200 backdrop-blur-sm flex-wrap">
                <button class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all duration-200 {{ $currencyFilter === 'all' ? 'bg-base-100 text-base-content shadow-sm' : 'text-base-content/60 hover:text-base-content' }}"
                        wire:click="$set('currencyFilter', 'all')">
                    🌐 全部（合併）
                </button>
                @foreach($this->availableCurrencies as $currency)
                    @php
                        $theme = config('business.currency_theme_map')[$currency->code] ?? 'gray';
                        $activeClass = match($theme) {
                            'blue' => 'bg-sky-500/20 text-sky-700 dark:text-sky-300 shadow-sm',
                            'red' => 'bg-rose-500/20 text-rose-700 dark:text-rose-300 shadow-sm',
                            'green' => 'bg-emerald-500/20 text-emerald-700 dark:text-emerald-300 shadow-sm',
                            'purple' => 'bg-purple-500/20 text-purple-700 dark:text-purple-300 shadow-sm',
                            default => 'bg-base-100 text-base-content shadow-sm',
                        };
                    @endphp
                    <button class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all duration-200 {{ $currencyFilter === $currency->code ? $activeClass : 'text-base-content/60 hover:text-base-content' }}"
                            wire:click="$set('currencyFilter', '{{ $currency->code }}')">
                        {{ $currency->symbol }} {{ $currency->code }}
                    </button>
                @endforeach
            </div>
        </div>
    @endif

    {{-- ============================================================ --}}
    {{-- 第二層與第三層分頁 --}}
    {{-- ============================================================ --}}
    <div class="flex flex-wrap gap-4 items-center justify-between">
        <div class="flex gap-1.5 p-1 bg-base-200 rounded-xl border border-base-200 backdrop-blur-sm">
            @if(!$showAssetTrend)
                @if($tab1 === 'account')
                    <button class="btn btn-xs md:btn-sm font-bold rounded-lg border-0 transition-all duration-200 {{ $tab2 === 'composition' ? 'bg-primary/20 text-primary hover:bg-primary/30' : 'btn-ghost text-base-content/50' }}" 
                            wire:click="$set('tab2', 'composition')">佔比</button>
                    <button class="btn btn-xs md:btn-sm font-bold rounded-lg border-0 transition-all duration-200 {{ $tab2 === 'trend' ? 'bg-primary/20 text-primary hover:bg-primary/30' : 'btn-ghost text-base-content/50' }}" 
                            wire:click="$set('tab2', 'trend')">趨勢</button>
                @else
                    <button class="btn btn-xs md:btn-sm font-bold rounded-lg border-0 transition-all duration-200 {{ $tab2 === 'expense' ? 'bg-error/20 text-error hover:bg-error/30' : 'btn-ghost text-base-content/50' }}" 
                            wire:click="$set('tab2', 'expense')">支出</button>
                    <button class="btn btn-xs md:btn-sm font-bold rounded-lg border-0 transition-all duration-200 {{ $tab2 === 'income' ? 'bg-success/20 text-success hover:bg-success/30' : 'btn-ghost text-base-content/50' }}" 
                            wire:click="$set('tab2', 'income')">收入</button>
                    @if($tab1 === 'trend')
                        <button class="btn btn-xs md:btn-sm font-bold rounded-lg border-0 transition-all duration-200 {{ $tab2 === 'balance' ? 'bg-base-100 text-base-content hover:bg-base-300' : 'btn-ghost text-base-content/50' }}" 
                                wire:click="$set('tab2', 'balance')">結餘</button>
                    @endif
                @endif
            @else
                <span class="px-3 py-1 text-xs font-bold text-primary bg-primary/10 rounded-lg">
                    本位幣：{{ $baseCurrency->symbol ?? 'NT$' }} {{ $baseCurrency->code ?? 'TWD' }}
                </span>
            @endif
        </div>

        <div class="join border border-base-200 rounded-lg overflow-hidden bg-base-200 backdrop-blur-sm">
            @if($showAssetTrend)
                <button class="join-item btn btn-xs md:btn-sm border-0 rounded-none {{ $assetTab === 'month' ? 'bg-base-100 text-primary' : 'btn-ghost text-base-content/50' }}" 
                        wire:click="$set('assetTab', 'month')">月度</button>
                <button class="join-item btn btn-xs md:btn-sm border-0 rounded-none {{ $assetTab === 'day' ? 'bg-base-100 text-primary' : 'btn-ghost text-base-content/50' }}" 
                        wire:click="$set('assetTab', 'day')">每日</button>
            @elseif($tab1 === 'account')
                {{-- ⬅️ 賬戶報表：年/月/日，預設 year（由 Component 控制） --}}
                <button class="join-item btn btn-xs md:btn-sm border-0 rounded-none {{ $tab3 === 'year' ? 'bg-base-100 text-base-content' : 'btn-ghost text-base-content/50' }}" 
                        wire:click="$set('tab3', 'year')">年度</button>
                <button class="join-item btn btn-xs md:btn-sm border-0 rounded-none {{ $tab3 === 'month' ? 'bg-base-100 text-base-content' : 'btn-ghost text-base-content/50' }}" 
                        wire:click="$set('tab3', 'month')">月度</button>
                <button class="join-item btn btn-xs md:btn-sm border-0 rounded-none {{ $tab3 === 'day' ? 'bg-base-100 text-base-content' : 'btn-ghost text-base-content/50' }}" 
                        wire:click="$set('tab3', 'day')">每日</button>
            @elseif($tab1 === 'category')
                <button class="join-item btn btn-xs md:btn-sm border-0 rounded-none {{ $tab3 === 'year' ? 'bg-base-100 text-base-content' : 'btn-ghost text-base-content/50' }}" 
                        wire:click="$set('tab3', 'year')">年{{ $tab2 === 'expense' ? '支出' : '收入' }}</button>
                <button class="join-item btn btn-xs md:btn-sm border-0 rounded-none {{ $tab3 === 'month' ? 'bg-base-100 text-base-content' : 'btn-ghost text-base-content/50' }}" 
                        wire:click="$set('tab3', 'month')">月{{ $tab2 === 'expense' ? '支出' : '收入' }}</button>
            @else
                <button class="join-item btn btn-xs md:btn-sm border-0 rounded-none {{ $tab3 === 'month' ? 'bg-base-100 text-base-content' : 'btn-ghost text-base-content/50' }}" 
                        wire:click="$set('tab3', 'month')">月{{ $tab2 === 'expense' ? '支出' : ($tab2 === 'income' ? '收入' : '結餘') }}</button>
                <button class="join-item btn btn-xs md:btn-sm border-0 rounded-none {{ $tab3 === 'day' ? 'bg-base-100 text-base-content' : 'btn-ghost text-base-content/50' }}" 
                        wire:click="$set('tab3', 'day')">日{{ $tab2 === 'expense' ? '支出' : ($tab2 === 'income' ? '收入' : '結餘') }}</button>
            @endif
        </div>
    </div>

    {{-- ============================================================ --}}
    {{-- 交易效應 vs 匯率效應分解（僅合併模式的總資產趨勢） --}}
    {{-- ============================================================ --}}
    @if($showAssetTrend && $effectBreakdown)
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <div class="bg-base-100 border border-base-200 p-4 rounded-xl">
                <div class="text-xs font-bold text-base-content/50 tracking-widest mb-1">期間變動</div>
                <div class="text-lg font-black font-mono {{ $effectBreakdown['total_change_raw'] >= 0 ? 'text-success' : 'text-error' }}">
                    {{ $effectBreakdown['total_change'] }}
                </div>
            </div>
            <div class="bg-base-100 border border-base-200 p-4 rounded-xl">
                <div class="text-xs font-bold text-base-content/50 tracking-widest mb-1">💼 交易貢獻</div>
                <div class="text-lg font-black font-mono {{ $effectBreakdown['transaction_effect_raw'] >= 0 ? 'text-success' : 'text-error' }}">
                    {{ $effectBreakdown['transaction_effect'] }}
                </div>
            </div>
            <div class="bg-base-100 border border-base-200 p-4 rounded-xl">
                <div class="text-xs font-bold text-base-content/50 tracking-widest mb-1">💱 匯率重估</div>
                <div class="text-lg font-black font-mono {{ $effectBreakdown['fx_effect_raw'] >= 0 ? 'text-success' : 'text-error' }}">
                    {{ $effectBreakdown['fx_effect'] }}
                </div>
            </div>
        </div>
    @endif

    {{-- ============================================================ --}}
    {{-- 主內容區 --}}
    {{-- ============================================================ --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        
        <div class="lg:col-span-5 bg-base-100 border border-base-200 p-5 rounded-2xl backdrop-blur-md shadow-sm flex flex-col justify-center items-center relative min-h-[350px] transition-all duration-300">
            @if($tab1 === 'account' && $tab2 === 'trend')
                <div id="smallMultiplesContainer" class="w-full grid grid-cols-1 gap-3" style="max-height: 520px; overflow-y: auto;"></div>
            @else
                <div class="w-full max-w-[320px] relative" style="min-height: 300px;">
                    <canvas id="financeChart" style="width: 100%; height: 100%; min-height: 300px;"></canvas>
                    @if($tab1 === 'category' && !$showAssetTrend)
                        <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none mt-4">
                            <span class="text-xs text-base-content/50 font-bold tracking-widest">總額</span>
                            <span class="text-lg font-black text-base-content font-mono" id="chartCenterTotal">{{ $this->categoryData['total'] ?? '0.00' }}</span>
                        </div>
                    @endif
                    @if($showAssetTrend)
                        <div class="absolute top-2 right-2 text-[10px] text-base-content/30 font-mono">
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

        <div class="lg:col-span-7 space-y-4">
            
            @if($tab1 === 'account')
                @if($tab2 === 'composition')
                    <div class="bg-base-100 border border-base-200 rounded-2xl backdrop-blur-md shadow-sm overflow-hidden">
                        <div class="p-4 bg-base-200 border-b border-base-200 font-bold text-xs tracking-widest text-base-content/70 flex justify-between items-center">
                            <span>💼 賬戶佔比明細</span>
                            <span class="text-[10px] font-normal text-base-content/40">
                                快照日：{{ $accountCompositionData['snapshot_date'] ?? '—' }}
                            </span>
                        </div>
                        <div class="hidden md:block overflow-x-auto">
                            <table class="table w-full text-sm">
                                <thead>
                                    <tr class="border-b border-base-200 text-base-content/40">
                                        <th class="bg-transparent font-bold">賬戶</th>
                                        <th class="bg-transparent text-right font-bold">餘額</th>
                                        <th class="bg-transparent text-right font-bold">佔比</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-base-200">
                                    @forelse($accountCompositionData['list'] as $item)
                                        <tr class="hover:bg-base-200 transition-colors border-0">
                                            <td class="font-bold text-base-content bg-transparent">
                                                {{ $item['name'] }}
                                                @if(!empty($item['children']))
                                                    <div class="text-[10px] text-base-content/40 font-normal mt-0.5">
                                                        {{ collect($item['children'])->pluck('name')->join('、') }}
                                                    </div>
                                                @endif
                                            </td>
                                            <td class="text-right font-mono font-bold text-base-content bg-transparent">{{ $item['amount_display'] }}</td>
                                            <td class="text-right font-mono text-base-content/50 bg-transparent">{{ $item['percentage'] }}%</td>
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
                                        <div class="text-xs text-base-content/50 mt-0.5">佔比 {{ $item['percentage'] }}%</div>
                                    </div>
                                    <div class="font-mono font-extrabold text-md text-base-content">{{ $item['amount_display'] }}</div>
                                </div>
                            @empty
                                <div class="p-8 text-center text-sm text-base-content/40">暫無賬戶數據</div>
                            @endforelse
                        </div>
                    </div>
                @else
                    <div class="bg-base-100 border border-base-200 rounded-2xl backdrop-blur-md shadow-sm overflow-hidden">
                        <div class="p-4 bg-base-200 border-b border-base-200 font-bold text-xs tracking-widest text-base-content/70 flex justify-between items-center">
                            <span>📈 各賬戶餘額明細</span>
                            <span class="text-[10px] font-normal text-base-content/40">
                                {{ $selectedYear }} 年 @if($tab3 === 'day') {{ $selectedMonth }} 月 @endif
                            </span>
                        </div>
                        <div class="hidden md:block overflow-x-auto max-h-[400px] overflow-y-auto">
                            <table class="table table-pin-rows w-full text-sm">
                                <thead>
                                    <tr class="border-b border-base-200 text-base-content/40">
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
                                            $changeClass = $change > 0 ? 'text-success' : ($change < 0 ? 'text-error' : 'text-base-content/30');
                                            $changeSymbol = $change > 0 ? '↑' : ($change < 0 ? '↓' : '—');
                                        @endphp
                                        <tr class="hover:bg-base-200 transition-colors border-0">
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
                                    $changeClass = $change > 0 ? 'text-success' : ($change < 0 ? 'text-error' : 'text-base-content/30');
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
                <div class="bg-base-100 border border-base-200 rounded-2xl backdrop-blur-md shadow-sm overflow-hidden">
                    <div class="p-4 bg-base-200 border-b border-base-200 font-bold text-xs tracking-widest text-base-content/70 flex justify-between items-center">
                        <span>📈 總資產趨勢明細</span>
                        <span class="text-[10px] font-normal text-base-content/40">
                            {{ $selectedYear }} 年 @if($assetTab === 'day') {{ $selectedMonth }} 月 @endif
                        </span>
                    </div>
                    
                    @php
                        // 統一取出 table 資料（合併 / 分幣別）
                        if (isset($this->assetTrendData['mode']) && $this->assetTrendData['mode'] === 'multi') {
                            $assetTable = $this->assetTrendData['table'] ?? [];
                            $assetSymbol = $this->assetTrendData['symbol'] ?? 'NT$';
                        } else {
                            $assetTable = collect($this->assetTrendData)->map(fn($item) => [
                                'label' => $item['label'],
                                'amount' => $item['amount'],
                                'amount_display' => ($item['symbol'] ?? 'NT$') . number_format($item['amount'], 2),
                                'symbol' => $item['symbol'] ?? 'NT$',
                            ])->all();
                            $assetSymbol = $baseCurrency->symbol ?? 'NT$';
                        }
                    @endphp

                    <div class="hidden md:block overflow-x-auto max-h-[400px] overflow-y-auto">
                        <table class="table table-pin-rows w-full text-sm">
                            <thead>
                                <tr class="border-b border-base-200 text-base-content/40">
                                    <th class="bg-base-100 font-bold">時間</th>
                                    <th class="bg-base-100 text-right font-bold">總資產</th>
                                    <th class="bg-base-100 text-right font-bold">變動</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-base-200">
                                @php $prevAmount = null; @endphp
                                @foreach($assetTable as $item)
                                    @php
                                        $change = $prevAmount !== null ? $item['amount'] - $prevAmount : 0;
                                        $changeClass = $change > 0 ? 'text-success' : ($change < 0 ? 'text-error' : 'text-base-content/30');
                                        $changeSymbol = $change > 0 ? '↑' : ($change < 0 ? '↓' : '—');
                                        $prevAmount = $item['amount'];
                                    @endphp
                                    <tr class="hover:bg-base-200 transition-colors border-0">
                                        <td class="font-medium text-base-content/70 bg-transparent">{{ $item['label'] }}</td>
                                        <td class="text-right font-mono font-bold text-primary bg-transparent">{{ $item['amount_display'] }}</td>
                                        <td class="text-right font-mono text-sm {{ $changeClass }} bg-transparent">
                                            @if($change != 0)
                                                {{ $changeSymbol }} {{ $assetSymbol }}{{ number_format(abs($change), 2) }}
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
                        @php $prevAmount = null; @endphp
                        @foreach($assetTable as $item)
                            @php
                                $change = $prevAmount !== null ? $item['amount'] - $prevAmount : 0;
                                $changeClass = $change > 0 ? 'text-success' : ($change < 0 ? 'text-error' : 'text-base-content/30');
                                $changeSymbol = $change > 0 ? '↑' : ($change < 0 ? '↓' : '—');
                                $prevAmount = $item['amount'];
                            @endphp
                            <div class="p-3.5 flex justify-between items-center bg-transparent">
                                <div>
                                    <span class="text-sm font-medium text-base-content/70">{{ $item['label'] }}</span>
                                    @if($change != 0)
                                        <span class="text-xs ml-2 {{ $changeClass }}">{{ $changeSymbol }} {{ $assetSymbol }}{{ number_format(abs($change), 2) }}</span>
                                    @endif
                                </div>
                                <span class="font-mono font-bold text-sm text-primary">{{ $item['amount_display'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

            @elseif($tab1 === 'category')
                <div class="bg-base-100 border border-base-200 rounded-2xl backdrop-blur-md shadow-sm overflow-hidden">
                    <div class="p-4 bg-base-200 border-b border-base-200 font-bold text-xs tracking-widest text-base-content/70 flex justify-between items-center">
                        <span>📋 分類數據明細 (點擊大類查看明細流水)</span>
                    </div>
                    <div class="hidden md:block overflow-x-auto">
                        <table class="table w-full text-sm">
                            <thead>
                                <tr class="border-b border-base-200 text-base-content/40">
                                    <th class="bg-transparent font-bold">分類大類</th>
                                    <th class="bg-transparent text-right font-bold">總計金額</th>
                                    <th class="bg-transparent text-right font-bold">佔比</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-base-200">
                                @forelse($this->categoryData['list'] as $item)
                                    <tr class="hover:bg-base-200 cursor-pointer transition-colors border-0"
                                        wire:click="openCategoryTransactions({{ $item['id'] }}, '{{ addslashes($item['name']) }}')">
                                        <td class="font-bold text-base-content bg-transparent flex items-center gap-2">
                                            <span>{{ $item['name'] }}</span>
                                            <x-heroicon-m-chevron-right class="w-4 h-4 text-base-content/40" />
                                        </td>
                                        <td class="text-right font-mono font-bold text-base-content bg-transparent">{{ $item['amount_display'] }}</td>
                                        <td class="text-right font-mono text-base-content/50 bg-transparent">{{ $item['percentage'] }}%</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-center py-8 text-base-content/40 bg-transparent">暫無相關財務交易數據</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="block md:hidden divide-y divide-base-200">
                        @forelse($this->categoryData['list'] as $item)
                            <div class="p-4 flex justify-between items-center bg-transparent hover:bg-base-200 active:bg-base-300 cursor-pointer transition-all"
                                 wire:click="openCategoryTransactions({{ $item['id'] }}, '{{ addslashes($item['name']) }}')">
                                <div>
                                    <div class="font-bold text-base-content text-sm flex items-center gap-1">
                                        {{ $item['name'] }}
                                        <x-heroicon-m-chevron-right class="w-3.5 h-3.5 text-base-content/40" />
                                    </div>
                                    <div class="text-xs text-base-content/50 mt-0.5">佔比 {{ $item['percentage'] }}%</div>
                                </div>
                                <div class="font-mono font-extrabold text-md text-base-content">{{ $item['amount_display'] }}</div>
                            </div>
                        @empty
                            <div class="p-8 text-center text-sm text-base-content/40">暫無相關財務交易數據</div>
                        @endforelse
                    </div>
                </div>

            @else
                <div class="bg-base-100 border border-base-200 rounded-2xl backdrop-blur-md shadow-sm overflow-hidden">
                    <div class="p-4 bg-base-200 border-b border-base-200 font-bold text-xs tracking-widest text-base-content/70">
                        📅 週期趨勢明細 (時間 + 金額)
                    </div>
                    <div class="hidden md:block overflow-x-auto max-h-[400px] overflow-y-auto">
                        <table class="table table-pin-rows w-full text-sm">
                            <thead>
                                <tr class="border-b border-base-200 text-base-content/40">
                                    <th class="bg-base-100 font-bold">時間區間</th>
                                    <th class="bg-base-100 text-right font-bold">金額</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-base-200">
                                @foreach($this->trendData as $item)
                                    <tr class="hover:bg-base-200 transition-colors border-0">
                                        <td class="font-medium text-base-content/70 bg-transparent">{{ $item['label'] }}</td>
                                        <td class="text-right font-mono font-bold text-base-content bg-transparent">{{ $item['amount_display'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="block md:hidden divide-y divide-base-200 max-h-[400px] overflow-y-auto">
                        @foreach($this->trendData as $item)
                            <div class="p-3.5 flex justify-between items-center bg-transparent">
                                <span class="text-sm font-medium text-base-content/70">{{ $item['label'] }}</span>
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
{{-- Chart.js 渲染 --}}
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

    setTimeout(function() {
        let chartInstance = null;
        const smallMultiplesInstances = [];

        function renderChart(data) {
            const canvasEl = document.getElementById('financeChart');
            if (!canvasEl) return;
            
            const ctx = canvasEl.getContext('2d');
            if (chartInstance) { chartInstance.destroy(); chartInstance = null; }

            const centerTotalEl = document.getElementById('chartCenterTotal');
            if (centerTotalEl && data.centerText !== undefined && data.centerText !== null) {
                const symbol = data.symbol || '';
                centerTotalEl.textContent = symbol + data.centerText;
            }

            const palette = getSongPalette();

            if (!data.labels || data.labels.length === 0 || (!data.values && !data.series)) {
                chartInstance = new Chart(ctx, {
                    type: 'doughnut',
                    data: { labels: ['無數據'], datasets: [{ data: [1], backgroundColor: [palette.grid], borderWidth: 0 }] },
                    options: { responsive: true, maintainAspectRatio: true, plugins: { legend: { display: false }, tooltip: { enabled: false } }, cutout: '75%' }
                });
                return;
            }

            const isPie = data.type === 'pie';
            const isLine = data.type === 'line';
            const isMultiLine = data.type === 'multiLine';
            const chartType = isPie ? 'doughnut' : 'line';

            let datasets = [];

            if (isMultiLine) {
                // 總資產分幣別多線
                datasets = (data.series || []).map(s => ({
                    label: s.name,
                    data: s.values,
                    borderColor: s.color,
                    backgroundColor: s.color + '20',
                    borderWidth: 2,
                    fill: false,
                    tension: 0.4,
                    pointRadius: 2,
                    pointBackgroundColor: s.color,
                }));
            } else if (isLine) {
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

            const config = {
                type: chartType,
                data: {
                    labels: data.labels,
                    datasets: datasets
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    aspectRatio: isPie ? 1 : 1.5,
                    plugins: {
                        legend: {
                            display: isPie || isMultiLine,
                            position: 'bottom',
                            labels: { 
                                boxWidth: 10, 
                                font: { size: 11 },
                                color: palette.text,
                                padding: 12
                            }
                        },
                        tooltip: {
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
                        }
                    },
                    cutout: isPie ? '75%' : undefined
                }
            };

            if (!isPie) {
                config.options.scales = {
                    y: {
                        beginAtZero: false,
                        grid: { color: palette.grid },
                        ticks: {
                            color: palette.text + '90',
                            font: { size: 10, family: 'mono' },
                            callback: function(value) {
                                if (Math.abs(value) >= 1e6) return (value / 1e6).toFixed(1) + 'M';
                                if (Math.abs(value) >= 1e3) return (value / 1e3).toFixed(0) + 'K';
                                return value;
                            }
                        }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { color: palette.text + '90', font: { size: 10 }, maxTicksLimit: 15 }
                    }
                };
            }

            chartInstance = new Chart(ctx, config);
        }

        function renderSmallMultiples(data) {
            const container = document.getElementById('smallMultiplesContainer');
            if (!container) return;

            smallMultiplesInstances.forEach(c => c.destroy());
            smallMultiplesInstances.length = 0;
            container.innerHTML = '';

            const palette = getSongPalette();

            if (!data.series || data.series.length === 0) {
                container.innerHTML = `
                    <div class="flex flex-col items-center justify-center py-16 text-base-content/40">
                        <span class="text-3xl mb-2">📭</span>
                        <span class="text-sm">暫無賬戶數據</span>
                    </div>`;
                return;
            }

            const symbol = data.symbol || '';

            data.series.forEach((s, idx) => {
                const card = document.createElement('div');
                card.className = 'bg-base-200/50 border border-base-200 rounded-xl p-3';
                card.innerHTML = `
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-xs font-bold text-base-content/80 flex items-center gap-1.5">
                            <span class="inline-block w-2 h-2 rounded-full" style="background: ${s.color}"></span>
                            ${s.name}
                        </span>
                        <span class="text-[10px] text-base-content/50 font-mono">
                            ${symbol}${(s.values[s.values.length - 1] || 0).toLocaleString()}
                        </span>
                    </div>
                    <div style="height: 110px; position: relative;">
                        <canvas id="sm-chart-${idx}"></canvas>
                    </div>
                `;
                container.appendChild(card);

                const ctx = card.querySelector('canvas').getContext('2d');
                const chart = new Chart(ctx, {
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
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                backgroundColor: 'rgba(20, 18, 18, 0.9)',
                                titleColor: '#FAF9F6',
                                bodyColor: '#E7E5E4',
                                callbacks: {
                                    label: function(context) {
                                        return ` ${symbol}${context.parsed.y.toLocaleString()}`;
                                    }
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
                                    callback: function(value) {
                                        if (Math.abs(value) >= 1e6) return (value / 1e6).toFixed(1) + 'M';
                                        if (Math.abs(value) >= 1e3) return (value / 1e3).toFixed(0) + 'K';
                                        return value;
                                    }
                                }
                            },
                            x: {
                                grid: { display: false },
                                ticks: { color: palette.text + '60', font: { size: 9 }, maxTicksLimit: 6 }
                            }
                        }
                    }
                });
                smallMultiplesInstances.push(chart);
            });
        }

        function renderByData(data) {
            if (!data) return;
            if (data.isSmallMultiples) renderSmallMultiples(data);
            else renderChart(data);
        }

        const initialData = @json($chartData ?? null);
        renderByData(initialData);

        Livewire.on('refreshChart', (data) => {
            if (data && data[0]) renderByData(data[0]);
        });

        const observer = new MutationObserver(() => {
            const fresh = @json($chartData ?? null);
            if (fresh) renderByData(fresh);
        });
        observer.observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });

        let resizeTimer;
        window.addEventListener('resize', function() {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(() => {
                if (chartInstance) chartInstance.resize();
                smallMultiplesInstances.forEach(c => c.resize());
            }, 250);
        });

    }, 100);
});
</script>
@endpush