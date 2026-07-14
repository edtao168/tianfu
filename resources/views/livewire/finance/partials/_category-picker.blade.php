{{-- resources/views/livewire/finance/partials/_category-picker-content.blade.php --}}

<div class="flex flex-col h-full">
    {{-- 頂部標題列 + 關閉按鈕 --}}
    <div class="flex items-center justify-between px-4 py-3 border-b border-stone-200 dark:border-stone-700 shrink-0">
        <div class="flex items-center gap-2">
            @php
                $currentType = $isTemplateCategoryPicker ? $templateType : $type;
                $themeColor = $currentType === 'expense' ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400';
                $barColor = $currentType === 'expense' ? 'bg-rose-600' : 'bg-emerald-600';
            @endphp
            <span class="w-1 h-5 {{ $barColor }} rounded-full"></span>
            <span class="text-sm font-bold {{ $themeColor }} tracking-wider">
                {{ $currentType === 'expense' ? '支出類別' : '收入類別' }}
            </span>
        </div>
        
        {{-- X 關閉按鈕 --}}
        <button type="button" 
                wire:click="$set('showCategoryPicker', false)"
                class="p-1.5 rounded-lg hover:bg-stone-100 dark:hover:bg-stone-700 text-stone-400 hover:text-stone-600 dark:text-stone-500 dark:hover:text-stone-300 transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>

    {{-- 類別內容區：撐滿剩餘高度，可滾動 --}}
    <div class="flex-1 overflow-y-auto px-4 py-4">
        @php
            $filteredCats = $this->filteredCategories;
        @endphp

        @if($filteredCats->isNotEmpty())
            <div class="grid grid-cols-3 gap-2">
                @foreach($filteredCats as $category)
                    @if($category->children->isNotEmpty())
                        <div class="col-span-3">
                            <div class="text-[10px] text-stone-500 dark:text-stone-400 mb-1.5 ml-1 font-bold uppercase tracking-wide">
                                {{ $category->name }}
                            </div>
                            <div class="grid grid-cols-3 gap-2">
                                @foreach($category->children as $child)
                                    <button type="button"
                                            wire:click="selectCategory({{ $child->id }})"
                                            class="p-3 bg-stone-50 hover:bg-stone-100 dark:bg-stone-800 dark:hover:bg-stone-700 rounded-xl transition-all text-center group active:scale-95">
                                        <div class="flex justify-center mb-1.5">
                                            <x-dynamic-component :component="'heroicon-o-' . ($child->icon ?? 'folder')" 
                                                    class="w-7 h-7 text-stone-600 dark:text-stone-300 group-hover:scale-110 transition-transform" />
                                        </div>
                                        <div class="text-xs font-medium text-stone-800 dark:text-stone-200 leading-tight">
                                            {{ $child->name }}
                                        </div>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <button type="button"
                                wire:click="selectCategory({{ $category->id }})"
                                class="p-3 bg-stone-50 hover:bg-stone-100 dark:bg-stone-800 dark:hover:bg-stone-700 rounded-xl transition-all text-center group active:scale-95">
                            <div class="flex justify-center mb-1.5">
                                <x-dynamic-component :component="'heroicon-o-' . ($category->icon ?? 'folder')" 
                                        class="w-7 h-7 text-stone-600 dark:text-stone-300 group-hover:scale-110 transition-transform" />
                            </div>
                            <div class="text-xs font-medium text-stone-800 dark:text-stone-200 leading-tight">
                                {{ $category->name }}
                            </div>
                        </button>
                    @endif
                @endforeach
            </div>
        @else
            <div class="flex flex-col items-center justify-center h-full py-12">
                <div class="text-5xl mb-4">📂</div>
                <p class="text-stone-500 dark:text-stone-400 font-medium text-sm">還沒有建立對應分類</p>
                <p class="text-xs text-stone-400 dark:text-stone-500 mt-1">請先到後台建立相符的分類</p>
            </div>
        @endif
    </div>

    {{-- 底部取消按鈕 --}}
    <div class="px-4 py-3 border-t border-stone-200 dark:border-stone-700 shrink-0">
        <button type="button"
                wire:click="$set('showCategoryPicker', false)"
                class="w-full py-2.5 rounded-xl font-medium text-stone-700 bg-stone-100 hover:bg-stone-200 dark:bg-stone-800 dark:text-stone-300 dark:hover:bg-stone-700 transition-colors text-sm">
            取消
        </button>
    </div>
</div>