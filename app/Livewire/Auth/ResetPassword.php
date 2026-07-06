<?php // app/Livewire/Auth/ResetPassword.php

namespace App\Livewire\Auth;

use Livewire\Component;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Str;

class ResetPassword extends Component
{
    public string $token = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';
    public string $status = '';

    public function mount($token, $email)
    {
        $this->token = $token;
        $this->email = $email;
    }

    protected $rules = [
        'token' => 'required',
        'email' => 'required|email|exists:users,email',
        'password' => 'required|min:6|confirmed',
        'password_confirmation' => 'required',
    ];

    protected $messages = [
        'email.required' => '請輸入電子郵件',
        'email.email' => '請輸入有效的電子郵件地址',
        'email.exists' => '此電子郵件尚未註冊',
        'password.required' => '請輸入新密碼',
        'password.min' => '密碼至少需要6個字元',
        'password.confirmed' => '密碼確認不一致',
        'password_confirmation.required' => '請再次輸入新密碼',
    ];

    public function resetPassword()
    {
        $this->validate();

        $status = Password::reset(
            [
                'email' => $this->email,
                'password' => $this->password,
                'password_confirmation' => $this->password_confirmation,
                'token' => $this->token,
            ],
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            $this->status = '密碼重設成功，即將跳轉至登入頁面...';
            session()->flash('status', $this->status);
            return redirect()->route('login');
        }

        $this->addError('email', '密碼重設失敗，請確認連結是否有效');
    }

    public function render()
    {
        return view('livewire.auth.reset-password')
            ->layout('layouts.guest');
    }
}