<?php

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

    public array $iconOptions = [
        // 基本與商務
        'folder', 'document-text', 'tag', 'gift', 'briefcase', 'ticket',
        
        // 金融與購物 (修正名稱)
        'shopping-cart', 'shopping-bag', 'credit-card', 'banknotes', 'currency-dollar',
        
        // 居家與生活 (Heroicons v2 房子是 home)
        'home', 'building-office', 'key', 'bolt', 'fire', 'wrench',
        
        // 交通
        'truck', 'map',
        
        // 教育與工具
        'academic-cap', 'book-open', 'pencil', 'pencil-square', 'paint-brush',
        
        // 娛樂與社交
        'film', 'musical-note', 'puzzle-piece', 'trophy',
        'user', 'users', 'user-group', 'heart',
        
        // 工具與符號
        'globe-alt', 'sun', 'moon', 'star',
        'chart-bar', 'chart-pie', 'arrow-trending-up', 'arrow-trending-down',
        
        // 動作與狀態
        'trash', 'plus', 'minus', 'x-mark', 'check', 'check-circle', 'exclamation-circle',
        'magnifying-glass', 'bell', 'calendar', 'clock', 'lock-closed', 'phone', 'envelope'
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
    }

    public function close()
    {
        $this->isOpen = false;
        $this->showForm = false;
        $this->resetForm();
        $this->search = '';
        $this->resetPage();
    }

    public function switchTab(string $tab)
    {
        $this->activeTab = $tab;
        $this->type = $tab;
        $this->resetPage();
        $this->resetForm();
        $this->showForm = false;
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function setViewMode($mode)
    {
        $this->viewMode = $mode;
    }

    public function toggleForm()
    {
        $this->showForm = !$this->showForm;
        if (!$this->showForm) {
            $this->resetForm();
        }
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

    public function editCategory($id)
    {
        $category = Category::findOrFail($id);
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
            'sort_order' => $this->sortOrder
        ];

        if ($this->editingId) {
            Category::findOrFail($this->editingId)->update($data);
            $message = '分類修改成功';
        } else {
            Category::create($data);
            $message = '新增分類成功';
        }

        $this->resetForm();
        $this->showForm = false;
        $this->dispatch('notify', message: $message, type: 'success');
    }

    public function confirmDeleteCategory($id)
    {
        $this->confirmingDeletion = $id;
    }

    public function cancelDeleteCategory()
    {
        $this->confirmingDeletion = null;
    }

    public function deleteCategory($id)
    {
        $category = Category::findOrFail($id);
        
        if ($category->children()->count() > 0) {
            $this->dispatch('notify', message: '無法刪除：該分類下仍有子分類項目！', type: 'error');
            $this->confirmingDeletion = null;
            return;
        }
        
        if (method_exists($category, 'records') && $category->records()->count() > 0) {
            $this->dispatch('notify', message: '無法刪除：已有記帳明細綁定此分類！', type: 'error');
            $this->confirmingDeletion = null;
            return;
        }

        $category->delete();
        $this->confirmingDeletion = null;
        $this->dispatch('notify', message: '分類已成功移除', type: 'success');
    }

    public function getCategoriesProperty()
    {
        return Category::where('type', $this->activeTab)
            ->whereNull('parent_id')
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
        ]);
    }
}