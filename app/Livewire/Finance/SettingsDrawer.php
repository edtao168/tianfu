<?php

namespace App\Livewire\Finance;

use Livewire\Component;
use Livewire\Attributes\On;

class SettingsDrawer extends Component
{
    public bool $isOpen = false;

    #[On('toggle-settings-drawer')]
    public function toggle()
    {
        $this->isOpen = !$this->isOpen;
    }

    #[On('open-settings-drawer')]
    public function open()
    {
        $this->isOpen = true;
    }

    public function close()
    {
        $this->isOpen = false;
    }

    #[On('open-category-manager')]
    public function openCategoryManager()
    {
        // 關閉 settings drawer
        $this->isOpen = false;
        // 通知 category drawer 打開
         $this->dispatch('category-drawer-open');
    }

    public function render()
    {
        return view('livewire.finance.settings-drawer');
    }
}