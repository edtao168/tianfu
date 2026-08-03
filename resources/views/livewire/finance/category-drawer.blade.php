<!-- filepath: resources/views/livewire/finance/category-drawer.blade.php -->
<div class="{{ $currentParentId ? 'template-modal-blue' : '' }}">
    
    {{-- 遮罩層：放置在根元素內部，點擊可關閉 Drawer --}}
    @if($isOpen)
        <div class="fixed inset-0 z-50 bg-stone-900/40 dark:bg-black/60 backdrop-blur-xs transition-opacity duration-300" wire:click="close"></div>
    @endif

    {{-- Drawer 主體 --}}
    <div class="fixed right-0 top-0 h-full z-60 transition-transform duration-300 ease-out shadow-2xl flex flex-col border-l
                {{ $currentParentId 
                    ? 'bg-gradient-to-br from-[#e8f0fe] to-[#d4e2f7] dark:from-stone-900 dark:to-stone-950 border-[#a2bddb]/40 dark:border-stone-800' 
                    : 'bg-base-100 border-base-200 dark:border-stone-800' 
                }}"
         style="width: 92%; max-width: 460px; transform: {{ $isOpen ? 'translateX(0)' : 'translateX(100%)' }};">
        
        {{-- Header --}}
        <div class="sticky top-0 z-10 flex items-center justify-between px-5 py-4 border-b transition-colors duration-300
                    {{ $currentParentId 
                        ? 'bg-white/80 dark:bg-stone-900/80 backdrop-blur-md border-[#a2bddb]/20 dark:border-stone-800' 
                        : 'bg-base-100/80 dark:bg-stone-900/80 backdrop-blur-md border-stone-200/50 dark:border-stone-800' 
                    }}">
            <div class="flex items-center gap-2.5">
                <x-heroicon-o-tag class="w-5 h-5 {{ $currentParentId ? 'text-[#4a5a6a] dark:text-stone-300' : 'text-stone-500 dark:text-stone-400' }}" />
                <div>
                    <h2 class="text-base font-bold {{ $currentParentId ? 'text-[#3a4a5a] dark:text-stone-100' : 'text-stone-800 dark:text-stone-100' }}">
                        @if($currentParentId)
                            <span class="text-xs font-normal text-[#8a9aaa] dark:text-stone-400">父類: {{ $currentParentName }}</span>
                        @else
                            <span>分類管理</span>
                        @endif
                    </h2>
                    <p class="text-[11px] {{ $currentParentId ? 'text-[#8a9aaa] dark:text-stone-400' : 'text-stone-400 dark:text-stone-500' }}">
                        {{ $currentParentId ? '管理此大類下的子項目' : '自訂個人收支大類與子項目' }}
                    </p>
                </div>
            </div>
            
            {{-- 關閉按鈕 --}}
            <button wire:click="close" 
                    class="btn btn-ghost btn-circle btn-sm transition-colors
                           {{ $currentParentId 
                                ? 'text-[#8a9aaa] dark:text-stone-400 hover:text-[#3a4a5a] dark:hover:text-stone-100 hover:bg-[#a2bddb]/20' 
                                : 'text-stone-400 hover:text-stone-700 dark:hover:text-stone-200 hover:bg-stone-200/50 dark:hover:bg-stone-800' 
                           }}">
                <x-heroicon-o-x-mark class="w-5 h-5" />
            </button>
        </div>

        <div class="flex-1 overflow-y-auto p-4 space-y-4">
            
            {{-- 工具列 --}}
            <div class="flex items-center justify-between gap-2">
                {{-- 支出/收入頁籤 --}}
                <div class="tabs tabs-boxed p-0.5 border transition-all duration-300
                            {{ $currentParentId 
                                ? 'bg-white/60 dark:bg-stone-800/60 border-[#a2bddb]/20 dark:border-stone-700' 
                                : 'bg-stone-200/60 dark:bg-stone-800/60 border-stone-300/30 dark:border-stone-700/50' 
                            }}">
                    <button class="tab tab-xs font-bold transition-all duration-200 {{ $activeTab === 'expense' ? 'tab-active bg-rose-500 text-white shadow-xs' : 'text-stone-500 dark:text-stone-400' }}"
                            wire:click="switchTab('expense')">
                        <span class="flex items-center gap-1">
                            <x-heroicon-o-arrow-down-right class="w-3.5 h-3.5" />
                            支出類
                        </span>
                    </button>
                    <button class="tab tab-xs font-bold transition-all duration-200 {{ $activeTab === 'income' ? 'tab-active bg-emerald-600 text-white shadow-xs' : 'text-stone-500 dark:text-stone-400' }}"
                            wire:click="switchTab('income')">
                        <span class="flex items-center gap-1">
                            <x-heroicon-o-arrow-up-right class="w-3.5 h-3.5" />
                            收入類
                        </span>
                    </button>
                </div>

                {{-- 右側視圖與新增按鈕 --}}
                <div class="flex items-center gap-1">
                    <div class="join hidden sm:inline-flex border transition-all duration-300
                                {{ $currentParentId 
                                    ? 'border-[#a2bddb]/20 dark:border-stone-700 bg-white/60 dark:bg-stone-800/60' 
                                    : 'border-stone-200 dark:border-stone-700 bg-stone-100/80 dark:bg-stone-800/80' 
                                }}">
                        <button wire:click="setViewMode('list')" class="join-item {{ $viewMode === 'list' ? ($currentParentId ? 'bg-[#a2bddb]/20 text-[#3a4a5a] dark:bg-stone-700 dark:text-stone-100' : 'btn-dark') : 'btn-ghost text-stone-500 dark:text-stone-400' }}">
                            <x-heroicon-o-bars-3 class="w-4 h-4" />
                        </button>
                        <button wire:click="setViewMode('grid')" class="join-item {{ $viewMode === 'grid' ? ($currentParentId ? 'bg-[#a2bddb]/20 text-[#3a4a5a] dark:bg-stone-700 dark:text-stone-100' : 'btn-dark') : 'btn-ghost text-stone-500 dark:text-stone-400' }}">
                            <x-heroicon-o-squares-2x2 class="w-4 h-4" />
                        </button>
                    </div>
                    
                    <button wire:click="openForm" 
                            class="btn btn-xs gap-1 transition-all duration-200
                                   {{ $currentParentId ? 'btn-blue' : 'btn-dark' }}">
                        @if($showForm)
                            <x-heroicon-o-x-mark class="w-3.5 h-3.5" />
                            <span>取消</span>
                        @else
                            <x-heroicon-o-plus class="w-3.5 h-3.5" />
                            <span>{{ $currentParentId ? '新增子類' : '新增大類' }}</span>
                        @endif
                    </button>
                </div>
            </div>

            {{-- 搜尋欄 --}}
            <div class="relative">
                <x-heroicon-o-magnifying-glass class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-stone-400 dark:text-stone-500" />
                <input type="text" 
                       wire:model.live.debounce.250ms="search" 
                       placeholder="{{ $currentParentId ? '搜尋子分類...' : '搜尋大分類...' }}"
                       class="input input-bordered input-sm w-full bg-white/95 dark:bg-stone-800/95 focus:border-stone-400 text-stone-800 dark:text-stone-100
                              {{ $currentParentId ? 'border-[#a2bddb]/30 dark:border-stone-700' : 'border-stone-200 dark:border-stone-700' }}">
            </div>

            {{-- 新增/修改表單 --}}
            @if($showForm)
                <div class="border rounded-xl p-4 shadow-sm space-y-3 transition-all duration-300
                            {{ $currentParentId 
                                ? 'bg-white/90 dark:bg-stone-900/90 border-[#a2bddb]/30 dark:border-stone-700' 
                                : 'bg-white dark:bg-stone-800 border-stone-200/80 dark:border-stone-700' 
                            }}">
                    <form wire:submit.prevent="saveCategory" class="space-y-3">
                        <div>
                            <label class="label label-text font-bold text-xs text-stone-600 dark:text-stone-300">分類名稱 *</label>
                            <input type="text" wire:model="name" class="input input-bordered input-sm w-full bg-white dark:bg-stone-900 text-stone-800 dark:text-stone-100 border-stone-300 dark:border-stone-700" placeholder="例如：餐飲、交通...">
                            @error('name') <span class="text-rose-500 text-xs block mt-1">{{ $message }}</span> @enderror
                        </div>

                        @if(!$currentParentId)
                            <div>
                                <label class="label label-text font-bold text-xs text-stone-600 dark:text-stone-300">歸屬上層分類（留空為獨立大類）</label>
                                <select wire:model="parentId" class="select select-bordered select-sm w-full bg-white dark:bg-stone-900 text-stone-800 dark:text-stone-100 border-stone-300 dark:border-stone-700">
                                    <option value="">設定為獨立主大類</option>
                                    @foreach($parentCategories as $cat)
                                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        {{-- 圖示選擇器 --}}
                        <div>
                            <label class="label label-text font-bold text-xs text-stone-600 dark:text-stone-300">選擇圖示</label>
                            <div class="grid grid-cols-8 gap-1.5 p-2.5 rounded-lg max-h-40 overflow-y-auto border bg-stone-50/50 dark:bg-stone-900/50 border-stone-200/60 dark:border-stone-700">
                                @foreach($iconOptions as $iconName)
                                    <button type="button" 
                                            wire:click="$set('icon', '{{ $iconName }}')"
                                            class="flex items-center justify-center p-2 rounded-lg transition-colors hover:bg-stone-200/50 dark:hover:bg-stone-700
                                                   {{ $icon === $iconName ? 'bg-stone-300 dark:bg-stone-700 ring-2 ring-stone-400 dark:ring-stone-500' : '' }}"
                                            title="{{ $iconName }}">
                                        <x-dynamic-component :component="'heroicon-o-' . $iconName" class="w-5 h-5 text-stone-600 dark:text-stone-300" />
                                    </button>
                                @endforeach
                            </div>
                            <p class="text-[10px] text-stone-400 dark:text-stone-500 mt-1">已選：{{ $icon }}</p>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="label label-text font-bold text-xs text-stone-600 dark:text-stone-300">排序號（越小越前）</label>
                                <input type="number" wire:model="sortOrder" class="input input-bordered input-sm w-full bg-white dark:bg-stone-900 text-stone-800 dark:text-stone-100 border-stone-300 dark:border-stone-700">
                            </div>
                            <div class="flex items-center pt-5">
                                <label class="label cursor-pointer gap-2">
                                    <input type="checkbox" wire:model="isActive" class="checkbox checkbox-sm checkbox-neutral border-stone-300 dark:border-stone-600">
                                    <span class="label-text text-xs font-bold text-stone-600 dark:text-stone-300">啟用此類別</span>
                                </label>
                            </div>
                        </div>

                        <div class="pt-2 border-t border-stone-100 dark:border-stone-800">
                            <button type="submit" class="btn btn-dark btn-sm w-full gap-1">
                                <x-heroicon-o-archive-box-arrow-down class="w-4 h-4" />
                                {{ $editingId ? '儲存修改' : '建立分類' }}
                            </button>
                        </div>
                    </form>
                </div>
            @endif

            {{-- 列表/網格視圖 --}}
            <div class="{{ $viewMode === 'grid' ? 'grid' : '' }} grid-cols-1 sm:grid-cols-2 gap-3">
                @forelse($categories as $category)
                    @php 
                        $subCategories = $this->getSubCategories($category->id);
                        $isDefault = $category->is_default ?? false;
                    @endphp
                    
                    @if($viewMode === 'list')
                        <div wire:key="list-cat-{{ $category->id }}" 
                             class="border rounded-xl p-4 shadow-xs hover:shadow-md transition-all duration-200
                                    {{ $currentParentId 
                                        ? 'bg-white/90 dark:bg-stone-900/80 border-[#a2bddb]/20 dark:border-stone-700' 
                                        : 'bg-white/80 dark:bg-stone-800/80 border-stone-200 dark:border-stone-700' 
                                    }}">
                            <div class="flex items-center justify-between">
                                
                                @if(!$currentParentId)
                                    <div class="flex items-center gap-3 flex-1 cursor-pointer" wire:click.stop="enterCategory({{ $category->id }})">
                                        <div class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0 bg-stone-100/80 dark:bg-stone-700/80">
                                            <x-dynamic-component :component="'heroicon-o-' . $category->icon" class="w-5 h-5 text-stone-600 dark:text-stone-300" />
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="font-bold text-sm flex items-center gap-2 {{ $currentParentId ? 'text-[#4a5a6a] dark:text-stone-200' : 'text-stone-800 dark:text-stone-100' }}">
                                                {{ $category->name }}
                                                @if($isDefault)
                                                    <span class="text-[10px] bg-stone-100 dark:bg-stone-700 text-stone-400 dark:text-stone-300 px-1.5 py-0.5 rounded">預設</span>
                                                @endif
                                                @if($subCategories->isNotEmpty())
                                                    <span class="text-[10px] text-stone-400 dark:text-stone-500">({{ $subCategories->count() }} 子項)</span>
                                                @endif
                                            </div>
                                            <span class="text-[10px] text-stone-400 dark:text-stone-500">排序 {{ $category->sort_order }}</span>
                                        </div>
                                    </div>
                                @else
                                    <div class="flex items-center gap-3 flex-1">
                                        <div class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0 bg-stone-100/80 dark:bg-stone-700/80">
                                            <x-dynamic-component :component="'heroicon-o-' . $category->icon" class="w-5 h-5 text-stone-600 dark:text-stone-300" />
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="font-bold text-sm flex items-center gap-2 {{ $currentParentId ? 'text-[#4a5a6a] dark:text-stone-200' : 'text-stone-800 dark:text-stone-100' }}">
                                                {{ $category->name }}
                                                @if($isDefault)
                                                    <span class="text-[10px] bg-stone-100 dark:bg-stone-700 text-stone-400 dark:text-stone-300 px-1.5 py-0.5 rounded">預設</span>
                                                @endif
                                            </div>
                                            <span class="text-[10px] text-stone-400 dark:text-stone-500">排序 {{ $category->sort_order }}</span>
                                        </div>
                                    </div>
                                @endif
                                
                                {{-- 按鈕群組 --}}
                                <div class="flex items-center gap-0.5 flex-shrink-0 ml-2">
                                    @if(!$currentParentId)
                                        <button wire:click.stop="enterCategory({{ $category->id }})" 
                                                class="btn btn-ghost btn-xs p-1 text-stone-400 dark:text-stone-500 hover:text-stone-600 dark:hover:text-stone-200"
                                                title="查看子分類">
                                            <x-heroicon-o-chevron-right class="w-4 h-4" />
                                        </button>
                                    @endif
                                    
                                    @if(!$isDefault)
                                        <button wire:click.stop="editCategory({{ $category->id }})" 
                                                class="btn btn-ghost btn-xs p-1 text-stone-500 dark:text-stone-400 hover:text-amber-700 dark:hover:text-amber-400">
                                            <x-heroicon-o-pencil-square class="w-4 h-4" />
                                        </button>
                                        
                                        <button wire:click.stop="confirmDeleteCategory({{ $category->id }})" 
                                                class="btn btn-ghost btn-xs p-1 text-rose-400 hover:text-rose-600">
                                            <x-heroicon-o-trash class="w-4 h-4" />
                                        </button>
                                    @else
                                        <span class="text-stone-300 dark:text-stone-600 p-1" title="系統預設分類不可編輯刪除">
                                            <x-heroicon-o-lock-closed class="w-4 h-4 text-stone-400 dark:text-stone-500" />
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @else
                        {{-- 網格模式 --}}
                        <div wire:key="grid-cat-{{ $category->id }}" 
                             class="border rounded-xl p-4 shadow-xs hover:shadow-md transition-all duration-200
                                    {{ $currentParentId 
                                        ? 'bg-white/90 dark:bg-stone-900/80 border-[#a2bddb]/20 dark:border-stone-700' 
                                        : 'bg-white/80 dark:bg-stone-800/80 border-stone-200 dark:border-stone-700' 
                                    }}">
                            <div class="flex items-center justify-between">
                                @if(!$currentParentId)
                                    <div class="flex-1 cursor-pointer" wire:click.stop="enterCategory({{ $category->id }})">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-lg flex items-center justify-center bg-stone-100 dark:bg-stone-700">
                                                <x-dynamic-component :component="'heroicon-o-' . $category->icon" class="w-5 h-5 text-stone-600 dark:text-stone-300" />
                                            </div>
                                            <div>
                                                <div class="font-bold text-sm flex items-center gap-2 {{ $currentParentId ? 'text-[#4a5a6a] dark:text-stone-200' : 'text-stone-800 dark:text-stone-100' }}">
                                                    {{ $category->name }}
                                                    @if($isDefault)
                                                        <span class="text-[10px] bg-stone-100 dark:bg-stone-700 text-stone-400 dark:text-stone-300 px-1.5 py-0.5 rounded">預設</span>
                                                    @endif
                                                </div>
                                                <span class="text-[10px] text-stone-400 dark:text-stone-500">排序 {{ $category->sort_order }}</span>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <div class="flex-1">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-lg flex items-center justify-center bg-stone-100 dark:bg-stone-700">
                                                <x-dynamic-component :component="'heroicon-o-' . $category->icon" class="w-5 h-5 text-stone-600 dark:text-stone-300" />
                                            </div>
                                            <div>
                                                <div class="font-bold text-sm flex items-center gap-2 {{ $currentParentId ? 'text-[#4a5a6a] dark:text-stone-200' : 'text-stone-800 dark:text-stone-100' }}">
                                                    {{ $category->name }}
                                                    @if($isDefault)
                                                        <span class="text-[10px] bg-stone-100 dark:bg-stone-700 text-stone-400 dark:text-stone-300 px-1.5 py-0.5 rounded">預設</span>
                                                    @endif
                                                </div>
                                                <span class="text-[10px] text-stone-400 dark:text-stone-500">排序 {{ $category->sort_order }}</span>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                                
                                <div class="flex items-center gap-0.5 flex-shrink-0 ml-2">
                                    @if(!$currentParentId)
                                        <button wire:click.stop="enterCategory({{ $category->id }})" 
                                                class="btn btn-ghost btn-xs p-1 text-stone-400 dark:text-stone-500 hover:text-stone-600 dark:hover:text-stone-200">
                                            <x-heroicon-o-chevron-right class="w-4 h-4" />
                                        </button>
                                    @endif
                                    
                                    @if(!$isDefault)
                                        <button wire:click.stop="editCategory({{ $category->id }})" 
                                                class="btn btn-ghost btn-xs p-1 text-stone-500 dark:text-stone-400 hover:text-amber-700 dark:hover:text-amber-400">
                                            <x-heroicon-o-pencil-square class="w-4 h-4" />
                                        </button>
                                        <button wire:click.stop="confirmDeleteCategory({{ $category->id }})" 
                                                class="btn btn-ghost btn-xs p-1 text-rose-400 hover:text-rose-600">
                                            <x-heroicon-o-trash class="w-4 h-4" />
                                        </button>
                                    @else
                                        <span class="text-stone-300 dark:text-stone-600 p-1" title="系統預設分類不可編輯刪除">
                                            <x-heroicon-o-lock-closed class="w-4 h-4 text-stone-400 dark:text-stone-500" />
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif
                    
                @empty
                    <div class="col-span-full text-center py-8 text-stone-400 dark:text-stone-500 text-sm">
                        <x-heroicon-o-folder class="w-8 h-8 mx-auto mb-2 text-stone-300 dark:text-stone-600" />
                        @if($currentParentId)
                            此大類下尚無子分類
                        @else
                            尚無建立任何大分類
                        @endif
                    </div>
                @endforelse
            </div>

            {{-- 返回按鈕 --}}
            @if($currentParentId)
                <div class="pt-2">
                    <button wire:click="backToParents" 
                            class="btn btn-blue btn-sm w-full gap-1">
                        <x-heroicon-o-arrow-uturn-left class="w-4 h-4" />
                        返回大分類列表
                    </button>
                </div>
            @endif

            {{-- 分頁 --}}
            @if($categories->hasPages())
                <div class="pt-2">
                    {{ $categories->links('livewire::simple-bootstrap') }}
                </div>
            @endif
        </div>
    </div>

    {{-- 獨立刪除確認對話框 --}}
    @if($confirmingDeletion)
        <div class="fixed inset-0 z-70 flex items-center justify-center p-4">
            {{-- 模糊背景遮罩 --}}
            <div class="fixed inset-0 bg-stone-900/40 dark:bg-black/60 backdrop-blur-xs transition-opacity" wire:click="$set('confirmingDeletion', null)"></div>
            
            {{-- 對話框主體 --}}
            <div class="relative w-full max-w-sm bg-[#FAF9F6] dark:bg-stone-900 border border-stone-200 dark:border-stone-800 rounded-2xl p-6 shadow-2xl transition-all duration-300 transform scale-100">
                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 rounded-full bg-rose-50 dark:bg-rose-950/50 flex items-center justify-center flex-shrink-0 text-rose-500 dark:text-rose-400">
                        <x-heroicon-o-exclamation-triangle class="w-5 h-5" />
                    </div>
                    <div class="flex-1">
                        <h3 class="text-sm font-bold text-stone-800 dark:text-stone-100">確認要刪除此分類嗎？</h3>
                        <p class="text-xs text-stone-500 dark:text-stone-400 mt-1.5 leading-relaxed">
                            此操作無法撤銷。如果該分類底下含有子項目或已被交易記錄引用，可能會導致數據結構異動。
                        </p>
                    </div>
                </div>
                
                {{-- 控制按鈕群組 --}}
                <div class="flex items-center justify-end gap-2 mt-6">
                    <button type="button" 
                            wire:click="$set('confirmingDeletion', null)" 
                            class="btn btn-ghost btn-sm px-4 text-stone-600 dark:text-stone-300">
                        取消
                    </button>
                    <button type="button" 
                            wire:click="deleteCategory({{ $confirmingDeletion }})" 
                            class="btn btn-red btn-sm px-4">
                        確認刪除
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>