<?php // [個人記帳系統] app/Livewire/Finance/AboutIndex.php

namespace App\Livewire\Finance;

use Livewire\Component;

class AboutIndex extends Component
{
    public function getVersion(): string
    {
        return 'v1.0.00';
    }

    public function render()
    {
        return view('livewire.finance.about-index')
            ->layout('layouts.app');
    }
}