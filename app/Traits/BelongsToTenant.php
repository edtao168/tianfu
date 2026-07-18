<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait BelongsToTenant
{
    /**
     * 程式啟動時，自動為 Model 注入全域多租戶/多用戶隔離
     */
    protected static function bootBelongsToTenant(): void
    {
        // 1. 寫入資料時，自動補上當前登入者的 user_id
        static::creating(function ($model) {
            if (Auth::check() && !$model->user_id) {
                $model->user_id = Auth::id();
            }
            
            // 預留多店/多帳本邏輯：若未設定 shop_id，預設為 1
            if (!$model->shop_id) {
                $model->shop_id = 1;
            }
        });

        // 2. 查詢資料時，自動加上 WHERE user_id = 當前登入者
        static::addGlobalScope('tenant', function (Builder $builder) {
            if (Auth::check()) {
                $builder->where('user_id', Auth::id());
            }
        });
    }
}