{{-- resources/views/livewire/auth/register.blade.php --}}
<div class="w-full max-w-md px-4 mx-auto my-12">
    <div class="login-card rounded-3xl shadow-2xl p-8 md:p-10 bg-white">
        
        <div class="text-center mb-8">
            <h1 class="text-xl font-bold tracking-tight text-stone-700">{{ __('建立帳號') }}</h1>
            <p class="text-sm text-stone-500 mt-1">{{ __('填寫以下資訊以建立管理員帳號') }}</p>
        </div>

        @if (session('status'))
            <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded-xl text-green-600 text-sm text-center">
                {{ session('status') }}
            </div>
        @endif

        <form wire:submit.prevent="register" class="space-y-5">
            
            <flux:input
                wire:model="name"
                :label="__('姓名')"
                type="text"
                required
                autofocus
                autocomplete="name"
                :placeholder="__('您的全名')"
            />

            <flux:input
                wire:model="email"
                :label="__('電子郵件')"
                type="email"
                required
                autocomplete="email"
                placeholder="email@example.com"
            />

            <flux:input
                wire:model="password"
                :label="__('密碼')"
                type="password"
                required
                autocomplete="new-password"
                :placeholder="__('請輸入密碼')"
                viewable
            />

            <flux:input
                wire:model="password_confirmation"
                :label="__('確認密碼')"
                type="password"
                required
                autocomplete="new-password"
                :placeholder="__('請再次輸入密碼')"
                viewable
            />

            <div class="pt-2">
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled" class="w-full py-3.5 bg-gradient-to-tr from-teal-600 to-emerald-500 border-none text-white font-bold rounded-xl shadow-lg shadow-emerald-700/30">
                    <span wire:loading.remove>{{ __('建立帳號') }}</span>
                    <span wire:loading>
                        <svg class="inline animate-spin w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        處理中...
                    </span>
                </flux:button>
            </div>
        </form>

        <div class="text-center text-sm text-stone-500 mt-6">
            <span>{{ __('已經有帳號了？') }}</span>
            <flux:link :href="route('login')" wire:navigate class="text-teal-600 hover:text-teal-700 font-bold transition-colors">{{ __('回登入頁面') }}</flux:link>
        </div>
    </div>
</div>