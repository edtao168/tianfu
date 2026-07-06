{{-- resources/views/livewire/finance/report-stats.blade.php --}}
<div class="p-4 md:p-6 max-w-7xl mx-auto space-y-6">
    
    {{-- 頂部篩選與第一層分頁 --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between border-b border-base-200 pb-4">
        {{-- 第一層分頁 --}}
        <div class="tabs tabs-boxed bg-base-200/60 p-1 self-start">
            <button class="tab tab-lg font-bold {{ $tab1 === 'category' ? 'tab-active bg-primary text-white' : '' }}" 
                    wire:click="$set('tab1', 'category')">📊 分類報表</button>
            <button class="tab tab-lg font-bold {{ $tab1 === 'trend' ? 'tab-active bg-primary text-white' : '' }}" 
                    wire:click="$set('tab1', 'trend')">📈 收支趨勢</button>
        </div>

        {{-- 年月快速篩選器 --}}
        <div class="flex items-center gap-2">
            <select wire:model.live="selectedYear" wire:change="changeDate" class="select select-bordered select-sm">
                @foreach(range(date('Y') - 3, date('Y') + 1) as $y)
                    <option value="{{ $y }}">{{ $y }} 年</option>
                @endforeach
            </select>
            @if(!($tab1 === 'category' && $tab3 === 'year') && !($tab1 === 'trend' && $tab3 === 'month'))
                <select wire:model.live="selectedMonth" wire:change="changeDate" class="select select-bordered select-sm">
                    @foreach(range(1, 12) as $m)
                        <option value="{{ $m }}">{{ $m }} 月</option>
                    @endforeach
                </select>
            @endif
        </div>
    </div>

    {{-- 第二層與第三層分頁控制區 --}}
    <div class="flex flex-wrap gap-4 items-center justify-between">
        {{-- 第二層分頁 --}}
        <div class="flex gap-1.5 p-1 bg-base-100 rounded-xl border border-base-200">
            <button class="btn btn-sm font-bold {{ $tab2 === 'expense' ? 'btn-error text-white' : 'btn-ghost text-gray-400' }}" wire:click="$set('tab2', 'expense')">支出</button>
            <button class="btn btn-sm font-bold {{ $tab2 === 'income' ? 'btn-success text-white' : 'btn-ghost text-gray-400' }}" wire:click="$set('tab2', 'income')">收入</button>
            @if($tab1 === 'trend')
                <button class="btn btn-sm font-bold {{ $tab2 === 'balance' ? 'btn-info text-white' : 'btn-ghost text-gray-400' }}" wire:click="$set('tab2', 'balance')">結餘</button>
            @endif
        </div>

        {{-- 第三層分頁 --}}
        <div class="join border border-base-200 rounded-lg overflow-hidden">
            @if($tab1 === 'category')
                <button class="join-item btn btn-xs md:btn-sm {{ $tab3 === 'year' ? 'btn-neutral' : 'btn-ghost' }}" wire:click="$set('tab3', 'year')">年{{ $tab2 === 'expense' ? '支出' : '收入' }}</button>
                <button class="join-item btn btn-xs md:btn-sm {{ $tab3 === 'month' ? 'btn-neutral' : 'btn-ghost' }}" wire:click="$set('tab3', 'month')">月{{ $tab2 === 'expense' ? '支出' : '收入' }}</button>
            @else
                <button class="join-item btn btn-xs md:btn-sm {{ $tab3 === 'month' ? 'btn-neutral' : 'btn-ghost' }}" wire:click="$set('tab3', 'month')">月{{ $tab2 === 'expense' ? '支出' : ($tab2 === 'income' ? '收入' : '結餘') }}</button>
                <button class="join-item btn btn-xs md:btn-sm {{ $tab3 === 'day' ? 'btn-neutral' : 'btn-ghost' }}" wire:click="$set('tab3', 'day')">日{{ $tab2 === 'expense' ? '支出' : ($tab2 === 'income' ? '收入' : '結餘') }}</button>
            @endif
        </div>
    </div>

    {{-- 主內容區 --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        
        {{-- 左側圖表區（佔 5 欄）--}}
        <div class="lg:col-span-5 bg-base-100 border border-base-200 p-5 rounded-2xl shadow-sm flex flex-col justify-center items-center relative min-h-[350px]">
            <div class="w-full max-w-[320px] relative" style="min-height: 300px;">
                <canvas id="financeChart" style="width: 100%; height: 100%; min-height: 300px;"></canvas>
                {{-- 圓餅圖中心總額文字 --}}
                @if($tab1 === 'category')
                    <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none mt-4">
                        <span class="text-xs text-gray-400 font-bold">總額</span>
                        <span class="text-xl font-black text-base-content font-mono" id="chartCenterTotal">${{ $this->categoryData['total'] ?? '0.00' }}</span>
                    </div>
                @endif
            </div>
        </div>

        {{-- 右側數據列表區（佔 7 欄）--}}
        <div class="lg:col-span-7 space-y-4">
            
            {{-- 分類報表數據面板 --}}
            @if($tab1 === 'category')
                <div class="bg-base-100 border border-base-200 rounded-2xl shadow-sm overflow-hidden">
                    <div class="p-4 bg-base-200/30 border-b border-base-200 font-bold text-sm tracking-wider text-gray-500">
                        📋 分類數據明細 (大類 + 金額)
                    </div>
                    
                    {{-- PC 端表格排版 --}}
                    <div class="hidden md:block overflow-x-auto">
                        <table class="table w-full text-sm">
                            <thead>
                                <tr class="bg-base-100 text-gray-400">
                                    <th>分類大類</th>
                                    <th class="text-right">總計金額</th>
                                    <th class="text-right">佔比</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($this->categoryData['list'] as $item)
                                    <tr class="hover:bg-base-200/40 transition-colors">
                                        <td class="font-bold text-base-content">{{ $item['name'] }}</td>
                                        <td class="text-right font-mono font-bold">${{ $item['amount_display'] }}</td>
                                        <td class="text-right font-mono text-gray-400">{{ $item['percentage'] }}%</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-center py-8 text-gray-400">暫無相關財務交易數據</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- 手機端卡片排版 --}}
                    <div class="block md:hidden divide-y divide-base-200">
                        @forelse($this->categoryData['list'] as $item)
                            <div class="p-4 flex justify-between items-center bg-base-100">
                                <div>
                                    <div class="font-bold text-base-content text-sm">{{ $item['name'] }}</div>
                                    <div class="text-xs text-gray-400 mt-0.5">佔比 {{ $item['percentage'] }}%</div>
                                </div>
                                <div class="font-mono font-extrabold text-md text-base-content">${{ $item['amount_display'] }}</div>
                            </div>
                        @empty
                            <div class="p-8 text-center text-sm text-gray-400">暫無相關財務交易數據</div>
                        @endforelse
                    </div>
                </div>

            {{-- 趨勢報表數據面板 --}}
            @else
                <div class="bg-base-100 border border-base-200 rounded-2xl shadow-sm overflow-hidden">
                    <div class="p-4 bg-base-200/30 border-b border-base-200 font-bold text-sm tracking-wider text-gray-500">
                        📅 週期趨勢明細 (時間 + 金額)
                    </div>
                    
                    {{-- PC 端表格排版 --}}
                    <div class="hidden md:block overflow-x-auto max-h-[400px] overflow-y-auto">
                        <table class="table table-pin-rows w-full text-sm">
                            <thead>
                                <tr class="bg-base-100 text-gray-400">
                                    <th>時間區間</th>
                                    <th class="text-right">金額</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($this->trendData as $item)
                                    <tr class="hover:bg-base-200/40 transition-colors">
                                        <td class="font-medium text-gray-600">{{ $item['label'] }}</td>
                                        <td class="text-right font-mono font-bold text-base-content">${{ $item['amount_display'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- 手機端卡片排版 --}}
                    <div class="block md:hidden divide-y divide-base-200 max-h-[400px] overflow-y-auto">
                        @foreach($this->trendData as $item)
                            <div class="p-3.5 flex justify-between items-center bg-base-100">
                                <span class="text-sm font-medium text-gray-500">{{ $item['label'] }}</span>
                                <span class="font-mono font-bold text-sm text-base-content">${{ $item['amount_display'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

        </div>
    </div>
</div>

{{-- Chart.js 核心渲染驅動 --}}
@push('scripts')
<script>
    document.addEventListener('livewire:init', function () {
        // 等待 DOM 完全加載
        setTimeout(function() {
            const canvas = document.getElementById('financeChart');
            if (!canvas) {
                console.error('Canvas element not found');
                return;
            }
            
            const ctx = canvas.getContext('2d');
            let chartInstance = null;

            function renderChart(data) {
                // 銷毀舊圖表
                if (chartInstance) {
                    chartInstance.destroy();
                    chartInstance = null;
                }

                // 確保畫布尺寸正確
                const container = canvas.parentElement;
                if (container) {
                    canvas.style.width = container.clientWidth + 'px';
                    canvas.style.height = container.clientHeight + 'px';
                }

                // 更新中央總額文字
                const centerTotalEl = document.getElementById('chartCenterTotal');
                if (centerTotalEl && data.centerText !== undefined && data.centerText !== null) {
                    centerTotalEl.textContent = '$' + data.centerText;
                }

                // 檢查是否有數據
                if (!data.labels || data.labels.length === 0 || !data.values || data.values.length === 0) {
                    // 無數據時顯示空白圖表
                    chartInstance = new Chart(ctx, {
                        type: data.type === 'pie' ? 'doughnut' : 'bar',
                        data: {
                            labels: ['無數據'],
                            datasets: [{
                                data: [1],
                                backgroundColor: ['#e5e7eb'],
                                borderWidth: 0
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { display: false },
                                tooltip: { enabled: false }
                            },
                            cutout: data.type === 'pie' ? '70%' : undefined
                        }
                    });
                    return;
                }

                // 構建圖表配置
                const isPie = data.type === 'pie';
                const config = {
                    type: isPie ? 'doughnut' : 'bar',
                    data: {
                        labels: data.labels,
                        datasets: [{
                            data: data.values,
                            backgroundColor: isPie 
                                ? ['#f87171', '#60a5fa', '#34d399', '#fbbf24', '#a78bfa', '#f472b6', '#2dd4bf', '#e2e8f0']
                                : data.color || '#60a5fa',
                            borderWidth: isPie ? 2 : 0,
                            borderRadius: isPie ? 0 : 4,
                            borderColor: isPie ? '#ffffff' : 'transparent'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: isPie,
                                position: 'bottom',
                                labels: { 
                                    boxWidth: 12, 
                                    font: { size: 11 },
                                    padding: 10
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        const label = context.label || '';
                                        const value = context.parsed;
                                        if (isPie) {
                                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                            const percentage = total > 0 ? ((value / total) * 100).toFixed(2) : 0;
                                            return ` ${label}: $${value.toLocaleString()} (${percentage}%)`;
                                        }
                                        return ` 金額: $${value.toLocaleString()}`;
                                    }
                                }
                            }
                        },
                        cutout: isPie ? '70%' : undefined
                    }
                };

                // 柱狀圖添加軸線
                if (!isPie) {
                    config.options.scales = {
                        y: { 
                            beginAtZero: true, 
                            grid: { color: '#f3f4f6' },
                            ticks: {
                                callback: function(value) {
                                    return '$' + value.toLocaleString();
                                }
                            }
                        },
                        x: { grid: { display: false } }
                    };
                }

                chartInstance = new Chart(ctx, config);
            }

            // 初始化圖表
            const initialData = @json($chartData);
            if (initialData) {
                renderChart(initialData);
            }

            // 監聽後端即時聯動重繪事件
            Livewire.on('refreshChart', (data) => {
                if (data && data[0]) {
                    renderChart(data[0]);
                }
            });

            // 監聽窗口大小變化重新繪製
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