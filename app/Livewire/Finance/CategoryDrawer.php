<?php
// app/Livewire/Finance/CategoryDrawer.php

namespace App\Livewire\Finance;

use App\Models\Category;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\On;

class CategoryDrawer extends Component
{
    use WithPagination;

    public bool $isOpen = false;
    public string $activeTab = 'expense';
    public string $viewMode = 'list';
    public string $search = '';
    public ?int $editingId = null;
    public string $name = '';
    public string $type = 'expense';
    public string $icon = 'folder';
    public ?int $parentId = null;
    public bool $isActive = true;
    public int $sortOrder = 0;
    public ?int $confirmingDeletion = null;
    public bool $showForm = false;
    
    // 新增：當前瀏覽的父分類 ID (null 表示在大類列表)
    public ?int $currentParentId = null;
    // 新增：當前瀏覽的父分類名稱
    public ?string $currentParentName = null;

    public array $iconOptions = [
        'folder', 'document-text', 'tag', 'gift', 'briefcase', 'ticket',
        'shopping-cart', 'shopping-bag', 'credit-card', 'banknotes', 'currency-dollar',
        'home', 'building-office', 'key', 'bolt', 'fire', 'wrench',
        'truck', 'map',
        'academic-cap', 'book-open', 'pencil', 'pencil-square', 'paint-brush',
        'film', 'musical-note', 'puzzle-piece', 'trophy',
        'user', 'users', 'user-group', 'heart',
        'globe-alt', 'sun', 'moon', 'star',
        'chart-bar', 'chart-pie', 'arrow-trending-up', 'arrow-trending-down',
        'trash', 'plus', 'minus', 'x-mark', 'check', 'check-circle', 'exclamation-circle',
        'magnifying-glass', 'bell', 'calendar', 'clock', 'lock-closed', 'phone', 'envelope',
        // 額外圖示
        'cake', 'hand-raised', 'sparkles', 'beaker', 'wifi', 'tv', 
        'cog', 'no-symbol', 'identification', 'scissors', 'face-smile',
        'shield-check', 'document-duplicate', 'receipt-percent', 'cube',
        'user-plus', 'megaphone', 'ellipsis-horizontal', 'paper-airplane',
        'exclamation-triangle', 'eye-slash', 'building-storefront', 'arrow-uturn-left'
    ];

    protected $rules = [
        'name' => 'required|string|max:50',
        'type' => 'required|in:expense,income',
        'icon' => 'nullable|string|max:50',
        'parentId' => 'nullable|exists:categories,id',
        'isActive' => 'boolean',
        'sortOrder' => 'integer'
    ];

    protected $messages = [
        'name.required' => '請填寫分類名稱',
        'name.max' => '分類名稱字數請在 50 字以內',
        'type.required' => '請選擇類別類型',
    ];

    public function mount()
    {
        $this->type = $this->activeTab;
    }

    #[On(['open-category-manager', 'category-drawer-open'])]
    public function open()
    {
        $this->isOpen = true;
        $this->resetForm();
        $this->showForm = false;
        $this->currentParentId = null;
        $this->currentParentName = null;
    }

    public function close()
    {
        $this->isOpen = false;
        $this->showForm = false;
        $this->resetForm();
        $this->search = '';
        $this->resetPage();
        $this->currentParentId = null;
        $this->currentParentName = null;
    }

    public function switchTab(string $tab)
    {
        $this->activeTab = $tab;
        $this->type = $tab;
        $this->resetPage();
        $this->resetForm();
        $this->showForm = false;
        $this->currentParentId = null;
        $this->currentParentName = null;
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function setViewMode($mode)
    {
        $this->viewMode = $mode;
    }

    public function resetForm()
    {
        $this->editingId = null;
        $this->name = '';
        $this->icon = 'folder';
        $this->parentId = null;
        $this->isActive = true;
        $this->sortOrder = 0;
        $this->confirmingDeletion = null;
    }

    // 點擊大類，進入子類列表
    public function enterCategory($id)
    {
        $category = Category::findOrFail($id);
        $this->currentParentId = $id;
        $this->currentParentName = $category->name;
        $this->parentId = $id;
        $this->resetPage();
        $this->search = '';
        $this->showForm = false;
        $this->resetForm();
    }

    // 返回大類列表
    public function backToParents()
    {
        $this->currentParentId = null;
        $this->currentParentName = null;
        $this->parentId = null;
        $this->resetPage();
        $this->search = '';
        $this->showForm = false;
        $this->resetForm();
    }
	
	// 統一開啟表單的入口
	public function openForm()
	{
		// 如果表單已開啟，則關閉並重置
		if ($this->showForm) {
			$this->showForm = false;
			$this->resetForm();
			return;
		}
		
		if ($this->currentParentId) {
			$this->addSubCategory($this->currentParentId);
		} else {
			$this->addCustomCategory();
		}
	}

    public function editCategory($id)
    {
        $category = Category::findOrFail($id);
        
        // 預設分類不可編輯
        if ($category->is_default) {
            $this->dispatch('notify', message: '系統預設分類不可編輯', type: 'error');
            return;
        }
        
        $this->editingId = $id;
        $this->name = $category->name;
        $this->type = $category->type;
        $this->icon = $category->icon ?? 'folder';
        $this->parentId = $category->parent_id;
        $this->isActive = $category->is_active;
        $this->sortOrder = $category->sort_order;
        $this->showForm = true;
    }

    public function addSubCategory($parentId)
	{
		$parent = Category::findOrFail($parentId);
		$this->resetForm();
		$this->parentId = $parentId;
		$this->type = $parent->type;
		
		// 自動計算排序：該大類下現有子類數量 + 1
		$maxSortOrder = Category::where('parent_id', $parentId)
			->max('sort_order') ?? 0;
		$this->sortOrder = $maxSortOrder + 1;
		
		$this->showForm = true;
	}

    // 新增自定義大類
    public function addCustomCategory()
	{
		$this->resetForm();
		$this->parentId = null;
		$this->type = $this->activeTab;
		
		// 自動計算該類型下的最大排序值
		$maxSortOrder = Category::where('type', $this->activeTab)
			->whereNull('parent_id')
			->max('sort_order') ?? 0;
		$this->sortOrder = $maxSortOrder + 1;
		
		$this->showForm = true;
	}

    public function saveCategory()
    {
        $this->validate();

        $data = [
            'name' => $this->name,
            'type' => $this->type,
            'icon' => $this->icon,
            'parent_id' => $this->parentId,
            'is_active' => $this->isActive,
            'sort_order' => $this->sortOrder,
        ];

        if ($this->editingId) {
            $category = Category::findOrFail($this->editingId);
            // 預設分類不可編輯
            if ($category->is_default) {
                $this->dispatch('notify', message: '系統預設分類不可修改', type: 'error');
                return;
            }
            $category->update($data);
            $message = '分類修改成功';
        } else {
            // 自定義分類標記
            $data['is_default'] = false;
            Category::create($data);
            $message = '新增分類成功';
        }

        $this->resetForm();
        $this->showForm = false;
        $this->dispatch('notify', message: $message, type: 'success');
    }

    public function confirmDeleteCategory($id)
    {
        $category = Category::findOrFail($id);
        if ($category->is_default) {
            $this->dispatch('notify', message: '系統預設分類不可刪除', type: 'error');
            return;
        }
        $this->confirmingDeletion = $id;
    }

    public function cancelDeleteCategory()
    {
        $this->confirmingDeletion = null;
    }

    public function deleteCategory($id)
	{
		try {
			$category = Category::findOrFail($id);
			
			// 1. 檢查是否為預設分類
			if ($category->is_default) {
				$this->dispatch('notify', message: '系統預設分類不可刪除', type: 'error');
				$this->confirmingDeletion = null;
				return;
			}
			
			// 2. 檢查是否有子分類
			$childrenCount = $category->children()->count();
			if ($childrenCount > 0) {
				$this->dispatch('notify', message: "無法刪除：該分類下仍有 {$childrenCount} 個子分類項目！", type: 'error');
				$this->confirmingDeletion = null;
				return;
			}
			
			// 3. 檢查是否有交易記錄（使用更安全的方式）
			$recordsCount = 0;
			if (method_exists($category, 'records')) {
				$recordsCount = $category->records()->count();
			}
			
			if ($recordsCount > 0) {
				$this->dispatch('notify', message: "無法刪除：已有 {$recordsCount} 筆記帳明細綁定此分類！", type: 'error');
				$this->confirmingDeletion = null;
				return;
			}
			
			// 4. 執行刪除
			$categoryName = $category->name;
			$category->delete();
			
			$this->confirmingDeletion = null;
			$this->dispatch('notify', message: "分類「{$categoryName}」已成功移除", type: 'success');
			
		} catch (\Exception $e) {
			$this->dispatch('notify', message: '刪除失敗：' . $e->getMessage(), type: 'error');
			$this->confirmingDeletion = null;
		}
	}

    // 獲取當前層級的分類
    public function getCategoriesProperty()
    {
        $query = Category::where('type', $this->activeTab);
        
        if ($this->currentParentId) {
            // 子類列表：只顯示該大類下的子分類
            $query->where('parent_id', $this->currentParentId);
        } else {
            // 大類列表：只顯示頂層分類 (parent_id = null)
            $query->whereNull('parent_id');
        }
        
        return $query
            ->when($this->search, function ($query) {
                return $query->where('name', 'like', '%' . $this->search . '%');
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(15);
    }

    public function getParentCategoriesProperty()
    {
        return Category::where('type', $this->activeTab)
            ->whereNull('parent_id')
            ->when($this->editingId, function ($query) {
                return $query->where('id', '!=', $this->editingId);
            })
            ->orderBy('name')
            ->get();
    }

    // 獲取當前大分類下的所有子分類（用於顯示）
    public function getSubCategories($parentId)
    {
        return Category::where('type', $this->activeTab)
            ->where('parent_id', $parentId)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function render()
    {
        return view('livewire.finance.category-drawer', [
            'categories' => $this->categories,
            'parentCategories' => $this->parentCategories,
            'currentParent' => $this->currentParentId ? Category::find($this->currentParentId) : null,
        ]);
    }
}