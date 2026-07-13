{{-- resources/views/livewire/finance/partials/_date-picker.blade.php --}}

@props([
    'model' => 'recordedAt',
    'format' => 'Y/m/d',
    'class' => 'mb-4',
])

<div class="flex items-center justify-between {{ $class }} px-2 py-1 rounded-xl bg-stone-100 dark:bg-stone-800">
    <button type="button" 
            wire:click="changeDate(-1)"
            class="p-2 hover:bg-stone-200 dark:hover:bg-stone-700 rounded-full transition-colors">
        <svg class="w-5 h-5 text-stone-500 dark:text-stone-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
    </button>
    
    <span class="text-base font-bold text-stone-800 dark:text-stone-200 tracking-wider">
        {{ \Carbon\Carbon::parse($this->$model)->format($format) }}
    </span>
    
    <button type="button" 
            wire:click="changeDate(1)"
            class="p-2 hover:bg-stone-200 dark:hover:bg-stone-700 rounded-full transition-colors">
        <svg class="w-5 h-5 text-stone-500 dark:text-stone-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
    </button>
</div>