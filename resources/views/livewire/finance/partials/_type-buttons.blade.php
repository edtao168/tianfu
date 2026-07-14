{{-- resources/views/livewire/finance/partials/_type-buttons.blade.php --}}

@props([
    'model' => 'type',
    'types' => ['expense' => '支出', 'income' => '收入', 'transfer' => '轉帳'],
    'columns' => 'grid-cols-3',
    'showTemplate' => false,
    'templateButton' => '📋 範本',
])

<div class="grid {{ $columns }} gap-1.5 mb-5">
    @if($showTemplate)
        <button type="button"
                wire:click="openTemplateList"
                class="py-2.5 text-sm font-bold rounded-xl transition-all duration-200 
                       bg-stone-100 text-stone-700 hover:bg-stone-200 
                       dark:bg-stone-800 dark:text-stone-100 dark:hover:bg-stone-700">
            <span class="mr-1">📋</span> {{ $templateButton }}
        </button>
    @endif
    
    @foreach($types as $value => $label)
        @php
            $colors = [
                'expense' => ['bg' => 'bg-rose-600', 'shadow' => 'shadow-rose-900/20', 'text' => 'text-rose-600'],
                'income' => ['bg' => 'bg-emerald-600', 'shadow' => 'shadow-emerald-900/20', 'text' => 'text-emerald-600'],
                'transfer' => ['bg' => 'bg-sky-600', 'shadow' => 'shadow-sky-900/20', 'text' => 'text-sky-600']
            ];
            $currentType = $this->$model ?? 'expense';
        @endphp
        <button type="button"
                wire:click="$set('{{ $model }}', '{{ $value }}')"
                class="py-2.5 text-sm font-bold rounded-xl transition-all duration-200
                    {{ $currentType === $value 
                        ? $colors[$value]['bg'] . ' text-white shadow-md ' . $colors[$value]['shadow']
                        : 'bg-stone-50 text-stone-600 hover:bg-stone-100 dark:bg-stone-800 dark:text-stone-400 dark:hover:bg-stone-700' }}">
            {{ $label }}
        </button>
    @endforeach
</div>