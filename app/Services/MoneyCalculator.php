<?php
// app/Services/MoneyCalculator.php

namespace App\Services;

class MoneyCalculator
{
    /**
     * 高精度加法 (bcadd)
     */
    public static function add(string|float|int $left, string|float|int $right, int $scale = 4): string
    {
        return bcadd(self::format($left), self::format($right), $scale);
    }

    /**
     * 高精度減法 (bcsub)
     */
    public static function sub(string|float|int $left, string|float|int $right, int $scale = 4): string
    {
        return bcsub(self::format($left), self::format($right), $scale);
    }

    /**
     * 高精度乘法 (bcmul)
     */
    public static function mul(string|float|int $left, string|float|int $right, int $scale = 4): string
    {
        return bcmul(self::format($left), self::format($right), $scale);
    }

    /**
     * 高精度除法 (bcdiv)
     */
    public static function div(string|float|int $left, string|float|int $right, int $scale = 4): string
    {
        if (bccomp(self::format($right), '0.0000', $scale) === 0) {
            throw new \InvalidArgumentException('除數不能為零');
        }
        return bcdiv(self::format($left), self::format($right), $scale);
    }

    /**
     * 高精度數值比較 (bccomp)
     * 返回：0 (相等), 1 (left > right), -1 (left < right)
     */
    public static function compare(string|float|int $left, string|float|int $right, int $scale = 4): int
    {
        return bccomp(self::format($left), self::format($right), $scale);
    }

    /**
     * 將輸入數值強制標準化為字串，避免浮點數失真
     */
    private static function format(string|float|int $value): string
    {
        return sprintf('%.4f', (float)$value);
    }
}