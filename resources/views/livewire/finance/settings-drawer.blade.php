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
                    <div class="w-10 h-10 flex items-center justify-center">
                        <x-heroicon-o-cog-6-tooth class="w-5 h-5" />
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-stone-800">系統設置</h2>
                        <p class="text-xs text-stone-400">帳戶與偏好設定</p>
                    </div>
                </div>
                <button wire:click="close" class="text-stone-400 hover:text-stone-600 transition-colors">
                    <x-heroicon-o-x-mark class="w-6 h-6" />
                </button>
            </div>

            {{-- Menu List --}}
            <div class="flex-1 overflow-y-auto p-4 space-y-3">
                
				{{-- 💡 新增：家庭成員管理超連結入口，使用與數據備份一致的 flex 架構撐開 --}}
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
                {{-- 💡 修正：在 class 最開頭加上 flex，確保區塊撐開，背景色與排版完美還原 --}}
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
                {{-- 💡 修正：同樣在 class 最開頭加上 flex --}}
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
                {{-- 💡 修正：同樣在 class 最開頭加上 flex --}}
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
            <div class="p-4 border-t border-stone-200/60 bg-white/30">
                <form method="POST" action="{{ route('logout') }}" class="w-full">
                    @csrf
                    <button type="submit" class="w-full flex items-center justify-center gap-2 py-3 rounded-xl bg-stone-200/50 hover:bg-red-50 hover:text-red-600 text-stone-600 font-medium transition-all duration-200 active:scale-[0.99]">
                        <x-heroicon-o-arrow-left-on-rectangle class="w-5 h-5" />
                        <span>安全登出</span>
                    </button>
                </form>
            </div>

        </div>
    </div>
</div>