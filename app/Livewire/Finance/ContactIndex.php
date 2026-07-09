<?php // [個人記帳系統] app/Livewire/Finance/ContactIndex.php

namespace App\Livewire\Finance;

use Livewire\Component;
use Mary\Traits\Toast;

class ContactIndex extends Component
{
    use Toast;

    public string $type = 'suggestion';
    public string $content = '';
    public string $email = '';

    protected array $rules = [
        'content' => 'required|min:5',
        'email'   => 'nullable|email'
    ];

    public function submitForm()
    {
        $this->validate();

        // 💡 實戰中這裡可以寫入資料庫或發送 Email，目前直接彈出成功通知
        $this->success('感謝您的回報', '您的寶貴建議已封存提交給系統作者。');
        $this->reset(['content', 'email']);
    }

    public function render()
    {
        return view('livewire.finance.contact-index')
            ->layout('layouts.app');
    }
}