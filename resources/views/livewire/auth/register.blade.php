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

        <x-form wire:submit="register" class="space-y-5">
            
            <x-input
                wire:model="name"
                :label="__('姓名')"
				icon="o-user"
                type="text"
                required
                autofocus
                autocomplete="name"
                :placeholder="__('您的全名')"
            />

            <x-input
                wire:model="email"
                :label="__('電子郵件')"
				icon="o-envelope"
                type="email"
                required
                autocomplete="email"
                placeholder="email@example.com"
            />

            <x-input
                wire:model="password"
                :label="__('密碼')"
				icon="o-key"
                type="password"
                required
                autocomplete="new-password"
                :placeholder="__('請輸入密碼')"
            />

            <x-input
                wire:model="password_confirmation"
                :label="__('確認密碼')"
				icon="o-shield-check"
                type="password"
                required
                autocomplete="new-password"
                :placeholder="__('請再次輸入密碼')"
            />

            <div class="pt-2">
                <x-button 
                    type="submit" 
                    :label="__('建立帳號')"
                    spinner="register"
                    class="w-full py-3.5 bg-gradient-to-tr from-teal-600 to-emerald-500 border-none text-white font-bold rounded-xl shadow-lg shadow-emerald-700/30"
                />
            </div>
        </x-form>

        <div class="text-center text-sm text-stone-500 mt-6">
            <span>{{ __('已經有帳號了？') }}</span>
            <a href="{{ route('login') }}" wire:navigate class="text-teal-600 hover:text-teal-700 font-bold transition-colors">
                {{ __('回登入頁面') }}
            </a>
        </div>
    </div>
</div>