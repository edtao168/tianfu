{{-- resources/views/livewire/finance/partials/_account-selector.blade.php --}}

@props([
    'wireModel' => 'fromAccountId',
    'label' => '帳戶',
    'required' => false,
    'showBalance' => true,
    'balanceKey' => null,
    'excludeAccountId' => null,
    'placeholder' => '選擇帳戶',
    'wrapperClass' => 'grid-cols-4 gap-3 mb-4',
])

<div class="grid {{ $wrapperClass }}">
    <div class="col-span-2">
        <select 
            wire:model.live="{{ $wireModel }}"
            class="select select-bordered border border-opacity-100 w-full h-11 text-sm rounded-xl 
                   bg-sky-50 border-sky-800 text-stone-900 font-medium
                   dark:bg-slate-900 dark:border-sky-900 dark:text-sky-800
                   focus:border-sky-500 dark:focus:border-sky-600 focus:text-stone-900 dark:focus:text-sky-800 focus:outline-none">
            <option value="" class="bg-white text-stone-400 dark:bg-slate-900 dark:text-stone-500">
                {{ $placeholder }}
            </option>
            @foreach($this->accounts as $account)
                @if(!$excludeAccountId || $account['id'] !== $excludeAccountId)
                    <option value="{{ $account['id'] }}" class="bg-white text-stone-900 dark:bg-slate-900 dark:text-stone-400">
                        {{ $account['name'] }}
                    </option>
                @endif
            @endforeach
        </select>
        @error($wireModel) 
            <span class="text-rose-600 text-xs">{{ $message }}</span> 
        @enderror
    </div>
    
    @if($showBalance)
        <div class="col-span-2 flex items-center justify-end gap-2">
            <span class="text-sm text-stone-500 dark:text-stone-400">餘額</span>
            <span class="text-sm font-bold text-rose-800 dark:text-rose-400" 
                  wire:key="{{ $balanceKey ?? 'balance-' . $wireModel . '-' . ($this->$wireModel ?? 'none') }}">
                @php
                    $selectedAccount = collect($this->accounts)->firstWhere('id', $this->$wireModel ?? null);
                @endphp
                @if($selectedAccount)
                    {{ $selectedAccount['currency'] }} 
					{{ number_format($selectedAccount['balance'] ?? 0, 0) }}
                @else
                    0
                @endif
            </span>
        </div>
    @endif
</div>