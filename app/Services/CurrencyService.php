<?php
// app/Services/CurrencyService.php

namespace App\Services;

use App\Models\Currency;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CurrencyService
{
    protected $shopId;
    protected $cacheDuration = 3600; // 1小時

    /**
     * 單帳本過渡期：shopId 恆為 1（UI 尚無多帳本切換入口）。
     * 多帳本啟用時需確認此服務的生命週期（若為 singleton，switchShop 後
     * 已解析的實例仍持舊 shopId，需改為 request-scoped 或每次取 session）。
     */
    public function __construct()
    {
        $this->shopId = session('current_shop_id') ?? 1;
    }

    /**
     * 獲取所有幣別（含快取）
     *
     * 註：此處顯式 where('shop_id', ...) 與 Currency 的 HasShop global scope
     *     在單帳本下等價（皆為 shop_id = 1）。多帳本啟用時，HasShop 改動態後，
     *     此處可保留（重複但正確）或移除以避免條件疊加。
     */
    public function getAllCurrencies(): Collection
    {
        try {
            $result = Cache::remember(
                "shop_{$this->shopId}_currencies_all",
                $this->cacheDuration,
                function () {
                    return Currency::where('shop_id', $this->shopId)
                                   ->where('is_active', true)
                                   ->get()
                                   ->keyBy('code');
                }
            );
            
            // Check if result is corrupted
            if ($result instanceof \__PHP_Incomplete_Class) {
                throw new \Exception('Corrupted cache data');
            }
            
            return $result;
        } catch (\Exception $e) {
            Log::warning('Cache corruption detected, regenerating data', [
                'shop_id' => $this->shopId,
                'error' => $e->getMessage()
            ]);
            
            // Clear the corrupted cache
            Cache::forget("shop_{$this->shopId}_currencies_all");
            
            // Regenerate and return fresh data
            return Currency::where('shop_id', $this->shopId)
                          ->where('is_active', true)
                          ->get()
                          ->keyBy('code');
        }
    }

    /**
     * 獲取基準貨幣
     *
     * 註：同 getAllCurrencies 的註解，顯式 shop_id 條件與 HasShop global scope 單帳本下等價。
     */
    public function getBaseCurrency(): ?Currency
    {
        try {
            $result = Cache::remember(
                "shop_{$this->shopId}_base_currency",
                $this->cacheDuration,
                function () {
                    return Currency::where('shop_id', $this->shopId)
                                   ->where('is_base', true)
                                   ->first();
                }
            );
            
            if ($result instanceof \__PHP_Incomplete_Class) {
                throw new \Exception('Corrupted cache data');
            }
            
            return $result;
        } catch (\Exception $e) {
            Log::warning('Cache corruption detected in base currency', [
                'shop_id' => $this->shopId,
                'error' => $e->getMessage()
            ]);
            
            Cache::forget("shop_{$this->shopId}_base_currency");
            
            return Currency::where('shop_id', $this->shopId)
                          ->where('is_base', true)
                          ->first();
        }
    }

    /**
     * 獲取基準貨幣代碼
     */
    public function getBaseCurrencyCode(): string
    {
        $base = $this->getBaseCurrency();
        return $base ? $base->code : 'TWD';
    }

    /**
     * 獲取基準貨幣符號
     */
    public function getBaseCurrencySymbol(): string
    {
        $base = $this->getBaseCurrency();
        return $base ? $base->symbol : 'NT$';
    }

    /**
     * 獲取匯率
     */
    public function getRate(string $code): float
    {
        $currencies = $this->getAllCurrencies();
        return isset($currencies[$code]) ? (float)$currencies[$code]->rate : 1.0;
    }

    /**
     * 獲取幣別資訊
     */
    public function getCurrency(string $code): ?Currency
    {
        $currencies = $this->getAllCurrencies();
        return $currencies[$code] ?? null;
    }

    /**
     * 轉換為基準貨幣
     */
    public function convertToBase(string $amount, string $currency): string
    {
        if (!is_numeric($amount) || $amount === '') {
            return '0.0000';
        }

        $rate = $this->getRate($currency);
        // 使用 MoneyCalculator 進行乘法
        return MoneyCalculator::mul($amount, (string)$rate, 4);
    }

    /**
     * 從基準貨幣轉換為目標貨幣
     */
    public function convertFromBase(string $amount, string $currency): string
    {
        if (!is_numeric($amount) || $amount === '') {
            return '0.0000';
        }

        $rate = $this->getRate($currency);
        if ($rate == 0) {
            return '0.0000';
        }
        // 使用 MoneyCalculator 進行除法
        return MoneyCalculator::div($amount, (string)$rate, 4);
    }

    /**
     * 獲取總資產（使用 MoneyCalculator）
     */
    public function getTotalAssets(array $balances): string
    {
        $total = '0.0000';
        
        foreach ($balances as $currency => $amount) {
            $converted = $this->convertToBase($amount, $currency);
            // 使用 MoneyCalculator 進行加法
            $total = MoneyCalculator::add($total, $converted, 4);
        }
        
        return $total;
    }
	
    /**
     * 清除快取
     */
    public function clearCache(): void
    {
        Cache::forget("shop_{$this->shopId}_currencies_all");
        Cache::forget("shop_{$this->shopId}_base_currency");
    }

    /**
     * 切換帳本
     *
     * 目前 UI 無多帳本切換入口，此方法暫無呼叫端。
     * 多帳本啟用時，需同步調整 HasShop 為動態 scope，並確認此服務是否為 singleton。
     */
    public function setShopId(int $shopId): void
    {
        $this->shopId = $shopId;
        session(['current_shop_id' => $shopId]);
    }
	
	/**
     * 格式化金額（委派給 Currency Model）
     *
     * 中文名稱：金額格式化
     * 用途：統一對外格式化入口，避免 Component 直接碰 Model 方法
     */
    public function format(string|float|int $amount, ?Currency $currency = null): string
    {
        $currency = $currency ?? $this->getBaseCurrency();

        if (!$currency) {
            return number_format((float) $amount, 2);
        }

        return $currency->formatAmount($amount);
    }

    /**
     * 取得幣別主題色
     */
    public function getThemeColor(string $code): string
    {
        $map = config('business.currency_theme_map', []);
        return $map[$code] ?? 'gray';
    }

    /**
     * 依幣別代碼取得 Currency（含快取）
     */
    public function findByCode(string $code): ?Currency
    {
        return $this->getAllCurrencies()[$code] ?? null;
    }
}