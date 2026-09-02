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
            <button class="px-4 py-2 rounded-lg text-sm font-bold transition-all duration-200 {{ $showAssetTrend ? 'bg-primary/20 text-primary shadow-sm' : 'text-base-content/60 hover:text-base-content' }}" 
                    wire:click="$set('tab1', 'asset')">🏦 總資產趨勢</button>
        </div>

        {{-- 日期導航組件：模型對齊 currentDate --}}
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
    {{-- 第二層與第三層分頁控制區 --}}
    {{-- ============================================================ --}}
    <div class="flex flex-wrap gap-4 items-center justify-between">
        {{-- 第二層分頁：支出/收入/結餘 --}}
        <div class="flex gap-1.5 p-1 bg-base-200 rounded-xl border border-base-200 backdrop-blur-sm">
            @if(!$showAssetTrend)
                <button class="btn btn-xs md:btn-sm font-bold rounded-lg border-0 transition-all duration-200 {{ $tab2 === 'expense' ? 'bg-error/20 text-error hover:bg-error/30' : 'btn-ghost text-base-content/50' }}" 
                        wire:click="$set('tab2', 'expense')">支出</button>
                <button class="btn btn-xs md:btn-sm font-bold rounded-lg border-0 transition-all duration-200 {{ $tab2 === 'income' ? 'bg-success/20 text-success hover:bg-success/30' : 'btn-ghost text-base-content/50' }}" 
                        wire:click="$set('tab2', 'income')">收入</button>
                @if($tab1 === 'trend')
                    <button class="btn btn-xs md:btn-sm font-bold rounded-lg border-0 transition-all duration-200 {{ $tab2 === 'balance' ? 'bg-base-100 text-base-content hover:bg-base-300' : 'btn-ghost text-base-content/50' }}" 
                            wire:click="$set('tab2', 'balance')">結餘</button>
                @endif
            @else
                {{-- 總資產模式：顯示本位幣資訊 --}}
                <span class="px-3 py-1 text-xs font-bold text-primary bg-primary/10 rounded-lg">
                    本位幣：{{ $baseCurrency->symbol ?? 'NT$' }} {{ $baseCurrency->code ?? 'TWD' }}
                </span>
            @endif
        </div>

        {{-- 第三層分頁：年/月/日 --}}
        <div class="join border border-base-200 rounded-lg overflow-hidden bg-base-200 backdrop-blur-sm">
            @if($showAssetTrend)
                {{-- 總資產模式專用 --}}
                <button class="join-item btn btn-xs md:btn-sm border-0 rounded-none {{ $assetTab === 'month' ? 'bg-base-100 text-primary' : 'btn-ghost text-base-content/50' }}" 
                        wire:click="$set('assetTab', 'month')">月度</button>
                <button class="join-item btn btn-xs md:btn-sm border-0 rounded-none {{ $assetTab === 'day' ? 'bg-base-100 text-primary' : 'btn-ghost text-base-content/50' }}" 
                        wire:click="$set('assetTab', 'day')">每日</button>
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
    {{-- 主內容區 --}}
    {{-- ============================================================ --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        
        {{-- 左側圖表區 --}}
        <div class="lg:col-span-5 bg-base-100 border border-base-200 p-5 rounded-2xl backdrop-blur-md shadow-sm flex flex-col justify-center items-center relative min-h-[350px] transition-all duration-300">
            <div class="w-full max-w-[320px] relative" style="min-height: 300px;">
                <canvas id="financeChart" style="width: 100%; height: 100%; min-height: 300px;"></canvas>
                @if($tab1 === 'category' && !$showAssetTrend)
                    <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none mt-4">
                        <span class="text-xs text-base-content/50 font-bold tracking-widest">總額</span>
                        <span class="text-lg font-black text-base-content font-mono" id="chartCenterTotal">${{ $this->categoryData['total'] ?? '0.00' }}</span>
                    </div>
                @endif
                @if($showAssetTrend)
                    <div class="absolute top-2 right-2 text-[10px] text-base-content/30 font-mono">
                        {{ $baseCurrency->code ?? 'TWD' }}
                    </div>
                @endif
            </div>
        </div>

        {{-- 右側數據列表區 --}}
        <div class="lg:col-span-7 space-y-4">
            
            @if($showAssetTrend)
                {{-- ============================================================ --}}
                {{-- 總資產趨勢列表 --}}
                {{-- ============================================================ --}}
                <div class="bg-base-100 border border-base-200 rounded-2xl backdrop-blur-md shadow-sm overflow-hidden">
                    <div class="p-4 bg-base-200 border-b border-base-200 font-bold text-xs tracking-widest text-base-content/70 flex justify-between items-center">
                        <span>📈 總資產趨勢明細</span>
                        <span class="text-[10px] font-normal text-base-content/40">
                            {{ $selectedYear }} 年 @if($assetTab === 'day') {{ $selectedMonth }} 月 @endif
                        </span>
                    </div>
                    
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
                                @php
                                    $prevAmount = null;
                                @endphp
                                @foreach($this->assetTrendData as $item)
                                    @php
                                        $change = $prevAmount !== null ? $item['amount'] - $prevAmount : 0;
                                        $changeClass = $change > 0 ? 'text-success' : ($change < 0 ? 'text-error' : 'text-base-content/30');
                                        $changeSymbol = $change > 0 ? '↑' : ($change < 0 ? '↓' : '—');
                                        $prevAmount = $item['amount'];
                                    @endphp
                                    <tr class="hover:bg-base-200 transition-colors border-0">
                                        <td class="font-medium text-base-content/70 bg-transparent">{{ $item['label'] }}</td>
                                        <td class="text-right font-mono font-bold text-primary bg-transparent">
                                            {{ $item['symbol'] }}{{ number_format($item['amount'], 2) }}
                                        </td>
                                        <td class="text-right font-mono text-sm {{ $changeClass }} bg-transparent">
                                            @if($change != 0)
                                                {{ $changeSymbol }} {{ $item['symbol'] }}{{ number_format(abs($change), 2) }}
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
                        @php
                            $prevAmount = null;
                        @endphp
                        @foreach($this->assetTrendData as $item)
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
                                        <span class="text-xs ml-2 {{ $changeClass }}">{{ $changeSymbol }} {{ number_format(abs($change), 2) }}</span>
                                    @endif
                                </div>
                                <span class="font-mono font-bold text-sm text-primary">
                                    {{ $item['symbol'] }}{{ number_format($item['amount'], 2) }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>

            @elseif($tab1 === 'category')
                {{-- ============================================================ --}}
                {{-- 分類報表列表 --}}
                {{-- ============================================================ --}}
                <div class="bg-base-100 border border-base-200 rounded-2xl backdrop-blur-md shadow-sm overflow-hidden">
                    <div class="p-4 bg-base-200 border-b border-base-200 font-bold text-xs tracking-widest text-base-content/70 flex justify-between items-center">
                        <span>📋 分類數據明細 (點擊大類查看明細流水)</span>
                    </div>
                    
                    {{-- PC 端表格 --}}
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
                                        <td class="text-right font-mono font-bold text-base-content bg-transparent">${{ $item['amount_display'] }}</td>
                                        <td class="text-right font-mono text-base-content/50 bg-transparent">{{ $item['percentage'] }}%</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-center py-8 text-base-content/40 bg-transparent">暫無相關財務交易數據</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- 手機端卡片 --}}
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
                                <div class="font-mono font-extrabold text-md text-base-content">${{ $item['amount_display'] }}</div>
                            </div>
                        @empty
                            <div class="p-8 text-center text-sm text-base-content/40">暫無相關財務交易數據</div>
                        @endforelse
                    </div>
                </div>

            @else
                {{-- ============================================================ --}}
                {{-- 趨勢報表列表 --}}
                {{-- ============================================================ --}}
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
                                        <td class="text-right font-mono font-bold text-base-content bg-transparent">${{ $item['amount_display'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="block md:hidden divide-y divide-base-200 max-h-[400px] overflow-y-auto">
                        @foreach($this->trendData as $item)
                            <div class="p-3.5 flex justify-between items-center bg-transparent">
                                <span class="text-sm font-medium text-base-content/70">{{ $item['label'] }}</span>
                                <span class="font-mono font-bold text-sm text-base-content">${{ $item['amount_display'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

        </div>
    </div>

    {{-- ============================================================ --}}
    {{-- 交易流水 Modal --}}
    {{-- ============================================================ --}}
    <livewire:finance.transaction-list-modal />
</div>

{{-- ============================================================ --}}
{{-- Chart.js 渲染 Script --}}
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
        const canvas = document.getElementById('financeChart');
        if (!canvas) return;
        
        const ctx = canvas.getContext('2d');
        let chartInstance = null;

        function renderChart(data) {
            if (chartInstance) {
                chartInstance.destroy();
                chartInstance = null;
            }

            const container = canvas.parentElement;
            if (container) {
                canvas.style.width = '100%';
                canvas.style.height = '100%';
            }

            const centerTotalEl = document.getElementById('chartCenterTotal');
            if (centerTotalEl && data.centerText !== undefined && data.centerText !== null) {
                centerTotalEl.textContent = '$' + data.centerText;
            }

            const palette = getSongPalette();

            // 無數據狀態
            if (!data.labels || data.labels.length === 0 || !data.values || data.values.length === 0) {
                chartInstance = new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: ['無數據'],
                        datasets: [{
                            data: [1],
                            backgroundColor: [palette.grid],
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: { legend: { display: false }, tooltip: { enabled: false } },
                        cutout: '75%'
                    }
                });
                return;
            }

            // 判斷圖表類型
            const isPie = data.type === 'pie';
            const isLine = data.type === 'line';
            const chartType = isPie ? 'doughnut' : (isLine ? 'line' : 'bar');

            const config = {
                type: chartType,
                data: {
                    labels: data.labels,
                    datasets: [{
                        data: data.values,
                        // 長條圖設定
                        backgroundColor: isPie ? palette.pies : (isLine ? 'rgba(129, 140, 248, 0.1)' : (data.color || palette.primary)),
                        borderColor: isPie ? (document.documentElement.getAttribute('data-theme') === 'dark' ? '#141212' : '#FAF9F6') : (isLine ? '#818cf8' : 'transparent'),
                        borderWidth: isPie ? 1.5 : (isLine ? 2 : 0),
                        borderRadius: isPie ? 0 : (isLine ? 0 : 4),
                        // 折線圖專屬
                        fill: isLine ? true : false,
                        tension: isLine ? 0.4 : 0,
                        pointRadius: isLine ? 3 : 0,
                        pointBackgroundColor: isLine ? '#818cf8' : undefined,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    aspectRatio: isPie ? 1 : 1.5,
                    plugins: {
                        legend: {
                            display: isPie,
                            position: 'bottom',
                            labels: { 
                                boxWidth: 10, 
                                font: { size: 11, family: 'Instrument Sans, sans-serif' },
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
                                    const label = context.label || '';
                                    const value = context.parsed.y || context.parsed.r || context.parsed;
                                    if (isPie) {
                                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                        const percentage = total > 0 ? ((value / total) * 100).toFixed(2) : 0;
                                        return ` ${label}: $${value.toLocaleString()} (${percentage}%)`;
                                    }
                                    return ` ${label}: $${value.toLocaleString()}`;
                                }
                            }
                        }
                    },
                    cutout: isPie ? '75%' : undefined
                }
            };

            // 長條圖/折線圖的軸設定
            if (!isPie) {
                config.options.scales = {
                    y: { 
                        beginAtZero: true, 
                        grid: { color: palette.grid },
                        ticks: {
                            color: palette.text + '90',
                            font: { size: 10, family: 'mono' },
                            callback: function(value) {
                                return '$' + value.toLocaleString();
                            }
                        }
                    },
                    x: { 
                        grid: { display: false },
                        ticks: {
                            color: palette.text + '90',
                            font: { size: 10 },
                            maxTicksLimit: 15,
                        }
                    }
                };
            }

            chartInstance = new Chart(ctx, config);
        }

        // 初始渲染
        const initialData = @json($chartData ?? null);
        if (initialData) {
            renderChart(initialData);
        }

        // 監聽 Livewire 事件
        Livewire.on('refreshChart', (data) => {
            if (data && data[0]) {
                renderChart(data[0]);
            }
        });

        // 監聽暗色模式切換
        const observer = new MutationObserver(() => {
            if (initialData) {
                renderChart(initialData);
            }
        });
        observer.observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });

        // 視窗縮放重繪
        let resizeTimer;
        window.addEventListener('resize', function() {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(() => {
                if (chartInstance) {
                    chartInstance.resize();
                }
            }, 250);
        });

    }, 100);
});
</script>
@endpush