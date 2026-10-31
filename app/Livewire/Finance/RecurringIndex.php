<?php

namespace App\Livewire\Finance;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\TransactionTemplate;
use App\Models\FinancialAccount;
use App\Models\Category;
use App\Traits\WithAlert;
use Illuminate\Support\Facades\Auth;

class RecurringIndex extends Component
{
    use WithPagination, WithAlert;

    public int $shop_id = 1;
    
    // 表單屬性
    public bool $showModal = false;
	public bool $showCategoryPicker = false;
    public ?int $editingId = null;
    public string $name = '';
    public string $type = 'expense';
    public ?int $fromAccountId = null;
    public ?int $toAccountId = null;
    public ?int $categoryId = null;
    public string $amount = '';
    public string $frequency = 'monthly';
    public int $interval = 1;
    public string $nextDueDate = '';
    public ?string $endDate = null;
    public string $memo = '';
    public bool $isActive = true;

    protected $rules = [
        'name' => 'required|string|max:50',
        'type' => 'required|in:expense,income,transfer',
        'fromAccountId' => 'required|exists:financial_accounts,id',
        'amount' => 'required|numeric|gt:0',
        'frequency' => 'required|in:daily,weekly,monthly,yearly',
        'interval' => 'required|integer|min:1',
        'nextDueDate' => 'required|date',
        'endDate' => 'nullable|date|after_or_equal:nextDueDate',
    ];

    public function mount()
    {
        $this->nextDueDate = now('Asia/Taipei')->format('Y-m-d');
        $this->fromAccountId = 1;
        $this->categoryId = 2;
    }

    public function updatedType($value)
    {
        if ($value === 'expense') {
            $this->categoryId = 2;
        } elseif ($value === 'income') {
            $this->categoryId = 70;
        } elseif ($value === 'transfer') {
            $this->categoryId = null;
            $this->toAccountId = null;
        }
		
		// 型別切換時若選擇器仍開著，一併關閉，避免顯示舊 type 的分類。
        $this->closeCategoryPicker();
    }

    public function openCreateModal()
    {
        $this->reset([
            'editingId', 'name', 'type', 'fromAccountId', 'toAccountId', 
            'categoryId', 'amount', 'interval', 'endDate', 'memo', 'isActive'
        ]);
        $this->frequency = 'monthly';
        $this->interval = 1;
        $this->nextDueDate = now('Asia/Taipei')->format('Y-m-d');
        $this->type = 'expense';
        $this->showModal = true;
    }
	
	public function openCategoryPicker(): void
    {
        $this->showCategoryPicker = true;
    }

    public function closeCategoryPicker(): void
    {
        $this->showCategoryPicker = false;
    }

    public function selectCategory(int $categoryId): void
    {
        $this->categoryId = $categoryId;
        $this->closeCategoryPicker();
    }

    public function edit($id)
    {
        $item = TransactionTemplate::recurring()
            ->where('shop_id', $this->shop_id)
            ->findOrFail($id);

        $this->editingId = $item->id;
        $this->name = $item->name;
        $this->type = $item->type;
        $this->fromAccountId = $item->from_account_id;
        $this->toAccountId = $item->to_account_id;
        $this->categoryId = $item->category_id;
        $this->amount = number_format((float)$item->amount, 2, '.', '');
        $this->frequency = $item->frequency ?? 'monthly';
        $this->interval = $item->interval ?? 1;
        $this->nextDueDate = $item->next_due_date ? $item->next_due_date->format('Y-m-d') : now()->format('Y-m-d');
        $this->endDate = $item->end_date ? $item->end_date->format('Y-m-d') : null;
        $this->memo = $item->memo ?? '';
        $this->isActive = $item->is_active;

        $this->showModal = true;
    }

    public function save()
    {
        $this->validate();

        $userId = Auth::id();
        if (!$userId) {
            $this->showErrorAlert('權限錯誤', '請先登入系統');
            return;
        }

        $data = [
            'shop_id' => $this->shop_id,
            'user_id' => $userId,
            'name' => $this->name,
            'type' => $this->type,
            'from_account_id' => $this->fromAccountId,
            'to_account_id' => $this->type === 'transfer' ? $this->toAccountId : null,
            'category_id' => $this->type !== 'transfer' ? $this->categoryId : null,
            'amount' => $this->amount,
            'is_recurring' => true, // 標記為定期收支項目
            'frequency' => $this->frequency,
            'interval' => $this->interval,
            'next_due_date' => $this->nextDueDate,
            'end_date' => $this->endDate ?: null,
            'memo' => $this->memo,
            'is_active' => $this->isActive,
        ];

        if ($this->editingId) {
            TransactionTemplate::where('shop_id', $this->shop_id)
                ->recurring()
                ->findOrFail($this->editingId)
                ->update($data);
            $this->showSuccess('定期收支項目已更新');
        } else {
            TransactionTemplate::create($data);
            $this->showSuccess('定期收支項目已建立');
        }

        $this->showModal = false;
    }

    public function delete($id)
    {
        $item = TransactionTemplate::recurring()
            ->where('shop_id', $this->shop_id)
            ->findOrFail($id);
            
        $item->delete();
        $this->showSuccess('定期收支項目已刪除');
    }

    public function getAccountsProperty()
    {
        // 轉為陣列格式，供 <x-account-select> 與 <x-choices> 使用。
        // 格式與 TransactionModal::getAccountsProperty() 一致，含 balance / currency。
        return FinancialAccount::where('is_active', true)
            ->where('shop_id', $this->shop_id)
            ->get()
            ->map(fn($a) => [
                'id' => $a->id,
                'name' => $a->name,
                'balance' => $a->calculated_balance,
                'currency' => $a->currency,
            ])
            ->toArray();
    }

	public function getCategoriesProperty()
    {
        // 只取父分類，並 eager load 同 type 的 children，
        // 供 <x-category-picker> 展開子分類使用。
        return Category::where('shop_id', $this->shop_id)
            ->where('type', $this->type)
            ->whereNull('parent_id')
            ->with(['children' => function ($query) {
                $query->where('type', $this->type)
                      ->orderBy('sort_order');
            }])
            ->orderBy('sort_order')
            ->get();
    }
	
	public function getSelectedCategoryProperty()
    {
        if (!$this->categoryId) {
            return null;
        }

        return Category::with('parent')
            ->where('shop_id', $this->shop_id)
            ->find($this->categoryId);
    }

    public function render()
    {
        $items = TransactionTemplate::recurring()
            ->where('shop_id', $this->shop_id)
            ->orderBy('next_due_date', 'asc')
            ->paginate(10);

        return view('livewire.finance.recurring-index', [
            'items' => $items,
        ])->layout('components.layouts.app'); // 依照你的系統 Layout 調整
    }
}