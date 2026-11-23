<?php

return [
    /*
    |--------------------------------------------------------------------------
    | 幣別主題色：目前只定義red、orange、green、blue、purple

    |--------------------------------------------------------------------------
    */
    'currency_theme_map' => [
        'TWD' => 'blue',
        'CNY' => 'red', 
        'HKD' => 'orange',
        'USD' => 'green',
		'CAD' => 'red',
		'EUR' => 'orange',
		'GBP' => 'purple',
		'AUD' => 'green',
		'CHF' => 'purple',
    ],

    /*
    |--------------------------------------------------------------------------
    | 帳戶類型 (Account Types)
    |--------------------------------------------------------------------------
    | 每個類型包含：顯示名稱、圖示、主題色
    */
    'account_types' => [
        'cash' => [
            'name'  => '現金',
            'icon'  => 'heroicon-o-currency-dollar',
            'theme' => 'orange',
        ],
        'bank' => [
            'name'  => '銀行',
            'icon'  => 'heroicon-o-building-library',
            'theme' => 'blue',
        ],
        'e-wallet' => [
            'name'  => '電子支付',
            'icon'  => 'heroicon-o-device-phone-mobile',
            'theme' => 'green',
        ],
        'securities' => [
            'name'  => '證券',
            'icon'  => 'heroicon-o-arrow-trending-up',
            'theme' => 'purple',
        ],
    ],
	
	// 收支統計卡片的主題映射（原寫死於 Blade）
    'period_theme_map' => [
        'today' => 'blue',
        'month' => 'green',
        'year'  => 'purple',
    ],

    // 幣別主題色 → Tailwind 色票名稱映射（原寫死於 Blade）
    // 註：key 為 currency_theme_map 的值（blue/red/green/purple/orange），
    //     value 為 Tailwind 色票（sky/rose/emerald/violet/orange）。
    'currency_tailwind_color_map' => [
        'blue'   => 'sky',
        'red'    => 'rose',
        'green'  => 'emerald',
        'purple' => 'violet',
        'orange' => 'orange',
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