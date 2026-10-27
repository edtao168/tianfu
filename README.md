# 添富記帳系統｜tianfu
> 一套以 Laravel 開發、重視隱私與資料自主的個人/小團體記帳系統

[![License: AGPL v3](https://img.shields.io/badge/License-AGPLv3-blue.svg)](https://www.gnu.org/licenses/agpl-3.0)

## ✨ 專案介紹
添富記帳是一套開源記帳系統，主打**資料自主掌控**：
- 無廣告、不追蹤使用者行為
- 原始碼完全公開，任何人可以審查程式邏輯
- 支援收支記錄、分類管理、預算規劃、報表統計、CSV 匯入匯出
- 適合個人記帳、家庭共用、小型工作室簡易收支管理

> 重要理念：
> 你永遠擁有自己的帳務資料。你可以自行部署、完整匯出資料，不受平台綁定。

---

## 🧩 兩種使用方式
### 1. 自託管（免費，推薦懂技術的使用者）
你可以自由下載原始碼、自行部署在自己的伺服器上，**完全免費使用**，遵守 AGPLv3 授權規範即可。

✅ 適合：會架站、想把帳務資料放在自己伺服器、重視隱私的使用者
⚠️ 目前狀態：本專案為 Laravel 專案，**尚未提供 Docker 一鍵包**，需自行準備 PHP / MySQL 環境，依照安裝文件部署。
> 後續會規劃 Docker Compose 簡化部署流程。

📌 自託管環境需求（Laravel）
- PHP 8.3+
- MySQL / MariaDB
- Composer
- Node.js（編譯前端資源）
> 本專案未依賴 Redis，不需要安裝

> 安裝步驟
```bash
git clone https://github.com/edtao168/tianfu.git
cd tianfu
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm install && npm run build
