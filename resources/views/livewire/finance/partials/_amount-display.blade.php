{{-- _amount-display.blade.php --}}

@props([
    'type' => 'expense',
    'amount' => '0',
    'model' => 'amount',
    'editable' => true,
    'showSign' => true,
    'class' => '',
])

@php
$sign = match($type) {
    'expense' => '−',
    'income' => '+',
    default => '',
};
$signColor = match($type) {
    'expense' => 'text-rose-500 dark:text-rose-400',
    'income' => 'text-emerald-500 dark:text-emerald-400',
    default => 'text-stone-500 dark:text-stone-400',
};
@endphp

<div class="col-span-4 flex items-center {{ $class }}">
    <div class="relative w-full flex items-center bg-stone-100/50 dark:bg-slate-900/60 px-3 py-2 rounded-xl border border-stone-200 dark:border-stone-700">
        
        @if($showSign)
            <span class="text-2xl font-bold shrink-0 mr-1 {{ $signColor }}">{{ $sign }}</span>
        @endif
        
        @if($editable)
            <input type="text"
                   inputmode="decimal"
                   wire:model.live.debounce.500ms="{{ $model }}"
                   x-data="{ shouldFocus: false }"
                   x-init="
                       $watch('shouldFocus', value => {
                           if (value) {
                               $nextTick(() => {
                                   $el.focus();
                                   $el.select();
                                   shouldFocus = false;
                               });
                           }
                       })
                   "
                   x-on:focus-amount-input.window="shouldFocus = true"
                   placeholder="0"
                   autocomplete="off"
                   class="w-full pl-1 pr-6 font-bold bg-transparent focus:outline-none focus:ring-2 focus:ring-sky-500/20 rounded
                          text-stone-900 dark:text-stone-100 placeholder:text-stone-300 dark:placeholder:text-stone-600 text-right
                          caret-stone-900 dark:caret-stone-100"
                   style="font-size: 2.25rem; height: 3rem; margin: 0;"
            />
        @else
            <span class="w-full pl-1 pr-6 font-bold text-stone-900 dark:text-stone-100 text-right text-4xl leading-none">
                {{ number_format((float) $amount, 0) }}
            </span>
        @endif
        
    </div>
    
    @error($model)
        <span class="text-rose-600 text-xs mt-1 block">{{ $message }}</span>
    @enderror
</div>