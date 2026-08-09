<?php
// app/Livewire/Finance/CurrencyIndex.php

namespace App\Livewire\Finance;

use App\Models\Currency;
use App\Models\Shop;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CurrencyIndex extends Component
{
    use WithPagination;

    // 表單屬性
    public $showForm = false;
    public $editingId = null;
    
    public $code = '';
    public $name = '';
    public $symbol = '';
    public $rate = '';
    public $is_base = false;
    public $is_active = true;

    // 當前選中的 Shop
    public $currentShopId = null;

    // 搜尋和篩選
    public $search = '';
    public $filterActive = 'all'; // all, active, inactive

    protected $rules = [
        'code' => 'required|string|size:3|alpha|unique:currencies,code,NULL,id,shop_id,{shop_id}',
        'name' => 'required|string|max:50',
        'symbol' => 'required|string|max:10',
        'rate' => 'required|numeric|min:0.000001|max:999999.999999',
        'is_base' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected $messages = [
        'code.required' => '請輸入幣別代碼',
        'code.size' => '幣別代碼必須為 3 個字元',
        'code.unique' => '此幣別代碼已存在',
        'name.required' => '請輸入幣別名稱',
        'symbol.required' => '請輸入幣別符號',
        'rate.required' => '請輸入匯率',
        'rate.min' => '匯率必須大於 0',
        'rate.numeric' => '匯率必須為數字',
    ];

    public function mount()
    {
        $this->currentShopId = session('current_shop_id');
        // 如果 session 沒有，從 user 的預設帳本取得
        if (!$this->currentShopId) {
            $this->currentShopId = Auth::user()->shops()->first()->id ?? null;
            session(['current_shop_id' => $this->currentShopId]);
        }
    }

    public function getDefaultShopId()
    {
        $shop = Shop::where('owner_id', Auth::id())->first();
        return $shop ? $shop->id : null;
    }

    protected function getCurrencies()
    {
        $query = Currency::where('shop_id', $this->currentShopId);

        // 搜尋
        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('code', 'like', '%' . $this->search . '%')
                  ->orWhere('name', 'like', '%' . $this->search . '%');
            });
        }

        // 篩選
        if ($this->filterActive === 'active') {
            $query->where('is_active', true);
        } elseif ($this->filterActive === 'inactive') {
            $query->where('is_active', false);
        }

        return $query->orderBy('is_base', 'desc')
                     ->orderBy('code')
                     ->paginate(10);
    }

    protected function getShops()
    {
        return Shop::where('owner_id', Auth::id())->get();
    }

    protected function getBaseCurrency()
    {
        return Currency::where('shop_id', $this->currentShopId)
                       ->where('is_base', true)
                       ->first();
    }

    // 取得非本幣的幣別（用於卡片顯示）
    protected function getForeignCurrencies()
    {
        return Currency::where('shop_id', $this->currentShopId)
                       ->where('is_base', false)
                       ->get();
    }

    // ============================================================
    // CRUD 操作
    // ============================================================

    public function create()
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit($id)
    {
        $currency = Currency::where('shop_id', $this->currentShopId)->findOrFail($id);
        
        $this->editingId = $id;
        $this->code = $currency->code;
        $this->name = $currency->name;
        $this->symbol = $currency->symbol;
        $this->rate = (string) $currency->rate;
        $this->is_base = $currency->is_base;
        $this->is_active = $currency->is_active;
        $this->showForm = true;
    }

    public function save()
    {
        // 動態驗證規則（排除自身）
        $rules = $this->rules;
        if ($this->editingId) {
            $rules['code'] = 'required|string|size:3|alpha|unique:currencies,code,' . $this->editingId . ',id,shop_id,' . $this->currentShopId;
        }
        $this->validate($rules);

        try {
            $data = [
                'shop_id' => $this->currentShopId,
                'code' => strtoupper($this->code),
                'name' => $this->name,
                'symbol' => $this->symbol,
                'rate' => $this->rate,
                'is_base' => $this->is_base,
                'is_active' => $this->is_active,
            ];

            // 如果要設為本幣，先把其他幣別取消本幣
            if ($this->is_base) {
                Currency::where('shop_id', $this->currentShopId)
                        ->update(['is_base' => false]);
            }

            if ($this->editingId) {
                // 更新
                $currency = Currency::where('shop_id', $this->currentShopId)->findOrFail($this->editingId);
                $currency->update($data);
                session()->flash('message', '✅ 幣別已更新！');
            } else {
                // 新增
                Currency::create($data);
                session()->flash('message', '✅ 幣別已新增！');
            }

            $this->resetForm();
            $this->showForm = false;
            
        } catch (\Exception $e) {
            Log::error('儲存幣別失敗：' . $e->getMessage());
            session()->flash('error', '❌ 操作失敗：' . $e->getMessage());
        }
    }

    public function toggleActive($id)
    {
        try {
            $currency = Currency::where('shop_id', $this->currentShopId)->findOrFail($id);
            
            // 本幣不能停用
            if ($currency->is_base && $currency->is_active) {
                session()->flash('error', '❌ 本幣無法停用！');
                return;
            }

            $currency->is_active = !$currency->is_active;
            $currency->save();
            
            session()->flash('message', '✅ 幣別狀態已更新！');
            
        } catch (\Exception $e) {
            session()->flash('error', '❌ 操作失敗：' . $e->getMessage());
        }
    }

    public function delete($id)
    {
        try {
            $currency = Currency::where('shop_id', $this->currentShopId)->findOrFail($id);
            
            // 檢查是否可刪除
            if ($currency->is_base) {
                session()->flash('error', '❌ 本幣無法刪除！');
                return;
            }

            // 檢查是否被帳戶使用
            $accountCount = $currency->financialAccounts()->count();
            if ($accountCount > 0) {
                session()->flash('error', "❌ 有 {$accountCount} 個財務帳戶正在使用此幣別，無法刪除！");
                return;
            }

            $currency->delete();
            session()->flash('message', '✅ 幣別已刪除！');
            
        } catch (\Exception $e) {
            session()->flash('error', '❌ 刪除失敗：' . $e->getMessage());
        }
    }

    public function setBase($id)
    {
        try {
            $currency = Currency::where('shop_id', $this->currentShopId)->findOrFail($id);
            
            // 取消所有本幣
            Currency::where('shop_id', $this->currentShopId)
                    ->update(['is_base' => false]);
            
            // 設定新的本幣
            $currency->update(['is_base' => true, 'rate' => 1.000000]);
            
            session()->flash('message', '✅ 本幣已更新！');
            
        } catch (\Exception $e) {
            session()->flash('error', '❌ 設定失敗：' . $e->getMessage());
        }
    }

    public function cancel()
    {
        $this->resetForm();
        $this->showForm = false;
    }

    public function resetForm()
    {
        $this->reset(['editingId', 'code', 'name', 'symbol', 'rate', 'is_base', 'is_active']);
        $this->resetValidation();
    }

    public function switchShop($shopId)
    {
        $this->currentShopId = $shopId;
        session(['current_shop_id' => $shopId]);
        $this->resetPage();
        $this->resetForm();
        
        session()->flash('message', '✅ 已切換帳本');
    }
    
    public function render()
    {
        $currencies = $this->getCurrencies();
        $shops = $this->getShops();
        $baseCurrency = $this->getBaseCurrency();
        $foreignCurrencies = $this->getForeignCurrencies();

        return view('livewire.finance.currency-index', [
            'currencies' => $currencies,
            'shops' => $shops,
            'baseCurrency' => $baseCurrency,
            'foreignCurrencies' => $foreignCurrencies,
        ])->layout('components.layouts.app');
    }
}