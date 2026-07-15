{{-- resources/views/livewire/finance/contact-index.blade.php --}}
<div class="p-6 max-w-xl mx-auto space-y-6">
    
    {{-- 頁頭區 --}}
    <div class="flex justify-between items-center border-b border-stone-200/60 pb-4">
        <div>
            <h1 class="text-lg font-bold tracking-wider text-stone-800 flex items-center gap-2">
                <span class="w-1.5 h-4 rounded-full bg-stone-600"></span>
                聯絡我
            </h1>
            <p class="text-xs text-stone-400 mt-1">遇到問題、有新想法，或想加入我們的社群討論？</p>
        </div>
    </div>

    {{-- 社群導流區塊 (優化定位：牢牢固定於右下角) --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        {{-- 粉絲專頁卡片 --}}
        <x-card class="bg-stone-50/50 border-stone-200/60 shadow-sm hover:border-stone-400/60 transition-all duration-300 relative overflow-hidden group">
            
            {{-- 💡 這裡控制 Logo 的位置與明度 --}}
            {{-- opacity-10 是明亮模式明度(10%)；dark:opacity-5 是深色模式明度(5%)。你可以自由修改這兩個值 --}}
            <div class="absolute right-0 bottom-0 translate-x-6 translate-y-6 text-stone-300 dark:text-stone-800 opacity-10 dark:opacity-5 pointer-events-none z-0 transition-all duration-500 group-hover:scale-110 group-hover:rotate-6">
                <svg class="w-32 h-32" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z" />
                </svg>
            </div>

            <div class="flex items-start gap-3 relative z-10">
                <div class="p-2 bg-stone-100 dark:bg-stone-800 rounded-lg text-stone-600 dark:text-stone-300 flex-shrink-0">
                    <x-icon name="o-megaphone" class="w-6 h-6" />
                </div>
                <div class="space-y-1">
                    <h3 class="text-sm font-bold text-stone-800 dark:text-stone-200">官方粉絲專頁</h3>
                    <p class="text-xs text-stone-500 dark:text-stone-400 leading-relaxed">發布最新公告、水晶知識與系統功能更新動態。</p>
                    <div class="pt-2">
                        <a href="https://www.facebook.com/profile.php?id=61592065435427" target="_blank" class="inline-flex items-center gap-1.5 text-xs font-semibold text-stone-700 dark:text-stone-300 hover:text-stone-900 dark:hover:text-stone-100 underline underline-offset-4 decoration-stone-400">
                            前往粉專
                            <x-icon name="o-arrow-top-right-on-square" class="w-3 h-3" />
                        </a>
                    </div>
                </div>
            </div>
        </x-card>

        {{-- 不公開社團卡片 --}}
        <x-card class="bg-stone-50/50 border-stone-200/60 shadow-sm hover:border-stone-400/60 transition-all duration-300 relative overflow-hidden group">
            
            {{-- 💡 這裡控制 Logo 的位置與明度 --}}
            {{-- opacity-10 是明亮模式明度(10%)；dark:opacity-5 是深色模式明度(5%)。你可以自由修改這兩個值 --}}
            <div class="absolute right-0 bottom-0 translate-x-6 translate-y-6 text-stone-300 dark:text-stone-800 opacity-10 dark:opacity-5 pointer-events-none z-0 transition-all duration-500 group-hover:scale-110 group-hover:rotate-6">
                <svg class="w-32 h-32" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z" />
                </svg>
            </div>

            <div class="flex items-start gap-3 relative z-10">
                <div class="p-2 bg-stone-100 dark:bg-stone-800 rounded-lg text-stone-600 dark:text-stone-300 flex-shrink-0">
                    <x-icon name="o-users" class="w-6 h-6" />
                </div>
                <div class="space-y-1">
                    <h3 class="text-sm font-bold text-stone-800 dark:text-stone-200">專屬不公開社團</h3>
                    <p class="text-xs text-stone-500 dark:text-stone-400 leading-relaxed">供真實用戶交流體驗、提出痛點與深度改進建議。</p>
                    <div class="pt-2">
                        <a href="https://www.facebook.com/groups/2592726791129880" target="_blank" class="inline-flex items-center gap-1.5 text-xs font-semibold text-stone-700 dark:text-stone-300 hover:text-stone-900 dark:hover:text-stone-100 underline underline-offset-4 decoration-stone-400">
                            申請加入社團
                            <x-icon name="o-arrow-top-right-on-square" class="w-3 h-3" />
                        </a>
                    </div>
                </div>
            </div>
        </x-card>
    </div>

    {{-- 分隔線 --}}
    <div class="relative flex py-2 items-center">
        <div class="flex-grow border-t border-stone-200/40"></div>
        <span class="flex-shrink mx-4 text-xs text-stone-400 font-medium">或填寫下方表單直接回報</span>
        <div class="flex-grow border-t border-stone-200/40"></div>
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