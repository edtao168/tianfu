<?php
// app/Livewire/Finance/TransactionIndex.php

namespace App\Livewire\Finance;

use App\Models\Category;
use App\Models\FinancialAccount;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

use Mary\Traits\Toast;

class TransactionIndex extends Component
{
    use WithPagination;
    use Toast;

    // 篩選器綁定變數
    public string $searchCurrency = '';  // 篩選幣別 (TWD, CNY, HKD, USD)
    public ?int $searchAccountId = null; // 篩選特定帳戶
    public ?int $searchCategoryId = null; // 篩選特定分類
    public string $searchType = '';       // 篩選類型 (expense, income)

    // 控制是否顯示進階篩選抽屜
    public bool $showFilters = false;

    // 預留多店店別，預設為 1
    public int $shopId = 1;

    /**
     * 當篩選條件變更時，自動跳回第一頁並防呆重置
     */
    public function updatedSearchCurrency() { $this->resetPage(); $this->searchAccountId = null; }
    public function updatedSearchAccountId() { $this->resetPage(); }
    public function updatedSearchCategoryId() { $this->resetPage(); }
    public function updatedSearchType() { $this->resetPage(); }

    /**
     * 刪除單筆記帳紀錄（反向高精度回滾帳戶餘額，嚴格防併發）
     */
    public function deleteTransaction(int $id)
    {
        try {
            DB::transaction(function () use ($id) {
                $transaction = Transaction::where('id', $id)
                    ->where('shop_id', $this->shopId)
                    ->firstOrFail();

                // 獲取關聯的帳戶，採用行鎖 (lockForUpdate)
                $accountId = $transaction->from_account_id ?? $transaction->to_account_id;
                $account = FinancialAccount::where('id', $accountId)->lockForUpdate()->firstOrFail();

                // 根據原本的支出/收入性質，執行高精度的反向餘額沖正
                if ($transaction->type === 'expense') {
                    // 原本是支出（扣錢），刪除時要「加回」金額
                    $newBalance = bcadd($account->balance, $transaction->amount, 4);
                } else {
                    // 原本是收入（加錢），刪除時要「扣除」金額
                    $newBalance = bcsub($account->balance, $transaction->amount, 4);
                }

                // 更新餘額並刪除交易明細
                $account->update(['balance' => $newBalance]);
                $transaction->delete();
            });

            $this->toast(type: 'success', title: '刪除成功', description: '該筆紀錄已移除，帳戶餘額已精確沖正回滾。');
        } catch (\Exception $e) {
            $this->toast(type: 'error', title: '刪除失敗', description: $e->getMessage());
        }
    }

    /**
     * 後端渲染主邏輯
     */
    public function render()
    {
        // 建立基本查詢器，預載關聯以優化 SQL 效能 (防止 N+1 問題)
        $query = Transaction::query()
            ->with(['category', 'fromAccount', 'toAccount'])
            ->where('shop_id', $this->shopId)
            ->orderBy('recorded_at', 'desc');

        // 套用動態條件過濾
        if ($this->searchType) {
            $query->where('type', $this->searchType);
        }
        if ($this->searchCategoryId) {
            $query->where('category_id', $this->searchCategoryId);
        }
        if ($this->searchAccountId) {
            $query->where(function($q) {
                $q->where('from_account_id', $this->searchAccountId)
                  ->orWhere('to_account_id', $this->searchAccountId);
            });
        }
        if ($this->searchCurrency) {
            $query->where(function($q) {
                $q->whereHas('fromAccount', fn($a) => $a->where('currency', $this->searchCurrency))
                  ->orWhereHas('toAccount', fn($a) => $a->where('currency', $this->searchCurrency));
            });
        }

        // 分頁抓取流水（每頁 15 筆）
        $transactions = $query->paginate(15);

        // 將當前頁面的紀錄依「日期 (Y-m-d)」進行分組，供前端時間線渲染
        $groupedTransactions = collect($transactions->items())->groupBy(function ($item) {
            return Carbon::parse($item->recorded_at)->format('Y-m-d');
        });

        // 撈取篩選選單所需資料，並與設定檔幣別關聯
        $accounts = FinancialAccount::where('is_active', true)->get();
        $filteredAccounts = $this->searchCurrency ? $accounts->where('currency', $this->searchCurrency) : $accounts;

        // 僅撈取子分類供篩選（parent_id 不為 null）
        $categories = Category::whereNotNull('parent_id')->orderBy('sort_order')->get();

        return view('livewire.finance.transaction-index', [
            'groupedTransactions' => $groupedTransactions,
            'transactionsPaginator' => $transactions, // 給分頁組件讀取
            'currencies' => config('business.currencies', []),
            'accounts' => $filteredAccounts,
            'categories' => $categories
        ])->layout('components.layouts.app');
    }
}