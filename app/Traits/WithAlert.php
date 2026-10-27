<?php

namespace App\Traits;

use Mary\Traits\Toast;

trait WithAlert
{
    use Toast; // 保留 Mary UI 原有的 Toast 能力（供一般成功/提示使用）

    /**
     * 顯示一般成功提示（自動消失）
     */
    public function showSuccess(string $message): void
    {
        $this->success($message);
    }

    /**
     * 顯示一般資訊提示（自動消失）
     */
    public function showInfo(string $message): void
    {
        $this->info($message);
    }

    /**
     * 顯示強制手動關閉的錯誤對話框 (結合 SweetAlert2)
     */
    public function showErrorAlert(string $title, string $message): void
    {
        $this->dispatch('swal:alert', [
            'icon' => 'error',
            'title' => $title,
            'text' => $message,
            'confirmButtonText' => '確 認',
            'allowOutsideClick' => false,
        ]);
    }

    /**
     * 顯示強制手動關閉的警告對話框 (結合 SweetAlert2)
     */
    public function showWarningAlert(string $title, string $message): void
    {
        $this->dispatch('swal:alert', [
            'icon' => 'warning',
            'title' => $title,
            'text' => $message,
            'confirmButtonText' => '明 白',
            'allowOutsideClick' => false,
        ]);
    }
}