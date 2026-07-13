{{-- resources/views/livewire/finance/partials/_category-block.blade.php --}}

@props([
    'type' => 'expense',           // 'expense' | 'income' | 'transfer'
    'categoryId' => null,
    'selectedCategory' => null,
    'isTemplate' => false,
    'showTransferIcon' => true,
    'wireClick' => 'openCategoryPicker(false)',
])

<div class="col-span-2">
    @if($type !== 'transfer')
        <div class="aspect-square bg-stone-50 rounded-2xl border border-stone-300
                    hover:border-stone-400 transition-all cursor-pointer 
                    flex flex-col items-center justify-center p-1 relative overflow-hidden
                    dark:bg-stone-800 dark:border-stone-700"
                 wire:click="{{ $wireClick }}">
            @if($categoryId && $selectedCategory && $selectedCategory->type === $type)
                <div class="text-3xl mb-1 flex justify-center">
                    <x-dynamic-component :component="'heroicon-o-' . ($selectedCategory->icon ?? 'folder')" 
                            class="w-10 h-10 text-stone-700 dark:text-stone-200" />
                </div>
                <div class="text-xs font-bold text-stone-800 dark:text-stone-200 text-center leading-tight">
                    {{ $selectedCategory->name }}
                </div>
                @if($selectedCategory->parent)
                    <div class="text-[10px] text-stone-500 dark:text-stone-400 mt-0.5">
                        {{ $selectedCategory->parent->name }}
                    </div>
                @endif
            @else
                <div class="text-3xl mb-1 flex justify-center">
                    <x-icon name="o-folder" class="w-8 h-8 text-stone-400 dark:text-stone-500" />
                </div>
                <div class="text-xs font-bold text-stone-400 dark:text-stone-500">類別</div>
            @endif
        </div>
    @else
        @if($showTransferIcon)
            <div class="aspect-square bg-teal-50 rounded-2xl border border-teal-200 
                        flex flex-col items-center justify-center
                        dark:bg-teal-950 dark:border-teal-800">
                <div class="text-3xl mb-1 flex justify-center">
                    <x-icon name="o-arrows-right-left" class="w-10 h-10 text-teal-600 dark:text-teal-400" />
                </div>
                <div class="text-xs font-bold text-teal-700 dark:text-teal-400">轉帳</div>
            </div>
        @endif
    @endif
</div>