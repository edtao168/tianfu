#!/bin/bash
# ==============================================================================
# 檔案名稱: rollback.sh
# 說明: OCI Ubuntu + Laravel + Apache/Nginx 系統完整災難復原與回滾腳本
# 支援: 自動還原 Code、.env、storage/、MySQL 資料庫、檔案權限與 OCI 防火牆
# ==============================================================================

set -eo pipefail

# 顏色定義
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
NC='\033[0m'

PROJECT_DIR="/var/www/html/tianfu"
BACKUP_DIR="/var/backups/tianfu"
TEMP_RESTORE_DIR="/tmp/tianfu_restore_temp"

echo -e "${RED}==========================================${NC}"
echo -e "${RED}   警告: 即將進行系統回滾 / 災難復原操作   ${NC}"
echo -e "${RED}==========================================${NC}"

# 確認權限
if [ "$EUID" -ne 0 ]; then
  echo -e "${RED}錯誤: 請使用 sudo 或 root 權限執行此腳本！${NC}"
  exit 1
fi

# 查找最新的備份 (允許指定參數或自動尋找最新)
if [ -n "$1" ] && [ -f "$1" ]; then
    LATEST_BACKUP="$1"
else
    LATEST_BACKUP=$(ls -t ${BACKUP_DIR}/tianfu_backup_*.tar.gz 2>/dev/null | head -1 || true)
fi

if [ -z "$LATEST_BACKUP" ] || [ ! -f "$LATEST_BACKUP" ]; then
    echo -e "${RED}錯誤: 在 ${BACKUP_DIR} 中找不到任何有效的備份檔案 (.tar.gz)${NC}"
    exit 1
fi

echo -e "${CYAN}準備使用的備份檔: ${LATEST_BACKUP}${NC}"
read -p "確定要回滾覆蓋現有系統與資料庫嗎？(y/N): " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo -e "${YELLOW}操作已取消。${NC}"
    exit 0
fi

# 清理解壓暫存區
rm -rf "${TEMP_RESTORE_DIR}"
mkdir -p "${TEMP_RESTORE_DIR}"

echo -e "${YELLOW}[1/8] 解壓縮備份包...${NC}"
tar -xzf "${LATEST_BACKUP}" -C "${TEMP_RESTORE_DIR}"
INNER_FOLDER=$(ls "${TEMP_RESTORE_DIR}")
RESTORE_SRC="${TEMP_RESTORE_DIR}/${INNER_FOLDER}"

# 進入維護模式 (若專案目錄還存在)
if [ -d "${PROJECT_DIR}" ] && [ -f "${PROJECT_DIR}/artisan" ]; then
    echo -e "${YELLOW}[2/8] 開啟 Laravel 維護模式...${NC}"
    php "${PROJECT_DIR}/artisan" down || true
fi

# 3. 還原程式碼結構
echo -e "${YELLOW}[3/8] 還原專案程式碼與結構...${NC}"
mkdir -p "${PROJECT_DIR}"

if [ -d "${RESTORE_SRC}/app_source" ]; then
    rsync -a --delete "${RESTORE_SRC}/app_source/" "${PROJECT_DIR}/"
fi

# 4. 還原 .env 檔案
echo -e "${YELLOW}[4/8] 還原 .env 環境變數設定檔...${NC}"
if [ -f "${RESTORE_SRC}/.env" ]; then
    cp "${RESTORE_SRC}/.env" "${PROJECT_DIR}/.env"
    echo -e "${GREEN}✓ .env 已恢復${NC}"
fi

# 5. 還原 storage/ 目錄
echo -e "${YELLOW}[5/8] 還原 storage/ 媒體與快取目錄...${NC}"
if [ -d "${RESTORE_SRC}/storage" ]; then
    mkdir -p "${PROJECT_DIR}/storage"
    rsync -a "${RESTORE_SRC}/storage/" "${PROJECT_DIR}/storage/"
    echo -e "${GREEN}✓ storage 目錄已恢復${NC}"
fi

# 6. 還原 MySQL 資料庫
echo -e "${YELLOW}[6/8] 還原 MySQL 資料庫...${NC}"
if [ -f "${RESTORE_SRC}/database.sql" ] && [ -f "${PROJECT_DIR}/.env" ]; then
    DB_DATABASE=$(grep "^DB_DATABASE=" "${PROJECT_DIR}/.env" | cut -d'=' -f2 | tr -d '"'')
    DB_USERNAME=$(grep "^DB_USERNAME=" "${PROJECT_DIR}/.env" | cut -d'=' -f2 | tr -d '"'')
    DB_PASSWORD=$(grep "^DB_PASSWORD=" "${PROJECT_DIR}/.env" | cut -d'=' -f2 | tr -d '"'')

    if [ -n "$DB_DATABASE" ]; then
        echo -e "${CYAN}正在匯入資料庫 [${DB_DATABASE}]...${NC}"
        MYSQL_PWD="${DB_PASSWORD}" mysql -u"${DB_USERNAME}" "${DB_DATABASE}" < "${RESTORE_SRC}/database.sql"
        echo -e "${GREEN}✓ 資料庫匯入成功${NC}"
    fi
elif [ -f "${RESTORE_SRC}/database_all.sql" ]; then
    mysql --defaults-extra-file=/etc/mysql/debian.cnf < "${RESTORE_SRC}/database_all.sql"
    echo -e "${GREEN}✓ 全域資料庫匯入成功${NC}"
fi

# 7. 修正權限與建立 Symlink
echo -e "${YELLOW}[7/8] 修正目錄權限與重建 storage:link...${NC}"
cd "${PROJECT_DIR}"

# 安裝/補全 composer 套件 (若有需要)
if [ -f "composer.json" ] && [ -d "vendor" ]; then
    composer install --no-dev --optimize-autoloader 2>/dev/null || true
fi

# 重新建立 storage 軟連結
php artisan storage:link --force 2>/dev/null || true

# 重新快照優化
php artisan optimize:clear 2>/dev/null || true
php artisan optimize 2>/dev/null || true

# 解除維護模式
php artisan up 2>/dev/null || true

# 權限修正 (給予 Apache/Nginx www-data 存取權)
chown -R www-data:www-data "${PROJECT_DIR}"
find "${PROJECT_DIR}" -type d -exec chmod 755 {} \;
find "${PROJECT_DIR}" -type f -exec chmod 644 {} \;
chmod -R 775 "${PROJECT_DIR}/storage" "${PROJECT_DIR}/bootstrap/cache"

# 8. 重啟服務 (Apache / Nginx / PHP-FPM)
echo -e "${YELLOW}[8/8] 重啟 Web 伺服器與相關服務...${NC}"
if systemctl is-active --quiet apache2; then
    systemctl restart apache2
    echo -e "${GREEN}✓ Apache2 已重啟${NC}"
fi

if systemctl is-active --quiet nginx; then
    systemctl restart nginx
    echo -e "${GREEN}✓ Nginx 已重啟${NC}"
fi

PHP_VER=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;" 2>/dev/null || echo "8.3")
if systemctl is-active --quiet "php${PHP_VER}-fpm"; then
    systemctl restart "php${PHP_VER}-fpm"
    echo -e "${GREEN}✓ php${PHP_VER}-fpm 已重啟${NC}"
fi

# 清理暫存
rm -rf "${TEMP_RESTORE_DIR}"

echo -e "${GREEN}==========================================${NC}"
echo -e "${GREEN}✓ 系統回滾與災難復原成功完成！${NC}"
echo -e "${GREEN}==========================================${NC}"
