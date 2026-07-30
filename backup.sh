#!/bin/bash
# ==============================================================================
# 檔案名稱: backup.sh
# 說明: OCI Ubuntu + Laravel 專用全自動備份腳本
# 包含: MySQL 資料庫、.env、storage/app/public、Apache Vhost & 系統設定檔
# ==============================================================================

set -eo pipefail

# 顏色定義
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
NC='\033[0m'

# 基本變數設定
PROJECT_DIR="/var/www/html/tianfu"
BACKUP_ROOT="/var/backups/tianfu"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_NAME="tianfu_backup_${TIMESTAMP}"
WORK_DIR="${BACKUP_ROOT}/${BACKUP_NAME}"
RETENTION_DAYS=30

# 建立備份目錄
mkdir -p "${WORK_DIR}"
mkdir -p "${WORK_DIR}/config"

echo -e "${CYAN}==========================================${NC}"
echo -e "${CYAN}   開始執行 Tianfu 系統全備份 [${TIMESTAMP}]${NC}"
echo -e "${CYAN}==========================================${NC}"

# 1. 備份 MySQL 資料庫
echo -e "${YELLOW}[1/6] 正在備份 MySQL 資料庫...${NC}"
if [ -f "${PROJECT_DIR}/.env" ]; then
    DB_DATABASE=$(grep "^DB_DATABASE=" "${PROJECT_DIR}/.env" | cut -d'=' -f2 | tr -d '"'')
    DB_USERNAME=$(grep "^DB_USERNAME=" "${PROJECT_DIR}/.env" | cut -d'=' -f2 | tr -d '"'')
    DB_PASSWORD=$(grep "^DB_PASSWORD=" "${PROJECT_DIR}/.env" | cut -d'=' -f2 | tr -d '"'')
    
    if [ -n "$DB_DATABASE" ]; then
        MYSQL_PWD="${DB_PASSWORD}" mysqldump -u"${DB_USERNAME}" "${DB_DATABASE}" > "${WORK_DIR}/database.sql"
        echo -e "${GREEN}✓ 資料庫已匯出: database.sql${NC}"
    else
        echo -e "${RED}✗ 無法從 .env 讀取資料庫設定，嘗試使用 default 備份...${NC}"
        mysqldump --defaults-extra-file=/etc/mysql/debian.cnf --all-databases > "${WORK_DIR}/database_all.sql" || true
    fi
else
    echo -e "${RED}✗ 找不到 .env 檔案，跳過資料庫備份！${NC}"
fi

# 2. 備份 .env 檔案 (敏感環境變數快照)
echo -e "${YELLOW}[2/6] 正在備份 .env 環境變數檔案...${NC}"
if [ -f "${PROJECT_DIR}/.env" ]; then
    cp "${PROJECT_DIR}/.env" "${WORK_DIR}/.env"
    echo -e "${GREEN}✓ .env 已備份${NC}"
else
    echo -e "${RED}✗ 找不到 .env 檔案！${NC}"
fi

# 3. 備份 storage/ 檔案庫 (商品圖片、附件等)
echo -e "${YELLOW}[3/6] 正在備份 storage/app 媒體附件...${NC}"
if [ -d "${PROJECT_DIR}/storage" ]; then
    mkdir -p "${WORK_DIR}/storage"
    # 僅備份 app 與必要目錄，忽略大型 logs 與 framework cache
    rsync -a --exclude='logs/*' --exclude='framework/cache/*' --exclude='framework/sessions/*' --exclude='framework/views/*' "${PROJECT_DIR}/storage/" "${WORK_DIR}/storage/"
    echo -e "${GREEN}✓ storage 目錄已打包${NC}"
fi

# 4. 備份關鍵系統設定 (Apache Vhost, Crontab, Supervisor, iptables)
echo -e "${YELLOW}[4/6] 正在備份伺服器相關配置檔...${NC}"
# Apache vhosts
if [ -d "/etc/apache2/sites-available" ]; then
    cp -r /etc/apache2/sites-available "${WORK_DIR}/config/apache_sites" 2>/dev/null || true
fi
# Nginx configs (相容性預留)
if [ -d "/etc/nginx/sites-available" ]; then
    cp -r /etc/nginx/sites-available "${WORK_DIR}/config/nginx_sites" 2>/dev/null || true
fi
# Crontab
crontab -l -u www-data > "${WORK_DIR}/config/crontab_www_data.txt" 2>/dev/null || true
crontab -l -u root > "${WORK_DIR}/config/crontab_root.txt" 2>/dev/null || true
# Supervisor
if [ -d "/etc/supervisor/conf.d" ]; then
    cp -r /etc/supervisor/conf.d "${WORK_DIR}/config/supervisor" 2>/dev/null || true
fi
# iptables rules (OCI 專屬)
iptables-save > "${WORK_DIR}/config/iptables.rules" 2>/dev/null || true
echo -e "${GREEN}✓ 系統設定檔已備份${NC}"

# 5. 打包專案代碼 (選備，提供完整冷備份)
echo -e "${YELLOW}[5/6] 正在複製專案原始碼結構...${NC}"
mkdir -p "${WORK_DIR}/app_source"
rsync -a --exclude='node_modules' --exclude='vendor' --exclude='storage' --exclude='.git' "${PROJECT_DIR}/" "${WORK_DIR}/app_source/"
echo -e "${GREEN}✓ 原始碼快照完成${NC}"

# 6. 壓縮打包並清理臨時檔案
echo -e "${YELLOW}[6/6] 正在壓縮成 tar.gz 封包...${NC}"
cd "${BACKUP_ROOT}"
tar -czf "${BACKUP_NAME}.tar.gz" "${BACKUP_NAME}"
rm -rf "${WORK_DIR}"

# 7. 自動清理過期備份
echo -e "${YELLOW}清理 ${RETENTION_DAYS} 天前的舊備份...${NC}"
find "${BACKUP_ROOT}" -name "tianfu_backup_*.tar.gz" -type f -mtime +${RETENTION_DAYS} -delete

BACKUP_SIZE=$(du -sh "${BACKUP_ROOT}/${BACKUP_NAME}.tar.gz" | cut -f1)
echo -e "${GREEN}==========================================${NC}"
echo -e "${GREEN}✓ 備份成功完成！${NC}"
echo -e "${GREEN}  檔名: ${BACKUP_NAME}.tar.gz${NC}"
echo -e "${GREEN}  大小: ${BACKUP_SIZE}${NC}"
echo -e "${GREEN}  路徑: ${BACKUP_ROOT}/${BACKUP_NAME}.tar.gz${NC}"
echo -e "${GREEN}==========================================${NC}"
