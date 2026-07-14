<?php

namespace App\Livewire\Finance;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Mary\Traits\Toast;
use Symfony\Component\Console\Output\BufferedOutput;

class BackupIndex extends Component
{
    use Toast;
    
    public string $storagePath;
    public string $disk;

    public function mount()
    {
        $this->disk = config('business.backup.disk', 'local');
        $this->storagePath = rtrim(config('business.backup.path', 'tianfu-backup'), '/');
    }

    public function getShopId()
    {
        return auth()->user()->shop_id ?? 1;
    }

    /**
     * 執行備份指令 - 改良版
     */
    public function runBackup()
    {        
        try {
            // 1. 確保備份目錄存在
            $disk = Storage::disk($this->disk);
            if (!$disk->exists($this->storagePath)) {
                $disk->makeDirectory($this->storagePath, 0755, true);
            }

            // 2. 記錄備份前的檔案
            $beforeFiles = $disk->files($this->storagePath);
            
            // 3. 執行備份並捕獲輸出
            $output = new BufferedOutput();
            $exitCode = Artisan::call('backup:run', [
                '--only-db' => true,  // 只備份資料庫
                '--no-interaction' => true,
            ], $output);
            
            $commandOutput = $output->fetch();
            
            // 4. 記錄到日誌以便除錯
            \Log::info('備份執行結果', [
                'exit_code' => $exitCode,
                'output' => $commandOutput
            ]);

            // 5. 檢查是否有新檔案產生
            $afterFiles = $disk->files($this->storagePath);
            $newFiles = array_diff($afterFiles, $beforeFiles);
            $zipFiles = array_filter($afterFiles, fn($f) => str_ends_with($f, '.zip'));
            
            if (empty($zipFiles)) {
                // 沒有產生 ZIP 檔案，備份失敗
                $errorMsg = "備份命令執行但未產生 ZIP 檔案\n";
                $errorMsg .= "命令輸出: " . substr($commandOutput, 0, 500);
                
                info('備份失敗：無 ZIP 檔案', [
                    'output' => $commandOutput,
                    'before_files' => $beforeFiles,
                    'after_files' => $afterFiles,
                    'new_files' => $newFiles
                ]);
                
                $this->error('備份失敗', '未產生備份檔案，請查看 storage/logs/laravel.log');
                return;
            }
            
            // 6. 備份成功
            unset($this->backups);
            $this->dispatch('$refresh');
            $this->success('備份成功', '新的備份檔已產生。');
            
        } catch (\Exception $e) {
            info('備份異常', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->error('備份失敗', $e->getMessage());
        }
    }
    
    /**
     * 獲取備份檔案列表
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
     * 下載備份檔
     */
    public function download($filename)
    {
        $disk = Storage::disk($this->disk);
        $path = $this->storagePath . '/' . $filename;
        
        if (!$disk->exists($path)) {
            $this->error('下載失敗', '找不到該備份檔案。');
            return;
        }
        
        return response()->streamDownload(function () use ($disk, $path) {
            $stream = $disk->readStream($path);
            if ($stream) {
                fpassthru($stream);
                fclose($stream);
            }
        }, $filename);
    }

    /**
     * 診斷功能 - 找出備份失敗原因
     */
    public function diagnoseBackup()
    {
        $results = [];
        
        // 1. 檢查配置
        $results['config'] = [
            'disk' => $this->disk,
            'path' => $this->storagePath,
            'backup_disk' => config('backup.destination.disk', '未配置'),
            'backup_path' => config('backup.destination.path', '未配置'),
        ];
        
        // 2. 檢查目錄
        $disk = Storage::disk($this->disk);
        $fullPath = storage_path('app/' . $this->storagePath);
        $results['storage'] = [
            'full_path' => $fullPath,
            'exists' => $disk->exists($this->storagePath),
            'is_writable' => is_writable(dirname($fullPath)),
            'permissions' => fileperms(dirname($fullPath)) ? substr(sprintf('%o', fileperms(dirname($fullPath))), -4) : 'N/A',
        ];
        
        // 3. 檢查備份檔案
        if ($disk->exists($this->storagePath)) {
            $files = $disk->files($this->storagePath);
            $zipFiles = array_filter($files, fn($f) => str_ends_with($f, '.zip'));
            $results['files'] = [
                'count' => count($files),
                'zip_count' => count($zipFiles),
                'all' => $files,
            ];
        } else {
            $results['files'] = ['count' => 0, 'zip_count' => 0, 'all' => []];
        }
        
        // 4. 檢查資料庫
        try {
            $connection = config('database.default');
            $dbName = config("database.connections.{$connection}.database");
            \DB::connection()->getPdo();
            $results['database'] = [
                'status' => 'connected',
                'connection' => $connection,
                'database' => $dbName,
            ];
        } catch (\Exception $e) {
            $results['database'] = [
                'status' => 'failed',
                'error' => $e->getMessage(),
            ];
        }
        
        // 5. 檢查 mysqldump
        $mysqldumpPaths = ['/usr/bin/mysqldump', '/usr/local/bin/mysqldump', '/opt/homebrew/bin/mysqldump'];
        $found = null;
        foreach ($mysqldumpPaths as $path) {
            if (file_exists($path)) {
                $found = $path;
                break;
            }
        }
        $results['mysqldump'] = [
            'found' => $found ?? 'not found',
            'configured' => config('backup.source.databases.mysql.dump.dump_binary_path', '未配置'),
        ];
        
        // 6. 寫入測試
        try {
            $testFile = $this->storagePath . '/test-' . date('Ymd_His') . '.txt';
            $disk->put($testFile, 'test');
            $disk->delete($testFile);
            $results['write_test'] = 'success';
        } catch (\Exception $e) {
            $results['write_test'] = 'failed: ' . $e->getMessage();
        }
        
        // 記錄到日誌
        \Log::info('備份診斷結果', $results);
        
        // 顯示簡易結果
        $msg = "=== 備份診斷結果 ===\n";
        $msg .= "備份目錄: {$fullPath}\n";
        $msg .= "目錄存在: " . ($results['storage']['exists'] ? '✅ 是' : '❌ 否') . "\n";
        $msg .= "目錄可寫: " . ($results['storage']['is_writable'] ? '✅ 是' : '❌ 否') . "\n";
        $msg .= "ZIP檔案數量: {$results['files']['zip_count']}\n";
        $msg .= "資料庫: " . ($results['database']['status'] === 'connected' ? '✅ 已連接' : '❌ 連接失敗') . "\n";
        $msg .= "mysqldump: " . ($results['mysqldump']['found'] ? '✅ 已找到' : '❌ 未找到') . "\n";
        $msg .= "寫入測試: " . ($results['write_test'] === 'success' ? '✅ 成功' : '❌ 失敗') . "\n";
        $msg .= "\n請查看 storage/logs/laravel.log 獲取詳細資訊";
        
        $this->info($msg);
    }

    public function render()
	{
		return view('livewire.finance.backup-index', [
			'backups' => $this->backups
		])->layout('layouts.app');
	}
}