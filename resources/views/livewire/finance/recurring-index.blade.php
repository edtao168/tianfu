<div class="max-w-5xl mx-auto p-4 sm:p-6 space-y-6">
    <!-- 頂部標題與新增按鈕 -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-base-content">定期收支管理</h1>
            <p class="text-sm text-base-content/60 mt-1">管理地價稅、牌照稅、固定房租或定期轉帳等週期性排程項目。</p>
        </div>
        <x-button label="新增定期項目" icon="o-plus" wire:click="openCreateModal" class="btn-green song-interactive" />
    </div>

    <!-- 列表表格 -->
    <div class="bg-base-100 rounded-2xl border border-base-300 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table w-full">
                <thead>
                    <tr class="border-b border-base-300 bg-base-200/50 text-xs uppercase tracking-wider text-base-content/70">
                        <th>名稱 / 類型</th>
                        <th>金額</th>
                        <th>頻率週期</th>
                        <th>下次觸發日</th>
                        <th>狀態</th>
                        <th class="text-right">操作</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-base-200">
                    @forelse($items as $item)
                        @php
                            $typeColor = match($item->type) {
                                'expense' => 'text-tx-expense',
                                'income' => 'text-tx-income',
                                default => 'text-tx-transfer',
                            };
                            $typeLabel = match($item->type) {
                                'expense' => '支出',
                                'income' => '收入',
                                default => '轉帳',
                            };
                            $freqLabel = match($item->frequency) {
                                'daily' => '每 ' . $item->interval . ' 天',
                                'weekly' => '每 ' . $item->interval . ' 週',
                                'monthly' => '每 ' . $item->interval . ' 個月',
                                'yearly' => '每 ' . $item->interval . ' 年',
                                default => $item->frequency,
                            };
                        @endphp
                        <tr class="hover:bg-base-200/40 transition-colors">
                            <td>
                                <div class="font-bold {{ $typeColor }}">{{ $item->name }}</div>
                                <div class="text-xs text-base-content/60">{{ $typeLabel }} · 備註：{{ $item->memo ?: '無' }}</div>
                            </td>
                            <td class="font-bold {{ $typeColor }}">
                                {{ number_format($item->amount, 2) }}
                            </td>
                            <td>
                                <span class="badge badge-sm badge-ghost">{{ $freqLabel }}</span>
                            </td>
                            <td>
                                <div class="text-sm font-medium">{{ $item->next_due_date?->format('Y-m-d') }}</div>
                                @if($item->end_date)
                                    <div class="text-[10px] text-base-content/50">結束於：{{ $item->end_date->format('Y-m-d') }}</div>
                                @endif
                            </td>
                            <td>
                                @if($item->is_active)
                                    <span class="badge badge-success badge-sm">啟用中</span>
                                @else
                                    <span class="badge badge-error badge-sm">已停用</span>
                                @endif
                            </td>
                            <td class="text-right space-x-1">
                                <x-button icon="o-pencil" wire:click="edit({{ $item->id }})" class="btn-sm btn-ghost song-interactive" title="編輯" />
                                <x-button icon="o-trash" wire:click="delete({{ $item->id }})" wire:confirm="確定要刪除這個定期項目嗎？" class="btn-sm btn-ghost text-error song-interactive" title="刪除" />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-12 text-base-content/50">
                                <x-icon name="o-calendar" class="w-12 h-12 mx-auto mb-2 opacity-40" />
                                <p>目前尚無設定任何定期收支項目</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-base-300">
            {{ $items->links() }}
        </div>
    </div>

    <!-- 新增/編輯 Modal -->
    <x-modal wire:model="showModal" title="{{ $editingId ? '編輯定期收支' : '新增定期收支' }}" separator persistent size="lg">
        <x-form wire:submit="save" class="space-y-4">
            <x-input label="名稱 (例如：地價稅、車輛牌照稅)" wire:model="name" placeholder="請輸入項目名稱" required />

            <div class="grid grid-cols-3 gap-2">
                @foreach(['expense' => '支出', 'income' => '收入', 'transfer' => '轉帳'] as $val => $lbl)
                    <button type="button" wire:click="$set('type', '{{ $val }}')" 
                            class="py-2 text-sm font-bold rounded-xl transition-all {{ $type === $val ? 'bg-base-content text-base-100' : 'bg-base-200 hover:bg-base-300' }}">
                        {{ $lbl }}
                    </button>
                @endforeach
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <x-select label="扣款/存入帳戶" wire:model="fromAccountId" :options="$this->accounts" option-value="id" option-label="name" required />
                
                @if($type === 'transfer')
                    <x-select label="轉入帳戶" wire:model="toAccountId" :options="$this->accounts" option-value="id" option-label="name" required />
                @else
                    <x-select label="收支分類" wire:model="categoryId" :options="$this->categories" option-value="id" option-label="name" required />
                @endif
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <x-input label="金額 (DECIMAL)" wire:model="amount" placeholder="0.00" icon="o-currency-dollar" required />
                <x-datetime label="下次預計發生日" wire:model="nextDueDate" icon="o-calendar" required />
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <x-select label="週期頻率" wire:model="frequency" :options="[
                    ['id' => 'monthly', 'name' => '每月'],
                    ['id' => 'yearly', 'name' => '每年'],
                    ['id' => 'weekly', 'name' => '每週'],
                    ['id' => 'daily', 'name' => '每天']
                ]" option-value="id" option-label="name" required />

                <x-input label="間隔倍數 (每 N 周期)" wire:model="interval" type="number" min="1" required />
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <x-datetime label="結束日期 (選填，無限期留空)" wire:model="endDate" icon="o-calendar" />
                <div class="flex items-center pt-6">
                    <x-checkbox label="啟用此定期排程" wire:model="isActive" />
                </div>
            </div>

            <x-textarea label="備註說明" wire:model="memo" placeholder="例如：每年11月開徵的地價稅" rows="2" />

            <x-slot:actions>
                <x-button label="取消" @click="$wire.showModal = false" class="btn-ghost" />
                <x-button label="儲存" type="submit" class="btn-green song-interactive" spinner="save" />
            </x-slot:actions>
        </x-form>
    </x-modal>
</div>