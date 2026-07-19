{{-- resources/views/livewire/finance/backup-index.blade.php --}}

<div class="p-6 max-w-5xl mx-auto space-y-6">
    
    {{-- 頁頭區 --}}
    <div class="flex justify-between items-center border-b border-stone-200/60 pb-4">
		<div>
			<h1 class="text-lg font-bold tracking-wider text-stone-800 dark:text-stone-500 flex items-center gap-2">
				<span class="w-1.5 h-4 rounded-full bg-stone-600"></span>
				帳簿備份設定
			</h1>
			<p class="text-xs text-stone-400 mt-1">管理雲端同步之資料庫備份檔</p>
		</div>
		<div class="flex gap-2">
			<x-button 
				label="診斷" 
				icon="o-beaker" 
				class="btn-sm rounded-lg bg-stone-100 border-stone-200 text-stone-600 hover:bg-stone-200 font-medium" 
				wire:click="diagnoseBackup" 
			/>
			<x-button 
				label="立即備份" 
				icon="o-cpu-chip" 
				class="btn-sm rounded-lg bg-stone-800 border-stone-800 text-stone-50 hover:bg-stone-700 font-medium tracking-wider shadow-sm" 
				wire:click="runBackup" 
				spinner="runBackup"
			/>
		</div>
	</div>

    {{-- 💻 PC 端大螢幕：嚴謹的 Mary UI 表格 --}}
    <div class="hidden md:block animate-fadeIn">
        <x-card class="bg-white/50 border-stone-200/60 shadow-sm">
            <x-table :headers="[
                ['key' => 'name', 'label' => '備份檔名'],
                ['key' => 'size', 'label' => '檔案大小'],
                ['key' => 'last_modified', 'label' => '備份時間'],
                ['key' => 'actions', 'label' => '操作', 'sortable' => false],
            ]" :rows="$this->backups">
                @scope('cell_name', $file)
                    <span class="font-medium text-stone-700 dark:text-stone-300">{{ $file['name'] }}</span>
                @endscope

                @scope('cell_size', $file)
                    <span class="font-mono text-xs opacity-80">{{ $file['size'] }}</span>
                @endscope

                @scope('cell_last_modified', $file)
                    <span class="font-mono text-xs opacity-70">{{ $file['last_modified'] }}</span>
                @endscope

                @scope('cell_actions', $file)
                    <x-button 
                        icon="o-arrow-down-tray" 
                        wire:click="download('{{ $file['name'] }}')" 
                        class="btn-ghost btn-sm text-stone-500 hover:text-stone-800 transition-colors" 
                        spinner="download('{{ $file['name'] }}')"
                        tooltip="下載到本地"
                    />
                @endscope
            </x-table>
        </x-card>
    </div>

    {{-- 📱 手機端小螢幕：直接將卡片代碼寫在這裡，不再調用外部 include --}}
    <div class="block md:hidden animate-fadeIn">
        <div class="grid grid-cols-1 gap-4">
            @forelse($this->backups as $file)
                <div class="card p-5 border border-stone-200/60 bg-white/80 dark:bg-stone-900/40 relative overflow-hidden pl-6 flex justify-between items-center flex-row">
                    <span class="absolute left-0 top-0 bottom-0 w-1.5 bg-stone-500/60"></span>
                    
                    <div class="max-w-[70%] space-y-1">
                        <div class="font-bold text-sm truncate text-stone-800 dark:text-stone-200 tracking-wide">
                            {{ $file['name'] }}
                        </div>
                        <div class="text-[11px] text-stone-400 font-mono">
                            {{ $file['last_modified'] }} <span class="mx-1 text-stone-300">|</span> {{ $file['size'] }}
                        </div>
                    </div>

                    <x-button 
                        icon="o-arrow-down-tray" 
                        wire:click="download('{{ $file['name'] }}')" 
                        class="btn-circle btn-ghost btn-sm text-stone-500 hover:text-stone-800" 
                        spinner="download('{{ $file['name'] }}')" 
                    />
                </div>
            @empty
                <div class="text-center py-10 border border-dashed border-stone-200 rounded-xl bg-stone-50/30 w-full">
                    <x-icon name="o-exclamation-triangle" class="w-8 h-8 mx-auto text-stone-300 mb-2" />
                    <p class="text-xs text-stone-400 font-medium tracking-wider">目前尚無備份檔案</p>
                </div>
            @endforelse
        </div>
    </div>
</div>