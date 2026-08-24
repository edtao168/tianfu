<!-- filepath: resources/views/livewire/finance/settings-drawer.blade.php -->
<div>
    <x-drawer wire:model="isOpen" title="系統設置" subtitle="帳戶與偏好設定" right class="w-11/12 max-w-[380px] bg-stone-50/95 backdrop-blur-xl">
        {{-- Header 內容在 Mary UI 已經透過 title / subtitle 自動產生，若要加自訂圖示可用 slot --}}
        
        {{-- Menu List --}}
        <div class="space-y-3 py-2">
            
			<!-- 介面主題選擇區塊 -->
			<div class="px-2 py-3 bg-base-200/50 rounded-2xl border border-base-200">
				<div class="text-xs font-bold opacity-70 mb-2.5 px-2 flex items-center gap-1.5">
					<x-heroicon-o-swatch class="w-4 h-4" />
					<span>介面主題</span>
				</div>
				<div class="grid grid-cols-3 gap-1.5 bg-base-300/40 p-1 rounded-xl">
					<button type="button" 
							@click="setTheme('system')"
							:class="theme === 'system' ? 'bg-base-100 text-teal-600 dark:text-teal-400 shadow-sm font-bold' : 'opacity-70 hover:opacity-100'"
							class="py-1.5 text-xs rounded-lg transition-all duration-200">
						跟隨系統
					</button>
					<button type="button" 
							@click="setTheme('dark')"
							:class="theme === 'dark' ? 'bg-base-100 text-teal-600 dark:text-teal-400 shadow-sm font-bold' : 'opacity-70 hover:opacity-100'"
							class="py-1.5 text-xs rounded-lg transition-all duration-200">
						玄英黑
					</button>
					<button type="button" 
							@click="setTheme('light')"
							:class="theme === 'light' ? 'bg-base-100 text-teal-600 dark:text-teal-400 shadow-sm font-bold' : 'opacity-70 hover:opacity-100'"
							class="py-1.5 text-xs rounded-lg transition-all duration-200">
						霜華白
					</button>
				</div>
			</div>

			<!-- 字體大小選擇區塊 -->
			<div class="px-2 py-3 bg-base-200/50 rounded-2xl border border-base-200">
				<div class="text-xs font-bold opacity-70 mb-2.5 px-2 flex items-center gap-1.5">
					<x-heroicon-o-adjustments-vertical class="w-4 h-4" />
					<span>字體大小</span>
				</div>
				<div class="grid grid-cols-3 gap-1.5 bg-base-300/40 p-1 rounded-xl">
					<button type="button" 
							@click="setFontSize('sm')"
							:class="fontSize === 'sm' ? 'bg-base-100 text-teal-600 dark:text-teal-400 shadow-sm font-bold' : 'opacity-70 hover:opacity-100'"
							class="py-1.5 text-xs rounded-lg transition-all duration-200">
						精簡 (小)
					</button>
					<button type="button" 
							@click="setFontSize('base')"
							:class="fontSize === 'base' ? 'bg-base-100 text-teal-600 dark:text-teal-400 shadow-sm font-bold' : 'opacity-70 hover:opacity-100'"
							class="py-1.5 text-xs rounded-lg transition-all duration-200">
						標準 (中)
					</button>
					<button type="button" 
							@click="setFontSize('lg')"
							:class="fontSize === 'lg' ? 'bg-base-100 text-teal-600 dark:text-teal-400 shadow-sm font-bold' : 'opacity-70 hover:opacity-100'"
							class="py-1.5 text-xs rounded-lg transition-all duration-200">
						清晰 (大)
					</button>
				</div>
			</div>
			
			{{-- 成員管理 --}}
			<a href="{{ route('finance.partners') }}" 
			   wire:click="close"
			   class="flex w-full group items-center gap-4 px-4 py-3.5 rounded-xl hover:bg-base-200/60 transition-all duration-200 border border-transparent hover:border-base-200">
				<div class="w-11 h-11 rounded-xl bg-gradient-to-br from-teal-400 to-emerald-500 flex items-center justify-center shadow-sm group-hover:shadow-md transition-shadow">
					<x-heroicon-o-users class="w-5 h-5 text-white" />
				</div>
				<div class="flex-1 text-left">
					<div class="font-semibold text-base-content">成員管理</div>
					<div class="text-xs opacity-60">設定家庭使用者與個人載具</div>
				</div>
				<x-heroicon-o-chevron-right class="w-4 h-4 opacity-40 group-hover:opacity-80 transition-opacity" />
			</a>

			{{-- 分類管理 --}}
			<button wire:click="openCategoryManager" 
					class="w-full group flex items-center gap-4 px-4 py-3.5 rounded-xl hover:bg-base-200/60 transition-all duration-200 border border-transparent hover:border-base-200">
				<div class="w-11 h-11 rounded-xl bg-gradient-to-br from-blue-400 to-blue-500 flex items-center justify-center shadow-sm group-hover:shadow-md transition-shadow">
					<x-heroicon-o-tag class="w-5 h-5 text-white" />
				</div>
				<div class="flex-1 text-left">
					<div class="font-semibold text-base-content">分類管理</div>
					<div class="text-xs opacity-60">編輯收支類別與圖標</div>
				</div>
				<x-heroicon-o-chevron-right class="w-4 h-4 opacity-40 group-hover:opacity-80 transition-opacity" />
			</button>
			
			 {{-- 匯率設定 --}}
			<a href="{{ route('finance.currencies') }}" 
			   wire:click="close"
			   class="flex w-full group items-center gap-4 px-4 py-3.5 rounded-xl hover:bg-base-200/60 transition-all duration-200 border border-transparent hover:border-base-200">
				<div class="w-11 h-11 rounded-xl bg-gradient-to-br from-amber-400 to-amber-500 flex items-center justify-center shadow-sm group-hover:shadow-md transition-shadow">
					<x-heroicon-o-currency-dollar class="w-5 h-5 text-white" />
				</div>
				<div class="flex-1 text-left">
					<div class="font-semibold text-base-content">匯率設定</div>
					<div class="text-xs opacity-60">管理多幣別與換算基準</div>
				</div>
				<x-heroicon-o-chevron-right class="w-4 h-4 opacity-40 group-hover:opacity-80 transition-opacity" />
			</a>
			
			{{-- 數據備份 --}}
			<a href="{{ route('finance.backup') }}" 
			   wire:click="close"
			   class="flex w-full group items-center gap-4 px-4 py-3.5 rounded-xl hover:bg-base-200/60 transition-all duration-200 border border-transparent hover:border-base-200">
				<div class="w-11 h-11 rounded-xl bg-gradient-to-br from-emerald-400 to-emerald-500 flex items-center justify-center shadow-sm group-hover:shadow-md transition-shadow">
					<x-heroicon-o-arrow-down-tray class="w-5 h-5 text-white" />
				</div>
				<div class="flex-1 text-left">
					<div class="font-semibold text-base-content">數據備份</div>
					<div class="text-xs opacity-60">匯出 / 匯入資料</div>
				</div>
				<x-heroicon-o-chevron-right class="w-4 h-4 opacity-40 group-hover:opacity-80 transition-opacity" />
			</a>

			{{-- 關於 --}}
			<a href="{{ route('finance.about') }}" 
			   wire:click="close"
			   class="flex w-full group items-center gap-4 px-4 py-3.5 rounded-xl hover:bg-base-200/60 transition-all duration-200 border border-transparent hover:border-base-200">
				<div class="w-11 h-11 rounded-xl bg-gradient-to-br from-indigo-400 to-indigo-500 flex items-center justify-center shadow-sm group-hover:shadow-md transition-shadow">
					<x-heroicon-o-information-circle class="w-5 h-5 text-white" />
				</div>
				<div class="flex-1 text-left">
					<div class="font-semibold text-base-content">關於</div>
					<div class="text-xs opacity-60">版本資訊與使用條款</div>
				</div>
				<x-heroicon-o-chevron-right class="w-4 h-4 opacity-40 group-hover:opacity-80 transition-opacity" />
			</a>

			{{-- 聯絡我 --}}
			<a href="{{ route('finance.contact') }}" 
			   wire:click="close"
			   class="flex w-full group items-center gap-4 px-4 py-3.5 rounded-xl hover:bg-base-200/60 transition-all duration-200 border border-transparent hover:border-base-200">
				<div class="w-11 h-11 rounded-xl bg-gradient-to-br from-rose-400 to-rose-500 flex items-center justify-center shadow-sm group-hover:shadow-md transition-shadow">
					<x-heroicon-o-envelope class="w-5 h-5 text-white" />
				</div>
				<div class="flex-1 text-left">
					<div class="font-semibold text-base-content">聯絡我</div>
					<div class="text-xs opacity-60">問題回報與建議</div>
				</div>
				<x-heroicon-o-chevron-right class="w-4 h-4 opacity-40 group-hover:opacity-80 transition-opacity" />
			</a>

		</div>

		{{-- Footer --}}
		<x-slot:actions>
			<form method="POST" action="{{ route('logout') }}" class="w-full">
				@csrf
				<button type="submit" class="w-full flex items-center justify-center gap-2 py-3 rounded-xl bg-base-200 hover:bg-error/10 hover:text-error text-base-content font-medium transition-all duration-200 active:scale-[0.99]">
					<x-heroicon-o-arrow-left-on-rectangle class="w-5 h-5" />
					<span>安全登出</span>
				</button>
			</form>
		</x-slot:actions>
	</x-drawer>
</div>