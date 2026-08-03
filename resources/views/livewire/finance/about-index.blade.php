{{-- resources/views/livewire/finance/about-index.blade.php --}}
<div class="p-6 max-w-3xl mx-auto space-y-6">
    
    {{-- 頁頭區 --}}
    <div class="flex justify-between items-center border-b border-stone-200/60 dark:border-stone-800 pb-4">
        <div>
            <h1 class="text-lg font-bold tracking-wider text-stone-800 dark:text-stone-100 flex items-center gap-2">
                <span class="w-1.5 h-4 rounded-full bg-stone-600 dark:bg-stone-400"></span>
                關於添富記賬
            </h1>
            <p class="text-xs text-stone-400 dark:text-stone-500 mt-1">版本 {{ $this->getVersion() }} · 簡單生活記帳系統</p>
        </div>
    </div>

    {{-- 核心理念 --}}
    <x-card class="bg-white/50 dark:bg-stone-900/50 border-stone-200/60 dark:border-stone-800 shadow-sm">
        <div class="flex items-center gap-4 mb-4">
            <div class="w-12 h-12 rounded-full overflow-hidden border border-stone-200 dark:border-stone-700 bg-white dark:bg-stone-800 flex items-center justify-center shadow-sm">
                <img src="{{ asset('me.jpg') }}" class="w-full h-full object-cover" />
            </div>
            <div>
                <h2 class="text-base font-bold text-stone-800 dark:text-stone-100 tracking-wide">陶老闆的記帳哲學</h2>
                <p class="text-xs text-stone-700 dark:text-stone-300 mt-0.5">理財如修身，落筆皆嚴謹</p>
            </div>
        </div>
        
        <p class="text-sm text-stone-700 dark:text-stone-300 leading-relaxed tracking-wide">
            這是一套專為個人與小微零售業量身打造的極簡資產明細系統。拋棄了傳統會計軟體臃腫的報表，保留最核心的日記帳、多幣別帳戶流水與資產快照。適合記錄個人或家庭每日的食衣住行和收支情形，以便於掌握自己的財務狀況。清楚自己的財務狀況，保持財務紀律，用智慧掌控自己的財商，則自保足矣，正所謂「富在術數，不在勞身；利在勢居，不在力耕。」——西漢·桓寬《鹽鐵論》。
        </p>
        <br>
        <p class="text-sm text-stone-600 dark:text-stone-400 leading-relaxed tracking-wide">        
            本系統結合大宋汝窯天青、千山青綠之色彩美學，讓記帳不再是冰冷的數字，而是一場治癒的每日生活儀式。
        </p>
        <br>
        <p class="text-sm text-stone-600 dark:text-stone-400 leading-relaxed tracking-wide">        
            本系統的編寫始自2026/6/28，7/1上線運行至今，這是因為有了前一套編寫了近兩年「陶老闆進銷存」的經驗，才有今天的效率。而今本系統仍持續更新，歡迎到粉絲專頁留下讚美，或到社團提出建議、批評，讓「添富記賬」越來越接近你的需求。
        </p>
    </x-card>

    {{-- 設計細節與規格 --}}
    <x-card class="bg-white/50 dark:bg-stone-900/50 border-stone-200/60 dark:border-stone-800 shadow-sm" title="架構規格">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
            <div class="p-3.5 rounded-xl bg-stone-50 dark:bg-stone-800/60 border border-stone-100/80 dark:border-stone-700/60 flex items-center gap-3">
                <x-heroicon-o-shield-check class="w-5 h-5 text-stone-500 dark:text-stone-400" />
                <div>
                    <div class="font-semibold text-stone-700 dark:text-stone-200 text-xs">記賬數據備份</div>
                    <div class="text-[11px] text-stone-400 dark:text-stone-500 mt-0.5">多重歷史壓縮檔快照與自動還原</div>
                </div>
            </div>
            <div class="p-3.5 rounded-xl bg-stone-50 dark:bg-stone-800/60 border border-stone-100/80 dark:border-stone-700/60 flex items-center gap-3">
                <x-heroicon-o-currency-dollar class="w-5 h-5 text-stone-500 dark:text-stone-400" />
                <div>
                    <div class="font-semibold text-stone-700 dark:text-stone-200 text-xs">多幣別全動態對齊</div>
                    <div class="text-[11px] text-stone-400 dark:text-stone-500 mt-0.5">精確支持新台幣、人民幣與港幣算力</div>
                </div>
            </div>
        </div>
    </x-card>

    {{-- 贊助區 --}}
    <x-card class="text-center bg-white/50 dark:bg-stone-900/50 border-stone-200/60 dark:border-stone-800 shadow-sm">
        <p class="mt-2 text-stone-500 dark:text-stone-400 text-sm">覺得不賴，可以贊助一下。</p>
        <div class="flex flex-col sm:flex-row items-center justify-center gap-4 mt-4">
            <img src="{{ asset('cathay_qr_code.png') }}" 
                 alt="國泰QR Code" 
                 class="w-[180px] h-auto rounded-lg border border-stone-200 dark:border-stone-700 p-1 bg-white dark:bg-stone-800">
                 
            <img src="{{ asset('alipay.jpg') }}" 
                 alt="支付寶" 
                 class="w-[180px] h-auto rounded-lg border border-stone-200 dark:border-stone-700 p-1 bg-white dark:bg-stone-800">
        </div>
    </x-card>
</div>