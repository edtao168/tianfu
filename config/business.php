<?php
// config/business.php

return [
	/*
    |--------------------------------------------------------------------------
    | 系統基準貨幣 (Base Currency)
    |--------------------------------------------------------------------------
    | 所有統計報表將以基準貨幣顯示
    */
    'base_currency' => 'TWD',
	
    /*
    |--------------------------------------------------------------------------
    | 多幣別字典與「大宋名窯山水」呈現定義 (Currencies)
    |--------------------------------------------------------------------------
    | TWD: 汝窯天青 (Sky-Cyan) | CNY: 古宮絳紅 (Pastel Rose) 
    | HKD: 千山青綠 (Sage-Emerald) | USD: 遠山黛紫 (Muted Violet)
    | 	
    | 匯率依據2026/07/02台灣銀行買進數據
    */
    'currencies' => [
        'TWD' => [
            'name' => '新台幣',
            'symbol' => 'NT$',
            'bg' => 'bg-sky-50/60 border-sky-200/60 dark:bg-sky-950/10 dark:border-sky-900/40',
            'symbol_color' => 'text-sky-600 dark:text-sky-400',
            'tag' => 'text-sky-500',
			'rate' => 1.0000,
        ],
        'CNY' => [
            'name' => '人民幣',
            'symbol' => '¥',
            'bg' => 'bg-rose-50/40 border-rose-200/50 dark:bg-rose-950/10 dark:border-rose-900/40',
            'symbol_color' => 'text-rose-600/90 dark:text-rose-400',
            'tag' => 'text-rose-400',
			'rate' => 4.6750,
        ],
        'HKD' => [
            'name' => '港幣',
            'symbol' => 'HK$',
            'bg' => 'bg-emerald-50/40 border-emerald-200/50 dark:bg-emerald-950/10 dark:border-emerald-900/40',
            'symbol_color' => 'text-emerald-600/90 dark:text-emerald-400',
            'tag' => 'text-emerald-500',
			'rate' => 4.0470,
        ],
        'USD' => [
            'name' => '美元',
            'symbol' => '$',
            'bg' => 'bg-violet-50/40 border-violet-200/50 dark:bg-violet-950/10 dark:border-violet-900/40',
            'symbol_color' => 'text-violet-600/90 dark:text-violet-400',
            'tag' => 'text-violet-500',
			'rate' => 31.8700,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 帳戶類型與「點茶文墨意象」呈現定義 (Account Types)
    |--------------------------------------------------------------------------
    | cash: 定窯牙白 (Amber/Stone) | bank: 松煙黛墨 (Slate Blue)
    | e-wallet: 官窯粉青 (Teal/Mint) | securities: 緙絲秋栗 (Sand/Stone/Purple)
    */
    'account_types' => [
        'cash' => [
            'name' => '現金',
            'bg' => 'bg-stone-50/80 hover:bg-amber-50/50 border-stone-200 dark:bg-stone-900/20 dark:hover:bg-stone-900/40 dark:border-stone-800',
            'border' => 'border-stone-200 hover:border-amber-200 dark:border-stone-800',
            'badge' => 'bg-stone-200/60 text-stone-700 dark:bg-stone-800 dark:text-stone-300',
            'left_bar' => 'bg-stone-400 dark:bg-stone-500', 
            'icon' => 'heroicon-o-currency-dollar',
        ],
        'bank' => [
            'name' => '銀行',
            'bg' => 'bg-slate-50/60 hover:bg-slate-100/60 dark:bg-slate-950/10 dark:hover:bg-slate-950/20',
            'border' => 'border-slate-200 hover:border-slate-300 dark:border-slate-900/30',
            'badge' => 'bg-slate-200/50 text-slate-700 dark:bg-slate-900/40 dark:text-slate-300',
            'left_bar' => 'bg-slate-600 dark:bg-slate-400',
            'icon' => 'heroicon-o-building-library',
        ],
        'e-wallet' => [
            'name' => '網絡支付',
            'bg' => 'bg-teal-50/30 hover:bg-teal-50/60 dark:bg-teal-950/10 dark:hover:bg-teal-950/20',
            'border' => 'border-teal-100 hover:border-teal-200 dark:border-teal-900/30',
            'badge' => 'bg-teal-100 text-teal-800 dark:bg-teal-900/40 dark:text-teal-300',
            'left_bar' => 'bg-teal-500/70',
            'icon' => 'heroicon-o-device-phone-mobile',
        ],
        'securities' => [
            'name' => '證券帳戶',
            'bg' => 'bg-neutral-50 hover:bg-purple-50/30 dark:bg-neutral-900/10 dark:hover:bg-purple-950/10',
            'border' => 'border-neutral-200 hover:border-purple-200/50 dark:border-neutral-800',
            'badge' => 'bg-purple-50 text-purple-700 dark:bg-purple-950/30 dark:text-purple-300',
            'left_bar' => 'bg-purple-400/80',
            'icon' => 'heroicon-o-arrow-trending-up',
        ],
    ],
    
	'backup' => [
        'disk' => env('BACKUP_DISK', 'local'),
        'path' => env('BACKUP_PATH', 'tianfu-backup'),
    ],
	
    /*
    |--------------------------------------------------------------------------
    | 其他業務邏輯預留
    |--------------------------------------------------------------------------
    */
    'payment_methods' => [],
    'expense_items' => [],
    'backup_paths' => [],
];