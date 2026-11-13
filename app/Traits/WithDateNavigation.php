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

    public function nextDate()
    {
        $date = $this->parseCurrentDate();
        $next = match ($this->dateMode) {
            'year'  => $date->copy()->addYear(),
            'day'   => $date->copy()->addDay(),
            default => $date->copy()->addMonth(),
        };
        $this->currentDate = match ($this->dateMode) {
            'year'  => $next->format('Y'),
            'day'   => $next->format('Y-m-d'),
            default => $next->format('Y-m'),
        };
        $this->onDateChanged();
    }

    public function prevDate()
    {
        $date = $this->parseCurrentDate();
        $prev = match ($this->dateMode) {
            'year'  => $date->copy()->subYear(),
            'day'   => $date->copy()->subDay(),
            default => $date->copy()->subMonth(),
        };
        $this->currentDate = match ($this->dateMode) {
            'year'  => $prev->format('Y'),
            'day'   => $prev->format('Y-m-d'),
            default => $prev->format('Y-m'),
        };
        $this->onDateChanged();
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