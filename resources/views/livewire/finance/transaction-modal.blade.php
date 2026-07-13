{{-- resources/views/livewire/finance/transaction-modal.blade.php --}}
<div>
    {{-- 主要記帳 Modal --}}
    <x-modal wire:model="showTransactionModal" 
             title="{{ $transactionId ? '修改記錄' : '新增記錄' }}" 
             separator 
             persistent 
             size="lg"
             class="!max-w-md"
			 x-on:click.stop>		 
        
        {{-- 類型選擇：範本 | 支出 | 收入 | 轉帳 --}}
        <div class="grid grid-cols-4 gap-1.5 mb-5">
            <button type="button"
                    @click="$wire.openTemplateList()"
                    class="py-2.5 text-sm font-bold rounded-xl transition-all duration-200 
                           bg-stone-100 text-stone-700 hover:bg-stone-200 
                           dark:bg-stone-800 dark:text-stone-100 dark:hover:bg-stone-700">
                <span class="mr-1">📋</span> 範本
            </button>
            
            @foreach(['expense' => '支出', 'income' => '收入', 'transfer' => '轉帳'] as $value => $label)
                @php
                    $colors = [
                        'expense' => ['bg' => 'bg-rose-600', 'shadow' => 'shadow-rose-900/20', 'text' => 'text-rose-600'],
                        'income' => ['bg' => 'bg-emerald-600', 'shadow' => 'shadow-emerald-900/20', 'text' => 'text-emerald-600'],
                        'transfer' => ['bg' => 'bg-sky-600', 'shadow' => 'shadow-sky-900/20', 'text' => 'text-sky-600']
                    ];
                @endphp
                <button type="button"
                        wire:click="$set('type', '{{ $value }}'); $set('categoryId', null);"
                        class="py-2.5 text-sm font-bold rounded-xl transition-all duration-200
                            {{ $type === $value 
                                ? $colors[$value]['bg'] . ' text-white shadow-md ' . $colors[$value]['shadow']
                                : 'bg-stone-50 text-stone-600 hover:bg-stone-100 dark:bg-stone-800 dark:text-stone-400 dark:hover:bg-stone-700' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>

        <x-form wire:submit="saveTransaction">
            {{-- 類別 + 金額（正方形類別 + 金額） --}}
            <div class="grid grid-cols-6 gap-3 mb-4">
                {{-- 左側：類別 - 定窯牙白風格（提高對比，去除大面積透明度） --}}
				<div class="col-span-2">
					@if($type !== 'transfer')
						<div class="aspect-square bg-stone-50 rounded-2xl border border-stone-300
									hover:border-stone-400 transition-all cursor-pointer 
									flex flex-col items-center justify-center p-1 relative overflow-hidden
									dark:bg-stone-800 dark:border-stone-700"
							 @click="$wire.openCategoryPicker()">
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
						{{-- 轉帳模式 - 官窯粉青風格 --}}
						<div class="aspect-square bg-teal-50 rounded-2xl border border-teal-200 
									flex flex-col items-center justify-center
									dark:bg-teal-950 dark:border-teal-800">
							<div class="text-3xl mb-1 flex justify-center">
								<x-icon name="o-arrows-right-left" class="w-10 h-10 text-teal-600 dark:text-teal-400" />
							</div>
							<div class="text-xs font-bold text-teal-700 dark:text-teal-400">轉帳</div>
						</div>
					@endif
				</div>

                {{-- 右側：金額 - 加深高亮文字對比 --}}
				<div class="col-span-4 flex items-center">
					<div class="relative w-full flex items-center bg-stone-100/50 dark:bg-slate-900/60 px-3 py-2 rounded-xl border border-stone-200 dark:border-stone-700">
						{{-- 左側正負號標記 --}}
						<span class="text-2xl font-bold shrink-0 mr-1
								   {{ $type === 'expense' ? 'text-rose-500 dark:text-rose-400' : 
									  ($type === 'income' ? 'text-emerald-500 dark:text-emerald-400' : 
									  'text-stone-500 dark:text-stone-400') }}">
							{{ $type === 'expense' ? '−' : ($type === 'income' ? '+' : '') }}
						</span>
						
						{{-- 輸入框 --}}
						<input type="number"
							   step="0.01"
							   min="0"
							   wire:model.live="amount"
							   placeholder="0"
							   class="w-full pl-1 pr-6 font-bold bg-transparent focus:outline-none
									  text-stone-900 dark:text-stone-100 placeholder:text-stone-300 dark:placeholder:text-stone-600 text-right
									  leading-none"
							   style="font-size: 2.25rem; height: 3rem; -webkit-appearance: none; margin: 0;">
					</div>
					@error('amount') <span class="text-rose-600 text-xs mt-1 block">{{ $message }}</span> @enderror
				</div>
            </div>

            {{-- 日期（左右箭頭） --}}
            <div class="flex items-center justify-between mb-4 px-2 py-1 rounded-xl bg-stone-100 dark:bg-stone-800">
                <button type="button" 
                        wire:click="changeDate(-1)"
                        class="p-2 hover:bg-stone-200 dark:hover:bg-stone-700 rounded-full transition-colors">
                    <svg class="w-5 h-5 text-stone-500 dark:text-stone-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </button>
                
                <span class="text-base font-bold text-stone-800 dark:text-stone-200 tracking-wider">
                    {{ \Carbon\Carbon::parse($recordedAt)->format('Y/m/d') }}
                </span>
                
                <button type="button" 
                        wire:click="changeDate(1)"
                        class="p-2 hover:bg-stone-200 dark:hover:bg-stone-700 rounded-full transition-colors">
                    <svg class="w-5 h-5 text-stone-500 dark:text-stone-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
            </div>

            {{-- 帳戶 + 餘額 - 汝窯天青風格 --}}
<div class="grid grid-cols-5 gap-3 mb-4">
    <div class="col-span-3">
        <select wire:model.live="fromAccountId" 
                class="select select-bordered border border-opacity-100 w-full h-11 text-sm rounded-xl 
                       bg-sky-50 border-sky-800 text-stone-900 font-medium
                       dark:bg-slate-900 dark:border-sky-900 dark:text-sky-800
                       focus:border-sky-500 dark:focus:border-sky-600 focus:text-stone-900 dark:focus:text-sky-800 focus:outline-none">
            <option value="" class="bg-white text-stone-400 dark:bg-slate-900 dark:text-stone-500">選擇帳戶</option>
            @foreach($this->accounts as $account)
                <option value="{{ $account['id'] }}" class="bg-white text-stone-900 dark:bg-slate-900 dark:text-stone-400">
                    {{ $account['name'] }}
                </option>
            @endforeach
        </select>
        @error('fromAccountId') <span class="text-rose-600 text-xs">{{ $message }}</span> @enderror
    </div>
    
    <div class="col-span-2 flex items-center justify-end gap-2">
        <span class="text-sm text-stone-500 dark:text-stone-400">餘額</span>
        {{-- 使用 wire:key 強制重新渲染 --}}
        <span class="text-sm font-bold text-rose-800 dark:text-rose-400" wire:key="balance-{{ $fromAccountId }}">
            @php
                $selectedAccount = collect($this->accounts)->firstWhere('id', $fromAccountId);
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

            {{-- 轉帳目標帳戶 - 千山青綠風格 --}}
			@if($type === 'transfer')
				<div class="grid grid-cols-5 gap-3 mb-4">
					<div class="col-span-3 col-start-1">
						<select wire:model="toAccountId" 
								class="select select-bordered w-full h-11 text-sm rounded-xl 
									   bg-emerald-50 border-emerald-300 text-stone-800
									   dark:bg-slate-900 dark:border-emerald-800 dark:text-stone-100
									   focus:border-emerald-500 dark:focus:border-emerald-600">
							<option value="" class="bg-white dark:bg-slate-900 text-stone-400">選擇目標帳戶</option>
							@foreach($this->accounts as $account)
								@if($account['id'] !== $fromAccountId)
									<option value="{{ $account['id'] }}" class="bg-white dark:bg-slate-900 text-stone-800 dark:text-stone-400">
										{{ $account['name'] }}
									</option>
								@endif
							@endforeach
						</select>
						@error('toAccountId') <span class="text-rose-600 text-xs">{{ $message }}</span> @enderror
					</div>
				</div>
			@endif

            {{-- 照片 + 備註（3:2 比例）- 修正輸入框手機端過黑/過淡問題 --}}
            <div class="grid grid-cols-5 gap-3 mb-5">
                {{-- 照片 --}}
                <div class="col-span-2">
                    <div class="aspect-square bg-stone-50 rounded-2xl border-2 border-dashed border-stone-300 
                                hover:border-stone-400 transition-all cursor-pointer 
                                flex flex-col items-center justify-center
                                dark:bg-stone-800 dark:border-stone-700 dark:hover:border-stone-600">
                        <svg class="w-8 h-8 text-stone-400 dark:text-stone-500 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <span class="text-xs text-stone-500 dark:text-stone-400 font-medium">照片</span>
                    </div>
                </div>

                {{-- 備註 --}}
                <div class="col-span-3">
                    <textarea wire:model="memo"
						placeholder="請輸入備註"
						class="textarea w-full h-full min-h-[80px] rounded-2xl text-sm p-3 
							 bg-stone-50 border border-stone-300 text-stone-900 placeholder:text-stone-400 font-medium
							 dark:bg-slate-900 dark:border-stone-700 dark:text-white dark:placeholder:text-stone-500
							 focus:border-stone-400 dark:focus:border-stone-500 
							 focus:text-stone-900 dark:focus:text-stone-400 focus:bg-white dark:focus:bg-slate-950
							 resize-none"
						style="height: 100%;"></textarea>
                </div>
            </div>

            {{-- 操作按鈕 --}}
            <x-slot:actions>
				<div class="grid grid-cols-2 gap-2 w-full pt-3 border-t border-stone-200 dark:border-stone-700">
					<x-button label="返回" 
						type="button"
						:link="route('finance.transactions')"
						class="rounded-xl font-medium text-stone-700 bg-stone-100 hover:bg-stone-200 dark:bg-stone-800 dark:text-stone-300 dark:hover:bg-stone-700 border-none" />
					
                    @if(!$transactionId)
						<x-button label="再記一筆" 
							type="button"
							wire:click="saveAndKeepOpen"
							class="rounded-xl font-medium text-stone-700 bg-stone-100 hover:bg-stone-200 dark:bg-stone-800 dark:text-stone-300 dark:hover:bg-stone-700 border-none"
							spinner="saveAndKeepOpen" />
					@endif
					
					<x-button label="存為範本" 
							  type="button"
							  @click="$wire.showTemplateModal = true"
							  class="rounded-xl font-medium text-sky-700 bg-sky-50 hover:bg-sky-100 dark:bg-sky-950 dark:text-sky-300 dark:hover:bg-sky-900 border-none" />

					<x-button label="儲存" 
							  type="submit" 
							  class="rounded-xl font-bold text-white bg-emerald-600 hover:bg-emerald-700 dark:bg-emerald-700 dark:hover:bg-emerald-600 border-none" 
							  spinner="saveTransaction" />
				</div>
			</x-slot:actions>
        </x-form>
    </x-modal>

    {{-- 類別選擇器 Modal --}}
	<x-modal wire:model="showCategoryPicker" 
			 title="選擇類別" 
			 separator 
			 size="lg" 
			 class="!max-w-md">
		<div class="space-y-4 max-h-[60vh] overflow-y-auto">
			@php
				$filteredCats = $this->filteredCategories;
                $themeColor = $type === 'expense' ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400';
                $barColor = $type === 'expense' ? 'bg-rose-600' : 'bg-emerald-600';
			@endphp

			@if($filteredCats->isNotEmpty())
				<div>
					<div class="text-xs font-bold {{ $themeColor }} mb-3 tracking-wider flex items-center gap-2">
						<span class="w-1 h-4 {{ $barColor }} rounded-full"></span>
						{{ $type === 'expense' ? '支出類別' : '收入類別' }}
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

    {{-- 範本列表 Modal --}}
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
                      @click="$wire.showTemplateModal = true; $wire.showTemplateListModal = false" 
                      class="btn-primary bg-sky-600 hover:bg-sky-700 text-white border-none shadow-md" />
        </x-slot:actions>
    </x-modal>

    {{-- 儲存範本 Modal --}}
    <x-modal wire:model="showTemplateModal" 
             title="{{ $editingTemplateId ? '編輯範本' : '儲存範本' }}" 
             separator 
             class="!max-w-md">
        <x-form wire:submit="saveAsTemplate">
            <x-input label="範本名稱" 
                     wire:model="templateName" 
                     placeholder="例如：早餐、薪資" 
                     class="input-bordered border-stone-300 dark:border-stone-700
                            focus:border-stone-500 dark:focus:border-stone-500
                            bg-stone-50 dark:bg-slate-900 text-stone-800 dark:text-stone-100" 
                     required />
            <x-slot:actions>
                <x-button label="取消" 
                          @click="$wire.showTemplateModal = false" 
                          class="btn-ghost text-stone-500 hover:text-stone-700 dark:text-stone-400 dark:hover:text-stone-200" />
                <x-button label="儲存" 
                          type="submit" 
                          class="btn-primary bg-emerald-600 hover:bg-emerald-700 text-white border-none shadow-md" 
                          spinner="saveAsTemplate" />
            </x-slot:actions>
        </x-form>
    </x-modal>
</div>