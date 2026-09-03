<?php
// filepath: app/Livewire/Finance/TransactionModal.php

namespace App\Livewire\Finance;

use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\WithFileUploads;
use App\Models\FinancialAccount;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\TransactionTemplate;
use App\Traits\WithDateNavigation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class TransactionModal extends Component
{
    use WithFileUploads, WithDateNavigation;

    public bool $showTransactionModal = false;
    public bool $showTemplateModal = false;
    public bool $showTemplateListModal = false;
    public bool $showTemplateList = false;
    public bool $showCategoryPicker = false;
    public bool $isTemplateCategoryPicker = false;
    public ?string $categoryPickerReturnTo = null;  // 'transaction' | 'template' | null

    public ?int $transactionId = null;
    public string $type = 'expense';
    public ?int $fromAccountId = null;
    public ?int $toAccountId = null;
    public ?int $categoryId = null;
    public string $amount = '';
    public string $recordedAt = '';
    public string $memo = '';
    public int $shop_id = 1;

    // 照片上傳相關屬性
    public $photo;               // 用於綁定前端上傳的暫存圖片檔案物件
    public ?string $existingPhotoPath = null; // 用於修改記錄時顯示現有圖片

    private ?Transaction $originalTransaction = null;

    // 範本表單欄位
    public ?int $editingTemplateId = null;
    public string $templateType = 'expense';
    public string $templateName = '';
    public ?int $templateFromAccountId = null;
    public ?int $templateToAccountId = null;
    public ?int $templateCategoryId = null;
    public string $templateMemo = '';

    public function mount()
    {
		$this->dateMode = 'day';
        $now = Carbon::now('Asia/Taipei');
        $this->currentDate = $now->format('Y-m-d');
        $this->recordedAt = $now->format('Y-m-d\TH:i');

        $this->categoryId = 2;
        $this->fromAccountId = 1;
        $this->type = 'expense';
        $this->updatedType('expense');
    }

    /**
     * 當 WithDateNavigation 更新 currentDate 後觸發 Hook
     */
    public function onDateChanged(): void
    {
        // 取得原先 recordedAt 中的時間部分 (HH:mm)，若無則預設當前時間
        $timePart = strlen($this->recordedAt) >= 16 
            ? substr($this->recordedAt, 11, 5) 
            : now('Asia/Taipei')->format('H:i');

        $this->recordedAt = "{$this->currentDate}T{$timePart}";
    }
	
	#[On('open-transaction-modal')]
    public function openModal($transactionId = null)
    {
		$this->resetForm();
        $this->showTemplateList = false;

        if ($transactionId) {
            $transaction = Transaction::where('shop_id', $this->shop_id)
                ->find($transactionId);
                
            if (!$transaction) {
                $this->dispatch('toast', type: 'error', text: '交易記錄不存在');
                $this->showTransactionModal = false;
                return;
            }

            $this->transactionId = $transaction->id;
            $this->type = $transaction->type;

            if ($transaction->type === 'income') {
                $this->fromAccountId = $transaction->to_account_id;
                $this->toAccountId = null;
            } elseif ($transaction->type === 'expense') {
                $this->fromAccountId = $transaction->from_account_id;
                $this->toAccountId = null;
            } else {
                $this->fromAccountId = $transaction->from_account_id;
                $this->toAccountId = $transaction->to_account_id;
            }

            $this->categoryId = $transaction->category_id;
            $this->amount = number_format((float)$transaction->amount, 2, '.', '');
            
            // 設定日期與時間
            $dt = Carbon::parse($transaction->recorded_at);
            $this->currentDate = $dt->format('Y-m-d');
            $this->recordedAt = $dt->format('Y-m-d\TH:i');
            $this->memo = $transaction->memo ?? '';
            $this->existingPhotoPath = $transaction->photo_path;
        } else {
            // 新增模式
            $now = now('Asia/Taipei');
            $this->currentDate = $now->format('Y-m-d');
            $this->recordedAt = $now->format('Y-m-d\TH:i');
            
            $this->type = 'expense';
            $this->fromAccountId = 1;
            $this->toAccountId = null;
            $this->categoryId = 2;
            $this->amount = '';
            $this->memo = '';
            $this->existingPhotoPath = null;
        }

        $this->showTransactionModal = true;
        $this->dispatch('focus-amount-input');
    }

    public function updatedFromAccountId($value)
    {
        unset($this->accounts);
    }

    public function updatedToAccountId($value)
    {
        unset($this->accounts);
    }

    public function updatedType($value)
    {
        if ($value === 'expense') {
            $this->fromAccountId = 1;
            $this->toAccountId = null;
            $this->categoryId = 2;
        } elseif ($value === 'income') {
            $this->fromAccountId = 1;
            $this->toAccountId = null;
            $this->categoryId = 70;
        } elseif ($value === 'transfer') {
            $this->fromAccountId = 2;
            $this->toAccountId = null;
            $this->categoryId = null;
        }
    }

    public function updatedTemplateType($value)
    {
        if ($value === 'expense') {
            $this->templateFromAccountId = 1;
            $this->templateToAccountId = null;
            $this->templateCategoryId = 2;
        } elseif ($value === 'income') {
            $this->templateFromAccountId = 1;
            $this->templateToAccountId = null;
            $this->templateCategoryId = 70;
        } elseif ($value === 'transfer') {
            $this->templateFromAccountId = 2;
            $this->templateToAccountId = null;
            $this->templateCategoryId = null;
        }
    }

    public function updatedTemplateFromAccountId($value)
    {
        if ($this->templateType === 'transfer' && $this->templateToAccountId === $value) {
            $this->templateToAccountId = null;
        }
        unset($this->accounts);
    }

    public function incrementAmount()
    {
        $current = (float) $this->amount;
        $this->amount = number_format($current + 100, 2, '.', '');
    }

    public function decrementAmount()
    {
        $current = (float) $this->amount;
        if ($current > 0) {
            $newAmount = max(0, $current - 100);
            $this->amount = number_format($newAmount, 2, '.', '');
        }
    }

    public function changeDate($direction)
    {
        $currentDate = Carbon::parse($this->recordedAt);
        if ($direction === -1) {
            $currentDate->subDay();
        } else {
            $currentDate->addDay();
        }
        $this->recordedAt = $currentDate->format('Y-m-d\TH:i');
    }

    public function openCategoryPicker($forTemplate = false)
    {
        $this->isTemplateCategoryPicker = $forTemplate;
        $this->categoryPickerReturnTo = $forTemplate ? 'template' : 'transaction';
        $this->showCategoryPicker = true;
    }

    public function selectCategory($categoryId)
    {
        if ($this->isTemplateCategoryPicker) {
            $this->templateCategoryId = $categoryId;
        } else {
            $this->categoryId = $categoryId;
        }
        $this->showCategoryPicker = false;
        $this->categoryPickerReturnTo = null;
        $this->isTemplateCategoryPicker = false;
    }

    public function getSelectedCategoryProperty()
    {
        if ($this->categoryId) {
            return Category::with('parent')->find($this->categoryId);
        }
        return null;
    }

    public function getSelectedTemplateCategoryProperty()
    {
        if ($this->templateCategoryId) {
            return Category::with('parent')->find($this->templateCategoryId);
        }
        return null;
    }

    public function getCurrentAccountBalance()
    {
        if (!$this->fromAccountId) {
            return 0;
        }
        
        $account = FinancialAccount::where('id', $this->fromAccountId)
            ->where('shop_id', $this->shop_id)
            ->first();
        
        return $account ? $account->calculated_balance : 0;
    }

    private function resetForm()
    {
        $this->transactionId = null;
        $this->amount = '';
        $this->photo = null;
        $this->existingPhotoPath = null;
    }

    public function resetTemplateForm()
    {
        $this->reset([
            'editingTemplateId', 'templateType', 'templateName',
            'templateFromAccountId', 'templateToAccountId',
            'templateCategoryId', 'templateMemo'
        ]);
        $this->templateType = 'expense';
        $this->templateFromAccountId = 1;
        $this->templateCategoryId = 2;
    }

    public function getAccountsProperty()
    {
        return FinancialAccount::where('is_active', true)
            ->where('shop_id', $this->shop_id)
            ->get()
            ->map(fn($a) => [
                'id' => $a->id,
                'name' => $a->name,
                'balance' => $a->calculated_balance,
                'currency' => $a->currency
            ])
            ->toArray();
    }

    public function getFilteredCategoriesProperty()
    {
        $type = $this->isTemplateCategoryPicker 
            ? ($this->templateType ?? 'expense') 
            : $this->type;
            
        return Category::where('shop_id', $this->shop_id)
            ->where('type', $type)
            ->whereNull('parent_id')
            ->with(['children' => function($query) use ($type) {
                $query->where('type', $type)
                      ->orderBy('sort_order');
            }])
            ->orderBy('sort_order')
            ->get();
    }

    public function getTemplatesProperty()
    {
        $query = TransactionTemplate::where('shop_id', $this->shop_id);
        
        if (Auth::check()) {
            $query->where(function($q) {
                $q->where('user_id', Auth::id())
                  ->orWhereNull('user_id');
            });
        }
        
        return $query->orderBy('name')->get()->toArray();
    }

    public function openTemplateList()
    {
        $this->showTemplateListModal = true;
    }

    public function applyTemplate($templateId)
    {
        $template = TransactionTemplate::findOrFail($templateId);
        $this->type = $template->type;
        $this->amount = number_format((float)$template->amount, 2, '.', '');
        $this->fromAccountId = $template->from_account_id;
        $this->toAccountId = $template->to_account_id;
        $this->categoryId = $template->category_id;
        $this->memo = $template->memo ?? '';

        $this->showTemplateListModal = false;
        $this->dispatch('toast', type: 'success', text: '已套用範本：' . $template->name);
    }

    public function deleteTemplate($templateId)
    {
        $template = TransactionTemplate::findOrFail($templateId);
        $template->delete();
        $this->dispatch('toast', type: 'success', text: '範本已刪除');
    }

    public function editTemplate($templateId)
    {
        $template = TransactionTemplate::findOrFail($templateId);
        $this->editingTemplateId = $template->id;
        $this->templateType = $template->type;
        $this->templateName = $template->name;
        $this->templateFromAccountId = $template->from_account_id;
        $this->templateToAccountId = $template->to_account_id;
        $this->templateCategoryId = $template->category_id;
        $this->templateMemo = $template->memo ?? '';

        $this->showTemplateListModal = false;
        $this->dispatch('open-template-modal');
    }

    public function saveAsTemplate()
    {
        $this->validate([
            'templateName' => 'required|string|max:50',
            'amount' => 'numeric|gte:0',
            'templateFromAccountId' => 'required|exists:financial_accounts,id',
        ]);

        if ($this->templateType === 'transfer') {
            $this->validate([
                'templateToAccountId' => 'required|exists:financial_accounts,id|different:templateFromAccountId',
            ]);
        } else {
            $this->validate([
                'templateCategoryId' => 'required|exists:categories,id',
            ]);
        }

        $userId = Auth::id();
        if (!$userId) {
            throw new \Exception('請先登入才能儲存範本');
        }

        $amount = (float) $this->amount;
        if ($amount < 0) {
            throw new \Exception('金額必須大於 0');
        }

        $templateData = [
            'user_id' => $userId,
            'shop_id' => $this->shop_id,
            'type' => $this->templateType,
            'name' => $this->templateName,
            'amount' => $amount,
            'from_account_id' => $this->templateFromAccountId,
            'to_account_id' => $this->templateType === 'transfer' ? $this->templateToAccountId : null,
            'category_id' => $this->templateType !== 'transfer' ? $this->templateCategoryId : null,
            'memo' => $this->templateMemo ?? '',
        ];

        if ($this->editingTemplateId) {
            TransactionTemplate::findOrFail($this->editingTemplateId)->update($templateData);
            $this->dispatch('toast', type: 'success', text: '範本已更新！');
        } else {
            TransactionTemplate::create($templateData);
            $this->dispatch('toast', type: 'success', text: '範本已儲存！');
        }

        $this->showTemplateModal = false;
        $this->showTransactionModal = true;
    }

    public function executeSaveProcedure()
    {
        $userId = Auth::id();
        if (!$userId) throw new \Exception('請先登入才能記帳');

        $rules = [
            'fromAccountId' => 'required|exists:financial_accounts,id',
            'amount' => 'required|numeric|gt:0',
            'recordedAt' => 'required|date',
            'photo' => 'nullable|image|max:5120',
        ];

        if ($this->type === 'transfer') {
            $rules['toAccountId'] = 'required|exists:financial_accounts,id|different:fromAccountId';
        } else {
            $rules['categoryId'] = 'required|exists:categories,id';
        }

        $this->validate($rules);

        $finalPhotoPath = $this->existingPhotoPath;
        if ($this->photo) {
            if ($this->existingPhotoPath) {
                Storage::disk('public')->delete($this->existingPhotoPath);
            }
            $finalPhotoPath = $this->photo->store('transactions', 'public');
        }

        DB::transaction(function () use ($userId, $finalPhotoPath) {
            if ($this->transactionId) {
                $oldTx = Transaction::where('shop_id', $this->shop_id)
                    ->lockForUpdate()
                    ->findOrFail($this->transactionId);

                if ($oldTx->type === 'transfer') {
                    $oldFrom = FinancialAccount::where('id', $oldTx->from_account_id)->lockForUpdate()->first();
                    $oldTo = FinancialAccount::where('id', $oldTx->to_account_id)->lockForUpdate()->first();
                    if ($oldFrom) $oldFrom->increment('balance', $oldTx->amount);
                    if ($oldTo) $oldTo->decrement('balance', $oldTx->amount);
                } else {
                    if ($oldTx->type === 'expense') {
                        $oldAcc = FinancialAccount::where('id', $oldTx->from_account_id)->lockForUpdate()->first();
                        if ($oldAcc) $oldAcc->increment('balance', $oldTx->amount);
                    } elseif ($oldTx->type === 'income') {
                        $oldAcc = FinancialAccount::where('id', $oldTx->to_account_id)->lockForUpdate()->first();
                        if ($oldAcc) $oldAcc->decrement('balance', $oldTx->amount);
                    }
                }
            }

            if ($this->type === 'transfer') {
                $fromAccount = FinancialAccount::where('id', $this->fromAccountId)->where('shop_id', $this->shop_id)->lockForUpdate()->firstOrFail();
                $toAccount = FinancialAccount::where('id', $this->toAccountId)->where('shop_id', $this->shop_id)->lockForUpdate()->firstOrFail();

                if (bccomp($fromAccount->balance, $this->amount, 4) < 0) {
                    throw new \Exception('來源帳戶餘額不足！');
                }

                $fromAccount->balance = bcsub($fromAccount->balance, $this->amount, 4);
                $toAccount->balance = bcadd($toAccount->balance, $this->amount, 4);
                $fromAccount->save();
                $toAccount->save();

                Transaction::updateOrCreate(
                    ['id' => $this->transactionId, 'shop_id' => $this->shop_id],
                    [
                        'user_id' => $userId,
                        'type' => 'transfer',
                        'from_account_id' => $this->fromAccountId,
                        'to_account_id' => $this->toAccountId,
                        'category_id' => null,
                        'amount' => $this->amount,
                        'recorded_at' => $this->recordedAt,
                        'memo' => $this->memo,
                        'photo_path' => $finalPhotoPath,
                    ]
                );
            } else {
                $account = FinancialAccount::where('id', $this->fromAccountId)->where('shop_id', $this->shop_id)->lockForUpdate()->firstOrFail();

                if ($this->type === 'expense') {
                    $account->balance = bcsub($account->balance, $this->amount, 4);
                    $account->save();

                    Transaction::updateOrCreate(
                        ['id' => $this->transactionId, 'shop_id' => $this->shop_id],
                        [
                            'user_id' => $userId,
                            'type' => 'expense',
                            'from_account_id' => $this->fromAccountId,
                            'to_account_id' => null,
                            'category_id' => $this->categoryId,
                            'amount' => $this->amount,
                            'recorded_at' => $this->recordedAt,
                            'memo' => $this->memo,
                            'photo_path' => $finalPhotoPath,
                        ]
                    );
                } else {
                    $account->balance = bcadd($account->balance, $this->amount, 4);
                    $account->save();

                    Transaction::updateOrCreate(
                        ['id' => $this->transactionId, 'shop_id' => $this->shop_id],
                        [
                            'user_id' => $userId,
                            'type' => 'income',
                            'from_account_id' => null,
                            'to_account_id' => $this->fromAccountId,
                            'category_id' => $this->categoryId,
                            'amount' => $this->amount,
                            'recorded_at' => $this->recordedAt,
                            'memo' => $this->memo,
                            'photo_path' => $finalPhotoPath,
                        ]
                    );
                }
            }
        });
    }

    public function saveTransaction()
    {
        $this->executeSaveProcedure();
        $this->showTransactionModal = false;

        // 同時通知主頁面與 Modal 列表刷新
        $this->dispatch('refresh-data');
        $this->dispatch('refresh-transaction-list');
        $this->dispatch('toast', type: 'success', text: '交易已成功儲存！');
    }

    public function saveAndKeepOpen()
    {
        $this->executeSaveProcedure();
        
        $keep = [
            'type', 'fromAccountId', 'toAccountId', 
            'categoryId', 'recordedAt', 'memo'
        ];
        
        $saved = [];
        foreach ($keep as $key) {
            $saved[$key] = $this->$key;
        }
        
        $this->transactionId = null;
        $this->amount = '';
        $this->photo = null;
        $this->existingPhotoPath = null;
        
        foreach ($saved as $key => $value) {
            $this->$key = $value;
        }
        
        $this->dispatch('refresh-data');
        $this->dispatch('refresh-transaction-list');
        $this->dispatch('toast', type: 'success', text: '儲存成功，請繼續操作！');
    }

    public function openTemplateModalFromTransaction()
    {
        $this->templateType = $this->type;
        $this->templateFromAccountId = $this->fromAccountId;
        $this->templateToAccountId = $this->toAccountId;
        $this->templateCategoryId = $this->categoryId;
        $this->templateMemo = $this->memo;
        
        $this->showTransactionModal = false;
        $this->showTemplateListModal = false;
        $this->showTemplateModal = true;
    }

    public function openTemplateModalFromList()
    {
        $this->showTemplateListModal = false;
        $this->resetTemplateForm();
        $this->dispatch('open-template-modal');
    }

    #[On('open-template-modal')]
    public function onOpenTemplateModal()
    {
        $this->showTransactionModal = false;
        $this->showTemplateModal = true;
    }

    public function openTemplateModal()
    {
        $this->resetTemplateForm();
        $this->showTransactionModal = false;
        $this->showTemplateListModal = false;
        $this->showTemplateModal = true;
    }
    
    public function swapAccounts()
    {
        $temp = $this->fromAccountId;
        $this->fromAccountId = $this->toAccountId;
        $this->toAccountId = $temp;
    }

    public function deleteTransaction()
    {
        $userId = Auth::id();
        if (!$userId) {
            $this->dispatch('toast', type: 'error', text: '請先登入才能刪除記錄');
            return;
        }

        if (!$this->transactionId) {
            $this->dispatch('toast', type: 'error', text: '找不到要刪除的記錄');
            return;
        }

        try {
            DB::transaction(function () use ($userId) {
                $transaction = Transaction::where('shop_id', $this->shop_id)
                    ->where('id', $this->transactionId)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($transaction->type === 'transfer') {
                    $fromAccount = FinancialAccount::where('id', $transaction->from_account_id)
                        ->where('shop_id', $this->shop_id)
                        ->lockForUpdate()
                        ->first();
                    $toAccount = FinancialAccount::where('id', $transaction->to_account_id)
                        ->where('shop_id', $this->shop_id)
                        ->lockForUpdate()
                        ->first();

                    if ($fromAccount) {
                        $fromAccount->balance = bcadd($fromAccount->balance, $transaction->amount, 4);
                        $fromAccount->save();
                    }
                    if ($toAccount) {
                        $toAccount->balance = bcsub($toAccount->balance, $transaction->amount, 4);
                        $toAccount->save();
                    }
                } elseif ($transaction->type === 'expense') {
                    $account = FinancialAccount::where('id', $transaction->from_account_id)
                        ->where('shop_id', $this->shop_id)
                        ->lockForUpdate()
                        ->first();
                    if ($account) {
                        $account->balance = bcadd($account->balance, $transaction->amount, 4);
                        $account->save();
                    }
                } elseif ($transaction->type === 'income') {
                    $account = FinancialAccount::where('id', $transaction->to_account_id)
                        ->where('shop_id', $this->shop_id)
                        ->lockForUpdate()
                        ->first();
                    if ($account) {
                        $account->balance = bcsub($account->balance, $transaction->amount, 4);
                        $account->save();
                    }
                }

                if ($transaction->photo_path) {
                    Storage::disk('public')->delete($transaction->photo_path);
                }

                $transaction->delete();
            });

            $this->showTransactionModal = false;
            $this->dispatch('refresh-transaction-list');
            $this->dispatch('refresh-data');
            $this->dispatch('toast', type: 'success', text: '記錄已成功刪除！');

        } catch (\Exception $e) {
            $this->dispatch('toast', type: 'error', text: '刪除失敗：' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.finance.transaction-modal', [
            'templates' => $this->templates,
            'selectedCategory' => $this->selectedCategory,
            'selectedTemplateCategory' => $this->selectedTemplateCategory,
        ]);
    }
}