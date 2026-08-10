<!-- filepath: resources/views/layouts/app.blade.php -->
<!DOCTYPE html>
<!-- filepath: resources/views/layouts/app.blade.php -->
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark bg-stone-900 text-stone-100 antialiased selection:bg-teal-800 selection:text-teal-100" data-theme="dark">
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
		/* 隱藏 Alpine 未完成初始化前的元素 */
        [x-cloak] { 
            display: none !important; 
        }
    </style>
	<script>
		function updateTheme(isDark) {
			if (isDark) {
				document.documentElement.classList.add('dark');
				document.documentElement.setAttribute('data-theme', 'dark');
			} else {
				document.documentElement.classList.remove('dark');
				document.documentElement.setAttribute('data-theme', 'light');
			}
		}

		// 1. 初始化判斷 OS 模式
		const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
		updateTheme(mediaQuery.matches);

		// 2. 動態監聽 OS 切換
		mediaQuery.addEventListener('change', (e) => {
			updateTheme(e.matches);
		});
	</script>
</head>
<body class="min-h-screen pb-28 md:pb-32">

    <header class="sticky top-0 z-40 backdrop-blur-md bg-stone-50/80 border-b border-stone-200/40 px-6 py-4 flex items-center justify-between">
		{{-- 替換原標題為圖形樣式 --}}
		<img src="{{ asset('tianfu.png') }}" alt="添富記賬" class="h-10 w-auto object-contain">
		
		<div class="flex items-center gap-2">
			<span class="text-xl font-black tracking-wider text-stone-800"></span>
		</div>

		<button x-data 
				@click="$dispatch('toggle-settings-drawer')" 
				class="w-10 h-10 rounded-full overflow-hidden border border-stone-200/80 active:scale-95 transition-transform focus:outline-none focus:ring-2 focus:ring-teal-500/50 bg-stone-100 flex items-center justify-center">
			
			@php
				$avatarUrl = auth()->user()?->partner?->getAvatarUrl();
			@endphp

			@if($avatarUrl)
				{{-- 有正確上傳並存在專屬頭像 --}}
				<img src="{{ $avatarUrl }}"
					 alt="{{ auth()->user()->name ?? 'User Avatar' }}" 
					 class="w-full h-full object-cover">
			@else
				{{-- 新使用者/無頭像：顯示姓名首字或通用預設 Icon --}}
				<div class="w-full h-full bg-stone-200 text-stone-600 flex items-center justify-center font-bold text-sm">
					@if(auth()->check() && auth()->user()->name)
						{{ mb_substr(auth()->user()->name, 0, 1) }}
					@else
						<x-icon name="o-user" class="w-5 h-5 text-stone-400" />
					@endif
				</div>
			@endif

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