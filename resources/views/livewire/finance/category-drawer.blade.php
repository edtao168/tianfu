<div>
    @if($isOpen)
        <div class="fixed inset-0 z-[60] bg-stone-900/40 backdrop-blur-sm" wire:click="close"></div>
    @endif

    <div class="fixed right-0 top-0 h-full z-[70] transition-transform duration-300 ease-out shadow-2xl flex flex-col bg-stone-50"
         style="width: 92%; max-width: 460px; transform: {{ $isOpen ? 'translateX(0)' : 'translateX(100%)' }};">
        
        {{-- Header --}}
        <div class="sticky top-0 z-10 flex items-center justify-between px-5 py-4 bg-white border-b border-stone-200">
            <div class="flex items-center gap-2.5">
                <x-heroicon-o-tag class="w-6 h-6 text-stone-600" />
                <div>
                    <h2 class="text-base font-bold text-stone-800">記帳分類管理</h2>
                    <p class="text-[11px] text-stone-400">自訂你的個人收支大類與子項目</p>
                </div>
            </div>
            <button wire:click="close" class="btn btn-ghost btn-circle btn-sm text-stone-400 hover:text-stone-700">
                <x-heroicon-o-x-mark class="w-5 h-5" />
            </button>
        </div>

        <div class="flex-1 overflow-y-auto p-4 space-y-4">
            
            {{-- 工具列 --}}
            <div class="flex items-center justify-between gap-2">
                <div class="tabs tabs-boxed bg-stone-200/60 p-0.5">
                    <button class="tab tab-xs font-bold {{ $activeTab === 'expense' ? 'tab-active bg-rose-500 text-white' : '' }}"
                            wire:click="switchTab('expense')">
                        <span class="flex items-center gap-1">
                            <x-heroicon-o-arrow-down-right class="w-3.5 h-3.5" />
                            支出類
                        </span>
                    </button>
                    <button class="tab tab-xs font-bold {{ $activeTab === 'income' ? 'tab-active bg-emerald-600 text-white' : '' }}"
                            wire:click="switchTab('income')">
                        <span class="flex items-center gap-1">
                            <x-heroicon-o-arrow-up-right class="w-3.5 h-3.5" />
                            收入類
                        </span>
                    </button>
                </div>

                <div class="flex items-center gap-1">
                    <div class="join hidden sm:inline-flex">
                        <button wire:click="setViewMode('list')" class="join-item btn btn-xs {{ $viewMode === 'list' ? 'btn-neutral' : 'btn-ghost' }}">
                            <x-heroicon-o-bars-3 class="w-4 h-4" />
                        </button>
                        <button wire:click="setViewMode('grid')" class="join-item btn btn-xs {{ $viewMode === 'grid' ? 'btn-neutral' : 'btn-ghost' }}">
                            <x-heroicon-o-squares-2x2 class="w-4 h-4" />
                        </button>
                    </div>
                    <button wire:click="toggleForm" class="btn btn-neutral btn-xs gap-1">
                        <x-heroicon-o-x-mark class="w-3.5 h-3.5 {{ $showForm ? '' : 'hidden' }}" />
                        <x-heroicon-o-plus class="w-3.5 h-3.5 {{ $showForm ? 'hidden' : '' }}" />
                        <span>{{ $showForm ? '取消' : '新增大類' }}</span>
                    </button>
                </div>
            </div>

            {{-- 搜尋 --}}
            <div class="relative">
                <x-heroicon-o-magnifying-glass class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-stone-400" />
                <input type="text" 
                       wire:model.live.debounce.250ms="search" 
                       placeholder="搜尋已有主分類..."
                       class="input input-bordered input-sm w-full bg-white pl-9">
            </div>

            {{-- 表單 --}}
            @if($showForm)
                <div class="bg-white border border-stone-200 rounded-xl p-4 shadow-sm space-y-3">
                    <form wire:submit.prevent="saveCategory" class="space-y-3">
                        <div>
                            <label class="label label-text font-bold text-xs text-stone-600">分類名稱 *</label>
                            <input type="text" wire:model="name" class="input input-bordered input-sm w-full" placeholder="例如：餐飲、交通...">
                            @error('name') <span class="text-rose-500 text-xs block mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="label label-text font-bold text-xs text-stone-600">歸屬上層分類（留空代表自己是主大類）</label>
                            <select wire:model="parentId" class="select select-bordered select-sm w-full">
                                <option value="">設定為獨立主大類</option>
                                @foreach($parentCategories as $cat)
                                    <option value="{{ $cat->id }}">
                                        <x-dynamic-component :component="'heroicon-o-' . $cat->icon" class="w-4 h-4 inline" />
                                        {{ $cat->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- 圖示選擇器 --}}
                        <div>
                            <label class="label label-text font-bold text-xs text-stone-600">選擇圖示</label>
                            <div class="grid grid-cols-8 gap-1.5 bg-stone-50 p-2.5 rounded-lg max-h-40 overflow-y-auto border border-stone-200/60">
                                @foreach($iconOptions as $iconName)
                                    <button type="button" 
                                            wire:click="$set('icon', '{{ $iconName }}')"
                                            class="flex items-center justify-center p-2 rounded-lg hover:bg-stone-200 transition-colors {{ $icon === $iconName ? 'bg-stone-300 ring-2 ring-stone-400' : '' }}"
                                            title="{{ $iconName }}">
                                        <x-dynamic-component :component="'heroicon-o-' . $iconName" class="w-5 h-5 text-stone-600" />
                                    </button>
                                @endforeach
                            </div>
                            <p class="text-[10px] text-stone-400 mt-1">已選：{{ $icon }}</p>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="label label-text font-bold text-xs text-stone-600">排序號（越小越前）</label>
                                <input type="number" wire:model="sortOrder" class="input input-bordered input-sm w-full">
                            </div>
                            <div class="flex items-center pt-5">
                                <label class="label cursor-pointer gap-2">
                                    <input type="checkbox" wire:model="isActive" class="checkbox checkbox-sm checkbox-neutral">
                                    <span class="label-text text-xs font-bold text-stone-600">啟用此類別</span>
                                </label>
                            </div>
                        </div>

                        <div class="pt-2 border-t border-stone-100">
                            <button type="submit" class="btn btn-neutral btn-sm w-full gap-1">
                                <x-heroicon-o-archive-box-arrow-down class="w-4 h-4" />
                                {{ $editingId ? '儲存修改' : '建立分類' }}
                            </button>
                        </div>
                    </form>
                </div>
            @endif

            {{-- 列表視圖 --}}
            @if($viewMode === 'list')
                <div class="hidden md:block bg-white border border-stone-200 rounded-xl overflow-hidden shadow-sm">
                    <table class="table table-sm w-full">
                        <thead>
                            <tr class="bg-stone-50 text-stone-500">
                                <th class="w-12 text-center">圖示</th>
                                <th>分類與子項目</th>
                                <th class="w-14 text-center">排序</th>
                                <th class="w-16 text-center">狀態</th>
                                <th class="w-24 text-right">操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($categories as $category)
                                @php $subCategories = $this->getSubCategories($category->id); @endphp
                                <tr class="hover:bg-stone-50/80">
                                    <td class="text-center">
                                        <x-dynamic-component :component="'heroicon-o-' . $category->icon" class="w-5 h-5 mx-auto text-stone-600" />
                                    </td>
                                    <td>
                                        <div class="font-bold text-stone-800">{{ $category->name }}</div>
                                        @if($subCategories->isNotEmpty())
                                            <div class="flex flex-wrap gap-1 mt-1.5">
                                                @foreach($subCategories as $sub)
                                                    <span class="inline-flex items-center gap-1 bg-stone-100 border border-stone-200/50 px-2 py-0.5 rounded text-[11px] text-stone-600">
                                                        <x-dynamic-component :component="'heroicon-o-' . $sub->icon"
 class="w-3 h-3" />
                                                        <span>{{ $sub->name }}</span>
                                                    </span>
                                                @endforeach
                                            </div>
                                        @endif
                                    </td>
                                    <td class="text-center text-stone-400">{{ $category->sort_order }}</td>
                                    <td class="text-center">
                                        @if($category->is_active)
                                            <x-heroicon-o-check-circle class="w-4 h-4 text-emerald-500 mx-auto" />
                                        @else
                                            <x-heroicon-o-x-circle class="w-4 h-4 text-stone-300 mx-auto" />
                                        @endif
                                    </td>
                                    <td>
                                        <div class="flex items-center justify-end gap-0.5">
                                            <button wire:click="editCategory({{ $category->id }})" class="btn btn-ghost btn-xs px-1.5">
                                                <x-heroicon-o-pencil-square class="w-4 h-4 text-stone-500" />
                                            </button>
                                            <button wire:click="addSubCategory({{ $category->id }})" class="btn btn-ghost btn-xs px-1.5">
                                                	<x-heroicon-o-plus class="w-4 h-4 text-blue-500" />
                                            </button>
                                            @if($confirmingDeletion === $category->id)
                                                <button wire:click="deleteCategory({{ $category->id }})" class="btn btn-error btn-xs text-white px-2 h-auto min-h-0 py-0.5">
                                                    <x-heroicon-o-trash class="w-3.5 h-3.5" />
                                                </button>
                                            @else
                                                <button wire:click="confirmDeleteCategory({{ $category->id }})" class="btn btn-ghost btn-xs px-1.5">
                                                    <x-heroicon-o-trash class="w-4 h-4 text-rose-500" />
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-8 text-stone-400 text-sm">
                                        <x-heroicon-o-folder class="w-8 h-8 mx-auto mb-2 text-stone-300" />
                                        尚無建立任何主分類
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @endif

            {{-- 網格視圖 --}}
            <div class="{{ $viewMode === 'grid' ? 'grid' : 'grid md:hidden' }} grid-cols-1 sm:grid-cols-2 gap-3">
                @forelse($categories as $category)
                    @php $subCategories = $this->getSubCategories($category->id); @endphp
                    <div class="bg-white border border-stone-200 rounded-xl p-4 shadow-sm space-y-3">
                        <div class="flex items-start justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-stone-100 flex items-center justify-center">
                                    <x-dynamic-component :component="'heroicon-o-' . $category->icon" class="w-5 h-5 text-stone-600" />
                                </div>
                                <div>
                                    <div class="font-bold text-sm text-stone-800">{{ $category->name }}</div>
                                    <span class="text-[10px] text-stone-400">排序 {{ $category->sort_order }}</span>
                                </div>
                            </div>
                            <div class="flex items-center gap-0.5">
                                <button wire:click="editCategory({{ $category->id }})" class="btn btn-ghost btn-xs p-1">
                                    <x-heroicon-o-pencil-square class="w-4 h-4 text-stone-500" />
                                </button>
                                <button wire:click="addSubCategory({{ $category->id }})" class="btn btn-ghost btn-xs p-1">
                                    	<x-heroicon-o-plus class="w-4 h-4 text-blue-500" />
                                </button>
                                @if($confirmingDeletion === $category->id)
                                    <button wire:click="deleteCategory({{ $category->id }})" class="btn btn-error btn-xs px-2 py-0.5 h-auto min-h-0 text-white">
                                        <x-heroicon-o-trash class="w-3.5 h-3.5" />
                                    </button>
                                @else
                                    <button wire:click="confirmDeleteCategory({{ $category->id }})" class="btn btn-ghost btn-xs p-1">
                                        <x-heroicon-o-trash class="w-4 h-4 text-rose-500" />
                                    </button>
                                @endif
                            </div>
                        </div>

                        @if($subCategories->isNotEmpty())
                            <div class="pt-2 border-t border-stone-100 flex flex-wrap gap-1.5">
                                @foreach($subCategories as $sub)
                                    <span class="inline-flex items-center gap-1 bg-stone-50 border border-stone-100 px-2 py-1 rounded-md text-[11px] text-stone-600">
                                        <x-dynamic-component :component="'heroicon-o-' . $sub->icon" class="w-3 h-3" />
                                        <span>{{ $sub->name }}</span>
                                    </span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="col-span-full text-center py-8 text-stone-400 text-sm">
                        <x-heroicon-o-folder class="w-8 h-8 mx-auto mb-2 text-stone-300" />
                        尚無建立任何主分類
                    </div>
                @endforelse
            </div>

            {{-- 分頁 --}}
            @if($categories->hasPages())
                <div class="pt-1">
                    {{ $categories->links('livewire::simple-bootstrap') }}
                </div>
            @endif
        </div>
    </div>
</div>