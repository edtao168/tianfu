{{-- resources/views/includes/_account-card.blade.php --}}

<div class="grid grid-cols-1 gap-4">
    {{-- 這裡必須是跑帳戶分組數據，千萬不能有 $this->backups --}}
    @foreach($group['accounts'] as $account)
        @php
            $typeStyle = config("business.account_types.{$account->type}") ?? config("business.account_types.cash");
        @endphp

        <div wire:click="viewAccountTransactions({{ $account->id }})" 
             class="{{ $typeStyle['bg'] }} {{ $typeStyle['border'] }} p-5 rounded-xl border shadow-sm cursor-pointer transition-all duration-200 active:scale-[0.98] relative overflow-hidden pl-5 flex flex-col justify-between h-28">
            
            <span class="absolute left-0 top-0 bottom-0 w-1.5 {{ $typeStyle['left_bar'] }} opacity-80"></span>
            
            <div class="flex justify-between items-start">
                <div class="flex items-center gap-2">
                    <x-dynamic-component :component="$typeStyle['icon']" class="w-4 h-4 opacity-40 text-base-content" />
                    <span class="font-bold text-base-content text-md">{{ $account->name }}</span>
                </div>
                <span class="px-2 py-0.5 text-[10px] font-extrabold tracking-wider rounded-md {{ $typeStyle['badge'] }}">
                    {{ $typeStyle['name'] }}
                </span>
            </div>
            
            <div class="flex justify-between items-end mt-2">
                <span class="text-[11px] text-gray-400 font-medium">當前餘額</span>
                <span class="font-mono text-xl font-extrabold text-base-content">
                    <span class="text-stone-500 mr-0.5 text-base font-bold">{{ $group['currency_symbol'] }}</span>{{ number_format($account->balance, 2) }}
                </span>
            </div>
        </div>
    @endforeach
</div>