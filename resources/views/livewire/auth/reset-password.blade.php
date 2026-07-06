<div class="w-full max-w-md px-4">
    <div class="login-card rounded-3xl shadow-2xl p-8 md:p-10">
        <!-- Logo -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center gap-3 mb-2">
                <span class="w-4 h-4 rounded-full bg-teal-600 shadow-sm"></span>
                <span class="font-black text-2xl tracking-widest text-stone-700 font-mono uppercase">添富</span>
                <span class="text-xs bg-stone-200/60 text-stone-600 px-2 py-0.5 rounded-md font-bold tracking-wider">記賬</span>
            </div>
            <p class="text-stone-500 text-sm">設定新密碼</p>
        </div>

        @if ($errors->any())
            <div class="mb-4 p-3 bg-rose-50 border border-rose-200 rounded-xl text-rose-600 text-sm">
                {{ $errors->first() }}
            </div>
        @endif

        <form wire:submit.prevent="resetPassword" class="space-y-5">
            <!-- Email (隱藏) -->
            <input type="hidden" wire:model="email">
            <input type="hidden" wire:model="token">

            <!-- Password -->
            <div>
                <label for="password" class="block text-sm font-bold text-stone-700 mb-1.5">
                    新密碼
                </label>
                <input type="password" 
                       id="password" 
                       wire:model="password"
                       class="w-full px-4 py-3 rounded-xl border border-stone-200/60 bg-stone-50/40 
                              focus:border-teal-400 focus:ring-2 focus:ring-teal-200/50 
                              transition-all outline-none text-stone-800 placeholder:text-stone-400/60
                              @error('password') border-rose-400 focus:border-rose-400 focus:ring-rose-200/50 @enderror"
                       placeholder="至少6個字元">
                @error('password') 
                    <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span>
                @enderror
            </div>

            <!-- Password Confirmation -->
            <div>
                <label for="password_confirmation" class="block text-sm font-bold text-stone-700 mb-1.5">
                    確認新密碼
                </label>
                <input type="password" 
                       id="password_confirmation" 
                       wire:model="password_confirmation"
                       class="w-full px-4 py-3 rounded-xl border border-stone-200/60 bg-stone-50/40 
                              focus:border-teal-400 focus:ring-2 focus:ring-teal-200/50 
                              transition-all outline-none text-stone-800 placeholder:text-stone-400/60
                              @error('password_confirmation') border-rose-400 focus:border-rose-400 focus:ring-rose-200/50 @enderror"
                       placeholder="再次輸入新密碼">
                @error('password_confirmation') 
                    <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span>
                @enderror
            </div>

            <button type="submit" 
                    wire:loading.attr="disabled"
                    class="w-full py-3.5 bg-gradient-to-tr from-teal-600 to-emerald-500 
                           text-white font-bold rounded-xl shadow-lg shadow-emerald-700/30 
                           hover:shadow-xl hover:shadow-emerald-700/40 hover:scale-[1.02] 
                           active:scale-[0.98] transition-all duration-200
                           disabled:opacity-70 disabled:cursor-not-allowed">
                <span wire:loading.remove>重設密碼</span>
                <span wire:loading>
                    <svg class="inline animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    重設中...
                </span>
            </button>
        </form>

        <p class="text-center text-sm text-stone-500 mt-6">
            <a href="{{ route('login') }}" wire:navigate class="text-teal-600 hover:text-teal-700 font-bold transition-colors">
                ← 返回登入
            </a>
        </p>
    </div>
</div>