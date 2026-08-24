<!-- filepath: resources/views/livewire/finance/category-drawer.blade.php -->
<div class="{{ $currentParentId ? 'template-modal-blue' : '' }}">
    
    {{-- 遮罩層：放置在根元素內部，點擊可關閉 Drawer --}}
    @if($isOpen)
        <div class="fixed inset-0 z-50 bg-base-100/50 backdrop-blur-xs transition-opacity duration-300" wire:click="close"></div>
    @endif

    {{-- Drawer 主體 --}}
    <div class="fixed right-0 top-0 h-full z-60 transition-transform duration-300 ease-out shadow-2xl flex flex-col border-l border-base-200
                {{ $currentParentId 
                    ? 'bg-base-200 text-base-content' 
                    : 'bg-base-100 text-base-content' 
                }}"
         style="width: 92%; max-width: 460px; transform: {{ $isOpen ? 'translateX(0)' : 'translateX(100%)' }};">
        
        {{-- Header --}}
        <div class="sticky top-0 z-10 flex items-center justify-between px-5 py-4 border-b border-base-200 transition-colors duration-300
                    {{ $currentParentId 
                        ? 'bg-base-200/80 backdrop-blur-md' 
                        : 'bg-base-100/80 backdrop-blur-md' 
                    }}">
            <div class="flex items-center gap-2.5">
                <x-heroicon-o-tag class="w-5 h-5 text-base-content/70" />
                <div>
                    <h2 class="text-base font-bold text-base-content">
                        @if($currentParentId)
                            <span class="text-xs font-normal text-base-content/70">父類: {{ $currentParentName }}</span>
                        @else
                            <span>分類管理</span>
                        @endif
                    </h2>
                    <p class="text-[11px] text-base-content/60">
                        {{ $currentParentId ? '管理此大類下的子項目' : '自訂個人收支大類與子項目' }}
                    </p>
                </div>
            </div>
            
            {{-- 關閉按鈕 --}}
            <button wire:click="close" 
                    class="btn btn-ghost btn-circle btn-sm transition-colors text-base-content/60 hover:text-base-content hover:bg-base-200">
                <x-heroicon-o-x-mark class="w-5 h-5" />
            </button>
        </div>

        <div class="flex-1 overflow-y-auto p-4 space-y-4">
            
            {{-- 工具列 --}}
            <div class="flex items-center justify-between gap-2">
                {{-- 支出/收入頁籤 --}}
                <div class="tabs tabs-boxed p-0.5 border border-base-200 transition-all duration-300
                            {{ $currentParentId 
                                ? 'bg-base-100/60' 
                                : 'bg-base-200/60' 
                            }}">
                    <button class="tab tab-xs font-bold transition-all duration-200 {{ $activeTab === 'expense' ? 'tab-active bg-rose-500 text-white shadow-xs' : 'text-base-content/70' }}"
                            wire:click="switchTab('expense')">
                        <span class="flex items-center gap-1">
                            <x-heroicon-o-arrow-down-right class="w-3.5 h-3.5" />
                            支出類
                        </span>
                    </button>
                    <button class="tab tab-xs font-bold transition-all duration-200 {{ $activeTab === 'income' ? 'tab-active bg-emerald-600 text-white shadow-xs' : 'text-base-content/70' }}"
                            wire:click="switchTab('income')">
                        <span class="flex items-center gap-1">
                            <x-heroicon-o-arrow-up-right class="w-3.5 h-3.5" />
                            收入類
                        </span>
                    </button>
                </div>

                {{-- 右側視圖與新增按鈕 --}}
                <div class="flex items-center gap-1">
                    <div class="join hidden sm:inline-flex border border-base-200 transition-all duration-300
                                {{ $currentParentId 
                                    ? 'bg-base-100/60' 
                                    : 'bg-base-200/80' 
                                }}">
                        <button wire:click="setViewMode('list')" class="join-item {{ $viewMode === 'list' ? 'bg-base-300 text-base-content' : 'btn-ghost text-base-content/70' }}">
                            <x-heroicon-o-bars-3 class="w-4 h-4" />
                        </button>
                        <button wire:click="setViewMode('grid')" class="join-item {{ $viewMode === 'grid' ? 'bg-base-300 text-base-content' : 'btn-ghost text-base-content/70' }}">
                            <x-heroicon-o-squares-2x2 class="w-4 h-4" />
                        </button>
                    </div>
                    
                    <button wire:click="openForm" 
                            class="btn btn-xs gap-1 transition-all duration-200 btn-neutral">
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
                <x-heroicon-o-magnifying-glass class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-base-content/50" />
                <input type="text" 
                       wire:model.live.debounce.250ms="search" 
                       placeholder="{{ $currentParentId ? '搜尋子分類...' : '搜尋大分類...' }}"
                       class="input input-bordered input-sm w-full bg-base-100 text-base-content border-base-200 focus:border-base-300">
            </div>

            {{-- 新增/修改表單 --}}
            @if($showForm)
                <div class="border border-base-200 rounded-xl p-4 shadow-sm space-y-3 transition-all duration-300
                            {{ $currentParentId 
                                ? 'bg-base-100' 
                                : 'bg-base-200/50' 
                            }}">
                    <form wire:submit.prevent="saveCategory" class="space-y-3">
                        <div>
                            <label class="label label-text font-bold text-xs text-base-content/80">分類名稱 *</label>
                            <input type="text" wire:model="name" class="input input-bordered input-sm w-full bg-base-100 text-base-content border-base-200" placeholder="例如：餐飲、交通...">
                            @error('name') <span class="text-rose-500 text-xs block mt-1">{{ $message }}</span> @enderror
                        </div>

                        @if(!$currentParentId)
                            <div>
                                <label class="label label-text font-bold text-xs text-base-content/80">歸屬上層分類（留空為獨立大類）</label>
                                <select wire:model="parentId" class="select select-bordered select-sm w-full bg-base-100 text-base-content border-base-200">
                                    <option value="">設定為獨立主大類</option>
                                    @foreach($parentCategories as $cat)
                                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        {{-- 圖示選擇器 --}}
                        <div>
                            <label class="label label-text font-bold text-xs text-base-content/80">選擇圖示</label>
                            <div class="grid grid-cols-8 gap-1.5 p-2.5 rounded-lg max-h-40 overflow-y-auto border bg-base-200/40 border-base-200">
                                @foreach($iconOptions as $iconName)
                                    <button type="button" 
                                            wire:click="$set('icon', '{{ $iconName }}')"
                                            class="flex items-center justify-center p-2 rounded-lg transition-colors hover:bg-base-300
                                                   {{ $icon === $iconName ? 'bg-base-300 ring-2 ring-base-content/30' : '' }}"
                                            title="{{ $iconName }}">
                                        <x-dynamic-component :component="'heroicon-o-' . $iconName" class="w-5 h-5 text-base-content/80" />
                                    </button>
                                @endforeach
                            </div>
                            <p class="text-[10px] text-base-content/50 mt-1">已選：{{ $icon }}</p>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="label label-text font-bold text-xs text-base-content/80">排序號（越小越前）</label>
                                <input type="number" wire:model="sortOrder" class="input input-bordered input-sm w-full bg-base-100 text-base-content border-base-200">
                            </div>
                            <div class="flex items-center pt-5">
                                <label class="label cursor-pointer gap-2">
                                    <input type="checkbox" wire:model="isActive" class="checkbox checkbox-sm checkbox-neutral border-base-200">
                                    <span class="label-text text-xs font-bold text-base-content/80">啟用此類別</span>
                                </label>
                            </div>
                        </div>

                        <div class="pt-2 border-t border-base-200">
                            <button type="submit" class="btn btn-neutral btn-sm w-full gap-1">
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
                             class="border border-base-200 rounded-xl p-4 shadow-xs hover:shadow-md transition-all duration-200
                                    {{ $currentParentId 
                                        ? 'bg-base-100/90' 
                                        : 'bg-base-100' 
                                    }}">
                            <div class="flex items-center justify-between">
                                
                                @if(!$currentParentId)
                                    <div class="flex items-center gap-3 flex-1 cursor-pointer" wire:click.stop="enterCategory({{ $category->id }})">
                                        <div class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0 bg-base-200">
                                            <x-dynamic-component :component="'heroicon-o-' . $category->icon" class="w-5 h-5 text-base-content/80" />
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="font-bold text-sm flex items-center gap-2 text-base-content">
                                                {{ $category->name }}
                                                @if($isDefault)
                                                    <span class="text-[10px] bg-base-200 text-base-content/60 px-1.5 py-0.5 rounded">預設</span>
                                                @endif
                                                @if($subCategories->isNotEmpty())
                                                    <span class="text-[10px] text-base-content/50">({{ $subCategories->count() }} 子項)</span>
                                                @endif
                                            </div>
                                            <span class="text-[10px] text-base-content/50">排序 {{ $category->sort_order }}</span>
                                        </div>
                                    </div>
                                @else
                                    <div class="flex items-center gap-3 flex-1">
                                        <div class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0 bg-base-200">
                                            <x-dynamic-component :component="'heroicon-o-' . $category->icon" class="w-5 h-5 text-base-content/80" />
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="font-bold text-sm flex items-center gap-2 text-base-content">
                                                {{ $category->name }}
                                                @if($isDefault)
                                                    <span class="text-[10px] bg-base-200 text-base-content/60 px-1.5 py-0.5 rounded">預設</span>
                                                @endif
                                            </div>
                                            <span class="text-[10px] text-base-content/50">排序 {{ $category->sort_order }}</span>
                                        </div>
                                    </div>
                                @endif
                                
                                {{-- 按鈕群組 --}}
                                <div class="flex items-center gap-0.5 flex-shrink-0 ml-2">
                                    @if(!$currentParentId)
                                        <button wire:click.stop="enterCategory({{ $category->id }})" 
                                                class="btn btn-ghost btn-xs p-1 text-base-content/50 hover:text-base-content"
                                                title="查看子分類">
                                            <x-heroicon-o-chevron-right class="w-4 h-4" />
                                        </button>
                                    @endif
                                    
                                    @if(!$isDefault)
                                        <button wire:click.stop="editCategory({{ $category->id }})" 
                                                class="btn btn-ghost btn-xs p-1 text-base-content/70 hover:text-amber-600">
                                            <x-heroicon-o-pencil-square class="w-4 h-4" />
                                        </button>
                                        
                                        <button wire:click.stop="confirmDeleteCategory({{ $category->id }})" 
                                                class="btn btn-ghost btn-xs p-1 text-rose-400 hover:text-rose-600">
                                            <x-heroicon-o-trash class="w-4 h-4" />
                                        </button>
                                    @else
                                        <span class="text-base-content/30 p-1" title="系統預設分類不可編輯刪除">
                                            <x-heroicon-o-lock-closed class="w-4 h-4 text-base-content/40" />
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @else
                        {{-- 網格模式 --}}
                        <div wire:key="grid-cat-{{ $category->id }}" 
                             class="border border-base-200 rounded-xl p-4 shadow-xs hover:shadow-md transition-all duration-200
                                    {{ $currentParentId 
                                        ? 'bg-base-100/90' 
                                        : 'bg-base-100' 
                                    }}">
                            <div class="flex items-center justify-between">
                                @if(!$currentParentId)
                                    <div class="flex-1 cursor-pointer" wire:click.stop="enterCategory({{ $category->id }})">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-lg flex items-center justify-center bg-base-200">
                                                <x-dynamic-component :component="'heroicon-o-' . $category->icon" class="w-5 h-5 text-base-content/80" />
                                            </div>
                                            <div>
                                                <div class="font-bold text-sm flex items-center gap-2 text-base-content">
                                                    {{ $category->name }}
                                                    @if($isDefault)
                                                        <span class="text-[10px] bg-base-200 text-base-content/60 px-1.5 py-0.5 rounded">預設</span>
                                                    @endif
                                                </div>
                                                <span class="text-[10px] text-base-content/50">排序 {{ $category->sort_order }}</span>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <div class="flex-1">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-lg flex items-center justify-center bg-base-200">
                                                <x-dynamic-component :component="'heroicon-o-' . $category->icon" class="w-5 h-5 text-base-content/80" />
                                            </div>
                                            <div>
                                                <div class="font-bold text-sm flex items-center gap-2 text-base-content">
                                                    {{ $category->name }}
                                                    @if($isDefault)
                                                        <span class="text-[10px] bg-base-200 text-base-content/60 px-1.5 py-0.5 rounded">預設</span>
                                                    @endif
                                                </div>
                                                <span class="text-[10px] text-base-content/50">排序 {{ $category->sort_order }}</span>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                                
                                <div class="flex items-center gap-0.5 flex-shrink-0 ml-2">
                                    @if(!$currentParentId)
                                        <button wire:click.stop="enterCategory({{ $category->id }})" 
                                                class="btn btn-ghost btn-xs p-1 text-base-content/50 hover:text-base-content">
                                            <x-heroicon-o-chevron-right class="w-4 h-4" />
                                        </button>
                                    @endif
                                    
                                    @if(!$isDefault)
                                        <button wire:click.stop="editCategory({{ $category->id }})" 
                                                class="btn btn-ghost btn-xs p-1 text-base-content/70 hover:text-amber-600">
                                            <x-heroicon-o-pencil-square class="w-4 h-4" />
                                        </button>
                                        <button wire:click.stop="confirmDeleteCategory({{ $category->id }})" 
                                                class="btn btn-ghost btn-xs p-1 text-rose-400 hover:text-rose-600">
                                            <x-heroicon-o-trash class="w-4 h-4" />
                                        </button>
                                    @else
                                        <span class="text-base-content/30 p-1" title="系統預設分類不可編輯刪除">
                                            <x-heroicon-o-lock-closed class="w-4 h-4 text-base-content/40" />
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif
                    
                @empty
                    <div class="col-span-full text-center py-8 text-base-content/50 text-sm">
                        <x-heroicon-o-folder class="w-8 h-8 mx-auto mb-2 text-base-content/30" />
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
                            class="btn btn-neutral btn-sm w-full gap-1">
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
            <div class="fixed inset-0 bg-base-300/60 backdrop-blur-xs transition-opacity" wire:click="$set('confirmingDeletion', null)"></div>
            
            {{-- 對話框主體 --}}
            <div class="relative w-full max-w-sm bg-base-100 border border-base-200 rounded-2xl p-6 shadow-2xl transition-all duration-300 transform scale-100">
                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 rounded-full bg-rose-500/10 flex items-center justify-center flex-shrink-0 text-rose-500">
                        <x-heroicon-o-exclamation-triangle class="w-5 h-5" />
                    </div>
                    <div class="flex-1">
                        <h3 class="text-sm font-bold text-base-content">確認要刪除此分類嗎？</h3>
                        <p class="text-xs text-base-content/60 mt-1.5 leading-relaxed">
                            此操作無法撤銷。如果該分類底下含有子項目或已被交易記錄引用，可能會導致數據結構異動。
                        </p>
                    </div>
                </div>
                
                {{-- 控制按鈕群組 --}}
                <div class="flex items-center justify-end gap-2 mt-6">
                    <button type="button" 
                            wire:click="$set('confirmingDeletion', null)" 
                            class="btn btn-ghost btn-sm px-4 text-base-content/70">
                        取消
                    </button>
                    <button type="button" 
                            wire:click="deleteCategory({{ $confirmingDeletion }})" 
                            class="btn btn-error btn-sm px-4 text-white">
                        確認刪除
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>