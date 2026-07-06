<?php
// app/Traits/HasShop.php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait HasShop
{
    /**
     * 啟動 Trait 的引導方法 (Boot Method)
     * Laravel 會在 Model 初始化時自動呼叫 boot[TraitName]
     */
    protected static function bootHasShop(): void
    {
        // 1. 自動寫入：在建立 (creating) 資料時，如果沒有傳入 shop_id，自動代入預設值 1
        static::creating(function ($model) {
            if (empty($model->shop_id)) {
                $model->shop_id = 1;
            }
        });

        // 2. 自動隔離：全域範圍查詢 (Global Scope)
        // 確保未來不管執行甚麼 Eloquent 查詢，都會自動加上 `WHERE shop_id = 1`
        static::addGlobalScope('shop_isolation', function (Builder $builder) {
            $builder->where('shop_id', 1);
        });
    }
}