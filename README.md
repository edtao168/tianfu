# 天富記賬 - 多幣別財務管理系統

## 📖 專案簡介

天富記賬是一個基於 Laravel 11 + Livewire 4 開發的多幣別個人財務管理系統，以宋代美學為視覺風格，提供直覺的資產追蹤與記帳體驗。

## 🎨 設計理念

以「宋代美學」為核心視覺語言，融合：
- **汝窯天青** - 主色調 (Sky Blue)
- **定窯牙白** - 背景基底 (Warm White)
- **古宮絳紅** - 人民幣意象 (Rose Red)
- **千山青綠** - 港幣意象 (Emerald Green)
- **遠山黛紫** - 美元意象 (Violet)

## 🛠️ 技術棧

| 技術 | 版本 |
|------|------|
| **PHP** | 8.3.16 |
| **Laravel** | 13.18.1 |
| **Livewire** | 4.3.3 |
| **Mary UI** | 2.8.3 |
| **Tailwind CSS** | 4.3.2 |
| **DaisyUI** | 5.6.14 |
| **Vite** | 8.1.3 |
| **MySQL** | 8.4.3 |
| **Node.js** | 22.12.0 |

## 🚀 快速安裝

```bash
# 1. 複製專案
git clone https://github.com/edtaoisgod/tianfu.git
cd tianfu

# 2. 安裝 PHP 依賴
composer install

# 3. 安裝前端依賴
npm install

# 4. 編譯前端資源
npm run build

# 5. 設定環境
cp .env.example .env
php artisan key:generate

# 6. 執行資料庫遷移
php artisan migrate

# 7. 啟動開發伺服器
php artisan serve