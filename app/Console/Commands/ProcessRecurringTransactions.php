<?php
// app/Console/Commands/ProcessRecurringTransactions.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TransactionTemplate;
use App\Models\Transaction;
use App\Models\FinancialAccount;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ProcessRecurringTransactions extends Command
{
    protected $signature = 'finance:process-recurring';
    protected $description = '自動掃描並執行到期的定期收支項目';

    public function handle()
    {
        $today = Carbon::now('Asia/Taipei')->toDateString();

        // 找出所有啟用中、且下次觸發日小於等於今日的定期收支項目
        $recurringItems = TransactionTemplate::recurring()
            ->where('is_active', true)
            ->whereDate('next_due_date', '<=', $today)
            ->where(function ($q) use ($today) {
                $q->whereNull('end_date')
                  ->orWhereDate('end_date', '>=', $today);
            })
            ->get();

        if ($recurringItems->isEmpty()) {
            $this->info('沒有需要執行的定期收支項目。');
            return Command::SUCCESS;
        }

        $processedCount = 0;
        $generatedTitles = [];

        foreach ($recurringItems as $item) {
            try {
                DB::transaction(function () use ($item, $today, &$generatedTitles) {
                    // 1. 寫入正式交易記錄
                    Transaction::create([
                        'shop_id' => $item->shop_id,
                        'user_id' => $item->user_id,
                        'type' => $item->type,
                        'from_account_id' => $item->from_account_id,
                        'to_account_id' => $item->to_account_id,
                        'category_id' => $item->category_id,
                        'amount' => $item->amount,
                        'recorded_at' => $today . ' ' . now('Asia/Taipei')->format('H:i:s'),
                        'memo' => '[定期自動生成] ' . ($item->memo ?? ''),
                    ]);

                    // 2. 計算並更新下次觸發日 (next_due_date)
                    $nextDate = Carbon::parse($item->next_due_date);
                    switch ($item->frequency) {
                        case 'daily':
                            $nextDate->addDays($item->interval);
                            break;
                        case 'weekly':
                            $nextDate->addWeeks($item->interval);
                            break;
                        case 'monthly':
                            $nextDate->addMonths($item->interval);
                            break;
                        case 'yearly':
                            $nextDate->addYears($item->interval);
                            break;
                    }

                    // 如果計算出的下次觸發日超過了結束日期 (end_date)，則停用此定期項目
                    $isActive = true;
                    if ($item->end_date && $nextDate->gt($item->end_date)) {
                        $isActive = false;
                    }

                    $item->update([
                        'next_due_date' => $nextDate->toDateString(),
                        'is_active' => $isActive,
                    ]);

                    $generatedTitles[] = "{$item->name}（金額：{$item->amount}）";
                });

                $processedCount++;
            } catch (\Exception $e) {
                $this->error("執行定期項目 ID {$item->id} 失敗: " . $e->getMessage());
            }
        }

        // 3. 將生成的清單寫入 Cache 或 Session，供前端使用者登入時彈出強制確認視窗
        if ($processedCount > 0) {
            // 可以存放在 Cache 中，讓使用者下次開啟前端頁面時彈出通知
            cache()->put('pending_recurring_notice_' . ($recurringItems->first()->shop_id ?? 1), $generatedTitles, now()->addDays(7));
            $this->info("成功自動生成 {$processedCount} 筆定期交易記錄！");
        }

        return Command::SUCCESS;
    }
}