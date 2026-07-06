<?php
// database/seeders/CategorySeeder.php
// php artisan db:seed --class=CategorySeeder

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Category::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // ==========================================
        // 1. 支出大類與子分類 (CWMoney 經典體系 - 移除字首冗餘)
        // ==========================================
        $expenseStructure = [
            '食品餐飲' => [
                'icon' => 'o-utensils',
                'sort_order' => 100,
                'children' => [
                    ['name' => '食材', 'icon' => 'o-shopping-bag', 'sort_order' => 101],
                    ['name' => '早餐', 'icon' => 'o-cake', 'sort_order' => 102],
                    ['name' => '午餐', 'icon' => 'o-hand-raised', 'sort_order' => 103],
                    ['name' => '晚餐', 'icon' => 'o-sparkles', 'sort_order' => 104],
                    ['name' => '酒', 'icon' => 'o-trophy', 'sort_order' => 105],
                    ['name' => '宵夜', 'icon' => 'o-moon', 'sort_order' => 106],
                ]
            ],
            '居家生活' => [
                'icon' => 'o-home',
                'sort_order' => 200,
                'children' => [
                    ['name' => '線下購物', 'icon' => 'o-shopping-cart', 'sort_order' => 201],
                    ['name' => '線上購物', 'icon' => 'o-globe-alt', 'sort_order' => 202],
                    ['name' => '房租房貸', 'icon' => 'o-key', 'sort_order' => 203],
                    ['name' => '水費', 'icon' => 'o-beaker', 'sort_order' => 204],
                    ['name' => '電費', 'icon' => 'o-bolt', 'sort_order' => 205],
                    ['name' => '燃氣', 'icon' => 'o-fire', 'sort_order' => 206],
                    ['name' => '網路通信', 'icon' => 'o-wifi', 'sort_order' => 207],
                    ['name' => '家電', 'icon' => 'o-tv', 'sort_order' => 208],
                    ['name' => '修繕', 'icon' => 'o-paint-brush', 'sort_order' => 209],
                ]
            ],
            '交通出行' => [
                'icon' => 'o-truck',
                'sort_order' => 300,
                'children' => [
                    ['name' => '保養維修', 'icon' => 'o-cog', 'sort_order' => 301],
                    ['name' => '大眾運輸', 'icon' => 'o-ticket', 'sort_order' => 302],                    
                    ['name' => '油費', 'icon' => 'o-fire', 'sort_order' => 303],
                    ['name' => '停車費', 'icon' => 'o-no-symbol', 'sort_order' => 304],
                    ['name' => '過路費', 'icon' => 'o-credit-card', 'sort_order' => 305],
                    ['name' => '計程車', 'icon' => 'o-user-group', 'sort_order' => 306], 
                ]
            ],
            '學習教育' => [
                'icon' => 'o-academic-cap',
                'sort_order' => 400,
                'children' => [
                    ['name' => '學費', 'icon' => 'o-academic-cap', 'sort_order' => 401],
                    ['name' => '書籍', 'icon' => 'o-book-open', 'sort_order' => 402],
                    ['name' => '文具', 'icon' => 'o-pencil-square', 'sort_order' => 403],
                    ['name' => '證照費', 'icon' => 'o-identification', 'sort_order' => 404],
                ]
            ],            
            '娛樂休閒' => [
                'icon' => 'o-film',
                'sort_order' => 500,
                'children' => [
                    ['name' => '旅遊', 'icon' => 'o-map', 'sort_order' => 501], // 修正為確定的官方圖示 o-map
                    ['name' => '電影劇場', 'icon' => 'o-film', 'sort_order' => 502],
                    ['name' => '唱歌', 'icon' => 'o-musical-note', 'sort_order' => 503],
                    ['name' => '遊戲', 'icon' => 'o-puzzle-piece', 'sort_order' => 504],
                    ['name' => '美髮美體', 'icon' => 'o-scissors', 'sort_order' => 505],
                ]
            ],
            '人情往來' => [
                'icon' => 'o-users',
                'sort_order' => 600,
                'children' => [
                    ['name' => '送禮', 'icon' => 'o-gift', 'sort_order' => 601],
                    ['name' => '孝親', 'icon' => 'o-user-group', 'sort_order' => 602],
                    ['name' => '子女教育', 'icon' => 'o-academic-cap', 'sort_order' => 603],
                    ['name' => '慈善', 'icon' => 'o-sun', 'sort_order' => 604],
                    ['name' => '寵物', 'icon' => 'o-face-smile', 'sort_order' => 605], // 修正重複的 o-sun
                ]
            ],
            '醫療保健' => [
                'icon' => 'o-heart',
                'sort_order' => 700,
                'children' => [
                    ['name' => '醫療', 'icon' => 'o-beaker', 'sort_order' => 701],
                    ['name' => '保健', 'icon' => 'o-heart', 'sort_order' => 702],
                ]
            ],
            '金融稅收' => [
                'icon' => 'o-shield-check',
                'sort_order' => 800,
                'children' => [
                    ['name' => '社會保險', 'icon' => 'o-building-office', 'sort_order' => 801],
                    ['name' => '私人保險', 'icon' => 'o-shield-check', 'sort_order' => 802],
                    ['name' => '財產險', 'icon' => 'o-document-duplicate', 'sort_order' => 803],
                    ['name' => '手續費', 'icon' => 'o-presentation-chart-line', 'sort_order' => 804],
                    ['name' => '投資虧損', 'icon' => 'o-arrow-trending-down', 'sort_order' => 805],
                    ['name' => '所得稅', 'icon' => 'o-receipt-percent', 'sort_order' => 806],
                    ['name' => '房屋稅', 'icon' => 'o-home', 'sort_order' => 807],
                    ['name' => '地價稅', 'icon' => 'o-globe-alt', 'sort_order' => 808],
                    ['name' => '牌照稅', 'icon' => 'o-key', 'sort_order' => 809],
                    ['name' => '公路養管費', 'icon' => 'o-wrench', 'sort_order' => 810], // 修正排序範圍與重複圖示
                ]
            ],
            '營業支出' => [
                'icon' => 'o-briefcase',
                'sort_order' => 900,
                'children' => [
                    ['name' => '進貨', 'icon' => 'o-cube', 'sort_order' => 901],
                    ['name' => '人工', 'icon' => 'o-user-plus', 'sort_order' => 902],
                    ['name' => '行銷', 'icon' => 'o-megaphone', 'sort_order' => 903],
                    ['name' => '辦公', 'icon' => 'o-building-office-2', 'sort_order' => 904],
                ]
            ],
            '其它支出' => [
                'icon' => 'o-ellipsis-horizontal',
                'sort_order' => 1000,
                'children' => [
                    ['name' => '借出', 'icon' => 'o-paper-airplane', 'sort_order' => 1001],
                    ['name' => '罰款', 'icon' => 'o-exclamation-triangle', 'sort_order' => 1002],
                    ['name' => '遺失', 'icon' => 'o-eye-slash', 'sort_order' => 1003],
                    ['name' => '呆帳', 'icon' => 'o-trash', 'sort_order' => 1004],
                ]
            ]
        ];

        // 寫入支出大類與對應子類
        foreach ($expenseStructure as $parentName => $parentInfo) {
            $parent = Category::create([
                'name' => $parentName,
                'type' => 'expense',
                'icon' => $parentInfo['icon'],
                'sort_order' => $parentInfo['sort_order'],
                'parent_id' => null,
                'is_active' => true,
            ]);

            foreach ($parentInfo['children'] as $child) {
                Category::create([
                    'name' => $child['name'],
                    'type' => 'expense',
                    'icon' => $child['icon'],
                    'sort_order' => $child['sort_order'],
                    'parent_id' => $parent->id,
                    'is_active' => true,
                ]);
            }
        }

        // ==========================================
        // 2. 收入大類與子分類 (主動與營運合併 / 被動 / 其它)
        // ==========================================
        $incomeStructure = [
            '主動收入' => [
                'icon' => 'o-banknotes',
                'sort_order' => 1200,
                'children' => [
                    ['name' => '工資', 'icon' => 'o-banknotes', 'sort_order' => 1201],
                    ['name' => '獎金', 'icon' => 'o-currency-dollar', 'sort_order' => 1202],
                    ['name' => '兼職', 'icon' => 'o-briefcase', 'sort_order' => 1203],
                    ['name' => '營業收入', 'icon' => 'o-shopping-bag', 'sort_order' => 1204],
                ]
            ],
            '被動收入' => [
                'icon' => 'o-chart-bar',
                'sort_order' => 1300,
                'children' => [
                    ['name' => '投資獲利', 'icon' => 'o-chart-bar', 'sort_order' => 1301],
                    ['name' => '存款利息', 'icon' => 'o-building-library', 'sort_order' => 1302],
                    ['name' => '配息', 'icon' => 'o-presentation-chart-line', 'sort_order' => 1303],
                    ['name' => '租金收入', 'icon' => 'o-home', 'sort_order' => 1304],
                    ['name' => '禮金', 'icon' => 'o-gift', 'sort_order' => 1305],
                    ['name' => '零用錢', 'icon' => 'o-user', 'sort_order' => 1306],
                    ['name' => '退休金', 'icon' => 'o-heart', 'sort_order' => 1307],
                ]
            ],
            '其它收入' => [
                'icon' => 'o-gift',
                'sort_order' => 1500,
                'children' => [
                    ['name' => '中獎', 'icon' => 'o-trophy', 'sort_order' => 1501],
                    ['name' => '退款', 'icon' => 'o-arrow-path', 'sort_order' => 1502],
                    ['name' => '理賠', 'icon' => 'o-shield-check', 'sort_order' => 1503],
                ]
            ]
        ];

        // 寫入收入大類與對應子類
        foreach ($incomeStructure as $parentName => $parentInfo) {
            $parent = Category::create([
                'name' => $parentName,
                'type' => 'income',
                'icon' => $parentInfo['icon'],
                'sort_order' => $parentInfo['sort_order'],
                'parent_id' => null,
                'is_active' => true,
            ]);

            foreach ($parentInfo['children'] as $child) {
                Category::create([
                    'name' => $child['name'],
                    'type' => 'income',
                    'icon' => $child['icon'],
                    'sort_order' => $child['sort_order'],
                    'parent_id' => $parent->id,
                    'is_active' => true,
                ]);
            }
        }
    }
}