<div class="max-w-5xl mx-auto p-4 sm:p-6 space-y-6">
    <!-- 頂部標題與新增按鈕 -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-base-content">定期收支管理</h1>
            <p class="text-sm text-base-content/60 mt-1">管理地價稅、牌照稅、固定房租或定期轉帳等週期性排程項目。</p>
        </div>
        <x-button label="新增定期項目" icon="o-plus" wire:click="openCreateModal" class="btn-green song-interactive" />
    </div>

    <!-- 列表（響應式卡片 grid） -->
    <div class="space-y-4">
        @forelse($items as $item)
            @php
                $typeColor = match($item->type) {
                    'expense' => 'text-tx-expense',
                    'income'  => 'text-tx-income',
                    default   => 'text-tx-transfer',
                };
                $typeBadge = match($item->type) {
                    'expense' => 'badge-tx-expense',
                    'income'  => 'badge-tx-income',
                    default   => 'badge-tx-transfer',
                };
                $typeLabel = match($item->type) {
                    'expense' => '支出',
                    'income'  => '收入',
                    default   => '轉帳',
                };
                $freqLabel = match($item->frequency) {
                    'daily'   => '每 ' . $item->interval . ' 天',
                    'weekly'  => '每 ' . $item->interval . ' 週',
                    'monthly' => '每 ' . $item->interval . ' 個月',
                    'yearly'  => '每 ' . $item->interval . ' 年',
                    default   => $item->frequency,
                };
            @endphp

            <div class="bg-base-100 rounded-2xl border border-base-300 shadow-sm p-4 sm:p-5 transition-colors hover:bg-base-200/30">
                {{-- 第一列：名稱 + 狀態 + 操作 --}}
                <div class="flex items-start justify-between gap-3 mb-3">
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="badge badge-sm {{ $typeBadge }}">{{ $typeLabel }}</span>
                            @if($item->is_active)
                                <span class="badge badge-success badge-sm">啟用中</span>
                            @else
                                <span class="badge badge-error badge-sm">已停用</span>
                            @endif
                        </div>
                        <div class="font-bold text-base mt-1.5 truncate {{ $typeColor }}">{{ $item->name }}</div>
                        <div class="text-xs text-base-content/60 mt-0.5 truncate">
                            備註：{{ $item->memo ?: '無' }}
                        </div>
                    </div>
                    <div class="flex items-center gap-1 shrink-0">
                        <x-button icon="o-pencil" wire:click="edit({{ $item->id }})" class="btn-sm btn-ghost song-interactive" title="編輯" />
                        <x-button icon="o-trash" wire:click="delete({{ $item->id }})" wire:confirm="確定要刪除這個定期項目嗎？" class="btn-sm btn-ghost text-error song-interactive" title="刪除" />
                    </div>
                </div>

                {{-- 第二列：金額 / 頻率 / 下次觸發日（三欄 grid，手機也清楚） --}}
                <div class="grid grid-cols-3 gap-3 pt-3 border-t border-base-200">
                    <div>
                        <div class="text-[10px] uppercase tracking-wider text-base-content/50 mb-0.5">金額</div>
                        <div class="font-bold text-sm sm:text-base {{ $typeColor }} truncate">
                            {{ number_format($item->amount, 2) }}
                        </div>
                    </div>
                    <div>
                        <div class="text-[10px] uppercase tracking-wider text-base-content/50 mb-0.5">頻率</div>
                        <div class="text-sm font-medium truncate">{{ $freqLabel }}</div>
                    </div>
                    <div>
                        <div class="text-[10px] uppercase tracking-wider text-base-content/50 mb-0.5">下次觸發</div>
                        <div class="text-sm font-medium truncate">{{ $item->next_due_date?->format('Y-m-d') }}</div>
                        @if($item->end_date)
                            <div class="text-[10px] text-base-content/50 truncate">結束：{{ $item->end_date->format('Y-m-d') }}</div>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="bg-base-100 rounded-2xl border border-base-300 shadow-sm text-center py-12 text-base-content/50">
                <x-icon name="o-calendar" class="w-12 h-12 mx-auto mb-2 opacity-40" />
                <p>目前尚無設定任何定期收支項目</p>
            </div>
        @endforelse

        {{-- 分頁 --}}
        <div class="pt-2">
            {{ $items->links() }}
        </div>
    </div>

    <!-- 新增/編輯 Modal -->
	<x-modal wire:model="showModal" title="{{ $editingId ? '編輯定期收支' : '新增定期收支' }}" separator persistent size="lg">
		@if($showCategoryPicker)
			{{-- 類別選擇器（共用元件） --}}
			<x-category-picker
				:type="$type"
				:categories="$this->categories"
				:selected-id="$categoryId"
				pick-action="selectCategory"
				close-action="closeCategoryPicker"
				empty-variant="emoji"
			/>
		@else
			{{-- 使用者表單：外層設定 flex 讓內部可滾動 --}}
			<x-form wire:submit="save" class="flex flex-col h-full">
				
				<!-- 【可捲動的表單內容區】 -->
				<div class="overflow-y-auto max-h-[60vh] sm:max-h-[70vh] pr-1 space-y-4">
					{{-- 類型切換 --}}
					@php
						$recurringTypeClasses = [
							'expense'  => 'bg-tx-expense text-white shadow-rose-900/20',
							'income'   => 'bg-tx-income text-white shadow-emerald-900/20',
							'transfer' => 'bg-tx-transfer text-white shadow-sky-900/20',
						];
					@endphp
					<div class="grid grid-cols-3 gap-1.5 mb-5">
						@foreach(['expense' => '支出', 'income' => '收入', 'transfer' => '轉帳'] as $value => $label)
							<button type="button"
									wire:click="$set('type', '{{ $value }}')"
									class="py-2.5 text-sm font-bold rounded-xl transition-all duration-200 song-interactive
										{{ $type === $value
											? $recurringTypeClasses[$value] . ' shadow-md'
											: 'bg-base-200 hover:bg-base-300 text-base-content' }}">
								{{ $label }}
							</button>
						@endforeach
					</div>

					{{-- 名稱 --}}
					<x-input
						label="名稱"
						wire:model="name"
						placeholder="例如：地價稅、車輛牌照稅"
						icon="o-bookmark"
						inline
						required
					/>

					{{-- 類別 + 金額 --}}
					<div class="grid grid-cols-6 gap-3 mb-4">
						<div class="col-span-2">
							@if($type !== 'transfer')
								<x-category-display-card
									variant="square"
									:category="$this->selectedCategory"
									:type="$type"
									click-action="openCategoryPicker"
								/>
								@error('categoryId') <span class="text-tx-expense text-xs mt-1 block">{{ $message }}</span> @enderror
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

						@php
							$sign = match($type) {
								'expense' => '−',
								'income'  => '+',
								default   => '',
							};
							$signColor = match($type) {
								'expense'  => 'text-tx-expense',
								'income'   => 'text-tx-income',
								'transfer' => 'text-tx-transfer',
								default    => 'text-base-content/60',
							};
						@endphp
						<div class="col-span-4 flex items-center">
							<div class="input w-full flex items-center gap-1 px-3 py-2 h-auto">
								<span class="text-2xl font-bold shrink-0 {{ $signColor }}">{{ $sign }}</span>
								<input type="text"
									   inputmode="decimal"
									   wire:model.live.debounce.500ms="amount"
									   x-on:focus="if ($el.value === '0' || $el.value === '0.00' || parseFloat($el.value) === 0) { $el.value = ''; $wire.set('amount', ''); }"
									   placeholder="0"
									   autocomplete="off"
									   class="grow min-w-0 bg-transparent outline-none text-right font-bold placeholder:text-base-content/40 caret-base-content {{ $signColor }}"
									   style="font-size: 2.25rem; height: 3rem; margin: 0;"
								/>
							</div>
							@error('amount')
								<span class="text-tx-expense text-xs mt-1 block">{{ $message }}</span>
							@enderror
						</div>
					</div>

					{{-- 帳戶選擇 --}}
					<div class="space-y-3 mb-4">
						<div class="min-w-0">
							<x-account-select
								model="fromAccountId"
								label="扣款/存入帳戶"
								:accounts="$this->accounts"
								:selectedAccountId="$fromAccountId"
							/>
						</div>

						@if($type === 'transfer')
							<div class="min-w-0">
								<x-account-select
									model="toAccountId"
									label="轉入帳戶"
									placeholder="選擇目標帳戶"
									:accounts="$this->accounts"
									:selectedAccountId="$toAccountId"
									:excludeId="$fromAccountId"
									:showBalance="false"
								/>
							</div>
						@endif
					</div>

					{{-- 頻率 + 間隔 --}}
					<div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
						<x-select label="週期頻率" wire:model="frequency" :options="[
							['id' => 'monthly', 'name' => '每月'],
							['id' => 'yearly',  'name' => '每年'],
							['id' => 'weekly',  'name' => '每週'],
							['id' => 'daily',   'name' => '每天'],
						]" option-value="id" option-label="name" required />

						<x-input label="間隔倍數 (每 N 周期)" wire:model="interval" type="number" min="1" required />
					</div>

					{{-- 日期設定 --}}
					<div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
						<div>
							<x-datetime label="下次觸發日" wire:model="nextDueDate" icon="o-calendar" required />
							<p class="text-xs text-base-content/60 mt-1.5 flex items-start gap-1">
								<x-icon name="o-information-circle" class="w-3.5 h-3.5 mt-0.5 shrink-0" />
								<span>每筆定期收支會依此日期自動執行，並依「週期頻率」推算下一次。</span>
							</p>
						</div>
						<x-datetime label="結束日期 (選填)" wire:model="endDate" icon="o-calendar" />
					</div>

					{{-- 啟用 --}}
					<div class="mb-4">
						<x-checkbox label="啟用此定期排程" wire:model="isActive" />
					</div>

					{{-- 備註 --}}
					<div class="grid grid-cols-5 gap-3 mb-4">
						<div class="col-span-5">
							<textarea wire:model="memo"
									  placeholder="例如：每年11月開徵的地價稅"
									  rows="3"
									  class="textarea w-full resize-none placeholder:font-medium"></textarea>
						</div>
					</div>
				</div>

				<!-- 【固定在底部的動作按鈕區】 -->
				<x-slot:actions>
					<div class="grid grid-cols-2 gap-2 w-full pt-3 border-t border-[var(--song-border)] bg-base-100">
						<x-button label="取消" type="button" @click="$wire.showModal = false" class="btn-sm rounded-xl bg-base-200 hover:bg-base-300 text-base-content song-interactive" />
						<x-button label="儲存" type="submit" class="btn-sm rounded-xl btn-green song-interactive" spinner="save" />
					</div>
				</x-slot:actions>
			</x-form>
		@endif
	</x-modal>
</div>