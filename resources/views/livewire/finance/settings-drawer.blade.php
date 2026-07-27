<!-- filepath: resources/views/livewire/finance/settings-drawer.blade.php -->
<div>
    <x-drawer wire:model="isOpen" title="系統設置" subtitle="帳戶與偏好設定" right class="w-11/12 max-w-[380px] bg-stone-50/95 backdrop-blur-xl">
        {{-- Header 內容在 Mary UI 已經透過 title / subtitle 自動產生，若要加自訂圖示可用 slot --}}
        
        {{-- Menu List --}}
        <div class="space-y-3 py-2">
            
            {{-- 成員管理 --}}
            <a href="{{ route('finance.partners') }}" 
               wire:click="close"
               class="flex w-full group items-center gap-4 px-4 py-3.5 rounded-xl hover:bg-white/70 transition-all duration-200 border border-transparent hover:border-stone-200/60">
                <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-teal-400 to-emerald-500 flex items-center justify-center shadow-sm group-hover:shadow-md transition-shadow">
                    <x-heroicon-o-users class="w-5 h-5 text-white" />
                </div>
                <div class="flex-1 text-left">
                    <div class="font-semibold text-stone-700 group-hover:text-stone-900">成員管理</div>
                    <div class="text-xs text-stone-400">設定家庭使用者與個人載具</div>
                </div>
                <x-heroicon-o-chevron-right class="w-4 h-4 text-stone-300 group-hover:text-stone-500 transition-colors" />
            </a>

            {{-- 分類管理 --}}
            <button wire:click="openCategoryManager" 
                    class="w-full group flex items-center gap-4 px-4 py-3.5 rounded-xl hover:bg-white/70 transition-all duration-200 border border-transparent hover:border-stone-200/60">
                <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-blue-400 to-blue-500 flex items-center justify-center shadow-sm group-hover:shadow-md transition-shadow">
                    <x-heroicon-o-tag class="w-5 h-5 text-white" />
                </div>
                <div class="flex-1 text-left">
                    <div class="font-semibold text-stone-700 group-hover:text-stone-900">分類管理</div>
                    <div class="text-xs text-stone-400">編輯收支類別與圖標</div>
                </div>
                <x-heroicon-o-chevron-right class="w-4 h-4 text-stone-300 group-hover:text-stone-500 transition-colors" />
            </button>
            
            {{-- 數據備份 --}}
            <a href="{{ route('finance.backup') }}" 
               wire:click="close"
               class="flex w-full group items-center gap-4 px-4 py-3.5 rounded-xl hover:bg-white/70 transition-all duration-200 border border-transparent hover:border-stone-200/60">
                <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-emerald-400 to-emerald-500 flex items-center justify-center shadow-sm group-hover:shadow-md transition-shadow">
                    <x-heroicon-o-arrow-down-tray class="w-5 h-5 text-white" />
                </div>
                <div class="flex-1 text-left">
                    <div class="font-semibold text-stone-700 group-hover:text-stone-900">數據備份</div>
                    <div class="text-xs text-stone-400">匯出 / 匯入資料</div>
                </div>
                <x-heroicon-o-chevron-right class="w-4 h-4 text-stone-300 group-hover:text-stone-500 transition-colors" />
            </a>

            {{-- 關於 --}}
            <a href="{{ route('finance.about') }}" 
               wire:click="close"
               class="flex w-full group items-center gap-4 px-4 py-3.5 rounded-xl hover:bg-white/70 transition-all duration-200 border border-transparent hover:border-stone-200/60">
                <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-indigo-400 to-indigo-500 flex items-center justify-center shadow-sm group-hover:shadow-md transition-shadow">
                    <x-heroicon-o-information-circle class="w-5 h-5 text-white" />
                </div>
                <div class="flex-1 text-left">
                    <div class="font-semibold text-stone-700 group-hover:text-stone-900">關於</div>
                    <div class="text-xs text-stone-400">版本資訊與使用條款</div>
                </div>
                <x-heroicon-o-chevron-right class="w-4 h-4 text-stone-300 group-hover:text-stone-500 transition-colors" />
            </a>

            {{-- 聯絡我 --}}
            <a href="{{ route('finance.contact') }}" 
               wire:click="close"
               class="flex w-full group items-center gap-4 px-4 py-3.5 rounded-xl hover:bg-white/70 transition-all duration-200 border border-transparent hover:border-stone-200/60">
                <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-rose-400 to-rose-500 flex items-center justify-center shadow-sm group-hover:shadow-md transition-shadow">
                    <x-heroicon-o-envelope class="w-5 h-5 text-white" />
                </div>
                <div class="flex-1 text-left">
                    <div class="font-semibold text-stone-700 group-hover:text-stone-900">聯絡我</div>
                    <div class="text-xs text-stone-400">問題回報與建議</div>
                </div>
                <x-heroicon-o-chevron-right class="w-4 h-4 text-stone-300 group-hover:text-stone-500 transition-colors" />
            </a>

        </div>

        {{-- Footer --}}
        <x-slot:actions>
            <form method="POST" action="{{ route('logout') }}" class="w-full">
                @csrf
                <button type="submit" class="w-full flex items-center justify-center gap-2 py-3 rounded-xl bg-stone-200/50 hover:bg-red-50 hover:text-red-600 text-stone-600 font-medium transition-all duration-200 active:scale-[0.99]">
                    <x-heroicon-o-arrow-left-on-rectangle class="w-5 h-5" />
                    <span>安全登出</span>
                </button>
            </form>
        </x-slot:actions>
    </x-drawer>
</div>