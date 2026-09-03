<!-- resources/views/livewire/transaction-modal.blade.php -->
<div>
    {{-- ==================== 主要記帳 Modal ==================== --}}
    <x-modal wire:model="showTransactionModal" title="{{ $transactionId ? '修改記錄' : '新增記錄' }}" separator persistent size="lg" x-on:click.stop>
        {{-- 添加右上角關閉按鈕 --}}
        <button 
            type="button" 
            wire:click="$set('showTransactionModal', false)" 
            class="btn btn-light btn-sm btn-square absolute right-4 top-4 text-base-content/60 hover:text-base-content"
        >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
		
		{{-- ================ 核心切換邏輯 ================ --}}
		@if($showCategoryPicker && $categoryPickerReturnTo === 'transaction')
        {{-- 1. 類別選擇器 --}}
        <div class="w-full flex flex-col" style="height: 420px;">
            <div class="flex flex-col h-full">
                {{-- 頂部標題列 + 關閉按鈕 --}}
                @php
                    $currentType = $isTemplateCategoryPicker ? $templateType : $type;
                    $themeText = match($currentType) {
                        'expense' => 'text-tx-expense',
                        'income'  => 'text-tx-income',
                        default   => 'text-tx-transfer',
                    };
                    $themeBg = match($currentType) {
                        'expense' => 'bg-tx-expense',
                        'income'  => 'bg-tx-income',
                        default   => 'bg-tx-transfer',
                    };
                @endphp
                <div class="flex items-center justify-between px-4 py-3 border-b shrink-0">
                    <div class="flex items-center gap-2">
                        <span class="w-1 h-5 rounded-full {{ $themeBg }}"></span>
                        <span class="text-sm font-bold tracking-wider {{ $themeText }}">
                            {{ $currentType === 'expense' ? '支出類別' : '收入類別' }}
                        </span>
                    </div>
                    <button type="button" wire:click="$set('showCategoryPicker', false)" class="p-1.5 rounded-lg hover:bg-base-200 transition-colors text-base-content">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                {{-- 類別內容區 --}}
                <div class="flex-1 overflow-y-auto px-4 py-4">
                    @php $filteredCats = $this->filteredCategories; @endphp
                    @if($filteredCats->isNotEmpty())
                        <div class="grid grid-cols-3 gap-2">
                            @foreach($filteredCats as $category)
                                @if($category->children->isNotEmpty())
                                    <div class="col-span-3">
                                        <div class="text-[10px] mb-1.5 ml-1 font-bold uppercase tracking-wide {{ $themeText }}">{{ $category->name }}</div>
                                        <div class="grid grid-cols-3 gap-2">
                                            @foreach($category->children as $child)
                                                <button type="button" wire:click="selectCategory({{ $child->id }})" class="p-3 bg-base-200 hover:bg-base-300 text-base-content rounded-xl transition-all text-center group active:scale-95">
                                                    <div class="flex justify-center mb-1.5">
                                                        <x-dynamic-component :component="'heroicon-o-' . ($child->icon ?? 'folder')" class="w-7 h-7 text-base-content/80 group-hover:scale-110 transition-transform" />
                                                    </div>
                                                    <div class="text-xs font-medium leading-tight text-base-content">{{ $child->name }}</div>
                                                </button>
                                            @endforeach
                                        </div>
                                    </div>
                                @else
                                    <button type="button" wire:click="selectCategory({{ $category->id }})" class="p-3 bg-base-200 hover:bg-base-300 text-base-content rounded-xl transition-all text-center group active:scale-95">
                                        <div class="flex justify-center mb-1.5">
                                            <x-dynamic-component :component="'heroicon-o-' . ($category->icon ?? 'folder')" class="w-7 h-7 text-base-content/80 group-hover:scale-110 transition-transform" />
                                        </div>
                                        <div class="text-xs font-medium leading-tight text-base-content">{{ $category->name }}</div>
                                    </button>
                                @endif
                            @endforeach
                        </div>
                    @else
                        <div class="flex flex-col items-center justify-center h-full py-12">
                            <div class="text-5xl mb-4">📂</div>
                            <p class="font-medium text-sm text-base-content/70">還沒有建立對應分類</p>
                            <p class="text-xs text-base-content/50 mt-1">請先到後台建立相符的分類</p>
                        </div>
                    @endif
                </div>

                {{-- 底部取消按鈕 --}}
                <div class="px-4 py-3 border-t shrink-0">
                    <button type="button" wire:click="$set('showCategoryPicker', false)" class="w-full py-2.5 rounded-xl font-medium text-sm bg-base-200 hover:bg-base-300 text-base-content"> 取消 </button>
                </div>
            </div>
        </div>
    @else
        {{-- 類型選擇（含範本按鈕） --}}
        @php
            $typeClasses = [
                'expense'  => 'bg-tx-expense text-white shadow-rose-900/20',
                'income'   => 'bg-tx-income text-white shadow-emerald-900/20',
                'transfer' => 'bg-tx-transfer text-white shadow-sky-900/20',
            ];
            $currentType = $this->type ?? 'expense';
        @endphp
        <div class="grid grid-cols-4 gap-1.5 mb-5">
            <button type="button" wire:click="openTemplateList" class="py-2.5 text-sm font-bold rounded-xl transition-all duration-200 bg-base-200 hover:bg-base-300 text-base-content">
                <span class="mr-1">📋</span> 範本
            </button>
            @foreach(['expense' => '支出', 'income' => '收入', 'transfer' => '轉帳'] as $value => $label)
                <button type="button" wire:click="$set('type', '{{ $value }}')" class="py-2.5 text-sm font-bold rounded-xl transition-all duration-200 {{ $currentType === $value ? $typeClasses[$value] . ' shadow-md' : 'bg-base-200 hover:bg-base-300 text-base-content' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>

        {{-- 2. 原本的表單 Form 內容 --}}
        <x-form wire:submit="saveTransaction">
            {{-- 日期選擇（不用dateDisplay()） --}}
            <div class="mb-4">
                <x-date-nav 
					model="currentDate" 
					:type="$dateMode"
					:display="date('Y-m-d', strtotime($currentDate))" 
					:isCurrent="$currentDate === date('Y-m-d')" 
					:disableNext="false"
				/>
            </div>
            
            {{-- 帳戶選擇 --}}
            @if($type === 'transfer')
                {{-- 轉帳模式 --}}
                <div class="grid grid-cols-5 gap-3 mb-4 items-end">
                    {{-- 轉出帳戶 --}}
                    <div class="col-span-2">
                        <label class="block text-xs font-bold mb-1 text-base-content">轉出賬戶</label>
                        <select wire:model.live="fromAccountId" class="select select-bordered w-full h-11 text-sm rounded-xl bg-base-100 text-base-content">
                            <option value="" class="bg-base-100 text-base-content"> 選擇帳戶 </option>
                            @foreach($this->accounts as $account)
                                <option value="{{ $account['id'] }}" class="bg-base-100 text-base-content"> {{ $account['name'] }} </option>
                            @endforeach
                        </select>
                        @error('fromAccountId') <span class="text-tx-expense text-xs">{{ $message }}</span> @enderror
                    </div>

                    {{-- 切換按鈕 --}}
                    <div class="col-span-1 flex justify-center pb-1">
                        <button type="button" wire:click="swapAccounts" class="p-2 rounded-full hover:bg-base-200 text-base-content" title="切換帳戶">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                        </button>
                    </div>

                    {{-- 轉入帳戶 --}}
                    <div class="col-span-2">
                        <label class="block text-xs font-bold mb-1 text-base-content">轉入賬戶</label>
                        <select wire:model.live="toAccountId" class="select select-bordered w-full h-11 text-sm rounded-xl bg-base-100 text-base-content">
                            <option value="" class="bg-base-100 text-base-content"> 選擇目標帳戶 </option>
                            @foreach($this->accounts as $account)
                                @if(!$fromAccountId || $account['id'] !== $fromAccountId)
                                    <option value="{{ $account['id'] }}" class="bg-base-100 text-base-content"> {{ $account['name'] }} </option>
                                @endif
                            @endforeach
                        </select>
                        @error('toAccountId') <span class="text-tx-expense text-xs">{{ $message }}</span> @enderror
                    </div>
                </div>
                
                {{-- 轉帳金額顯示 (僅轉帳模式) --}}               
                <div class="grid grid-cols-2 gap-3 mb-4">
                    {{-- 轉出金額 --}}
                    <div>
                        <label class="block text-xs font-bold mb-1 text-base-content">轉出金額</label>
                        <div class="relative w-full flex items-center px-3 py-2 rounded-xl border border-base-300 bg-base-200/50">
                            <span class="text-lg font-bold shrink-0 mr-1 text-tx-expense">-</span>
                            <input 
                                type="text" 
                                inputmode="decimal" 
                                wire:model.live.debounce.500ms="amount" 
                                x-data="{ shouldFocus: false }" 
                                x-init="
                                    $watch('shouldFocus', value => { 
                                        if (value) { 
                                            $nextTick(() => { 
                                                $el.focus(); 
                                                $el.select(); 
                                                shouldFocus = false; 
                                            }); 
                                        } 
                                    })
                                " 
                                x-on:focus-amount-input.window="shouldFocus = true" 
                                placeholder="0" 
                                autocomplete="off" 
                                class="w-full pl-1 pr-6 font-bold bg-transparent focus:outline-none focus:ring-2 focus:ring-sky-500/20 rounded text-right caret-base-content text-lg text-tx-expense" 
                            />
                        </div>
                    </div>
                    {{-- 轉入金額 (示例) --}}
                    <div>
                        <label class="block text-xs font-bold mb-1 text-base-content">轉入金額 (估算)</label>
                        <div class="w-full px-3 py-2 rounded-xl border border-base-300 bg-base-200/50">
                            <span class="text-lg font-bold text-tx-income">+</span>
                            <span class="text-lg font-bold text-tx-income">{{ $amount ?? 0 }}</span>
                        </div>
                    </div>
                </div>
                
            @else
                
                {{-- 非轉帳模式：類別 + 金額 --}}
                @php
                    $sign = match($type) { 'expense' => '−', 'income' => '+', default => '', };
                    $signColor = match($type) { 'expense' => 'text-tx-expense', 'income' => 'text-tx-income', default => 'text-tx-transfer', };
                @endphp
                <div class="grid grid-cols-3 gap-3 mb-4">
                    {{-- 類別方塊 --}}
                    <div class="col-span-1">
                        <div class="aspect-square rounded-2xl border border-base-300 transition-all cursor-pointer flex flex-col items-center justify-center p-1 relative overflow-hidden bg-base-200 hover:bg-base-300" wire:click="openCategoryPicker(false)">
                            @if($categoryId && $selectedCategory && $selectedCategory->type === $type)
                                <div class="text-3xl mb-1 flex justify-center">
                                    <x-dynamic-component :component="'heroicon-o-' . ($selectedCategory->icon ?? 'folder')" class="w-10 h-10 {{ $signColor }}" />
                                </div>
                                <div class="text-xs font-bold text-center leading-tight {{ $signColor }}"> {{ $selectedCategory->name }} </div>
                                @if($selectedCategory->parent)
                                    <div class="text-[10px] text-base-content/60 mt-0.5"> {{ $selectedCategory->parent->name }} </div>
                                @endif
                            @else
                                <div class="text-3xl mb-1 flex justify-center">
                                    <x-icon name="o-folder" class="w-8 h-8 text-base-content/40" />
                                </div>
                                <div class="text-xs font-bold text-base-content/60">類別</div>
                            @endif
                        </div>
                    </div>

                    {{-- 金額顯示與輸入 --}}
                    <div class="col-span-2 flex items-center">
                        <div class="relative w-full flex items-center px-3 py-2 rounded-xl border border-base-300 bg-base-200/50">
                            <span class="text-2xl font-bold shrink-0 mr-1 {{ $signColor }}">{{ $sign }}</span>
                            <input type="text" inputmode="decimal" wire:model.live.debounce.500ms="amount" x-data="{ shouldFocus: false }" x-init=" $watch('shouldFocus', value => { if (value) { $nextTick(() => { $el.focus(); $el.select(); shouldFocus = false; }); } }) " x-on:focus-amount-input.window="shouldFocus = true" placeholder="0" autocomplete="off" class="w-full pl-1 pr-6 font-bold bg-transparent focus:outline-none focus:ring-2 focus:ring-sky-500/20 rounded text-right caret-base-content text-4xl {{ $signColor }}" />
                        </div>
                        @error('amount') <span class="text-tx-expense text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>
                
                <div class="grid grid-cols-4 gap-3 mb-4">
                    <div class="col-span-2">
                        <x-select
                            wire:model.live="fromAccountId"
                            :options="$this->accounts"
                            option-label="name"
                            option-value="id"
                            placeholder="選擇帳戶"
                        />
                        @error('fromAccountId') <span class="text-tx-expense text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div class="col-span-2 flex items-center justify-end gap-2">
                        <span class="text-sm text-base-content/60">餘額</span>
                        <span class="text-sm font-bold text-base-content" wire:key="balance-fromAccountId-{{ $this->fromAccountId ?? 'none' }}">
                            @php $selectedAccount = collect($this->accounts)->firstWhere('id', $this->fromAccountId ?? null); @endphp
                            @if($selectedAccount) {{ $selectedAccount['currency'] }} {{ number_format($selectedAccount['balance'] ?? 0, 0) }} @else 0 @endif
                        </span>
                    </div>
                </div>
            @endif

            {{-- 照片 + 備註 --}}
            <div class="grid grid-cols-5 gap-3 mb-5">
                <div class="col-span-2">
                    <label class="aspect-square rounded-2xl border-2 border-dashed border-base-300 hover:border-base-content/40 transition-all cursor-pointer flex flex-col items-center justify-center relative overflow-hidden bg-base-200/50">
                        <input type="file" wire:model="photo" accept="image/*" class="hidden" id="photo-input">
                        @if(isset($photo) && method_exists($photo, 'temporaryUrl'))
                            <img src="{{ $photo->temporaryUrl() }}" class="absolute inset-0 w-full h-full object-cover">
                        @elseif(isset($existingPhotoPath) && $existingPhotoPath)
                            <img src="{{ Storage::url($existingPhotoPath) }}" class="absolute inset-0 w-full h-full object-cover">
                        @else
                            <svg class="w-8 h-8 mb-1 text-base-content/40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            <span class="text-xs font-medium text-base-content/60">照片</span>
                        @endif
                        <div wire:loading wire:target="photo" class="absolute inset-0 bg-base-900/50 flex items-center justify-center text-xs text-white"> 上傳中... </div>
                    </label>
                </div>
                <div class="col-span-3">
                    <div class="relative w-full h-full min-h-[80px] px-3 py-2 rounded-xl border border-base-300 bg-base-200/50">
                        <textarea wire:model="memo" placeholder="請輸入備註" class="w-full h-full bg-transparent text-base-content placeholder:text-base-content/40 focus:outline-none placeholder:font-medium resize-none text-sm" style="min-height: 60px;"></textarea>
                    </div>
                </div>
            </div>

            {{-- 操作按鈕 --}}
            <x-slot:actions>
                @if(!$transactionId)
                    {{-- 新增模式：存為範本、再記一筆、儲存 --}}
                    <div class="grid grid-cols-3 gap-2 w-full pt-3 border-t border-base-300">
                        <x-button label="存為範本" type="button" wire:click="openTemplateModalFromTransaction" class="btn-purple" />
                        <x-button label="再記一筆" type="button" wire:click="saveAndKeepOpen" class="btn-green" spinner="saveAndKeepOpen" />
                        <x-button label="儲存" type="submit" class="btn-dark" spinner="saveTransaction" />
                    </div>
                @else
                    {{-- 修改模式：刪除、儲存 --}}
                    <div class="grid grid-cols-2 gap-2 w-full pt-3 border-t border-base-300">
                        <x-button label="刪除" type="button" wire:click="deleteTransaction" class="btn-red" spinner="deleteTransaction" />
                        <x-button label="儲存" type="submit" class="btn-green" spinner="saveTransaction" />
                    </div>
                @endif
            </x-slot:actions>
        </x-form>
    @endif
</x-modal>

{{-- ==================== 範本 Modal ==================== --}}
<x-modal wire:model="showTemplateModal" 
         title="{{ $editingTemplateId ? '編輯範本' : '儲存範本' }}" 
         separator 
         persistent
         size="lg"
         x-on:click.stop>
         
    @if($showCategoryPicker && $categoryPickerReturnTo === 'template')
        @php
            $currentType = $templateType ?? 'expense';
            $themeText = match($currentType) {
                'expense' => 'text-tx-expense',
                'income'  => 'text-tx-income',
                default   => 'text-tx-transfer',
            };
            $themeBg = match($currentType) {
                'expense' => 'bg-tx-expense',
                'income'  => 'bg-tx-income',
                default   => 'bg-tx-transfer',
            };
        @endphp
        {{-- 範本 Modal 內的類別選擇器 --}}
        <div class="w-full flex flex-col" style="height: 420px;">
            <div class="flex flex-col h-full">
                
                <div class="flex items-center justify-between px-4 py-3 border-b border-base-300 shrink-0">
                    <div class="flex items-center gap-2">
                        <span class="w-1 h-5 rounded-full {{ $themeBg }}"></span>
                        <span class="text-sm font-bold tracking-wider {{ $themeText }}">
                            {{ $currentType === 'expense' ? '支出類別' : '收入類別' }}
                        </span>
                    </div>
                    
                    <button type="button" 
                            wire:click="$set('showCategoryPicker', false)"
                            class="p-1.5 rounded-lg bg-base-300 text-base-content transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto px-4 py-4">
                    @php
                        $filteredCats = $this->filteredCategories;
                    @endphp

                    @if($filteredCats->isNotEmpty())
                        <div class="grid grid-cols-3 gap-2">
                            @foreach($filteredCats as $category)
                                @if($category->children->isNotEmpty())
                                    <div class="col-span-3">
                                        <div class="text-[10px] {{ $themeText }} mb-1.5 ml-1 font-bold uppercase tracking-wide">
                                            {{ $category->name }}
                                        </div>
                                        <div class="grid grid-cols-3 gap-2">
                                            @foreach($category->children as $child)
                                                <button type="button"
                                                        wire:click="selectCategory({{ $child->id }})"
                                                        class="p-3 bg-base-200 hover:bg-base-300 text-base-content rounded-xl transition-all text-center group active:scale-95">
                                                    <div class="flex justify-center mb-1.5">
                                                        <x-dynamic-component :component="'heroicon-o-' . ($child->icon ?? 'folder')" 
                                                                class="w-7 h-7 text-base-content/80 group-hover:scale-110 transition-transform" />
                                                    </div>
                                                    <div class="text-xs font-medium text-base-content leading-tight">
                                                        {{ $child->name }}
                                                    </div>
                                                </button>
                                            @endforeach
                                        </div>
                                    </div>
                                @else
                                    <button type="button"
                                            wire:click="selectCategory({{ $category->id }})"
                                            class="p-3 bg-base-200 hover:bg-base-300 text-base-content rounded-xl transition-all text-center group active:scale-95">
                                        <div class="flex justify-center mb-1.5">
                                            <x-dynamic-component :component="'heroicon-o-' . ($category->icon ?? 'folder')" 
                                                    class="w-7 h-7 text-base-content/80 group-hover:scale-110 transition-transform" />
                                        </div>
                                        <div class="text-xs font-medium text-base-content leading-tight">
                                            {{ $category->name }}
                                        </div>
                                    </button>
                                @endif
                            @endforeach
                        </div>
                    @else
                        <div class="flex flex-col items-center justify-center h-full py-12">
                            <div class="text-5xl mb-4">📂</div>
                            <p class="text-base-content/70 font-medium text-sm">還沒有建立對應分類</p>
                            <p class="text-xs text-base-content/50 mt-1">請先到後台建立相符的分類</p>
                        </div>
                    @endif
                </div>

                <div class="px-4 py-3 border-t border-base-300 shrink-0">
                    <button type="button"
                            wire:click="$set('showCategoryPicker', false)"
                            class="w-full py-2.5 rounded-xl font-medium text-base-content bg-base-200 hover:bg-base-300 transition-colors text-sm">
                        取消
                    </button>
                </div>
            </div>
        </div>
    @else
        <x-form wire:submit="saveAsTemplate">
            {{-- 範本類型選擇 --}}
            @php
                $templateTypeClasses = [
                    'expense'  => 'bg-tx-expense text-white shadow-rose-900/20',
                    'income'   => 'bg-tx-income text-white shadow-emerald-900/20',
                    'transfer' => 'bg-tx-transfer text-white shadow-sky-900/20',
                ];
                $currentTemplateType = $this->templateType ?? 'expense';
            @endphp
            <div class="grid grid-cols-3 gap-1.5 mb-5">
                @foreach(['expense' => '支出', 'income' => '收入', 'transfer' => '轉帳'] as $value => $label)
                    <button type="button"
                            wire:click="$set('templateType', '{{ $value }}')"
                            class="py-2.5 text-sm font-bold rounded-xl transition-all duration-200
                                {{ $currentTemplateType === $value 
                                    ? $templateTypeClasses[$value] . ' shadow-md'
                                    : 'bg-base-200 hover:bg-base-300 text-base-content' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            <x-input 
                label="範本名稱" 
                wire:model="templateName" 
                placeholder="例如：早餐、股息" 
                icon="o-bookmark"
                inline
                required 
            />

            <div class="grid grid-cols-6 gap-3 mb-4">
                {{-- 範本類別方塊 --}}
                <div class="col-span-2">
                    @if($templateType !== 'transfer')
                        <div class="aspect-square bg-base-200 rounded-2xl border border-base-300
                                    hover:border-base-content/40 transition-all cursor-pointer 
                                    flex flex-col items-center justify-center p-1 relative overflow-hidden"
                             wire:click="openCategoryPicker(true)">
                            @if($templateCategoryId && $selectedTemplateCategory && $selectedTemplateCategory->type === $templateType)
                                <div class="text-3xl mb-1 flex justify-center">
                                    <x-dynamic-component :component="'heroicon-o-' . ($selectedTemplateCategory->icon ?? 'folder')" 
                                            class="w-10 h-10 {{ match($templateType) { 'expense' => 'text-tx-expense', 'income' => 'text-tx-income', default => 'text-base-content' } }}" />
                                </div>
                                <div class="text-xs font-bold text-center leading-tight {{ match($templateType) { 'expense' => 'text-tx-expense', 'income' => 'text-tx-income', default => 'text-base-content' } }}">
                                    {{ $selectedTemplateCategory->name }}
                                </div>
                                @if($selectedTemplateCategory->parent)
                                    <div class="text-[10px] text-base-content/60 mt-0.5">
                                        {{ $selectedTemplateCategory->parent->name }}
                                    </div>
                                @endif
                            @else
                                <div class="text-3xl mb-1 flex justify-center">
                                    <x-icon name="o-folder" class="w-8 h-8 text-base-content/40" />
                                </div>
                                <div class="text-xs font-bold text-base-content/60">類別</div>
                            @endif
                        </div>
                    @else
                        <div class="aspect-square badge-tx-transfer rounded-2xl border
                                    flex flex-col items-center justify-center">
                            <div class="text-3xl mb-1 flex justify-center">
                                <x-icon name="o-arrow-path" class="w-10 h-10 text-tx-transfer" />
                            </div>
                            <div class="text-xs font-bold text-tx-transfer">轉帳</div>
                        </div>
                    @endif
                </div>

                {{-- 範本金額 --}}
                @php
                    $sign = match($templateType) {
                        'expense' => '−',
                        'income'  => '+',
                        default   => '',
                    };
                    $signColor = match($templateType) {
                        'expense'  => 'text-tx-expense',
                        'income'   => 'text-tx-income',
                        'transfer' => 'text-tx-transfer',
                        default    => 'text-base-content/60',
                    };
                @endphp
                <div class="col-span-4 flex items-center">
                    <div class="relative w-full flex items-center bg-base-200/50 px-3 py-2 rounded-xl border border-base-300">
                        <span class="text-2xl font-bold shrink-0 mr-1 {{ $signColor }}">{{ $sign }}</span>
                        <input type="text"
                               inputmode="decimal"
                               wire:model.live.debounce.500ms="amount"
                               placeholder="0"
                               autocomplete="off"
                               class="w-full pl-1 pr-6 font-bold bg-transparent focus:outline-none focus:ring-2 focus:ring-sky-500/20 rounded
                                      placeholder:text-base-content/40 text-right
                                      caret-base-content {{ $signColor }}"
                               style="font-size: 2.25rem; height: 3rem; margin: 0;"
                        />
                    </div>
                    @error('amount')
                        <span class="text-tx-expense text-xs mt-1 block">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            {{-- 範本帳戶選擇 --}}
            <div class="grid grid-cols-4 gap-3 mb-4">
                <div class="col-span-2">
                    <select 
                        wire:model.live="templateFromAccountId"
                        class="select select-bordered w-full h-11 text-sm rounded-xl bg-base-100 text-base-content">
                        <option value="" class="bg-base-100 text-base-content/50">
                            選擇帳戶
                        </option>
                        @foreach($this->accounts as $account)
                            <option value="{{ $account['id'] }}" class="bg-base-100 text-base-content">
                                {{ $account['name'] }}
                            </option>
                        @endforeach
                    </select>
                    @error('templateFromAccountId') 
                        <span class="text-tx-expense text-xs">{{ $message }}</span> 
                    @enderror
                </div>
                
                <div class="col-span-2 flex items-center justify-end gap-2">
                    <span class="text-sm text-base-content/60">餘額</span>
                    <span class="text-sm font-bold text-base-content" 
                          wire:key="balance-templateFromAccountId-{{ $this->templateFromAccountId ?? 'none' }}">
                        @php
                            $selectedAccount = collect($this->accounts)->firstWhere('id', $this->templateFromAccountId ?? null);
                        @endphp
                        @if($selectedAccount)
                            {{ $selectedAccount['currency'] }} 
                            {{ number_format($selectedAccount['balance'] ?? 0, 0) }}
                        @else
                            0
                        @endif
                    </span>
                </div>
            </div>

            {{-- 範本轉帳目標帳戶 --}}
            @if($templateType === 'transfer')
                <div class="grid grid-cols-5 gap-3 mb-4">
                    <div class="col-span-2">
                        <select 
                            wire:model.live="templateToAccountId"
                            class="select select-bordered w-full h-11 text-sm rounded-xl bg-base-100 text-base-content">
                            <option value="" class="bg-base-100 text-base-content/50">
                                選擇目標帳戶
                            </option>
                            @foreach($this->accounts as $account)
                                @if(!$templateFromAccountId || $account['id'] !== $templateFromAccountId)
                                    <option value="{{ $account['id'] }}" class="bg-base-100 text-base-content">
                                        {{ $account['name'] }}
                                    </option>
                                @endif
                            @endforeach
                        </select>
                        @error('templateToAccountId') 
                            <span class="text-tx-expense text-xs">{{ $message }}</span> 
                        @enderror
                    </div>
                </div>
            @endif

            {{-- 範本備註（不顯示照片） --}}
            <div class="grid grid-cols-5 gap-3 mb-4">
                <div class="col-span-5">
                    <div class="relative w-full bg-base-200/50 px-3 py-2 rounded-xl border border-base-300">
                        <textarea wire:model="templateMemo"
                                  placeholder="請輸入備註"
                                  rows="3"
                                  class="w-full bg-transparent focus:outline-none
                                         text-base-content 
                                         placeholder:text-base-content/40 
                                         font-medium resize-none text-sm"></textarea>
                    </div>
                </div>
            </div>

            <x-slot:actions>
                <div class="grid grid-cols-2 gap-2 w-full pt-3 border-t border-base-300">
                    <x-button label="取消" type="button" @click="$wire.showTemplateModal = false; $wire.resetTemplateForm()" class="btn-sm rounded-xl bg-base-200 hover:bg-base-300 text-base-content" />
                    <x-button label="{{ $editingTemplateId ? '更新' : '儲存' }}" type="submit" class="btn-sm rounded-xl btn-green" />
                </div>
            </x-slot:actions>
        </x-form>
    @endif
</x-modal>

{{-- =================== 範本列表 Modal =================== --}}
<x-modal wire:model="showTemplateListModal" 
         title="選擇範本" 
         separator 
         persistent
         size="lg">
    
    <div class="space-y-2 max-h-96 overflow-y-auto">
        @forelse($templates as $template)
            @php
                $templateTypeColor = match($template['type'] ?? 'expense') {
                    'expense'  => 'text-tx-expense',
                    'income'   => 'text-tx-income',
                    'transfer' => 'text-tx-transfer',
                    default    => 'text-base-content',
                };
            @endphp
            <div class="flex items-center justify-between p-3 bg-base-200 rounded-xl hover:bg-base-300 transition-colors">
                <div class="flex-1 cursor-pointer " wire:click="applyTemplate({{ $template['id'] }})">
                    <div class="font-medium {{ $templateTypeColor }}">{{ $template['name'] }}</div>
                    <div class="text-sm text-base-content/60">
                        {{ match($template['type'] ?? 'expense') { 'expense' => '支出', 'income' => '收入', 'transfer' => '轉帳', default => $template['type'] } }} · {{ number_format($template['amount'], 2) }}
                    </div>
                </div>
                <div class="flex gap-1">
                    <button wire:click="editTemplate({{ $template['id'] }})" 
                            class="p-1.5 text-base-content/60 hover:text-base-content">
                        ✏️
                    </button>
                    <button wire:click="deleteTemplate({{ $template['id'] }})" 
                            wire:confirm="確定要刪除此範本嗎？"
                            class="p-1.5 text-tx-expense hover:opacity-80">
                        🗑️
                    </button>
                </div>
            </div>
        @empty
            <div class="text-center py-8 text-base-content/60">
                <div class="text-4xl mb-2">📭</div>
                <p>尚無範本</p>
                <button wire:click="openTemplateModalFromList" 
                        class="mt-3 text-sm text-tx-transfer hover:opacity-80">
                    建立第一個範本 →
                </button>
            </div>
        @endforelse
    </div>
    
    <x-slot:actions>
        <div class="grid grid-cols-2 gap-2 w-full pt-3 border-t border-base-300">
            <x-button label="取消" 
                      type="button" 
                      @click="$wire.showTemplateListModal = false" 
                      class="btn-sm rounded-xl bg-base-200 hover:bg-base-300 text-base-content" />
            <x-button label="新增範本" 
                      type="button" 
                      wire:click="openTemplateModalFromList" 
                      class="btn-sm rounded-xl btn-green" />
        </div>
    </x-slot:actions>
</x-modal>
</div>