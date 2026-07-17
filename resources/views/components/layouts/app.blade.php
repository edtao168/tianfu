<!-- filepath: resources/views/layouts/app.blade.php -->
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="bg-stone-50/60 text-stone-800 antialiased selection:bg-teal-100 selection:text-teal-900">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', '添富記賬') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
	<!-- Chart.js -->
		<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Microsoft JhengHei", sans-serif;
            background-color: #FAF9F6;
        }
		/* 🔥 強制 Drawer 在所有元素之上 */
		[x-drawer] {
			z-index: 9999 !important;
		}		
		
		.drawer {
			z-index: 9999 !important;
		}
    </style>
</head>
<body class="min-h-screen pb-28 md:pb-32">

    <header class="sticky top-0 z-40 backdrop-blur-md bg-stone-50/80 border-b border-stone-200/40 px-6 py-4 flex items-center justify-between">
        <div class="flex items-center gap-2">
			<span class="w-3.5 h-3.5 rounded-full bg-teal-600 shadow-sm animate-pulse"></span>
			<span class="font-black text-lg tracking-widest text-stone-700 font-mono uppercase">添富</span>
			<!-- 新增：微小聚財金點，比左側綠點更小，象徵納財 -->
			<span class="w-2 h-2 rounded-full bg-amber-400/70 shadow-sm shadow-amber-400/20 animate-pulse" style="animation-delay: 0.5s;"></span>
			<span class="text-xs bg-stone-200/60 text-stone-600 px-2 py-0.5 rounded-md font-bold tracking-wider">記賬</span>
		</div>
        
        <div class="flex items-center gap-2">
            <span class="text-xl font-black tracking-wider text-stone-800"></span>
        </div>

        <button x-data 
				@click="$dispatch('toggle-settings-drawer')" 
				class="w-10 h-10 rounded-full overflow-hidden border border-stone-200/80 active:scale-95 transition-transform focus:outline-none focus:ring-2 focus:ring-teal-500/50 bg-stone-100">
			{{-- 💡 動態綁定：若目前登入者有綁定 Partner 且上傳過照片，則吃 photo_path；否則自動降級回 me.jpg --}}
			<img src="{{ auth()->user()->partner?->photo_path ? asset('storage/' . auth()->user()->partner->photo_path) : asset('me.jpg') }}"
				 alt="User Avatar" 
				 class="w-full h-full object-cover">
		</button>
    </header>

    <main class="max-w-lg md:max-w-3xl lg:max-w-5xl xl:max-w-6xl mx-auto px-4 pt-6 animate-fadeIn">
        {{ $slot }}
    </main>

    <nav class="fixed bottom-0 left-0 right-0 z-50 px-6 pb-6 pt-2 bg-gradient-to-t from-stone-100/90 via-stone-50/90 to-transparent backdrop-blur-lg">
        <div class="max-w-lg mx-auto bg-stone-900/90 text-stone-200 rounded-3xl py-2.5 px-3 shadow-2xl border border-stone-800/80 flex items-center justify-between relative">
            
            <a href="{{ route('finance.accounts') }}" class="flex flex-col items-center gap-1 flex-1 py-1 transition-all duration-300 {{ request()->routeIs('finance.accounts') ? 'text-teal-400 scale-105' : 'text-stone-400 hover:text-stone-200' }}">
                <x-icon name="o-wallet" class="w-5 h-5" />
                <span class="text-[9px] md:text-[10px] font-bold tracking-wider">資產總覽</span>
            </a>

            <a href="{{ route('finance.reports') }}" class="flex flex-col items-center gap-1 flex-1 py-1 transition-all duration-300 {{ request()->routeIs('finance.reports') ? 'text-teal-400 scale-105' : 'text-stone-400 hover:text-stone-200' }}">
                <x-icon name="o-chart-pie" class="w-5 h-5 opacity-80" />
                <span class="text-[9px] md:text-[10px] font-bold tracking-wider">報表統計</span>
            </a>

            <div class="absolute -top-6 left-1/2 -translate-x-1/2">
                <button type="button"
                        x-data
                        @click="$dispatch('open-transaction-modal')"
                        class="w-14 h-14 bg-gradient-to-tr from-teal-600 to-emerald-500 text-white rounded-full flex items-center justify-center shadow-lg shadow-emerald-700/30 hover:scale-105 active:scale-95 transition-all ring-4 ring-stone-900">
                    <x-icon name="o-pencil-square" class="w-7 h-7" />
                </button>
            </div>
            
            <div class="w-14 flex-none"></div>

            <a href="{{ route('finance.transactions') }}" class="flex flex-col items-center gap-1 flex-1 py-1 transition-all duration-300 {{ request()->routeIs('finance.transactions') ? 'text-teal-400 scale-105' : 'text-stone-400 hover:text-stone-200' }}">
                <x-icon name="o-list-bullet" class="w-5 h-5" />
                <span class="text-[9px] md:text-[10px] font-bold tracking-wider">流水明細</span>
            </a>

            <button x-data @click="$dispatch('toggle-settings-drawer')" 
                class="flex flex-col items-center gap-1 flex-1 py-1 transition-all duration-300 text-stone-400 hover:text-stone-200">
            <x-icon name="o-cog-6-tooth" class="w-5 h-5 opacity-80" />
            <span class="text-[9px] md:text-[10px] font-bold tracking-wider">系統設置</span>
        </button>
        </div>
    </nav>

    <livewire:finance.transaction-modal />
	<livewire:finance.settings-drawer />
	<livewire:finance.category-drawer />

    <x-toast />
    @livewireScripts
    
    <!-- 添加 Alpine.js 初始化 -->
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('dropdown', () => ({
                open: false
            }))
        })
		
		// 監聽 toggle-settings-drawer 事件
		document.addEventListener('toggle-settings-drawer', () => {
			Livewire.dispatch('toggle-settings-drawer');
		});
    </script>
	<!-- 重要：輸出 push 的 scripts -->
    @stack('scripts')
</body>
</html>