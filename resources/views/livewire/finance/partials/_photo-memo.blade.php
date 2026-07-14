{{-- resources/views/livewire/finance/partials/_photo-memo.blade.php --}}

@props([
    'memoModel' => 'memo',
    'memoPlaceholder' => '請輸入備註',
    'class' => 'mb-5',
    'photoLabel' => '照片',
    'showPhoto' => true,
    'memoRows' => 3,
])

<div class="grid grid-cols-5 gap-3 {{ $class }}">
    {{-- 照片區塊（可選） --}}
    @if($showPhoto)
        <div class="col-span-2">
            <div class="aspect-square bg-stone-50 rounded-2xl border-2 border-dashed border-stone-300 
                        hover:border-stone-400 transition-all cursor-pointer 
                        flex flex-col items-center justify-center
                        dark:bg-stone-800 dark:border-stone-700 dark:hover:border-stone-600">
                <svg class="w-8 h-8 text-stone-400 dark:text-stone-500 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <span class="text-xs text-stone-500 dark:text-stone-400 font-medium">{{ $photoLabel }}</span>
            </div>
        </div>
        <div class="col-span-3">
            {{-- 仿照 amount 的外層容器結構 --}}
            <div class="relative w-full h-full min-h-[80px] bg-stone-100/50 dark:bg-slate-900/60 px-3 py-2 rounded-xl border border-stone-200 dark:border-stone-700">
                <textarea wire:model="{{ $memoModel }}"
                          placeholder="{{ $memoPlaceholder }}"
                          class="w-full h-full bg-transparent focus:outline-none
                                 text-stone-900 dark:text-stone-100 
                                 placeholder:text-stone-300 dark:placeholder:text-stone-600 
                                 font-medium resize-none text-sm"
                          style="min-height: 60px;"></textarea>
            </div>
        </div>
    @else
        <div class="col-span-5">
            {{-- 仿照 amount 的外層容器結構 --}}
            <div class="relative w-full bg-stone-100/50 dark:bg-slate-900/60 px-3 py-2 rounded-xl border border-stone-200 dark:border-stone-700">
                <textarea wire:model="{{ $memoModel }}"
                          placeholder="{{ $memoPlaceholder }}"
                          rows="{{ $memoRows }}"
                          class="w-full bg-transparent focus:outline-none
                                 text-stone-900 dark:text-stone-100 
                                 placeholder:text-stone-300 dark:placeholder:text-stone-600 
                                 font-medium resize-none text-sm"></textarea>
            </div>
        </div>
    @endif
</div>