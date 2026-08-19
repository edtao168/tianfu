# Tianfu 系統備份與災難復原 (Disaster Recovery) 說明文件

本打包工具包含完整針對 OCI (Oracle Cloud Infrastructure) Ubuntu 伺服器運行的 Laravel 專案所設計的備份與回滾腳本。

---

## 📁 檔案結構

- `backup.sh`：全自動備份腳本（備份 MySQL、.env、storage/、Apache/Nginx/Supervisor 設定檔）
- `rollback.sh`：自動化回滾與災難復原腳本（恢復 Code、.env、storage/、MySQL、權限修復與重啟服務）
- `deploy.sh`：日常 Git 部署腳本（含 OCI iptables 開放與權限修復）

---

## 🚀 快速使用指南

### 1. 建立自動化每日備份 (Crontab)

請在伺服器上執行 `sudo crontab -e` 並加入以下排程（每天凌晨 03:00 自動執行備份）：

```bash
0 3 * * * /bin/bash /var/www/html/tianfu/backup.sh >> /var/log/tianfu_backup.log 2>&1
```

### 2. 手動執行備份

```bash
sudo bash backup.sh
```

備份檔將自動儲存於 `/var/backups/tianfu/tianfu_backup_YYYYMMDD_HHMMSS.tar.gz`。

---

## 🔄 執行回滾 / 災難復原

當系統崩潰或更新出錯需要退回上一版本時：

### 情況 A：自動尋找最新備份檔並回滾

```bash
sudo bash rollback.sh
```

### 情況 B：指定特定的歷史備份檔進行還原

```bash
sudo bash rollback.sh /var/backups/tianfu/tianfu_backup_20260330_120000.tar.gz
```

---

## 🛡️ OCI 專屬注意事項

1. **iptables 防火牆：** OCI Ubuntu 預設封鎖 80/443 port，`backup.sh` 與 `deploy.sh` 已包含自動修復規則。
2. **權限問題：** 腳本執行完成後會自動將 `storage/` 及 `bootstrap/cache/` 設定為 `www-data:www-data` 並給予 `775` 權限，避免 500 錯誤。
3. **.env 安全性：** 備份檔包含 `.env`（內含 DB 密碼與 APP_KEY），請妥善保管 `/var/backups/tianfu/` 目錄。
4.已忽略對 deploy.sh 的上傳（oci與本地不一致，已鎖定git update-index --assume-unchanged deploy.sh，解鎖需git update-index --no-assume-unchanged deploy.sh）

#檢查備份執行狀況：
	## 查看進銷存備份日誌cat /var/log/taotique_backup.log
	## 查看記帳系統備份日誌cat /var/log/tianfu_backup.log
如果日誌最後出現 Backup completed successfully 之類的成功提示，且備份目錄 /var/backups/taotique 與 /var/backups/tianfu 都有產生新的 .tar.gz 壓縮檔，就代表整個災難復原（DR）備份機制已經完全穩定運作了！

# 備份檔將自動儲存於 /var/backups/tianfu/， /var/backups/taotique/
	## OCI： ls /var/backups/tianfu/
	## 本地：ssh -i "C:\laragon\www\keys\ssh-key-2026-03-07.key" ubuntu@158.101.10.167 "ls -l /var/backups/tianfu/"
	## OCI： ls  /var/backups/taotique/
	## 本地：ssh -i "C:\laragon\www\keys\ssh-key-2026-03-07.key" ubuntu@158.101.10.167 "ls -l /var/backups/taotique/"

# 備份複製到本地，
	## 先查找OCI備份文件目錄
		### ssh -i "C:\laragon\www\keys\ssh-key-2026-03-07.key" ubuntu@158.101.10.167
		### OCI cd /var/www/html
		ls /var/backups/taotique/ *.tar.gz
		exit
	## 下載到本地（本地命令）
		scp -i "C:\laragon\www\keys\ssh-key-2026-03-07.key" ubuntu@158.101.10.167:/var/backups/tianfu/*.tar.gz D:\Users\Administrator\Downloads
		scp -i "C:\laragon\www\keys\ssh-key-2026-03-07.key" ubuntu@158.101.10.167:/var/backups/taotique/*.tar.gz D:\Users\Administrator\Downloads
		（指定日期）scp -i "C:\laragon\www\keys\ssh-key-2026-03-07.key" ubuntu@158.101.10.167:/var/backups/tianfu/*0815*.tar.gz D:\Users\Administrator\Downloads
		（指定日期）scp -i "C:\laragon\www\keys\ssh-key-2026-03-07.key" ubuntu@158.101.10.167:/var/backups/taotique/*0815*.tar.gz D:\Users\Administrator\Downloads
