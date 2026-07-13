{{-- resources/views/livewire/finance/partials/_amount-display.blade.php --}}

@props([
    'type' => 'expense',           // 'expense' | 'income' | 'transfer'
    'amount' => '0',
    'model' => 'amount',           // 綁定的 Livewire 模型
    'editable' => true,
    'showSign' => true,
    'class' => '',
])

<div class="col-span-4 flex items-center {{ $class }}">
    <div class="relative w-full flex items-center bg-stone-100/50 dark:bg-slate-900/60 px-3 py-2 rounded-xl border border-stone-200 dark:border-stone-700">
        @if($showSign)
            <span class="text-2xl font-bold shrink-0 mr-1
                       {{ $type === 'expense' ? 'text-rose-500 dark:text-rose-400' : 
                          ($type === 'income' ? 'text-emerald-500 dark:text-emerald-400' : 
                          'text-stone-500 dark:text-stone-400') }}">
                {{ $type === 'expense' ? '−' : ($type === 'income' ? '+' : '') }}
            </span>
        @endif
        
        @if($editable)
            <input type="number"
                   step="0.01"
                   min="0"
                   wire:model.live="{{ $model }}"
                   placeholder="0"
                   class="w-full pl-1 pr-6 font-bold bg-transparent focus:outline-none
                          text-stone-900 dark:text-stone-100 placeholder:text-stone-300 dark:placeholder:text-stone-600 text-right
                          leading-none"
                   style="font-size: 2.25rem; height: 3rem; -webkit-appearance: none; margin: 0;">
        @else
            <span class="w-full pl-1 pr-6 font-bold bg-transparent
                       text-stone-900 dark:text-stone-100 text-right text-4xl leading-none">
                {{ number_format((float) $amount, 0) }}
            </span>
        @endif
    </div>
    @error($model) 
        <span class="text-rose-600 text-xs mt-1 block">{{ $message }}</span> 
    @enderror
</div>