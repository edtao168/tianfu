{{-- resources/views/livewire/finance/transaction-modal.blade.php --}}

<div>
    {{-- ==================== 主要記帳 Modal ==================== --}}
    <x-modal wire:model="showTransactionModal" 
             title="{{ $transactionId ? '修改記錄' : '新增記錄' }}" 
             separator 
             persistent 
             size="lg"
             class="!max-w-md"
             x-on:click.stop>
        
        {{-- 類型選擇（含範本按鈕） --}}
        @include('livewire.finance.partials._type-buttons', [
            'model' => 'type',
            'columns' => 'grid-cols-4',
            'showTemplate' => true,
            'templateButton' => '範本',
        ])

        <x-form wire:submit="saveTransaction">
            {{-- 類別 + 金額 --}}
            <div class="grid grid-cols-6 gap-3 mb-4">
                @include('livewire.finance.partials._category-block', [
                    'type' => $type,
                    'categoryId' => $categoryId,
                    'selectedCategory' => $selectedCategory,
                    'isTemplate' => false,
                    'wireClick' => 'openCategoryPicker(false)',
                ])

                @include('livewire.finance.partials._amount-display', [
                    'type' => $type,
                    'amount' => $amount,
                    'model' => 'amount',
                    'editable' => true,
                ])
            </div>

            {{-- 日期 --}}
            @include('livewire.finance.partials._date-picker', [
                'model' => 'recordedAt',
                'class' => 'mb-4',
            ])

            {{-- 帳戶 + 餘額 --}}
            @include('livewire.finance.partials._account-selector', [
                'wireModel' => 'fromAccountId',
                'label' => '帳戶',
                'required' => true,
            ])

            {{-- 轉帳目標帳戶 --}}
            @if($type === 'transfer')
                @include('livewire.finance.partials._account-selector', [
                    'wireModel' => 'toAccountId',
                    'label' => '目標帳戶',
                    'required' => true,
                    'showBalance' => false,
                    'excludeAccountId' => $fromAccountId,
                    'placeholder' => '選擇目標帳戶',
                    'wrapperClass' => 'grid-cols-5 gap-3 mb-4',
                ])
            @endif

            {{-- 照片 + 備註（顯示照片） --}}
            @include('livewire.finance.partials._photo-memo', [
                'memoModel' => 'memo',
                'memoPlaceholder' => '請輸入備註',
                'class' => 'mb-5',
                'showPhoto' => true,
            ])

            {{-- 操作按鈕 --}}
            <x-slot:actions>
                <div class="grid grid-cols-2 gap-2 w-full pt-3 border-t border-stone-200 dark:border-stone-700">
                    <x-button label="返回" 
                        type="button"
                        :link="route('finance.transactions')"
                        class="btn-sm rounded-xl font-medium text-stone-700 bg-stone-100 hover:bg-stone-200 dark:bg-stone-800 dark:text-stone-300 dark:hover:bg-stone-700 border-none" />
                    
                    @if(!$transactionId)
                        <x-button label="再記一筆" 
                            type="button"
                            wire:click="saveAndKeepOpen"
                            class="btn-sm rounded-xl font-medium text-stone-700 bg-stone-100 hover:bg-stone-200 dark:bg-stone-800 dark:text-stone-300 dark:hover:bg-stone-700 border-none"
                            spinner="saveAndKeepOpen" />
                    @endif
                    
                    <x-button label="存為範本" 
                              type="button"
                               wire:click="openTemplateModalFromTransaction"
                              class="btn-sm rounded-xl font-medium text-sky-700 bg-sky-50 hover:bg-sky-100 dark:bg-sky-950 dark:text-sky-300 dark:hover:bg-sky-900 border-none" />

                    <x-button label="儲存" 
                              type="submit" 
                              class="btn-sm rounded-xl font-bold text-white bg-emerald-600 hover:bg-emerald-700 dark:bg-emerald-700 dark:hover:bg-emerald-600 border-none" 
                              spinner="saveTransaction" />
                </div>
            </x-slot:actions>
        </x-form>
    </x-modal>

    {{-- ==================== 共用類別選擇器 Modal ==================== --}}
    <x-modal wire:model="showCategoryPicker" 
             title="選擇類別" 
             separator 
             size="lg" 
             class="!max-w-md !z-50"
             x-on:click.stop>
        <div class="space-y-4 max-h-[60vh] overflow-y-auto">
            @php
                $filteredCats = $this->filteredCategories;
                $currentType = $isTemplateCategoryPicker ? $templateType : $type;
                $themeColor = $currentType === 'expense' ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400';
                $barColor = $currentType === 'expense' ? 'bg-rose-600' : 'bg-emerald-600';
            @endphp

            @if($filteredCats->isNotEmpty())
                <div>
                    <div class="text-xs font-bold {{ $themeColor }} mb-3 tracking-wider flex items-center gap-2">
                        <span class="w-1 h-4 {{ $barColor }} rounded-full"></span>
                        {{ $currentType === 'expense' ? '支出類別' : '收入類別' }}
                    </div>
                    <div class="grid grid-cols-3 gap-2">
                        @foreach($filteredCats as $category)
                            @if($category->children->isNotEmpty())
                                <div class="col-span-3">
                                    <div class="text-[10px] text-stone-500 dark:text-stone-400 mb-1 ml-1 font-bold">
                                        {{ $category->name }}
                                    </div>
                                    <div class="grid grid-cols-3 gap-2">
                                        @foreach($category->children as $child)
                                            <button type="button"
                                                    wire:click="selectCategory({{ $child->id }})"
                                                    class="p-3 bg-stone-50 hover:bg-stone-100 dark:bg-stone-800 dark:hover:bg-stone-700 rounded-xl transition-all text-center group">
                                                <div class="text-xs font-medium mb-1 flex justify-center">
                                                    <x-dynamic-component :component="'heroicon-o-' . ($child->icon ?? 'folder')" 
                                                            class="w-7 h-7 text-stone-700 dark:text-stone-300 group-hover:scale-110 transition-transform" />
                                                </div>
                                                <div class="text-xs font-medium text-stone-800 dark:text-stone-200">
                                                    {{ $child->name }}
                                                </div>
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            @else
                                <button type="button"
                                        wire:click="selectCategory({{ $category->id }})"
                                        class="p-3 bg-stone-50 hover:bg-stone-100 dark:bg-stone-800 dark:hover:bg-stone-700 rounded-xl transition-all text-center group">
                                    <div class="text-xs font-medium mb-1 flex justify-center">
                                        <x-dynamic-component :component="'heroicon-o-' . ($category->icon ?? 'folder')" 
                                                class="w-7 h-7 text-stone-700 dark:text-stone-300 group-hover:scale-110 transition-transform" />
                                    </div>
                                    <div class="text-xs font-medium text-stone-800 dark:text-stone-200">
                                        {{ $category->name }}
                                    </div>
                                </button>
                            @endif
                        @endforeach
                    </div>
                </div>
            @else
                <div class="text-center py-8">
                    <div class="text-4xl mb-3">📂</div>
                    <p class="text-stone-500 dark:text-stone-400 font-medium">還沒有建立對應分類</p>
                    <p class="text-xs text-stone-400 dark:text-stone-500 mt-1">請先到後台建立相符的分類</p>
                </div>
            @endif
        </div>
        <x-slot:actions>
            <x-button label="取消" 
                      @click="$wire.showCategoryPicker = false" 
                      class="btn-ghost text-stone-500 hover:text-stone-700 dark:text-stone-400 dark:hover:text-stone-200" />
        </x-slot:actions>
    </x-modal>

    {{-- ==================== 儲存範本 Modal ==================== --}}
    <x-modal wire:model="showTemplateModal" 
             title="{{ $editingTemplateId ? '編輯範本' : '儲存範本' }}" 
             separator 
             persistent
             size="lg"
             class="!max-w-md !z-40 template-modal-blue"
             x-on:click.stop>

        <x-form wire:submit="saveAsTemplate">
            {{-- 類型選擇（不含範本按鈕） --}}
            @include('livewire.finance.partials._type-buttons', [
                'model' => 'templateType',
                'columns' => 'grid-cols-3',
                'showTemplate' => false,
            ])

            {{-- 範本名稱 --}}
            <x-input label="範本名稱" 
                     wire:model="templateName" 
                     placeholder="例如：早餐、薪資" 
                     class="input-bordered border-stone-300 dark:border-stone-700
                            focus:border-stone-500 dark:focus:border-stone-500
                            bg-stone-50 dark:bg-slate-900 text-stone-800 dark:text-stone-100 mb-4" 
                     required />

            {{-- 類別 + 金額（唯讀） --}}
            <div class="grid grid-cols-6 gap-3 mb-4">
                @include('livewire.finance.partials._category-block', [
                    'type' => $templateType,
                    'categoryId' => $templateCategoryId,
                    'selectedCategory' => $selectedTemplateCategory,
                    'isTemplate' => true,
                    'wireClick' => 'openCategoryPicker(true)',
                ])

                @include('livewire.finance.partials._amount-display', [
                    'type' => $templateType,
                    'amount' => $amount,
                    'model' => 'amount',
                    'editable' => false,
                    'showSign' => true,
                ])
            </div>

            {{-- 帳戶 + 餘額 --}}
            @include('livewire.finance.partials._account-selector', [
                'wireModel' => 'templateFromAccountId',
                'label' => '帳戶',
                'required' => true,
                'balanceKey' => 'template-balance-' . $templateFromAccountId,
            ])

            {{-- 轉帳目標帳戶 --}}
            @if($templateType === 'transfer')
                @include('livewire.finance.partials._account-selector', [
                    'wireModel' => 'templateToAccountId',
                    'label' => '目標帳戶',
                    'required' => true,
                    'showBalance' => false,
                    'excludeAccountId' => $templateFromAccountId,
                    'placeholder' => '選擇目標帳戶',
                    'wrapperClass' => 'grid-cols-5 gap-3 mb-4',
                ])
            @endif

            {{-- 照片 + 備註（隱藏照片） --}}
            @include('livewire.finance.partials._photo-memo', [
                'memoModel' => 'templateMemo',
                'memoPlaceholder' => '請輸入備註',
                'class' => 'mb-4',
                'showPhoto' => false,      // ✅ 範本不顯示照片
                'memoRows' => 3,
            ])

            <x-slot:actions>
                <div class="grid grid-cols-2 gap-2 w-full pt-3 border-t border-stone-200 dark:border-stone-700">
                    <x-button label="取消" 
                              type="button"
                              @click="$wire.showTemplateModal = false; $wire.resetTemplateForm()" 
                              class="btn-sm rounded-xl font-medium text-stone-700 bg-stone-100 hover:bg-stone-200 dark:bg-stone-800 dark:text-stone-300 dark:hover:bg-stone-700 border-none" />
                    <x-button label="{{ $editingTemplateId ? '更新' : '儲存' }}" 
                              type="submit" 
                              class="btn-sm rounded-xl font-bold text-white bg-emerald-600 hover:bg-emerald-700 dark:bg-emerald-700 dark:hover:bg-emerald-600 border-none" 
                              spinner="saveAsTemplate" />
                </div>
            </x-slot:actions>
        </x-form>
    </x-modal>

    {{-- ==================== 範本列表 Modal ==================== --}}
    <x-modal wire:model="showTemplateListModal" 
             title="範本" 
             separator 
             size="lg" 
             class="!max-w-md">
        <div class="space-y-3 max-h-[60vh] overflow-y-auto">
            @if(empty($templates))
                <div class="text-center py-8">
                    <div class="text-4xl mb-3">📋</div>
                    <p class="text-stone-500 dark:text-stone-400 font-medium">還沒有建立範本</p>
                </div>
            @else
                @foreach(['expense' => '支出', 'income' => '收入', 'transfer' => '轉帳'] as $type => $label)
                    @php
                        $typeTemplates = array_filter($templates, function($t) use ($type) {
                            return $t['type'] === $type;
                        });
                    @endphp
                    @if(!empty($typeTemplates))
                        <div>
                            <div class="text-xs font-bold text-stone-500 dark:text-stone-400 mb-2 tracking-wider">
                                {{ $label }}
                            </div>
                            @foreach($typeTemplates as $template)
                                <div class="flex items-center justify-between p-3 
                                            bg-stone-50 hover:bg-stone-100 dark:bg-stone-800 dark:hover:bg-stone-700
                                            rounded-xl transition-colors mb-1.5">
                                    <div class="flex-1 cursor-pointer" wire:click="applyTemplate({{ $template['id'] }})">
                                        <div class="font-bold text-sm text-stone-800 dark:text-stone-200">
                                            {{ $template['name'] }}
                                        </div>
                                        <div class="text-xs font-medium text-stone-500 dark:text-stone-400">
                                            ${{ number_format($template['amount'], 0) }}
                                        </div>
                                    </div>
                                    <div class="flex gap-1">
                                        <button type="button"
                                                wire:click="editTemplate({{ $template['id'] }})"
                                                class="btn btn-ghost btn-xs text-stone-500 hover:text-stone-700 dark:text-stone-400 dark:hover:text-stone-200">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                            </svg>
                                        </button>
                                        <button type="button"
                                                wire:click="deleteTemplate({{ $template['id'] }})"
                                                wire:confirm="確定刪除？"
                                                class="btn btn-ghost btn-xs text-rose-500 hover:text-rose-700 dark:text-rose-400 dark:hover:text-rose-300">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                @endforeach
            @endif
        </div>
        <x-slot:actions>
            <x-button label="關閉" 
                      @click="$wire.showTemplateListModal = false" 
                      class="btn-ghost text-stone-500 hover:text-stone-700 dark:text-stone-400 dark:hover:text-stone-200" />
            <x-button label="新增範本" 
					  icon="o-plus"
					  wire:click="openTemplateModalFromList"
					  class="btn-primary bg-sky-600 hover:bg-sky-700 text-white border-none shadow-md" />
		</x-slot:actions>
    </x-modal>
</div>