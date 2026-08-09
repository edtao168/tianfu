<?php

namespace App\Livewire\Auth;

use App\Models\User;
use App\Models\Shop;
use App\Models\Currency;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;

class Register extends Component
{
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
    }

    protected function messages(): array
    {
        return [
            'name.required' => '請輸入姓名',
            'email.required' => '請輸入電子郵件',
            'email.email' => '請輸入有效的電子郵件地址',
            'email.unique' => '此電子郵件已被註冊',
            'password.required' => '請輸入密碼',
            'password.confirmed' => '兩次輸入的密碼不一致',
        ];
    }

    public function register()
    {
        $this->validate();

        // 1. 建立使用者
        $user = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => Hash::make($this->password),
        ]);

        // 2. 建立預設帳本
        $shop = Shop::create([
            'name' => $this->name . ' 的帳本',
            'description' => '預設記帳本',
            'owner_id' => $user->id,
        ]);

        // 3. 將使用者加入帳本（多對多關聯）
        $shop->users()->attach($user->id, [
            'role' => 'owner',
            'joined_at' => now(),
        ]);

        // 4. 建立預設幣別
        $this->createDefaultCurrencies($shop->id);

        // 5. 設定當前帳本到 session
        session(['current_shop_id' => $shop->id]);

        // 6. 登入並跳轉
        Auth::login($user);

        return redirect()->intended('/');
    }

    /**
     * 為新帳本建立預設幣別
     */
    protected function createDefaultCurrencies($shopId)
    {
        $defaults = [
            'TWD' => ['name' => '新台幣', 'symbol' => 'NT$', 'rate' => 1.000000, 'is_base' => true],            
            'CNY' => ['name' => '人民幣', 'symbol' => '¥', 'rate' => 4.690000, 'is_base' => false],
			'USD' => ['name' => '美元', 'symbol' => '$', 'rate' => 32.150000, 'is_base' => false],
        ];

        foreach ($defaults as $code => $data) {
            Currency::create([
                'shop_id' => $shopId,
                'code' => $code,
                'name' => $data['name'],
                'symbol' => $data['symbol'],
                'rate' => $data['rate'],
                'is_base' => $data['is_base'],
                'is_active' => true,
            ]);
        }
    }

    public function render()
    {
        return view('livewire.auth.register')
            ->layout('layouts.guest');
    }
}