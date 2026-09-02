<?php // app/Livewire/Traits/WithDateNavigation.php
// 後端 Component Trait 化（讓邏輯也可複用）
// 為了不讓 previousDate 與 nextDate 的加減算邏輯重複寫在每個 Component，我們可以抽成一個 Trait。

namespace App\Traits;

use Carbon\Carbon;

trait WithDateNavigation
{
    public string $currentDate = '';
    public string $dateMode = 'month'; // 支援: 'day', 'month', 'year'

    public function parseCurrentDate(): Carbon
    {
        if (empty($this->currentDate)) {
            return now();
        }

        try {
            if ($this->dateMode === 'year') {
                // 年份模式：確保是4位數字
                $year = (int) substr($this->currentDate, 0, 4);
                return Carbon::create($year, 1, 1);
            }
            if ($this->dateMode === 'day') {
                return Carbon::parse($this->currentDate);
            }
            // month 模式：確保是 YYYY-MM 格式
            if (strlen($this->currentDate) === 7 && strpos($this->currentDate, '-') !== false) {
                return Carbon::createFromFormat('Y-m', $this->currentDate)->startOfMonth();
            }
            // 如果格式不對，回傳當前時間
            return now();
        } catch (\Exception $e) {
            return now();
        }
    }

    public function previousDate(): void
    {
        $date = $this->parseCurrentDate();

        $this->currentDate = match ($this->dateMode) {
            'day' => $date->subDay()->format('Y-m-d'),
            'year' => $date->subYear()->format('Y'),
            default => $date->subMonth()->format('Y-m'),
        };

        $this->afterDateUpdated();
    }

    public function nextDate(): void
    {
        $date = $this->parseCurrentDate();

        $this->currentDate = match ($this->dateMode) {
            'day' => $date->addDay()->format('Y-m-d'),
            'year' => $date->addYear()->format('Y'),
            default => $date->addMonth()->format('Y-m'),
        };

        $this->afterDateUpdated();
    }

    public function updatedCurrentDate(): void
    {
        $this->afterDateUpdated();
    }

    protected function afterDateUpdated(): void
    {
        if (method_exists($this, 'onDateChanged')) {
            $this->onDateChanged();
        }
    }
    
    public function gotoCurrentDate()
    {
        $this->currentDate = match ($this->dateMode) {
            'year' => now()->format('Y'),
            'day' => now()->format('Y-m-d'),
            default => now()->format('Y-m'),
        };

        if (method_exists($this, 'onDateChanged')) {
            $this->onDateChanged();
        }
    }
}