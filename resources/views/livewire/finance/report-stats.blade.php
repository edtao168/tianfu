<!-- filepath: resources/views/livewire/finance/report-stats.blade.php -->
<div class="p-4 md:p-6 max-w-7xl mx-auto space-y-6">
    
    {{-- 頂部篩選與第一層分頁 --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between border-b border-[var(--song-border)] pb-4">
        <div class="inline-flex p-1 rounded-xl bg-[var(--song-card-bg)] border border-[var(--song-border)] backdrop-blur-sm self-start">
            <button class="px-4 py-2 rounded-lg text-sm font-bold transition-all duration-200 {{ $tab1 === 'category' ? 'bg-[rgba(162,189,219,0.25)] text-[var(--song-text)] shadow-sm' : 'text-[var(--song-text)]/60 hover:text-[var(--song-text)]' }}" 
                    wire:click="$set('tab1', 'category')">📊 分類報表</button>
            <button class="px-4 py-2 rounded-lg text-sm font-bold transition-all duration-200 {{ $tab1 === 'trend' ? 'bg-[rgba(162,189,219,0.25)] text-[var(--song-text)] shadow-sm' : 'text-[var(--song-text)]/60 hover:text-[var(--song-text)]' }}" 
                    wire:click="$set('tab1', 'trend')">📈 收支趨勢</button>
        </div>

        <div class="flex items-center gap-2">
            <select wire:model.live="selectedYear" wire:change="changeDate" 
                    class="select select-sm pl-3 pr-10 bg-[var(--song-bg)] border-[var(--song-border)] text-[var(--song-text)] focus:border-[#a2bddb] focus:ring-1 focus:ring-[#a2bddb] rounded-lg opacity-100 font-medium">
                @foreach(range(date('Y') - 3, date('Y') + 1) as $y)
                    <option value="{{ $y }}" class="bg-[var(--song-bg)] text-[var(--song-text)]">{{ $y }} 年</option>
                @endforeach
            </select>
            @if(!($tab1 === 'category' && $tab3 === 'year') && !($tab1 === 'trend' && $tab3 === 'month'))
                <select wire:model.live="selectedMonth" wire:change="changeDate" 
                        class="select select-sm pl-3 pr-10 bg-[var(--song-bg)] border-[var(--song-border)] text-[var(--song-text)] focus:border-[#a2bddb] focus:ring-1 focus:ring-[#a2bddb] rounded-lg opacity-100 font-medium">
                    @foreach(range(1, 12) as $m)
                        <option value="{{ $m }}" class="bg-[var(--song-bg)] text-[var(--song-text)]">{{ $m }} 月</option>
                    @endforeach
                </select>
            @endif
        </div>
    </div>

    {{-- 第二層與第三層分頁控制區 --}}
    <div class="flex flex-wrap gap-4 items-center justify-between">
        <div class="flex gap-1.5 p-1 bg-[var(--song-card-bg)] rounded-xl border border-[var(--song-border)] backdrop-blur-sm">
            <button class="btn btn-xs md:btn-sm font-bold rounded-lg border-0 transition-all duration-200 {{ $tab2 === 'expense' ? 'bg-[#b57a7a]/20 text-[#b57a7a] hover:bg-[#b57a7a]/30' : 'btn-ghost text-[var(--song-text)]/50' }}" wire:click="$set('tab2', 'expense')">支出</button>
            <button class="btn btn-xs md:btn-sm font-bold rounded-lg border-0 transition-all duration-200 {{ $tab2 === 'income' ? 'bg-[#5b8c7a]/20 text-[#5b8c7a] hover:bg-[#5b8c7a]/30' : 'btn-ghost text-[var(--song-text)]/50' }}" wire:click="$set('tab2', 'income')">收入</button>
            @if($tab1 === 'trend')
                <button class="btn btn-xs md:btn-sm font-bold rounded-lg border-0 transition-all duration-200 {{ $tab2 === 'balance' ? 'bg-[rgba(162,189,219,0.25)] text-[#5a6a7a] hover:bg-[rgba(162,189,219,0.35)]' : 'btn-ghost text-[var(--song-text)]/50' }}" wire:click="$set('tab2', 'balance')">結餘</button>
            @endif
        </div>

        <div class="join border border-[var(--song-border)] rounded-lg overflow-hidden bg-[var(--song-card-bg)] backdrop-blur-sm">
            @if($tab1 === 'category')
                <button class="join-item btn btn-xs md:btn-sm border-0 rounded-none {{ $tab3 === 'year' ? 'bg-[rgba(162,189,219,0.15)] text-[var(--song-text)]' : 'btn-ghost text-[var(--song-text)]/50' }}" wire:click="$set('tab3', 'year')">年{{ $tab2 === 'expense' ? '支出' : '收入' }}</button>
                <button class="join-item btn btn-xs md:btn-sm border-0 rounded-none {{ $tab3 === 'month' ? 'bg-[rgba(162,189,219,0.15)] text-[var(--song-text)]' : 'btn-ghost text-[var(--song-text)]/50' }}" wire:click="$set('tab3', 'month')">月{{ $tab2 === 'expense' ? '支出' : '收入' }}</button>
            @else
                <button class="join-item btn btn-xs md:btn-sm border-0 rounded-none {{ $tab3 === 'month' ? 'bg-[rgba(162,189,219,0.15)] text-[var(--song-text)]' : 'btn-ghost text-[var(--song-text)]/50' }}" wire:click="$set('tab3', 'month')">月{{ $tab2 === 'expense' ? '支出' : ($tab2 === 'income' ? '收入' : '結餘') }}</button>
                <button class="join-item btn btn-xs md:btn-sm border-0 rounded-none {{ $tab3 === 'day' ? 'bg-[rgba(162,189,219,0.15)] text-[var(--song-text)]' : 'btn-ghost text-[var(--song-text)]/50' }}" wire:click="$set('tab3', 'day')">日{{ $tab2 === 'expense' ? '支出' : ($tab2 === 'income' ? '收入' : '結餘') }}</button>
            @endif
        </div>
    </div>

    {{-- 主內容區 --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        
        {{-- 左側圖表區 --}}
        <div class="lg:col-span-5 bg-[var(--song-card-bg)] border border-[var(--song-border)] p-5 rounded-2xl backdrop-blur-md shadow-[0_8px_32px_rgba(0,0,0,0.03)] flex flex-col justify-center items-center relative min-h-[350px] transition-all duration-300">
            <div class="w-full max-w-[320px] relative" style="min-height: 300px;">
                <canvas id="financeChart" style="width: 100%; height: 100%; min-height: 300px;"></canvas>
                @if($tab1 === 'category')
                    <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none mt-4">
                        <span class="text-xs text-[var(--song-text)]/50 font-bold tracking-widest">總額</span>
                        <span class="text-lg font-black text-[var(--song-text)] font-mono" id="chartCenterTotal">${{ $this->categoryData['total'] ?? '0.00' }}</span>
                    </div>
                @endif
            </div>
        </div>

        {{-- 右側數據列表區 --}}
        <div class="lg:col-span-7 space-y-4">
            
            @if($tab1 === 'category')
                <div class="bg-[var(--song-card-bg)] border border-[var(--song-border)] rounded-2xl backdrop-blur-md shadow-[0_8px_32px_rgba(0,0,0,0.03)] overflow-hidden transition-all duration-300">
                    <div class="p-4 bg-[rgba(162,189,219,0.1)] border-b border-[var(--song-border)] font-bold text-xs tracking-widest text-[var(--song-text)]/70 flex justify-between items-center">
                        <span>📋 分類數據明細 (點擊大類查看明細流水)</span>
                    </div>
                    
                    {{-- PC 端表格排版 (點擊觸發 Modal) --}}
                    <div class="hidden md:block overflow-x-auto">
                        <table class="table w-full text-sm">
                            <thead>
                                <tr class="border-b border-[var(--song-border)] text-[var(--song-text)]/40">
                                    <th class="bg-transparent font-bold">分類大類</th>
                                    <th class="bg-transparent text-right font-bold">總計金額</th>
                                    <th class="bg-transparent text-right font-bold">佔比</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[var(--song-border)]/50">
                                @forelse($this->categoryData['list'] as $item)
                                    <tr class="hover:bg-[rgba(162,189,219,0.15)] cursor-pointer transition-colors border-0"
                                        wire:click="openCategoryTransactions({{ $item['id'] }}, '{{ addslashes($item['name']) }}')">
                                        <td class="font-bold text-[var(--song-text)] bg-transparent flex items-center gap-2">
                                            <span>{{ $item['name'] }}</span>
                                            <x-heroicon-m-chevron-right class="w-4 h-4 text-stone-400 opacity-60" />
                                        </td>
                                        <td class="text-right font-mono font-bold text-[var(--song-text)] bg-transparent">${{ $item['amount_display'] }}</td>
                                        <td class="text-right font-mono text-[var(--song-text)]/50 bg-transparent">{{ $item['percentage'] }}%</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-center py-8 text-[var(--song-text)]/40 bg-transparent">暫無相關財務交易數據</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- 手機端卡片排版 (點擊觸發 Modal) --}}
                    <div class="block md:hidden divide-y divide-[var(--song-border)]/50">
                        @forelse($this->categoryData['list'] as $item)
                            <div class="p-4 flex justify-between items-center bg-transparent hover:bg-[rgba(162,189,219,0.1)] active:bg-[rgba(162,189,219,0.2)] cursor-pointer transition-all"
                                 wire:click="openCategoryTransactions({{ $item['id'] }}, '{{ addslashes($item['name']) }}')">
                                <div>
                                    <div class="font-bold text-[var(--song-text)] text-sm flex items-center gap-1">
                                        {{ $item['name'] }}
                                        <x-heroicon-m-chevron-right class="w-3.5 h-3.5 text-stone-400 opacity-60" />
                                    </div>
                                    <div class="text-xs text-[var(--song-text)]/50 mt-0.5">佔比 {{ $item['percentage'] }}%</div>
                                </div>
                                <div class="font-mono font-extrabold text-md text-[var(--song-text)]">${{ $item['amount_display'] }}</div>
                            </div>
                        @empty
                            <div class="p-8 text-center text-sm text-[var(--song-text)]/40">暫無相關財務交易數據</div>
                        @endforelse
                    </div>
                </div>

            @else
                <div class="bg-[var(--song-card-bg)] border border-[var(--song-border)] rounded-2xl backdrop-blur-md shadow-[0_8px_32px_rgba(0,0,0,0.03)] overflow-hidden transition-all duration-300">
                    <div class="p-4 bg-[rgba(162,189,219,0.1)] border-b border-[var(--song-border)] font-bold text-xs tracking-widest text-[var(--song-text)]/70">
                        📅 週期趨勢明細 (時間 + 金額)
                    </div>
                    
                    <div class="hidden md:block overflow-x-auto max-h-[400px] overflow-y-auto">
                        <table class="table table-pin-rows w-full text-sm">
                            <thead>
                                <tr class="border-b border-[var(--song-border)] text-[var(--song-text)]/40">
                                    <th class="bg-[var(--song-card-bg)] font-bold">時間區間</th>
                                    <th class="bg-[var(--song-card-bg)] text-right font-bold">金額</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[var(--song-border)]/50">
                                @foreach($this->trendData as $item)
                                    <tr class="hover:bg-[rgba(162,189,219,0.05)] transition-colors border-0">
                                        <td class="font-medium text-[var(--song-text)]/70 bg-transparent">{{ $item['label'] }}</td>
                                        <td class="text-right font-mono font-bold text-[var(--song-text)] bg-transparent">${{ $item['amount_display'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="block md:hidden divide-y divide-[var(--song-border)]/50 max-h-[400px] overflow-y-auto">
                        @foreach($this->trendData as $item)
                            <div class="p-3.5 flex justify-between items-center bg-transparent">
                                <span class="text-sm font-medium text-[var(--song-text)]/70">{{ $item['label'] }}</span>
                                <span class="font-mono font-bold text-sm text-[var(--song-text)]">${{ $item['amount_display'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

        </div>
    </div>

    {{-- 引用交易流水 Modal --}}
    <livewire:finance.transaction-list-modal />
</div>

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
                primary: '#a2bddb'
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
                    canvas.removeAttribute('style');
					canvas.style.width = '100%';
					canvas.style.height = '100%';
                }

                const centerTotalEl = document.getElementById('chartCenterTotal');
                if (centerTotalEl && data.centerText !== undefined && data.centerText !== null) {
                    centerTotalEl.textContent = '$' + data.centerText;
                }

                const palette = getSongPalette();

                if (!data.labels || data.labels.length === 0 || !data.values || data.values.length === 0) {
                    chartInstance = new Chart(ctx, {
                        type: data.type === 'pie' ? 'doughnut' : 'bar',
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
                            plugins: {
                                legend: { display: false },
                                tooltip: { enabled: false }
                            },
                            cutout: data.type === 'pie' ? '75%' : undefined
                        }
                    });
                    return;
                }

                const isPie = data.type === 'pie';
                const config = {
                    type: isPie ? 'doughnut' : 'bar',
                    data: {
                        labels: data.labels,
                        datasets: [{
                            data: data.values,
                            backgroundColor: isPie ? palette.pies : palette.primary,
                            borderWidth: isPie ? 1.5 : 0,
                            borderRadius: isPie ? 0 : 4,
                            borderColor: isPie ? (document.documentElement.getAttribute('data-theme') === 'dark' ? '#141212' : '#FAF9F6') : 'transparent'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,  // 改為 true，防止半圓拉長變形
						aspectRatio: isPie ? 1 : 1.5, // 圓餅圖強制 1:1 正圓，長條圖 1.5:1
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
                        cutout: isPie ? '75%' : undefined
                    }
                };

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
                                font: { size: 10 }
                            }
                        }
                    };
                }

                chartInstance = new Chart(ctx, config);
            }

            const initialData = @json($chartData);
            if (initialData) {
                renderChart(initialData);
            }

            Livewire.on('refreshChart', (data) => {
                if (data && data[0]) {
                    renderChart(data[0]);
                }
            });

            const observer = new MutationObserver(() => {
                if (initialData) {
                    renderChart(initialData);
                }
            });
            observer.observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });

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