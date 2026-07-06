<?php // app/Livewire/Auth/ForgotPassword.php

namespace App\Livewire\Auth;

use Livewire\Component;
use Illuminate\Support\Facades\Password;

class ForgotPassword extends Component
{
    public string $email = '';
    public string $status = '';

    protected $rules = [
        'email' => 'required|email|exists:users,email',
    ];

    protected $messages = [
        'email.required' => '請輸入電子郵件',
        'email.email' => '請輸入有效的電子郵件地址',
        'email.exists' => '此電子郵件尚未註冊',
    ];

    public function sendResetLink()
    {
        $this->validate();

        $status = Password::sendResetLink(['email' => $this->email]);

        if ($status === Password::RESET_LINK_SENT) {
            $this->status = '我們已發送密碼重置連結到您的電子郵件信箱';
            $this->email = '';
        } else {
            $this->addError('email', '無法發送重置連結，請稍後再試');
        }
    }

    public function render()
    {
        return view('livewire.auth.forgot-password')
            ->layout('layouts.guest');
    }
}