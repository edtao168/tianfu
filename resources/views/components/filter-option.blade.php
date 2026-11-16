{{-- resources/views/components/filter-option.blade.php --}}
{{-- report-stats專用 --}}
@props([
    'opt'      => [],          // ['value','label','active','tone'?]
    'wireSet'  => null,        // property 名稱，例如 'tab2'
    'context'  => 'desktop',   // 'desktop' | 'mobile'
])

@php
    $active = (bool) ($opt['active'] ?? false);
    $tone   = $opt['tone'] ?? null;

    // 桌機：DaisyUI join 按鈕；手機：圓角卡片
    if ($context === 'desktop') {
        $class = $active
            ? match($tone) {
                'error'   => 'btn-error',
                'success' => 'btn-success',
                default   => 'btn-neutral',
              }
            : 'btn-ghost';
        $base = 'btn btn-xs join-item song-interactive';
    } else {
        $class = $active
            ? match($tone) {
                'error'   => 'bg-rose-500/20 text-rose-800 dark:text-rose-300 border-rose-500/30',
                'success' => 'bg-emerald-500/20 text-emerald-800 dark:text-emerald-300 border-emerald-500/30',
                default   => 'bg-stone-800 text-stone-50 border-stone-800 dark:bg-stone-200 dark:text-stone-900',
              }
            : 'bg-base-100 border-base-300 text-base-content';
        $base = 'p-2.5 rounded-xl text-xs font-bold border text-center transition-all song-interactive';
    }
@endphp

<button
    {{ $attributes->merge(['class' => trim($base . ' ' . $class)]) }}
    @if($wireSet) wire:click="$set('{{ $wireSet }}', '{{ $opt['value'] }}')" @endif
>
    {{ $opt['label'] }}
</button>