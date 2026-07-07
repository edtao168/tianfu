# ============================================
# 部署腳本 - OCI 生產環境
# 版本: 1.0.0
# 最後更新: 2026-07-07
# ============================================

set -e  # 遇到錯誤即停止

# 顏色定義
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# 設定變數
PROJECT_DIR="/var/www/html/tianfu"
BACKUP_DIR="/var/backups/tianfu"
PHP_VERSION="8.3"
DATE=$(date +%Y%m%d_%H%M%S)

# 顯示標題
echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}   添富記賬 - OCI 部署腳本                ${NC}"
echo -e "${BLUE}========================================${NC}"
echo -e "開始時間: $(date)"
echo ""

# 檢查是否在正確的目錄
if [ ! -d "$PROJECT_DIR" ]; then
    echo -e "${RED}錯誤: 專案目錄不存在: $PROJECT_DIR${NC}"
    exit 1
fi

# 進入專案目錄
cd $PROJECT_DIR || exit 1

# 1. 拉取最新代碼
echo -e "\n${YELLOW}[1/8] 拉取代碼...${NC}"
git fetch origin
git pull origin main

if [ $? -ne 0 ]; then
    echo -e "${RED}錯誤: Git pull 失敗${NC}"
    exit 1
fi
echo -e "${GREEN}✓ 代碼更新完成${NC}"

# 2. 安裝 PHP 依賴
echo -e "\n${YELLOW}[2/8] 安裝 PHP 依賴...${NC}"
composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev

if [ $? -ne 0 ]; then
    echo -e "${RED}錯誤: Composer 安裝失敗${NC}"
    exit 1
fi
echo -e "${GREEN}✓ PHP 依賴處理完成${NC}"

# 3. 安裝前端依賴
echo -e "\n${YELLOW}[3/8] 安裝前端依賴...${NC}"
if [ -f "package-lock.json" ]; then
    npm ci --no-audit --no-fund
else
    npm install --no-audit --no-fund
fi
echo -e "${GREEN}✓ 前端依賴安裝完成${NC}"

# 4. 編譯前端資源
echo -e "\n${YELLOW}[4/8] 編譯前端資源...${NC}"
npm run build

if [ $? -ne 0 ]; then
    echo -e "${RED}錯誤: 前端編譯失敗${NC}"
    exit 1
fi
echo -e "${GREEN}✓ 前端編譯完成${NC}"

# 5. 執行資料庫遷移
echo -e "\n${YELLOW}[5/8] 執行資料庫遷移...${NC}"
php artisan migrate --force

if [ $? -ne 0 ]; then
    echo -e "${RED}錯誤: 資料庫遷移失敗${NC}"
    exit 1
fi
echo -e "${GREEN}✓ 資料庫遷移完成${NC}"

# 6. 清除快取
echo -e "\n${YELLOW}[6/8] 清除快取...${NC}"
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
echo -e "${GREEN}✓ 快取處理完成${NC}"

# 7. 設定權限
echo -e "\n${YELLOW}[7/8] 設定權限...${NC}"
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
sudo chmod -R 755 public/build
echo -e "${GREEN}✓ 權限設定完成${NC}"

# 8. 重啟服務
echo -e "\n${YELLOW}[8/8] 重啟服務...${NC}"
sudo systemctl restart nginx
sudo systemctl restart php${PHP_VERSION}-fpm
echo -e "${GREEN}✓ 服務重啟完成${NC}"

# 計算時間
END_TIME=$(date +%s)
DURATION=$((END_TIME - START_TIME))

echo -e "\n${GREEN}========================================${NC}"
echo -e "${GREEN}✓ 部署成功完成！${NC}"
echo -e "  耗時: ${DURATION} 秒"
echo -e "  時間: $(date)"
echo -e "${GREEN}========================================${NC}"