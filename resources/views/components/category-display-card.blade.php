{{-- resources/views/components/category-display-card.blade.php --}}
{{--
    已選類別顯示卡片（純呈現元件）

    用途：
    - 顯示「目前選了哪個類別」，點擊時觸發呼叫端指定的 Livewire method（通常是開啟選擇器）。
    - 不查 DB、不持有狀態。category model 由呼叫端傳入。

    variant：
    - square：正方形大卡片（TransactionModal 用）
    - row：橫向小卡片（recurring-index 用）

    使用範例（TransactionModal）：
        <x-category-display-card
            variant="square"
            :category="$selectedCategory"
            :type="$type"
            click-action="openCategoryPicker(false)"
        />

    使用範例（recurring-index）：
        <x-category-display-card
            variant="row"
            :category="$this->selectedCategory"
            :type="$type"
            click-action="openCategoryPicker"
            placeholder="請選擇分類"
        />
--}}

@props([
    'category' => null,
    'type' => 'expense',
    'clickAction' => null,
    'placeholder' => '類別',
    'variant' => 'square',
])

@php
    $signColor = match ($type) {
        'expense' => 'text-tx-expense',
        'income'  => 'text-tx-income',
        'transfer'=> 'text-tx-transfer',
        default   => 'text-base-content',
    };

    // 只有當 category 存在、且 type 與目前表單一致時才顯示已選內容，
    // 避免切換 type 後短暫顯示舊 type 的類別。
    $hasSelected = $category && (!isset($category->type) || $category->type === $type);
@endphp

@if ($variant === 'row')
    {{-- ==================== 橫向卡片（recurring-index） ==================== --}}
    <div
        class="clickable-card w-full rounded-xl border border-base-300 transition-all cursor-pointer flex items-center gap-3 p-3 bg-base-200 hover:bg-base-300"
        @if ($clickAction) wire:click="{{ $clickAction }}" @endif
    >
        @if ($hasSelected)
            <x-dynamic-component
                :component="'heroicon-o-' . ($category->icon ?? 'folder')"
                class="w-6 h-6 shrink-0 {{ $signColor }}"
            />
            <div class="min-w-0 flex-1">
                <div class="text-sm font-bold truncate {{ $signColor }}">{{ $category->name }}</div>
                @if ($category->parent)
                    <div class="text-[10px] text-base-content/60 truncate">{{ $category->parent->name }}</div>
                @endif
            </div>
        @else
            <x-icon name="o-folder" class="w-6 h-6 shrink-0 text-base-content/40" />
            <div class="text-sm font-bold text-base-content/60 flex-1">{{ $placeholder }}</div>
        @endif
        <x-icon name="o-chevron-right" class="w-4 h-4 shrink-0 text-base-content/40" />
    </div>
@else
    {{-- ==================== 正方形卡片（TransactionModal） ==================== --}}
    <div
        class="clickable-card aspect-square rounded-2xl border border-base-300 transition-all cursor-pointer flex flex-col items-center justify-center p-1 relative overflow-hidden bg-base-200 hover:bg-base-300"
        @if ($clickAction) wire:click="{{ $clickAction }}" @endif
    >
        @if ($hasSelected)
            <div class="text-3xl mb-1 flex justify-center">
                <x-dynamic-component
                    :component="'heroicon-o-' . ($category->icon ?? 'folder')"
                    class="w-10 h-10 {{ $signColor }}"
                />
            </div>
            <div class="text-xs font-bold text-center leading-tight {{ $signColor }}">
                {{ $category->name }}
            </div>
            @if ($category->parent)
                <div class="text-[10px] text-base-content/60 mt-0.5">{{ $category->parent->name }}</div>
            @endif
        @else
            <div class="text-3xl mb-1 flex justify-center">
                <x-icon name="o-folder" class="w-8 h-8 text-base-content/40" />
            </div>
            <div class="text-xs font-bold text-base-content/60">{{ $placeholder }}</div>
        @endif
    </div>
@endif