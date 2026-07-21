{{-- resources/views/livewire/finance/transaction-modal.blade.php --}}

<div>
    {{-- ==================== 主要記帳 Modal ==================== --}}
    <x-modal wire:model="showTransactionModal" 
             title="{{ $transactionId ? '修改記錄' : '新增記錄' }}" 
             separator 
             persistent 
             size="lg"
             class="!max-w-md" {{-- 移除任何 min-h 與 relative，確保 Modal 內部按鈕完好 --}}
             x-on:click.stop>        
        
        {{-- ================ 核心切換邏輯 ================ --}}
        @if($showCategoryPicker && $categoryPickerReturnTo === 'transaction')
            {{-- 1. 當需要顯示 Picker 時，這裡會「瞬間替換」表單，高度會自動撐滿 Modal 內容區 --}}
            <div class="w-full flex flex-col" style="height: 420px;"> {{-- 給 Picker 一個舒適的固定內容高度 --}}
                @include('livewire.finance.partials._category-picker')
            </div>
        @else
			{{-- 類型選擇（含範本按鈕） --}}
			@include('livewire.finance.partials._type-buttons', [
				'model' => 'type',
				'columns' => 'grid-cols-4',
				'showTemplate' => true,
				'templateButton' => '範本',
				'templateAction' => 'openTemplateList',
			])

            {{-- 2. 原本的表單 Form 內容 --}}
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
                            wire:click="$set('showTransactionModal', false)"
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
            {{-- 範本 Modal 內的類別選擇器瞬間替換 --}}
            <div class="w-full flex flex-col" style="height: 420px;">
                @include('livewire.finance.partials._category-picker')
            </div>
        @else
            <x-form wire:submit="saveAsTemplate">
                @include('livewire.finance.partials._type-buttons', [
                    'model' => 'templateType',
                    'columns' => 'grid-cols-3',
                    'showTemplate' => false,
                ])

                <x-input label="範本名稱" 
                         wire:model="templateName" 
                         placeholder="例如：早餐、薪資" 
                         class="input-bordered border-stone-300 dark:border-stone-700 focus:border-stone-500 bg-stone-50 text-stone-800 mb-4" 
                         required />

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
                        'editable' => true,
                        'showSign' => true,
                    ])
                </div>

                @include('livewire.finance.partials._account-selector', [
                    'wireModel' => 'templateFromAccountId',
                    'label' => '帳戶',
                    'required' => true,
                ])

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

                @include('livewire.finance.partials._photo-memo', [
                    'memoModel' => 'templateMemo',
                    'memoPlaceholder' => '請輸入備註',
                    'class' => 'mb-4',
                    'showPhoto' => false,
                    'memoRows' => 3,
                ])

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