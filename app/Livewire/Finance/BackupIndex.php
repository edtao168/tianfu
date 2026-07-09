<?php // [個人記帳系統] app/Livewire/Finance/BackupIndex.php

namespace App\Livewire\Finance;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\Attributes\Computed;
use Mary\Traits\Toast;

class BackupIndex extends Component
{
    use Toast;
	
    public string $storagePath;
    public string $disk;

    public function mount(): void
    {
        // 統一由商務設定檔讀取真相來源，初期預設為 local
        $this->disk = config('business.backup.disk', 'local');
        $this->storagePath = rtrim(config('business.backup.path', 'tianfu-backup'), '/');
    }

    /**
     * 執行備份指令
     */
    public function runBackup(): void
    {        
        try {
            // 呼叫 Spatie Laravel-backup 指令
            Artisan::call('backup:run', ['--only-db' => true]); // 個人記帳通常只需備份資料庫
            unset($this->backups);
            $this->success('備份成功', '大宋金櫃已有新的帳簿快照。');
        } catch (\Exception $e) {
            $this->error('備份失敗', $e->getMessage());
        }
    }
	
    /**
     * 獲取備份檔案列表 (Livewire 4 現代化 Computed 屬性)
     */
    #[Computed]
    public function backups(): array
    {        
        if (!Storage::disk($this->disk)->exists($this->storagePath)) {
            return [];
        }

        $files = Storage::disk($this->disk)->files($this->storagePath);
        
        return collect($files)
            ->filter(fn($path) => str_ends_with($path, '.zip')) // 只抓取壓縮檔
            ->map(fn($path) => [
                'name' => basename($path),
                'size' => round(Storage::disk($this->disk)->size($path) / 1024 / 1024, 2) . ' MB',
                'last_modified' => date('Y-m-d H:i:s', Storage::disk($this->disk)->lastModified($path)),
            ])
            ->sortByDesc('last_modified')
            ->toArray();
    }

	/**
     * 獲取備份檔案列表 (計算屬性)
     */
    public function getBackupsProperty(): array
    {        
        if (!Storage::disk($this->disk)->exists($this->storagePath)) {
            return [];
        }

        $files = Storage::disk($this->disk)->files($this->storagePath);
        
        return collect($files)
            ->filter(fn($path) => str_ends_with($path, '.zip')) 
            ->map(fn($path) => [
                'name' => basename($path),
                'size' => round(Storage::disk($this->disk)->size($path) / 1024 / 1024, 2) . ' MB',
                'last_modified' => date('Y-m-d H:i:s', Storage::disk($this->disk)->lastModified($path)),
            ])
            ->sortByDesc('last_modified')
            ->toArray();
    }
	
    /**
     * 線上下載備份檔
     */
    public function download($filename)
    {
        $disk = Storage::disk($this->disk);
        $path = $this->storagePath . '/' . $filename;
		
        if (!$disk->exists($path)) {
            $this->error('下載失敗', '找不到該帳簿快照。');
            return;
        }
	
        // 使用 Laravel/Livewire 串流下載，保護伺服器記憶體
        return response()->streamDownload(function () use ($disk, $path) {
            $stream = $disk->readStream($path);
            if ($stream) {
                fpassthru($stream);
                fclose($stream);
            }
        }, $filename);
    }

    public function render()
    {
        return view('livewire.finance.backup-index', [
            'backups' => $this->backups
        ])->layout('layouts.app');
    }
}