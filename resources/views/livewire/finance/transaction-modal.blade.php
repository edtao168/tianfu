<!-- filepath: resources/views/livewire/finance/transaction-modal.blade.php -->
<div>
    {{-- ==================== 主要記帳 Modal ==================== --}}
    {{-- 移除了 !max-w-md，讓 CSS 中的 .modal-box 規則來控制寬度 --}}
    <x-modal wire:model="showTransactionModal" title="{{ $transactionId ? '修改記錄' : '新增記錄' }}" separator persistent size="lg" x-on:click.stop>
		{{-- 添加右上角關閉按鈕 --}}
        <button 
			type="button" 
			wire:click="$set('showTransactionModal', false)" 
			class="btn btn-ghost btn-sm btn-square absolute right-4 top-4 text-base-content/60 hover:text-base-content"
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
                    <div class="flex items-center justify-between px-4 py-3 border-b shrink-0">
                        <div class="flex items-center gap-2">
                            @php
                                $currentType = $isTemplateCategoryPicker ? $templateType : $type;
                                $themeColor = $currentType === 'expense' ? 'text-rose-600' : 'text-emerald-600';
                                $barColor = $currentType === 'expense' ? 'bg-rose-600' : 'bg-emerald-600';
                            @endphp
                            {{-- 顏色類別保留，因為它們是動態的業務邏輯 --}}
                            <span class="w-1 h-5 {{ $barColor }} rounded-full"></span>
                            <span class="text-sm font-bold {{ $themeColor }} tracking-wider">
                                {{ $currentType === 'expense' ? '支出類別' : '收入類別' }}
                            </span>
                        </div>
                        <button type="button" wire:click="$set('showCategoryPicker', false)" class="p-1.5 rounded-lg hover:bg-stone-100 transition-colors">
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
                                            <div class="text-[10px] mb-1.5 ml-1 font-bold uppercase tracking-wide">{{ $category->name }}</div>
                                            <div class="grid grid-cols-3 gap-2">
                                                @foreach($category->children as $child)
                                                    <button type="button" wire:click="selectCategory({{ $child->id }})" class="p-3 bg-stone-50 rounded-xl transition-all text-center group active:scale-95">
                                                        <div class="flex justify-center mb-1.5">
                                                            <x-dynamic-component :component="'heroicon-o-' . ($child->icon ?? 'folder')" class="w-7 h-7 group-hover:scale-110 transition-transform" />
                                                        </div>
                                                        <div class="text-xs font-medium leading-tight">{{ $child->name }}</div>
                                                    </button>
                                                @endforeach
                                            </div>
                                        </div>
                                    @else
                                        <button type="button" wire:click="selectCategory({{ $category->id }})" class="p-3 bg-stone-50 rounded-xl transition-all text-center group active:scale-95">
                                            <div class="flex justify-center mb-1.5">
                                                <x-dynamic-component :component="'heroicon-o-' . ($category->icon ?? 'folder')" class="w-7 h-7 group-hover:scale-110 transition-transform" />
                                            </div>
                                            <div class="text-xs font-medium leading-tight">{{ $category->name }}</div>
                                        </button>
                                    @endif
                                @endforeach
                            </div>
                        @else
                            <div class="flex flex-col items-center justify-center h-full py-12">
                                <div class="text-5xl mb-4">📂</div>
                                <p class="font-medium text-sm">還沒有建立對應分類</p>
                                <p class="text-xs mt-1">請先到後台建立相符的分類</p>
                            </div>
                        @endif
                    </div>

                    {{-- 底部取消按鈕 --}}
                    <div class="px-4 py-3 border-t shrink-0">
                        <button type="button" wire:click="$set('showCategoryPicker', false)" class="w-full py-2.5 rounded-xl font-medium text-sm"> 取消 </button>
                    </div>
                </div>
            </div>
        @else
            {{-- 類型選擇（含範本按鈕） --}}
            <div class="grid grid-cols-4 gap-1.5 mb-5">
                <button type="button" wire:click="openTemplateList" class="py-2.5 text-sm font-bold rounded-xl transition-all duration-200">
                    <span class="mr-1">📋</span> 範本
                </button>
                @foreach(['expense' => '支出', 'income' => '收入', 'transfer' => '轉帳'] as $value => $label)
                    @php
                        $colors = [
                            'expense' => ['bg' => 'bg-rose-600', 'shadow' => 'shadow-rose-900/20', 'text' => 'text-rose-600'],
                            'income' => ['bg' => 'bg-emerald-600', 'shadow' => 'shadow-emerald-900/20', 'text' => 'text-emerald-600'],
                            'transfer' => ['bg' => 'bg-sky-600', 'shadow' => 'shadow-sky-900/20', 'text' => 'text-sky-600']
                        ];
                        $currentType = $this->type ?? 'expense';
                    @endphp
                    {{-- 顏色類別保留，因為它們是動態的業務邏輯 --}}
                    <button type="button" wire:click="$set('type', '{{ $value }}')" class="py-2.5 text-sm font-bold rounded-xl transition-all duration-200 {{ $currentType === $value ? $colors[$value]['bg'] . ' text-white shadow-md ' . $colors[$value]['shadow'] : 'bg-stone-50' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            {{-- 2. 原本的表單 Form 內容 --}}
            <x-form wire:submit="saveTransaction">
				{{-- 日期選擇 --}}
                <div class="flex items-center justify-between mb-4 px-2 py-1 rounded-xl">
                    <button type="button" wire:click="changeDate(-1)" class="p-2 hover:bg-stone-200 rounded-full transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    </button>
                    <span class="text-base font-bold tracking-wider"> {{ \Carbon\Carbon::parse($this->recordedAt)->format('Y/m/d') }} </span>
                    <button type="button" wire:click="changeDate(1)" class="p-2 hover:bg-stone-200 rounded-full transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </button>
                </div>
                
                {{-- 帳戶選擇 --}}
                @if($type === 'transfer')
                    {{-- 转账模式 --}}
                    <div class="grid grid-cols-5 gap-3 mb-4 items-end">
                        {{-- 转出账户 --}}
                        <div class="col-span-2">
                            <label class="block text-xs font-bold mb-1">轉出賬戶</label>
                            <select wire:model.live="fromAccountId" class="select select-bordered w-full h-11 text-sm rounded-xl">
                                <option value="" class="bg-white"> 選擇帳戶 </option>
                                @foreach($this->accounts as $account)
                                    <option value="{{ $account['id'] }}" class="bg-white"> {{ $account['name'] }} </option>
                                @endforeach
                            </select>
                            @error('fromAccountId') <span class="text-rose-600 text-xs">{{ $message }}</span> @enderror
                        </div>

                        {{-- 切换按钮 --}}
                        <div class="col-span-1 flex justify-center pb-1">
                            <button type="button" wire:click="swapAccounts" class="p-2 rounded-full" title="切换账户">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                            </button>
                        </div>

                        {{-- 转入账户 --}}
                        <div class="col-span-2">
                            <label class="block text-xs font-bold mb-1">轉入賬戶</label>
                            <select wire:model.live="toAccountId" class="select select-bordered w-full h-11 text-sm rounded-xl">
                                <option value="" class="bg-white"> 選擇目標帳戶 </option>
                                @foreach($this->accounts as $account)
                                    @if(!$fromAccountId || $account['id'] !== $fromAccountId)
                                        <option value="{{ $account['id'] }}" class="bg-white"> {{ $account['name'] }} </option>
                                    @endif
                                @endforeach
                            </select>
                            @error('toAccountId') <span class="text-rose-600 text-xs">{{ $message }}</span> @enderror
                        </div>
                    </div>
					
					{{-- 转账金额显示 (仅转账模式) --}}               
					<div class="grid grid-cols-2 gap-3 mb-4">
						{{-- 转出金额 --}}
						<div>
							<label class="block text-xs font-bold mb-1">轉出金額</label>
							<div class="relative w-full flex items-center px-3 py-2 rounded-xl border">
								{{-- 顏色類別保留，因為它們是動態的業務邏輯 --}}
								<span class="text-lg font-bold shrink-0 mr-1 text-rose-500">-</span>
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
									class="w-full pl-1 pr-6 font-bold bg-transparent focus:outline-none focus:ring-2 focus:ring-sky-500/20 rounded text-right caret-stone-900 text-lg text-rose-500" 
								/>
							</div>
						</div>
						{{-- 转入金额 (示例) --}}
						<div>
							<label class="block text-xs font-bold mb-1">轉入金額 (估算)</label>
							<div class="w-full px-3 py-2 rounded-xl border border-stone-200">
								<span class="text-lg font-bold text-emerald-500">+</span>
								<span class="text-lg font-bold">{{ $amount ?? 0 }}</span>
							</div>
						</div>
					</div>
					
                @else
					
					{{-- 非转账模式：類別 + 金額 --}}
					<div class="grid grid-cols-3 gap-3 mb-4">
						{{-- 類別方塊 --}}
						@php
							$sign = match($type) { 'expense' => '−', 'income' => '+', default => '', };
							$signColor = match($type) { 'expense' => 'text-rose-500', 'income' => 'text-emerald-500', default => '', };
						@endphp
						<div class="col-span-1">
							
								<div class="aspect-square rounded-2xl border transition-all cursor-pointer flex flex-col items-center justify-center p-1 relative overflow-hidden " wire:click="openCategoryPicker(false)">
									@if($categoryId && $selectedCategory && $selectedCategory->type === $type)
										<div class="text-3xl mb-1 flex justify-center">
											<x-dynamic-component :component="'heroicon-o-' . ($selectedCategory->icon ?? 'folder')" class="w-10 h-10  {{ $signColor }}" />
										</div>
										<div class="text-xs font-bold text-center leading-tight  {{ $signColor }}"> {{ $selectedCategory->name }} </div>
										@if($selectedCategory->parent)
											<div class="text-[10px] mt-0.5"> {{ $selectedCategory->parent->name }} </div>
										@endif
									@else
										<div class="text-3xl mb-1 flex justify-center">
											<x-icon name="o-folder" class="w-8 h-8" />
										</div>
										<div class="text-xs font-bold">類別</div>
									@endif
								</div>

						</div>

						{{-- 金額顯示與輸入 --}}
						
						<div class="col-span-2 flex items-center">
							<div class="relative w-full flex items-center px-3 py-2 rounded-xl border">
								{{-- 顏色類別保留，因為它們是動態的業務邏輯 --}}
								<span class="text-2xl font-bold shrink-0 mr-1 {{ $signColor }}">{{ $sign }}</span>
								<input type="text" inputmode="decimal" wire:model.live.debounce.500ms="amount" x-data="{ shouldFocus: false }" x-init=" $watch('shouldFocus', value => { if (value) { $nextTick(() => { $el.focus(); $el.select(); shouldFocus = false; }); } }) " x-on:focus-amount-input.window="shouldFocus = true" placeholder="0" autocomplete="off" class="w-full pl-1 pr-6 font-bold bg-transparent focus:outline-none focus:ring-2 focus:ring-sky-500/20 rounded text-right caret-stone-900 text-4xl {{ $signColor }}" />
							</div>
							@error('amount') <span class="text-rose-600 text-xs mt-1 block">{{ $message }}</span> @enderror
						</div>
					</div>
					
                    <div class="grid grid-cols-4 gap-3 mb-4">
                        <div class="col-span-2">
                            <select wire:model.live="fromAccountId" class="select select-bordered w-full h-11 text-sm rounded-xl">
                                <option value="" class="bg-white"> 選擇帳戶 </option>
                                @foreach($this->accounts as $account)
                                    <option value="{{ $account['id'] }}" class="bg-white"> {{ $account['name'] }} </option>
                                @endforeach
                            </select>
                            @error('fromAccountId') <span class="text-rose-600 text-xs">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-span-2 flex items-center justify-end gap-2">
                            <span class="text-sm">餘額</span>
                            <span class="text-sm font-bold" wire:key="balance-fromAccountId-{{ $this->fromAccountId ?? 'none' }}">
                                @php $selectedAccount = collect($this->accounts)->firstWhere('id', $this->fromAccountId ?? null); @endphp
                                @if($selectedAccount) {{ $selectedAccount['currency'] }} {{ number_format($selectedAccount['balance'] ?? 0, 0) }} @else 0 @endif
                            </span>
                        </div>
                    </div>
                @endif

                {{-- 照片 + 備註 --}}
                <div class="grid grid-cols-5 gap-3 mb-5">
                    <div class="col-span-2">
                        <label class="aspect-square rounded-2xl border-2 border-dashed transition-all cursor-pointer flex flex-col items-center justify-center relative overflow-hidden">
                            <input type="file" wire:model="photo" accept="image/*" class="hidden" id="photo-input">
                            @if(isset($photo) && method_exists($photo, 'temporaryUrl'))
                                <img src="{{ $photo->temporaryUrl() }}" class="absolute inset-0 w-full h-full object-cover">
                            @elseif(isset($existingPhotoPath) && $existingPhotoPath)
                                <img src="{{ Storage::url($existingPhotoPath) }}" class="absolute inset-0 w-full h-full object-cover">
                            @else
                                <svg class="w-8 h-8 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                <span class="text-xs font-medium">照片</span>
                            @endif
                            <div wire:loading wire:target="photo" class="absolute inset-0 bg-stone-900/50 flex items-center justify-center text-xs text-white"> 上傳中... </div>
                        </label>
                    </div>
                    <div class="col-span-3">
                        <div class="relative w-full h-full min-h-[80px] px-3 py-2 rounded-xl border">
                            <textarea wire:model="memo" placeholder="請輸入備註" class="w-full h-full bg-transparent focus:outline-none placeholder:font-medium resize-none text-sm" style="min-height: 60px;"></textarea>
                        </div>
                    </div>
                </div>

                {{-- 操作按鈕 --}}
                <x-slot:actions>
                    @if(!$transactionId)
                        {{-- 新增模式：存為範本、再記一筆、儲存 --}}
                        <div class="grid grid-cols-3 gap-2 w-full pt-3 border-t">
                            <x-button label="存為範本" type="button" wire:click="openTemplateModalFromTransaction" class="btn-ghost" />
                            <x-button label="再記一筆" type="button" wire:click="saveAndKeepOpen" class="btn-ghost" spinner="saveAndKeepOpen" />
                            <x-button label="儲存" type="submit" class="btn-green" spinner="saveTransaction" />
                        </div>
                    @else
                        {{-- 修改模式：刪除、儲存 --}}
                        <div class="grid grid-cols-2 gap-2 w-full pt-3 border-t">
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
             class="!max-w-md template-modal-blue"
             x-on:click.stop>
             
        @if($showCategoryPicker && $categoryPickerReturnTo === 'template')
            {{-- 範本 Modal 內的類別選擇器 --}}
            <div class="w-full flex flex-col" style="height: 420px;">
                <div class="flex flex-col h-full">
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
                        
                        <button type="button" 
                                wire:click="$set('showCategoryPicker', false)"
                                class="p-1.5 rounded-lg hover:bg-stone-100 dark:hover:bg-stone-700 text-stone-400 hover:text-stone-600 dark:text-stone-500 dark:hover:text-stone-300 transition-colors">
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

                    <div class="px-4 py-3 border-t border-stone-200 dark:border-stone-700 shrink-0">
                        <button type="button"
                                wire:click="$set('showCategoryPicker', false)"
                                class="w-full py-2.5 rounded-xl font-medium text-stone-700 bg-stone-100 hover:bg-stone-200 dark:bg-stone-800 dark:text-stone-300 dark:hover:bg-stone-700 transition-colors text-sm">
                            取消
                        </button>
                    </div>
                </div>
            </div>
        @else
            <x-form wire:submit="saveAsTemplate">
                {{-- 範本類型選擇 --}}
                <div class="grid grid-cols-3 gap-1.5 mb-5">
                    @foreach(['expense' => '支出', 'income' => '收入', 'transfer' => '轉帳'] as $value => $label)
                        @php
                            $colors = [
                                'expense' => ['bg' => 'bg-rose-600', 'shadow' => 'shadow-rose-900/20', 'text' => 'text-rose-600'],
                                'income' => ['bg' => 'bg-emerald-600', 'shadow' => 'shadow-emerald-900/20', 'text' => 'text-emerald-600'],
                                'transfer' => ['bg' => 'bg-sky-600', 'shadow' => 'shadow-sky-900/20', 'text' => 'text-sky-600']
                            ];
                            $currentType = $this->templateType ?? 'expense';
                        @endphp
                        <button type="button"
                                wire:click="$set('templateType', '{{ $value }}')"
                                class="py-2.5 text-sm font-bold rounded-xl transition-all duration-200
                                    {{ $currentType === $value 
                                        ? $colors[$value]['bg'] . ' text-white shadow-md ' . $colors[$value]['shadow']
                                        : 'bg-stone-50 text-stone-600 hover:bg-stone-100 dark:bg-stone-800 dark:text-stone-400 dark:hover:bg-stone-700' }}">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>

                <x-input label="範本名稱" 
                         wire:model="templateName" 
                         placeholder="例如：早餐、薪資" 
                         class="input-bordered border-stone-300 dark:border-stone-700 focus:border-stone-500 bg-stone-50 text-stone-800 mb-4" 
                         required />

                <div class="grid grid-cols-6 gap-3 mb-4">
                    {{-- 範本類別方塊 --}}
                    <div class="col-span-2">
                        @if($templateType !== 'transfer')
                            <div class="aspect-square bg-stone-50 rounded-2xl border border-stone-300
                                        hover:border-stone-400 transition-all cursor-pointer 
                                        flex flex-col items-center justify-center p-1 relative overflow-hidden
                                        dark:bg-stone-800 dark:border-stone-700"
                                 wire:click="openCategoryPicker(true)">
                                @if($templateCategoryId && $selectedTemplateCategory && $selectedTemplateCategory->type === $templateType)
                                    <div class="text-3xl mb-1 flex justify-center">
                                        <x-dynamic-component :component="'heroicon-o-' . ($selectedTemplateCategory->icon ?? 'folder')" 
                                                class="w-10 h-10 text-stone-700 dark:text-stone-200" />
                                    </div>
                                    <div class="text-xs font-bold text-stone-800 dark:text-stone-200 text-center leading-tight">
                                        {{ $selectedTemplateCategory->name }}
                                    </div>
                                    @if($selectedTemplateCategory->parent)
                                        <div class="text-[10px] text-stone-500 dark:text-stone-400 mt-0.5">
                                            {{ $selectedTemplateCategory->parent->name }}
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
                            <div class="aspect-square bg-teal-50 rounded-2xl border border-teal-200 
                                        flex flex-col items-center justify-center
                                        dark:bg-teal-950 dark:border-teal-800">
                                <div class="text-3xl mb-1 flex justify-center">
                                    <x-icon name="o-arrow-path" class="w-10 h-10 text-teal-600 dark:text-teal-400" />
                                </div>
                                <div class="text-xs font-bold text-teal-700 dark:text-teal-400">轉帳</div>
                            </div>
                        @endif
                    </div>

                    {{-- 範本金額 --}}
                    @php
                        $sign = match($templateType) {
                            'expense' => '−',
                            'income' => '+',
                            default => '',
                        };
                        $signColor = match($templateType) {
                            'expense' => 'text-rose-500 dark:text-rose-400',
                            'income' => 'text-emerald-500 dark:text-emerald-400',
                            default => 'text-stone-500 dark:text-stone-400',
                        };
                    @endphp
                    <div class="col-span-4 flex items-center">
                        <div class="relative w-full flex items-center bg-stone-100/50 dark:bg-slate-900/60 px-3 py-2 rounded-xl border border-stone-200 dark:border-stone-700">
                            <span class="text-2xl font-bold shrink-0 mr-1 {{ $signColor }}">{{ $sign }}</span>
                            <input type="text"
                                   inputmode="decimal"
                                   wire:model.live.debounce.500ms="amount"
                                   placeholder="0"
                                   autocomplete="off"
                                   class="w-full pl-1 pr-6 font-bold bg-transparent focus:outline-none focus:ring-2 focus:ring-sky-500/20 rounded
                                          text-stone-900 dark:text-stone-100 placeholder:text-stone-300 dark:placeholder:text-stone-600 text-right
                                          caret-stone-900 dark:caret-stone-100"
                                   style="font-size: 2.25rem; height: 3rem; margin: 0;"
                            />
                        </div>
                        @error('amount')
                            <span class="text-rose-600 text-xs mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                {{-- 範本帳戶選擇 --}}
                <div class="grid grid-cols-4 gap-3 mb-4">
                    <div class="col-span-2">
                        <select 
                            wire:model.live="templateFromAccountId"
                            class="select select-bordered border border-opacity-100 w-full h-11 text-sm rounded-xl 
                                   bg-sky-50 border-sky-800 text-stone-900 font-medium
                                   dark:bg-slate-900 dark:border-sky-900 dark:text-sky-800
                                   focus:border-sky-500 dark:focus:border-sky-600 focus:text-stone-900 dark:focus:text-sky-800 focus:outline-none">
                            <option value="" class="bg-white text-stone-400 dark:bg-slate-900 dark:text-stone-500">
                                選擇帳戶
                            </option>
                            @foreach($this->accounts as $account)
                                <option value="{{ $account['id'] }}" class="bg-white text-stone-900 dark:bg-slate-900 dark:text-stone-400">
                                    {{ $account['name'] }}
                                </option>
                            @endforeach
                        </select>
                        @error('templateFromAccountId') 
                            <span class="text-rose-600 text-xs">{{ $message }}</span> 
                        @enderror
                    </div>
                    
                    <div class="col-span-2 flex items-center justify-end gap-2">
                        <span class="text-sm text-stone-500 dark:text-stone-400">餘額</span>
                        <span class="text-sm font-bold text-rose-800 dark:text-rose-400" 
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
                                class="select select-bordered border border-opacity-100 w-full h-11 text-sm rounded-xl 
                                       bg-sky-50 border-sky-800 text-stone-900 font-medium
                                       dark:bg-slate-900 dark:border-sky-900 dark:text-sky-800
                                       focus:border-sky-500 dark:focus:border-sky-600 focus:text-stone-900 dark:focus:text-sky-800 focus:outline-none">
                                <option value="" class="bg-white text-stone-400 dark:bg-slate-900 dark:text-stone-500">
                                    選擇目標帳戶
                                </option>
                                @foreach($this->accounts as $account)
                                    @if(!$templateFromAccountId || $account['id'] !== $templateFromAccountId)
                                        <option value="{{ $account['id'] }}" class="bg-white text-stone-900 dark:bg-slate-900 dark:text-stone-400">
                                            {{ $account['name'] }}
                                        </option>
                                    @endif
                                @endforeach
                            </select>
                            @error('templateToAccountId') 
                                <span class="text-rose-600 text-xs">{{ $message }}</span> 
                            @enderror
                        </div>
                    </div>
                @endif

                {{-- 範本備註（不顯示照片） --}}
                <div class="grid grid-cols-5 gap-3 mb-4">
                    <div class="col-span-5">
                        <div class="relative w-full bg-stone-100/50 dark:bg-slate-900/60 px-3 py-2 rounded-xl border border-stone-200 dark:border-stone-700">
                            <textarea wire:model="templateMemo"
                                      placeholder="請輸入備註"
                                      rows="3"
                                      class="w-full bg-transparent focus:outline-none
                                             text-stone-900 dark:text-stone-100 
                                             placeholder:text-stone-300 dark:placeholder:text-stone-600 
                                             font-medium resize-none text-sm"></textarea>
                        </div>
                    </div>
                </div>

                <x-slot:actions>
                    <div class="grid grid-cols-2 gap-2 w-full pt-3 border-t border-stone-200">
                        <x-button label="取消" type="button" @click="$wire.showTemplateModal = false; $wire.resetTemplateForm()" class="btn-sm rounded-xl text-stone-700 bg-stone-100" />
                        <x-button label="{{ $editingTemplateId ? '更新' : '儲存' }}" type="submit" class="btn-sm rounded-xl text-white bg-emerald-600" />
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
			 size="lg"
			 class="!max-w-md">
		
		<div class="space-y-2 max-h-96 overflow-y-auto">
			@forelse($templates as $template)
				<div class="flex items-center justify-between p-3 bg-stone-50 dark:bg-stone-800 rounded-xl hover:bg-stone-100 dark:hover:bg-stone-700 transition-colors">
					<div class="flex-1 cursor-pointer" wire:click="applyTemplate({{ $template['id'] }})">
						<div class="font-medium text-stone-800 dark:text-stone-200">{{ $template['name'] }}</div>
						<div class="text-sm text-stone-500 dark:text-stone-400">
							{{ $template['type'] }} · {{ number_format($template['amount'], 2) }}
						</div>
					</div>
					<div class="flex gap-1">
						<button wire:click="editTemplate({{ $template['id'] }})" 
								class="p-1.5 text-stone-500 hover:text-stone-700 dark:text-stone-400 dark:hover:text-stone-200">
							✏️
						</button>
						<button wire:click="deleteTemplate({{ $template['id'] }})" 
								wire:confirm="確定要刪除此範本嗎？"
								class="p-1.5 text-rose-500 hover:text-rose-700">
							🗑️
						</button>
					</div>
				</div>
			@empty
				<div class="text-center py-8 text-stone-500 dark:text-stone-400">
					<div class="text-4xl mb-2">📭</div>
					<p>尚無範本</p>
					<button wire:click="openTemplateModalFromList" 
							class="mt-3 text-sm text-sky-600 hover:text-sky-700 dark:text-sky-400">
						建立第一個範本 →
					</button>
				</div>
			@endforelse
		</div>
		
		<x-slot:actions>
			<div class="grid grid-cols-2 gap-2 w-full pt-3 border-t border-stone-200">
				<x-button label="取消" 
						  type="button" 
						  @click="$wire.showTemplateListModal = false" 
						  class="btn-sm rounded-xl text-stone-700 bg-stone-100" />
				<x-button label="新增範本" 
						  type="button" 
						  wire:click="openTemplateModalFromList" 
						  class="btn-sm rounded-xl text-white bg-emerald-600" />
			</div>
		</x-slot:actions>
	</x-modal>
</div>