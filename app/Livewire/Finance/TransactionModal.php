<?php
// app/Livewire/Finance/TransactionModal.php

namespace App\Livewire\Finance;

use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\FinancialAccount;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\TransactionTemplate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class TransactionModal extends Component
{
    public bool $showTransactionModal = false;
    public bool $showTemplateModal = false;
    public bool $showTemplateListModal = false;
    public bool $showCategoryPicker = false;

    public ?int $transactionId = null;
    public string $type = 'expense';
    public ?int $fromAccountId = null;
    public ?int $toAccountId = null;
    public ?int $categoryId = null;
    public string $amount = '';
    public string $recordedAt = '';
    public string $memo = '';
    public int $shop_id = 1;

    private ?Transaction $originalTransaction = null;

    // 範本表單欄位
    public ?int $editingTemplateId = null;
    public string $templateType = 'expense';
    public string $templateName = '';
    public string $templateAmount = '';
    public ?int $templateFromAccountId = null;
    public ?int $templateToAccountId = null;
    public ?int $templateCategoryId = null;
    public string $templateMemo = '';

    public function mount()
    {
        $this->recordedAt = now()->format('Y-m-d\TH:i');
        $this->categoryId = 2;  // 預設支出類別
    }

    #[On('open-transaction-modal')]
    public function openModal($transaction_id = null)
    {
        $this->resetForm();
        $this->recordedAt = now()->format('Y-m-d\TH:i');

        if ($transaction_id) {
            // 修改模式...
            $transaction = Transaction::where('shop_id', $this->shop_id)
                ->findOrFail($transaction_id);

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
            $this->recordedAt = Carbon::parse($transaction->recorded_at)->format('Y-m-d\TH:i');
            $this->memo = $transaction->memo ?? '';
        } else {
            // 新增模式
            $this->type = 'expense';
            $this->fromAccountId = 1;
            $this->toAccountId = 1;
            $this->categoryId = 2;  // 支出類別預設
        }

        $this->showTransactionModal = true;
    }

    /**
     * 當類型變更時自動更新類別
     */
    public function updatedType($value)
    {
        // 檢查當前類別是否還有效
        if ($this->categoryId !== null) {
            $category = Category::find($this->categoryId);
            if ($category && $category->type === $value) {
                return;  // 保留使用者選擇的類別
            }
        }
        
        // 否則設定新的預設值
        $this->categoryId = match ($value) {
            'expense' => 2,
            'income'  => 70,
            default   => null,
        };
    }

    // ============ 金額增減方法 ============
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

    // ============ 日期變更方法 ============
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

    // ============ 類別選擇方法 ============
    public function openCategoryPicker()
    {
        $this->showCategoryPicker = true;
    }

    public function selectCategory($categoryId)
    {
        $this->categoryId = $categoryId;
        $this->showCategoryPicker = false;
    }

    public function getSelectedCategoryProperty()
    {
        if ($this->categoryId) {
            return Category::with('parent')->find($this->categoryId);
        }
        return null;
    }

    /**
     * 重置表單（只清除交易 ID 和金額）
     */
    private function resetForm()
    {
        $this->transactionId = null;
        $this->amount = '';
        // 保留 type, fromAccountId, toAccountId, categoryId, recordedAt, memo
    }

    private function resetTemplateForm()
    {
        $this->reset([
            'editingTemplateId', 'templateType', 'templateName',
            'templateAmount', 'templateFromAccountId', 'templateToAccountId',
            'templateCategoryId', 'templateMemo'
        ]);
    }

    // ============ 計算屬性：帳戶與分類 ============
    public function getAccountsProperty()
    {
        return FinancialAccount::where('is_active', true)
            ->where('shop_id', $this->shop_id)
            ->get()
            ->map(fn($a) => [
                'id' => $a->id,
                'name' => $a->name,
                'balance' => $a->balance,
                'currency' => $a->currency
            ])
            ->toArray();
    }

    public function getFilteredCategoriesProperty()
    {
        return Category::where('shop_id', $this->shop_id)
            ->where('type', $this->type)
            ->whereNull('parent_id')
            ->with(['children' => function($query) {
                $query->where('type', $this->type)
                      ->orderBy('sort_order');
            }])
            ->orderBy('sort_order')
            ->get();
    }

    // ============ 範本功能 ============
    public function getTemplatesProperty()
    {
        $query = TransactionTemplate::where('shop_id', $this->shop_id);
        if (Auth::check()) {
            $query->where('user_id', Auth::id());
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
        $this->templateName = $template->name;
        $this->templateType = $template->type;
        $this->templateAmount = number_format((float)$template->amount, 2, '.', '');
        $this->templateFromAccountId = $template->from_account_id;
        $this->templateToAccountId = $template->to_account_id;
        $this->templateCategoryId = $template->category_id;
        $this->templateMemo = $template->memo ?? '';

        $this->showTemplateListModal = false;
        $this->showTemplateModal = true;
    }

    public function saveAsTemplate()
    {
        $this->validate(['templateName' => 'required|string|max:50']);
        $userId = Auth::id();
        if (!$userId) throw new \Exception('請先登入才能儲存範本');

        $templateData = [
            'user_id' => $userId,
            'shop_id' => $this->shop_id,
            'type' => $this->type,
            'name' => $this->templateName,
            'amount' => $this->amount,
            'from_account_id' => $this->fromAccountId,
            'to_account_id' => $this->toAccountId,
            'category_id' => $this->categoryId,
            'memo' => $this->memo,
        ];

        if ($this->editingTemplateId) {
            TransactionTemplate::findOrFail($this->editingTemplateId)->update($templateData);
            $this->dispatch('toast', type: 'success', text: '範本已更新！');
        } else {
            TransactionTemplate::create($templateData);
            $this->dispatch('toast', type: 'success', text: '範本已儲存！');
        }

        $this->showTemplateModal = false;
        $this->resetTemplateForm();
    }

    // ============ 主要儲存與修改程序 ============
    public function executeSaveProcedure()
    {
        $userId = Auth::id();
        if (!$userId) throw new \Exception('請先登入才能記帳');

        $rules = [
            'fromAccountId' => 'required|exists:financial_accounts,id',
            'amount' => 'required|numeric|gt:0',
            'recordedAt' => 'required|date',
        ];

        if ($this->type === 'transfer') {
            $rules['toAccountId'] = 'required|exists:financial_accounts,id|different:fromAccountId';
        } else {
            $rules['categoryId'] = 'required|exists:categories,id';
        }

        $this->validate($rules);

        DB::transaction(function () use ($userId) {
            // 編輯模式：回滾舊交易
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

            // 執行新交易
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
                    ]
                );
            } else {
                $account = FinancialAccount::where('id', $this->fromAccountId)->where('shop_id', $this->shop_id)->lockForUpdate()->firstOrFail();

                if ($this->type === 'expense') {
                    if (bccomp($account->balance, $this->amount, 4) < 0) {
                        throw new \Exception('帳戶餘額不足！');
                    }
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
        $this->dispatch('page-reload');
    }

    /**
     * ✅ 「再記一筆」：保留所有欄位，只清空金額和交易 ID
     */
    public function saveAndKeepOpen()
    {
        $this->executeSaveProcedure();
        
        // ✅ 保存所有需要保留的狀態（除了 transactionId 和 amount）
        $keep = [
            'type', 'fromAccountId', 'toAccountId', 
            'categoryId', 'recordedAt', 'memo'
        ];
        
        $saved = [];
        foreach ($keep as $key) {
            $saved[$key] = $this->$key;
        }
        
        // 重置（只清除 transactionId 和 amount）
        $this->transactionId = null;
        $this->amount = '';
        
        // ✅ 恢復所有保存的狀態
        foreach ($saved as $key => $value) {
            $this->$key = $value;
        }
        
        $this->dispatch('refresh-data');
        $this->dispatch('toast', type: 'success', text: '儲存成功，請繼續操作！');
    }

    public function render()
    {
        return view('livewire.finance.transaction-modal', [
            'templates' => $this->templates,
            'selectedCategory' => $this->selectedCategory,
        ]);
    }
}