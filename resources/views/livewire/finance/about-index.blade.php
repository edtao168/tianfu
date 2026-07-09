{{-- resources/views/livewire/finance/about-index.blade.php --}}
<div class="p-6 max-w-3xl mx-auto space-y-6">
    
    {{-- 頁頭區 --}}
    <div class="flex justify-between items-center border-b border-stone-200/60 pb-4">
        <div>
            <h1 class="text-lg font-bold tracking-wider text-stone-800 flex items-center gap-2">
                <span class="w-1.5 h-4 rounded-full bg-stone-600"></span>
                關於添富記賬
            </h1>
            <p class="text-xs text-stone-400 mt-1">版本 {{ $this->getVersion() }} · 大宋名窯美學記帳系統</p>
        </div>
    </div>

    {{-- 核心理念 --}}
    <x-card class="bg-white/50 border-stone-200/60 shadow-sm">
        <div class="flex items-center gap-4 mb-4">
            <div class="w-12 h-12 rounded-full overflow-hidden border border-stone-200 bg-white flex items-center justify-center shadow-sm">
                <img src="{{ asset('me.jpg') }}" class="w-full h-full object-cover" />
            </div>
            <div>
                <h2 class="text-base font-bold text-stone-800 tracking-wide">陶老闆的記帳哲學</h2>
                <p class="text-xs text-stone-400 mt-0.5">理財如修身，落筆皆嚴謹</p>
            </div>
        </div>
        
        <p class="text-sm text-stone-600 leading-relaxed tracking-wide">
            這是一套專為個人與小微零售業量身打造的極簡資產明細系統。拋棄了傳統會計軟體臃腫的報表，保留最核心的日記帳、多幣別帳戶流水與資產快照。
            結合大宋汝窯天青、千山青綠之色彩美學，讓記帳不再是冰冷的數字，而是一場治癒的每日生活儀式。
        </p>
    </x-card>

    {{-- 設計細節與規格 --}}
    <x-card class="bg-white/50 border-stone-200/60 shadow-sm" title="架構規格">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
            <div class="p-3.5 rounded-xl bg-stone-50 border border-stone-100/80 flex items-center gap-3">
                <x-heroicon-o-shield-check class="w-5 h-5 text-stone-500" />
                <div>
                    <div class="font-semibold text-stone-700 text-xs">大宋金櫃備份</div>
                    <div class="text-[11px] text-stone-400 mt-0.5">多重歷史壓縮檔快照與自動還原</div>
                </div>
            </div>
            <div class="p-3.5 rounded-xl bg-stone-50 border border-stone-100/80 flex items-center gap-3">
                <x-heroicon-o-currency-dollar class="w-5 h-5 text-stone-500" />
                <div>
                    <div class="font-semibold text-stone-700 text-xs">多幣別全動態對齊</div>
                    <div class="text-[11px] text-stone-400 mt-0.5">精確支持新台幣、人民幣與港幣算力</div>
                </div>
            </div>
        </div>
    </x-card>
</div>