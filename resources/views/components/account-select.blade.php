<!-- resources/views/components/account-select.blade.php -->
@props([
    'model',                      // Livewire 的 wire:model 綁定欄位名稱
    'accounts' => [],             // 帳戶清單陣列
    'selectedAccountId' => null,  // 當前選中的帳戶 ID
    'excludeId' => null,          // 需排除的帳戶 ID (轉帳模式下避免選到相同帳戶)
    'showBalance' => true,        // 是否顯示餘額
    'label' => null,              // 標籤文字 (如：轉出帳戶、轉入帳戶)
    'placeholder' => '選擇帳戶'
])

@php
    // 過濾排除的帳戶
    $filteredAccounts = collect($accounts)->reject(fn($acc) => $excludeId && $acc['id'] == $excludeId)->values()->toArray();
    
    // 計算當前選中帳戶
    $selectedAccount = collect($accounts)->firstWhere('id', $selectedAccountId);
@endphp

<div {{ $attributes->merge(['class' => 'w-full']) }}>
    <div class="grid grid-cols-4 gap-3">
        <div class="{{ $showBalance ? 'col-span-2' : 'col-span-4' }} min-w-0">
            @if($label)
                <label class="block text-xs font-bold mb-1 text-base-content">{{ $label }}</label>
            @endif
            
            <x-choices 
                :wire:model.live="$model" 
                :options="$filteredAccounts" 
                single 
                searchable 
                class="rounded-xl truncate"
                :placeholder="$placeholder" 
            />
            
            @error($model) 
                <span class="text-tx-expense text-xs mt-1 block">{{ $message }}</span> 
            @enderror
        </div>

        @if($showBalance)
            <div class="col-span-2 flex items-center justify-end gap-2">
                <span class="text-sm text-base-content/60">餘額</span>
                <span class="text-sm font-bold text-base-content" wire:key="balance-{{ $model }}-{{ $selectedAccountId ?? 'none' }}">
                    @if($selectedAccount) 
                        {{ $selectedAccount['currency'] }} {{ number_format($selectedAccount['balance'] ?? 0, 0) }} 
                    @else 
                        0 
                    @endif
                </span>
            </div>
        @endif
    </div>
</div>