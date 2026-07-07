# ============================================
# 回滾腳本 - OCI 生產環境
# ============================================

set -e

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

PROJECT_DIR="/var/www/html/tianfu"
BACKUP_DIR="/var/backups/tianfu"

echo -e "${YELLOW}警告: 這將回滾到上一個版本${NC}"
read -p "確認執行回滾？(y/n): " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "已取消"
    exit 1
fi

# 查找最新的備份
LATEST_BACKUP=$(ls -t $BACKUP_DIR/tianfu_backup_*.tar.gz 2>/dev/null | head -1)

if [ -z "$LATEST_BACKUP" ]; then
    echo -e "${RED}錯誤: 找不到備份檔案${NC}"
    exit 1
fi

echo -e "使用備份: $LATEST_BACKUP"

# 執行回滾
cd /var/www/html
sudo rm -rf tianfu
sudo tar -xzf $LATEST_BACKUP
sudo chown -R www-data:www-data tianfu
sudo systemctl restart nginx php8.3-fpm

echo -e "${GREEN}✓ 回滾完成${NC}"