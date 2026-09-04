<!-- resources/views/components/date-nav.blade.php -->
@props([
    'model' => 'currentDate',
    'type' => 'month', // month | year | day
    'display' => '',
    'isCurrent' => false,
    'disableNext' => false,
])

<div class="w-full">
    <div class="flex items-center justify-between gap-2 p-1.5 bg-base-100 border border-base-200 rounded-2xl shadow-sm" x-data="{ openYearPicker: false }">
        
        {{-- 上一頁 --}}
        <button 
            type="button" 
            wire:click="previousDate" 
            class="btn btn-ghost btn-sm btn-square rounded-xl hover:bg-base-200/70 text-base-content/70 hover:text-base-content transition-all flex-shrink-0"
            title="上一頁"
        >
            <x-heroicon-o-chevron-left class="w-5 h-5" />
        </button>

        {{-- 中間區域 --}}
        <div class="flex items-center justify-center gap-1.5 flex-1 min-w-0">
            
            @if($type === 'year')
                {{-- 年份選擇器 --}}
                <div class="relative flex items-center">
                    <button 
                        type="button"
                        @click="openYearPicker = !openYearPicker"
                        class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-base-200/60 hover:bg-base-200 border border-base-200/50 cursor-pointer transition-all group"
                    >
                        <x-heroicon-o-calendar-days class="w-4 h-4 text-primary/70 flex-shrink-0" />
                        <span class="font-mono font-bold text-sm sm:text-base text-base-content">
                            {{ $display }}
                        </span>
                        <x-heroicon-o-chevron-down class="w-3.5 h-3.5 text-base-content/40 group-hover:text-base-content/70 transition-colors" />
                    </button>

                    {{-- 年份快選選單 --}}
                    <div 
                        x-show="openYearPicker" 
                        @click.outside="openYearPicker = false"
                        x-transition:enter.duration.200ms
                        x-transition:leave.duration.150ms
                        class="absolute top-full left-1/2 -translate-x-1/2 mt-2 z-50 w-56 p-2 bg-base-100 border border-base-200 rounded-xl shadow-lg"
                        style="display: none;"
                    >
                        <div class="grid grid-cols-3 gap-1">
                            @php
                                $currentYear = (int) date('Y');
                                $startYear = $currentYear - 5;
                                $endYear = $currentYear + 6;
                            @endphp
                            @for($y = $endYear; $y >= $startYear; $y--)
                                <button 
                                    type="button"
                                    wire:click="$set('{{ $model }}', '{{ $y }}')"
                                    @click="openYearPicker = false"
                                    class="px-2 py-1.5 text-xs font-mono font-bold rounded-lg hover:bg-primary/10 hover:text-primary transition-colors text-base-content/80"
                                >
                                    {{ $y }}
                                </button>
                            @endfor
                        </div>
                    </div>
                </div>

                {{-- 「年」標籤 --}}
                <!-- span class="text-sm font-bold text-base-content/60 select-none">年</span -->

            @else
                {{-- 月/日選擇器 --}}
                <div 
                    @click="$refs.dateInput.showPicker ? $refs.dateInput.showPicker() : $refs.dateInput.focus()"
                    class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-base-200/60 hover:bg-base-200 border border-base-200/50 cursor-pointer transition-all group"
                >
                    <x-heroicon-o-calendar-days class="w-4 h-4 text-primary/70 flex-shrink-0" />
                    <span class="font-mono font-bold text-sm sm:text-base text-base-content">
                        {{ $display }}
                    </span>
                    <x-heroicon-o-chevron-down class="w-3.5 h-3.5 text-base-content/40 group-hover:text-base-content/70 transition-colors" />
                    
                    <input 
                        x-ref="dateInput"
                        type="{{ $type === 'month' ? 'month' : 'date' }}" 
                        wire:model.live="{{ $model }}"
                        class="sr-only"
                    />
                </div>

                {{-- 顯示類型標籤 --}}
                <!-- span class="text-sm font-bold text-base-content/60 select-none">
                    {{ $type === 'month' ? '月' : '日' }}
                </span -->
            @endif

            {{-- 重置按鈕 --}}
            @if(!$isCurrent)
                <button 
                    type="button" 
                    wire:click="gotoCurrentDate" 
                    class="btn btn-xs btn-ghost rounded-lg text-xs font-semibold gap-1 hover:bg-primary/10 hover:text-primary transition-all"
                >
                    <x-heroicon-o-arrow-path class="w-3 h-3" />
                    <span class="hidden sm:inline">{{ $type === 'year' ? '今年' : '本月' }}</span>
                </button>
            @endif
        </div>

        {{-- 下一頁 --}}
        <button 
            type="button" 
            wire:click="nextDate" 
            @if($disableNext) disabled @endif
            class="btn btn-ghost btn-sm btn-square rounded-xl hover:bg-base-200/70 text-base-content/70 hover:text-base-content disabled:opacity-30 disabled:bg-transparent transition-all flex-shrink-0"
            title="下一頁"
        >
            <x-heroicon-o-chevron-right class="w-5 h-5" />
        </button>

    </div>
</div>