{{-- resources/views/livewire/finance/partner-index.blade.php --}}
<div class="p-4 sm:p-6 max-w-7xl mx-auto space-y-6">

    {{-- 頁頭操作區 --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 border-b border-stone-200/60 pb-4">
        <div>
            <h1 class="text-lg font-bold tracking-wider text-stone-800 dark:text-stone-500 flex items-center gap-2">
                <span class="w-1.5 h-4 rounded-full bg-teal-600"></span>
                家庭成員管理
            </h1>
            <p class="text-xs text-stone-400 mt-1">點擊卡片任一處即可編輯</p>
        </div>
        <div class="flex items-center gap-2 w-full sm:w-auto">
            <x-input wire:model.live.debounce.400ms="search" icon="o-magnifying-glass" placeholder="搜尋姓名..." class="input-sm rounded-xl bg-white/60 flex-1 sm:flex-initial" clearable />
            <x-button label="建立成員" icon="o-plus" class="btn-sm rounded-xl bg-stone-800 border-stone-800 text-stone-50 hover:bg-stone-700 font-medium tracking-wide shadow-sm whitespace-nowrap" wire:click="create" />
        </div>
    </div>

    {{-- 📇 卡片網格（統一版本，不分裝置） --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        @forelse($partners as $partner)
            {{-- 整張卡片可點擊開啟編輯 --}}
            <div 
                class="group bg-white/80 border border-stone-200/60 rounded-2xl p-4 shadow-sm hover:shadow-md hover:border-stone-300/80 transition-all duration-200 cursor-pointer active:scale-[0.98]"
                wire:click="openEdit({{ $partner->id }})"
            >
                {{-- 卡片內容 --}}
                <div class="flex items-start gap-3">
                    {{-- 頭像 --}}
                    <div class="w-14 h-14 rounded-full border-2 border-stone-200/80 shadow-sm overflow-hidden flex-shrink-0 group-hover:border-teal-200 transition-colors">
                        <img 
                            src="{{ $partner->photo_path ? asset('storage/' . $partner->photo_path) : asset('me.jpg') }}" 
                            class="w-full h-full object-cover" 
                            alt="{{ $partner->name }}"
                        />
                    </div>

                    {{-- 主要資訊 --}}
                    <div class="flex-1 min-w-0 space-y-1">
                        <div class="flex items-center justify-between gap-2">
                            <span class="font-bold text-base text-stone-800 truncate">{{ $partner->name }}</span>
                            <span class="text-[10px] uppercase font-bold tracking-wider text-stone-400 shrink-0">{{ $partner->role }}</span>
                        </div>
                        
                        <p class="text-xs text-stone-400 truncate">{{ $partner->user?->email ?? '未綁定帳號' }}</p>
                        
                        {{-- 聯絡資訊標籤 --}}
                        <div class="flex flex-wrap gap-1.5 pt-1">
                            @if(!empty($partner->contacts['line']))
                                <span class="inline-flex items-center gap-0.5 text-[10px] bg-sky-50 text-sky-600 px-2 py-0.5 rounded-full border border-sky-100/50">
                                    <x-icon name="o-chat-bubble-left" class="w-3 h-3" />
                                    Line
                                </span>
                            @endif
                            @if(!empty($partner->contacts['carrier_num']))
                                <span class="inline-flex items-center gap-0.5 text-[10px] bg-amber-50 text-amber-600 px-2 py-0.5 rounded-full border border-amber-100/50">
                                    <x-icon name="o-qr-code" class="w-3 h-3" />
                                    載具
                                </span>
                            @endif
                            <span class="inline-flex items-center text-[10px] {{ $partner->is_active ? 'bg-teal-50 text-teal-600' : 'bg-rose-50 text-rose-500' }} px-2 py-0.5 rounded-full border {{ $partner->is_active ? 'border-teal-100/50' : 'border-rose-100/50' }}">
                                {{ $partner->is_active ? '● 啟用' : '● 凍結' }}
                            </span>
                        </div>
                    </div>
                </div>

                {{-- 卡片底部：刪除按鈕（獨立避免觸發編輯） --}}
                <div class="flex justify-end mt-3 pt-2 border-t border-stone-100/60">
                    <x-button 
                        icon="o-trash" 
                        class="btn-xs btn-ghost text-stone-400 hover:text-rose-500 hover:bg-rose-50 transition-colors" 
                        wire:confirm="確定要將「{{ $partner->name }}」移出金櫃嗎？" 
                        wire:click.stop="delete({{ $partner->id }})" 
                        tooltip="刪除成員"
                    />
                </div>
            </div>
        @empty
            {{-- 無資料狀態 --}}
            <div class="col-span-full">
                <div class="text-center py-16 bg-white/30 rounded-2xl border-2 border-dashed border-stone-200">
                    <x-icon name="o-user-group" class="w-12 h-12 text-stone-300 mx-auto mb-3" />
                    <p class="text-stone-400 font-medium">還沒有家庭成員</p>
                    <p class="text-xs text-stone-300 mt-1">點擊「建立成員」開始新增</p>
                </div>
            </div>
        @endforelse
    </div>

    {{-- 📑 編輯抽屜（含底部共用按鈕） --}}
    <x-drawer 
        wire:model="partnerModal" 
        title="{{ $editingPartner ? '修改家庭成員' : '新增家庭成員' }}" 
        right 
        separator 
        with-close-button 
        class="w-full sm:w-11/12 md:w-1/2 lg:w-2/5 xl:w-1/3 bg-stone-50"
    >
        <form wire:submit.prevent="save" class="space-y-4">
            {{-- 照片上傳區 --}}
<div class="flex justify-center py-2">
    <div class="relative">
        {{-- 頭像顯示 --}}
        <div class="h-24 w-24 rounded-full border-2 border-stone-300 overflow-hidden bg-white shadow-inner">
            @if ($photo)
                <img src="{{ $photo->temporaryUrl() }}" class="h-full w-full object-cover" />
            @elseif ($photo_path)
                <img src="{{ asset('storage/' . $photo_path) }}" class="h-full w-full object-cover" />
            @else
                <div class="h-full w-full flex flex-col items-center justify-center text-stone-400">
                    <x-icon name="o-camera" class="w-6 h-6 mb-0.5" />
                    <span class="text-[10px] font-medium">上傳頭像</span>
                </div>
            @endif
        </div>
        
        {{-- 隱藏的上傳 input --}}
        <input 
            type="file" 
            accept="image/*"
            class="absolute inset-0 opacity-0 cursor-pointer"
            wire:model="photo"
        />
    </div>
</div>
            {{-- 表單欄位 --}}
            <x-input label="成員姓名" wire:model="name" placeholder="例如：老婆、大兒子" icon="o-user" required />
            <x-select label="繫結系統登入帳號" wire:model="user_id" :options="$users" placeholder="請選擇對應的登入帳戶" icon="o-key" />
            <x-input label="稱謂 / 系統角色" wire:model="role" placeholder="例如：配偶、記帳員" icon="o-briefcase" />
            <x-input label="聯絡電話" wire:model="phone" icon="o-phone" />
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <x-input label="Line ID" wire:model="line_id" icon="o-chat-bubble-left-right" />
                <x-input label="載具流水號" wire:model="carrier_num" placeholder="/ABC1234" icon="o-qr-code" />
            </div>

            <x-input type="date" label="加入日期 / 生日" wire:model="joined_at" icon="o-calendar" />
            <x-toggle label="帳號啟用狀態" wire:model="is_active" class="pt-2 text-stone-700" />

            {{-- ⬇️ 底部共用按鈕 --}}
            <div class="border-t border-stone-200/70 pt-4 mt-2">
                <div class="flex flex-col-reverse sm:flex-row justify-end gap-2 sm:gap-3">
                    <x-button 
                        label="返回" 
                        icon="o-arrow-left" 
                        class="w-full sm:w-auto btn-ghost text-stone-500 hover:bg-stone-100" 
                        wire:click="closeDrawer" 
                        type="button"
                    />
                    <x-button 
                        label="存檔" 
                        type="submit" 
                        icon="o-check" 
                        class="w-full sm:w-auto bg-stone-800 text-stone-50 hover:bg-stone-700 px-6" 
                        spinner="save" 
                    />
                </div>
            </div>
        </form>
    </x-drawer>

</div>