{{-- resources/views/livewire/finance/contact-index.blade.php --}}
<div class="p-6 max-w-xl mx-auto space-y-6">
    
    {{-- 頁頭區 --}}
    <div class="flex justify-between items-center border-b border-stone-200/60 pb-4">
        <div>
            <h1 class="text-lg font-bold tracking-wider text-stone-800 flex items-center gap-2">
                <span class="w-1.5 h-4 rounded-full bg-stone-600"></span>
                聯絡我
            </h1>
            <p class="text-xs text-stone-400 mt-1">遇到問題或有新功能想法？隨時與我聯繫</p>
        </div>
    </div>

    {{-- 回報表單 --}}
    <x-card class="bg-white/50 border-stone-200/60 shadow-sm">
        <form wire:submit.prevent="submitForm" class="space-y-4">
            
            {{-- 類型選擇 --}}
            <x-radio :options="[
                ['id' => 'suggestion', 'name' => '功能建議與改善'],
                ['id' => 'bug', 'name' => '系統故障 / Bug 回報'],
            ]" label="反饋類型" wire:model="type" />

            {{-- 聯絡 Email --}}
            <x-input label="您的聯絡電子郵件 (選填)" placeholder="example@email.com" wire:model="email" icon="o-envelope" />

            {{-- 詳細內容 --}}
            <x-textarea label="反饋內容詳細說明" placeholder="請填寫您希望新增的功能或遇到的 Bug 細節（至少 5 個字）..." wire:model="content" rows="5" class="text-sm" />

            {{-- 提交按鈕 --}}
            <div class="flex justify-end pt-2">
                <x-button label="送出反饋" icon="o-paper-airplane" type="submit" class="bg-stone-800 border-stone-800 text-stone-50 hover:bg-stone-700 rounded-xl px-5 shadow-sm font-medium" spinner="submitForm" />
            </div>
        </form>
    </x-card>
</div>