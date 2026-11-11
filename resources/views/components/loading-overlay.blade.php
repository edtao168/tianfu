{{-- resources/views/components/loading-overlay.blade.php --}}
@props([
    'target',           // 必填：wire:target 的值，例如 showPeriodDetail('today')
    'text'    => null,  // 可選：顯示文字（總資產卡片那種）
    'size'    => 'lg',  // sm | md | lg
    'color'   => 'text-base-content/80',
    'blur'    => true,  // 是否 backdrop-blur
])

<template x-teleport="body">
    <div wire:loading.flex
         wire:target="{{ $target }}"
         class="fixed inset-0 z-[999] items-center justify-center bg-base-100/40 {{ $blur ? 'backdrop-blur-[1px]' : '' }}">
        @if($text)
            <div class="flex flex-col items-center gap-3 text-base-content">
                <span class="loading loading-spinner loading-{{ $size }} {{ $color }}"></span>
                <span class="text-sm font-medium tracking-wide opacity-80">{{ $text }}</span>
            </div>
        @else
            <span class="loading loading-spinner loading-{{ $size }} {{ $color }}"></span>
        @endif
    </div>
</template>