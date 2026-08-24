<div class="p-6 max-w-5xl mx-auto space-y-6">
    
    {{-- 頁頭區 --}}
    <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4 border-b border-base-200 pb-4">
        <div>
            <h1 class="text-lg font-bold tracking-wider text-base-content flex items-center gap-2">
                <span class="w-1.5 h-4 bg-primary rounded-full"></span>
                帳簿備份設定
            </h1>
            <p class="text-xs text-base-content/70 mt-1">管理雲端同步之資料庫備份檔</p>
        </div>
        <div class="flex gap-2">
            <x-button 
                label="診斷" 
                icon="o-beaker" 
                class="btn-purple btn-sm sm:btn-md" 
                wire:click="diagnoseBackup" 
            />
            <x-button 
                label="立即備份" 
                icon="o-cpu-chip" 
                class="btn-dark btn-sm sm:btn-md" 
                wire:click="runBackup" 
                spinner="runBackup"
            />
        </div>
    </div>

    {{-- RWD 自適應表格區 --}}
    <div class="bg-base-100 rounded-lg border border-base-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table w-full">
                <thead>
                    <tr class="bg-base-200/50 text-base-content/70 text-xs">
                        <th>備份檔名</th>
                        <th>檔案大小</th>
                        <th>備份時間</th>
                        <th class="text-end">操作</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-base-200">
                    @forelse($this->backups as $file)
                        <tr class="hover:bg-base-200/30 transition-colors">
                            <td class="font-medium text-base-content break-all">
                                {{ $file['name'] }}
                            </td>
                            <td class="font-mono text-xs text-base-content/70 whitespace-nowrap">
                                {{ $file['size'] }}
                            </td>
                            <td class="font-mono text-xs text-base-content/70 whitespace-nowrap">
                                {{ $file['last_modified'] }}
                            </td>
                            <td class="text-end">
                                <x-button 
                                    icon="o-arrow-down-tray" 
                                    wire:click="download('{{ $file['name'] }}')" 
                                    class="btn-ghost btn-sm text-base-content/70 hover:text-base-content" 
                                    spinner="download('{{ $file['name'] }}')"
                                    tooltip="下載到本地"
                                />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center py-10 bg-base-100">
                                <x-icon name="o-exclamation-triangle" class="w-8 h-8 mx-auto text-base-content/30 mb-2" />
                                <p class="text-xs text-base-content/60 font-medium tracking-wider">目前尚無備份檔案</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>