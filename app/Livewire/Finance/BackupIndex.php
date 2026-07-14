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
    
    public string $storagePath = 'backup';
    public string $disk = 'local';

    public function mount()
    {
        $this->disk = 'local';
        $this->storagePath = 'backup';
        
        // 確保目錄存在
        $this->ensureBackupDirectoryExists();
    }
    
    protected function ensureBackupDirectoryExists()
    {
        $disk = Storage::disk($this->disk);
        if (!$disk->exists($this->storagePath)) {
            $disk->makeDirectory($this->storagePath, 0755, true);
        }
    }

    public function runBackup()
    {        
        try {
            // 確保目錄存在
            $this->ensureBackupDirectoryExists();
            
            // 執行備份
            $output = new BufferedOutput();
            $exitCode = Artisan::call('backup:run', [
                '--only-db' => true,
                '--no-interaction' => true,
            ], $output);
            
            $commandOutput = $output->fetch();
            
            \Log::info('備份執行結果', [
                'exit_code' => $exitCode,
                'output' => $commandOutput
            ]);
            
            // 檢查備份檔案
            $disk = Storage::disk($this->disk);
            
            // 檢查多個可能的路徑（因為 backup:run 可能在不同目錄產生檔案）
            $possiblePaths = [
                'backup',
                'private/backup',
                'private/' . config('backup.backup.name', 'backup'),
            ];
            
            $foundFiles = [];
            foreach ($possiblePaths as $path) {
                if ($disk->exists($path)) {
                    $files = $disk->files($path);
                    foreach ($files as $file) {
                        if (str_ends_with($file, '.zip')) {
                            $foundFiles[] = $file;
                        }
                    }
                }
            }
            
            // 如果找到檔案但不在 backup 目錄，移動到 backup 目錄
            foreach ($foundFiles as $file) {
                $filename = basename($file);
                $targetPath = 'backup/' . $filename;
                if ($file !== $targetPath) {
                    $disk->move($file, $targetPath);
                    \Log::info('移動備份檔案', ['from' => $file, 'to' => $targetPath]);
                }
            }
            
            // 再次檢查 backup 目錄
            $files = $disk->files('backup');
            $zipFiles = array_filter($files, fn($f) => str_ends_with($f, '.zip'));
            
            if (empty($zipFiles)) {
                \Log::error('備份失敗：無 ZIP 檔案', [
                    'output' => $commandOutput,
                    'checked_paths' => $possiblePaths,
                    'all_files' => $disk->allFiles()
                ]);
                
                $this->error('備份失敗', '未產生備份檔案。請查看 storage/logs/laravel.log');
                return;
            }
            
            // 備份成功
            unset($this->backups);
            $this->dispatch('$refresh');
            $this->success('備份成功', '新的備份檔已產生。');
            
        } catch (\Exception $e) {
            \Log::error('備份異常', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->error('備份失敗', $e->getMessage());
        }
    }
    
    public function getBackupsProperty(): array
    {        
        $disk = Storage::disk($this->disk);
        
        // 檢查 backup 目錄
        if (!$disk->exists('backup')) {
            return [];
        }

        $files = $disk->files('backup');
        
        return collect($files)
            ->filter(fn($path) => str_ends_with($path, '.zip'))
            ->map(fn($path) => [
                'name' => basename($path),
                'size' => round($disk->size($path) / 1024 / 1024, 2) . ' MB',
                'last_modified' => date('Y-m-d H:i:s', $disk->lastModified($path)),
            ])
            ->sortByDesc('last_modified')
            ->values()
            ->toArray();
    }

    public function download($filename)
    {
        $disk = Storage::disk($this->disk);
        $path = 'backup/' . $filename;
        
        if (!$disk->exists($path)) {
            $this->error('下載失敗', '找不到該備份檔案。');
            return;
        }
        
        return $disk->download($path, $filename);
    }

    /**
     * 診斷備份問題
     */
    public function diagnoseBackup()
    {
        $disk = Storage::disk($this->disk);
        
        // 1. 檢查 mysqldump
        $mysqldumpPath = config('backup.backup.source.databases.mysql.dump.dump_binary_path', '');
        $mysqldumpFull = $mysqldumpPath . 'mysqldump.exe';
        
        // 2. 檢查目錄
        $directories = [
            'backup' => $disk->exists('backup'),
            'private/backup' => $disk->exists('private/backup'),
            'backup-temp' => $disk->exists('backup-temp'),
        ];
        
        // 3. 檢查檔案
        $allFiles = $disk->allFiles();
        $zipFiles = array_filter($allFiles, fn($f) => str_ends_with($f, '.zip'));
        
        // 4. 顯示結果
        $msg = "=== 備份診斷 ===\n";
        $msg .= "mysqldump 路徑: {$mysqldumpFull}\n";
        $msg .= "mysqldump 存在: " . (file_exists($mysqldumpFull) ? '✅' : '❌') . "\n";
        $msg .= "\n目錄狀態:\n";
        foreach ($directories as $name => $exists) {
            $msg .= "  {$name}: " . ($exists ? '✅' : '❌') . "\n";
        }
        $msg .= "\nZIP 檔案總數: " . count($zipFiles) . "\n";
        if (!empty($zipFiles)) {
            $msg .= "檔案列表:\n";
            foreach ($zipFiles as $file) {
                $size = round($disk->size($file) / 1024, 2) . ' KB';
                $msg .= "  - {$file} ({$size})\n";
            }
        }
        
        $this->info($msg);
    }

    public function render()
    {
        return view('livewire.finance.backup-index', [
            'backups' => $this->backups
        ])->layout('layouts.app');
    }
}