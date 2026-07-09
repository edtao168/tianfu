<div>
    @if($isOpen)
        <div class="fixed inset-0 z-50 bg-black/50 backdrop-blur-sm transition-opacity duration-300"
             wire:click="close">
        </div>
    @endif

    <div class="fixed right-0 top-0 h-full z-50 transition-transform duration-300 ease-out"
         :class="$wire.isOpen ? 'translate-x-0' : 'translate-x-full'"
         style="width: 85%; max-width: 380px;">
        
        <div class="h-full bg-stone-50/95 backdrop-blur-xl shadow-2xl flex flex-col">
            
            {{-- Header --}}
            <div class="flex items-center justify-between px-6 py-5 border-b border-stone-200/60 bg-white/50">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-gradient-to-br from-teal-500 to-emerald-500 flex items-center justify-center shadow-md">
                        <x-heroicon-o-cog-6-tooth class="w-5 h-5 text-white" />
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-stone-800">系統設置</h2>
                        <p class="text-xs text-stone-400">帳戶與偏好設定</p>
                    </div>
                </div>
                <button wire:click="close" class="p-2 rounded-full hover:bg-stone-100 transition-colors">
                    <x-heroicon-o-x-mark class="w-5 h-5 text-stone-500" />
                </button>
            </div>

            {{-- Content --}}
            <div class="flex-1 overflow-y-auto px-4 py-6 space-y-2">
                
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

{{-- 💡 數據備份：改為標準 a 標籤跳轉，點擊時先觸發 Livewire 關閉抽屜，再執行路由網址跳轉 --}}
                <a href="{{ route('finance.backups') }}" 
                   wire:click="close"
                   class="w-full group flex items-center gap-4 px-4 py-3.5 rounded-xl hover:bg-white/70 transition-all duration-200 border border-transparent hover:border-stone-200/60 block">
                    <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-amber-400 to-orange-500 flex items-center justify-center shadow-sm group-hover:shadow-md transition-shadow">
                        <x-heroicon-o-cloud-arrow-up class=\"w-5 h-5 text-white\" />
                    </div>
                    <div class="flex-1 text-left">
                        <div class="font-semibold text-stone-700 group-hover:text-stone-900">數據備份</div>
                        <div class="text-xs text-stone-400">金櫃快照與歷史還原</div>
                    </div>
                    <x-heroicon-o-chevron-right class="w-4 h-4 text-stone-300 group-hover:text-stone-500 transition-colors" />
                </a>

                <div class="h-px bg-stone-200/60 my-3 mx-2"></div>

                {{-- 關於 --}}
                {{-- 💡 修正：改用 href 跳轉路由，點擊時觸發 wire:click="close" 先關閉抽屜防止殘影 --}}
                <a href="{{ route('finance.about') }}" 
                   wire:click="close"
                   class="w-full group flex items-center gap-4 px-4 py-3.5 rounded-xl hover:bg-white/70 transition-all duration-200 border border-transparent hover:border-stone-200/60 block">
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
                {{-- 💡 修正：同樣改為標準 a 標籤路由導航 --}}
                <a href="{{ route('finance.contact') }}" 
                   wire:click="close"
                   class="w-full group flex items-center gap-4 px-4 py-3.5 rounded-xl hover:bg-white/70 transition-all duration-200 border border-transparent hover:border-stone-200/60 block">
                    <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-rose-400 to-rose-500 flex items-center justify-center shadow-sm group-hover:shadow-md transition-shadow">
                        <x-heroicon-o-envelope class="w-5 h-5 text-white" />
                    </div>
                    <div class="flex-1 text-left">
                        <div class="font-semibold text-stone-700 group-hover:text-stone-900">聯絡我</div>
                        <div class="text-xs text-stone-400">問題回報與建議</div>
                    </div>
                    <x-heroicon-o-chevron-right class="w-4 h-4 text-stone-300 group-hover:text-stone-500 transition-colors" />
                </a>

                <div class="h-px bg-stone-200/60 my-3 mx-2"></div>
                
                <div class="px-4 py-3 text-center">
                    <span class="text-xs text-stone-400">添富記賬 v1.0.0</span>
                </div>
            </div>
        </div>
    </div>
</div>