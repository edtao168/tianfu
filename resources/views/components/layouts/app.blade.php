<!-- filepath: resources/views/layouts/app.blade.php -->
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light" class="bg-stone-50/60 text-stone-800 antialiased selection:bg-teal-100 selection:text-teal-900">
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
        <div class="flex items-center gap-3">
            <!-- 用戶頭像 + 下拉選單 -->
            <div class="relative" x-data="{ open: false }">
                <div class="avatar placeholder cursor-pointer" @click="open = !open">
                    <div class="bg-stone-200 text-stone-600 rounded-full w-8 h-8 font-bold text-xs ring-2 ring-stone-200 ring-offset-2 hover:ring-teal-400 transition-all">
                        {{ Auth::user() ? strtoupper(substr(Auth::user()->name ?? Auth::user()->email ?? 'U', 0, 1)) : 'U' }}
                    </div>
                </div>
                
                <!-- 下拉選單 -->
                <div x-show="open" 
                     @click.away="open = false"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     class="absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-xl border border-stone-200/60 py-1 z-50">
                    
                    <!-- 用戶資訊 -->
                    <div class="px-4 py-3 border-b border-stone-100">
                        <div class="text-sm font-bold text-stone-800">
                            {{ Auth::user()?->name ?? '訪客' }}
                        </div>
                        <div class="text-xs text-stone-400 truncate">
                            {{ Auth::user()?->email ?? '' }}
                        </div>
                    </div>
                    
                    <!-- 選單項目 -->
                    <!-- <a href="{{-- route('profile.edit') --}}" class="flex items-center gap-3 px-4 py-2.5 text-sm text-stone-700 hover:bg-stone-50 transition-colors"> --!>
                        <x-icon name="o-user" class="w-4 h-4 text-stone-400" />
                        個人資料
                    </a>
                    
                    <!-- <a href="{{-- route('settings') --}}" class="flex items-center gap-3 px-4 py-2.5 text-sm text-stone-700 hover:bg-stone-50 transition-colors"> --!>
                        <x-icon name="o-cog-6-tooth" class="w-4 h-4 text-stone-400" />
                        系統設置
                    </a>
                    
                    <hr class="my-1 border-stone-100">
                    
                    <!-- 登出按鈕 -->
                    <form method="POST" action="{{ route('logout') }}" class="block">
                        @csrf
                        <button type="submit" class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-rose-600 hover:bg-rose-50 transition-colors">
                            <x-icon name="o-arrow-right-on-rectangle" class="w-4 h-4 text-rose-400" />
                            登出
                        </button>
                    </form>
                </div>
            </div>
        </div>
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