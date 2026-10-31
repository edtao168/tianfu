{{-- resources/views/components/category-picker.blade.php --}}
{{--
    共用類別選擇器（純呈現元件）

    設計原則：
    - 不持有 Livewire 狀態，不宣告 wire:model。
    - 資料由呼叫端傳入（已依 type 過濾、已 eager load children 的 Collection）。
    - 選取與關閉行為以 method 名稱傳入，元件僅以 wire:click 觸發。

    選中視覺（方案 C）：
    - 選中卡片：淡主題色底 bg-tx-{type}/10 + 主題色邊框 border-tx-{type} + 右上角勾勾
    - 未選中卡片：bg-base-200 hover:bg-base-300 + border-transparent（預留邊框避免 layout shift）
    - 所有顏色 class 均為 colors.css 既有 token，不新增 CSS。

    使用範例（主記帳 Modal）：
        <x-category-picker
            :type="$type"
            :categories="$this->filteredCategories"
            :selected-id="$categoryId"
            pick-action="selectCategory"
            close-action="closeCategoryPicker"
        />

    使用範例（範本 Modal）：
        <x-category-picker
            :type="$templateType"
            :categories="$this->filteredCategories"
            :selected-id="$templateCategoryId"
            pick-action="selectCategory"
            close-action="closeCategoryPicker"
            empty-variant="icon"
        />
--}}

@props([
    'type' => 'expense',
    'categories' => null,
    'selectedId' => null,
    'pickAction' => 'selectCategory',
    'closeAction' => null,
    'showHeader' => true,
    'height' => 420,
    'emptyVariant' => 'emoji',
])

@php
    $themeText = match ($type) {
        'expense' => 'text-tx-expense',
        'income'  => 'text-tx-income',
        default   => 'text-tx-transfer',
    };

    $themeBg = match ($type) {
        'expense' => 'bg-tx-expense',
        'income'  => 'bg-tx-income',
        default   => 'bg-tx-transfer',
    };

    // 選中態用的三組 class（皆為 colors.css 既有 token）
    $selectedBg = match ($type) {
        'expense' => 'bg-tx-expense/10 hover:bg-tx-expense/20',
        'income'  => 'bg-tx-income/10 hover:bg-tx-income/20',
        default   => 'bg-tx-transfer/10 hover:bg-tx-transfer/20',
    };

    $selectedBorder = match ($type) {
        'expense' => 'border-tx-expense',
        'income'  => 'border-tx-income',
        default   => 'border-tx-transfer',
    };

    $title = match ($type) {
        'expense' => '支出類別',
        'income'  => '收入類別',
        default   => '轉帳類別',
    };

    // 容錯：允許呼叫端傳 null 或 array，統一轉為 Collection 以便使用 isNotEmpty()
    $items = $categories instanceof \Illuminate\Support\Collection
        ? $categories
        : collect($categories);
@endphp

<div class="w-full flex flex-col" style="height: {{ (int) $height }}px;">
    <div class="flex flex-col h-full">

        {{-- ==================== Header ==================== --}}
        @if ($showHeader)
            <div class="flex items-center justify-between px-4 py-3 border-b shrink-0">
                <div class="flex items-center gap-2">
                    <span class="w-1 h-5 rounded-full {{ $themeBg }}"></span>
                    <span class="text-sm font-bold tracking-wider {{ $themeText }}">
                        {{ $title }}
                    </span>
                </div>

                @if ($closeAction)
                    <button
                        type="button"
                        wire:click="{{ $closeAction }}"
                        class="p-1.5 rounded-lg hover:bg-base-200 transition-colors text-base-content song-interactive"
                        title="關閉"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                @endif
            </div>
        @endif

        {{-- ==================== 分類 Grid ==================== --}}
        <div class="flex-1 overflow-y-auto px-4 py-4">
            @if ($items->isNotEmpty())
                <div class="grid grid-cols-3 gap-2">
                    @foreach ($items as $category)
                        @if ($category->children->isNotEmpty())
                            {{-- 有子分類：顯示父分類標題 + 子分類 grid --}}
                            <div class="col-span-3">
                                <div class="text-[10px] mb-1.5 ml-1 font-bold uppercase tracking-wide {{ $themeText }}">
                                    {{ $category->name }}
                                </div>
                                <div class="grid grid-cols-3 gap-2">
                                    @foreach ($category->children as $child)
                                        @php $isSelected = $selectedId && (int) $selectedId === (int) $child->id; @endphp
                                        <button
                                            type="button"
                                            wire:click="{{ $pickAction }}({{ $child->id }})"
                                            class="relative p-3 rounded-xl border transition-all text-center group song-interactive
                                                {{ $isSelected
                                                    ? $selectedBg . ' ' . $selectedBorder
                                                    : 'bg-base-200 hover:bg-base-300 border-transparent' }}"
                                        >
                                            @if ($isSelected)
                                                <span class="absolute top-1.5 right-1.5 {{ $themeText }}">
                                                    <x-icon name="o-check-circle" class="w-4 h-4" />
                                                </span>
                                            @endif
                                            <div class="flex justify-center mb-1.5">
                                                <x-dynamic-component
                                                    :component="'heroicon-o-' . ($child->icon ?? 'folder')"
                                                    class="w-7 h-7 text-base-content/80 group-hover:scale-110 transition-transform"
                                                />
                                            </div>
                                            <div class="text-xs font-medium leading-tight text-base-content">
                                                {{ $child->name }}
                                            </div>
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            {{-- 無子分類：直接作為可選項目 --}}
                            @php $isSelected = $selectedId && (int) $selectedId === (int) $category->id; @endphp
                            <button
                                type="button"
                                wire:click="{{ $pickAction }}({{ $category->id }})"
                                class="relative p-3 rounded-xl border transition-all text-center group song-interactive
                                    {{ $isSelected
                                        ? $selectedBg . ' ' . $selectedBorder
                                        : 'bg-base-200 hover:bg-base-300 border-transparent' }}"
                            >
                                @if ($isSelected)
                                    <span class="absolute top-1.5 right-1.5 {{ $themeText }}">
                                        <x-icon name="o-check-circle" class="w-4 h-4" />
                                    </span>
                                @endif
                                <div class="flex justify-center mb-1.5">
                                    <x-dynamic-component
                                        :component="'heroicon-o-' . ($category->icon ?? 'folder')"
                                        class="w-7 h-7 text-base-content/80 group-hover:scale-110 transition-transform"
                                    />
                                </div>
                                <div class="text-xs font-medium leading-tight text-base-content">
                                    {{ $category->name }}
                                </div>
                            </button>
                        @endif
                    @endforeach
                </div>
            @else
                {{-- ==================== 空狀態 ==================== --}}
                <div class="flex flex-col items-center justify-center h-full py-12">
                    @if ($emptyVariant === 'icon')
                        <x-icon name="o-folder-open" class="w-12 h-12 mb-4 text-base-content/40" />
                        <p class="font-medium text-sm text-base-content/70">還沒有建立對應分類</p>
                        <p class="text-xs text-base-content/50 mt-1">請先到後台建立相符的分類</p>
                    @else
                        <div class="text-5xl mb-4">📂</div>
                        <p class="font-medium text-sm text-base-content/70">還沒有建立對應分類</p>
                        <p class="text-xs text-base-content/50 mt-1">請先到後台建立相符的分類</p>
                    @endif
                </div>
            @endif
        </div>

        {{-- ==================== Footer ==================== --}}
        <div class="px-4 py-3 border-t shrink-0">
            @if ($closeAction)
                <button
                    type="button"
                    wire:click="{{ $closeAction }}"
                    class="w-full py-2.5 rounded-xl font-medium text-sm bg-base-200 hover:bg-base-300 text-base-content song-interactive"
                >
                    取消
                </button>
            @else
                <button
                    type="button"
                    wire:click="$set('showCategoryPicker', false)"
                    class="w-full py-2.5 rounded-xl font-medium text-sm bg-base-200 hover:bg-base-300 text-base-content song-interactive"
                >
                    取消
                </button>
            @endif
        </div>
    </div>
</div>