<?php

namespace App\Livewire\Finance;

use App\Models\Partner;
use App\Models\User;
use Livewire\Component;
use Livewire\WithFileUploads;
use Mary\Traits\Toast;

class PartnerIndex extends Component
{
    use Toast, WithFileUploads;

    public string $search = '';
    public bool $partnerModal = false;

    public ?Partner $editingPartner = null;
    
    public $name;
    public $phone;
    public $role = 'member';
    public $user_id;
    public $joined_at;
    public $line_id;
    public $carrier_num;
    public $photo;
    public $photo_path;
    public bool $is_active = true;

    public function render()
    {
        $partners = Partner::with('user')
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('role', 'like', '%' . $this->search . '%');
            })
            ->get();

        $users = User::all();

        return view('livewire.finance.partner-index', [
            'partners' => $partners,
            'users' => $users,
        ])->layout('layouts.app');
    }

    public function create()
    {
        $this->resetForm();
        $this->editingPartner = null;
        $this->partnerModal = true;
    }

    public function openEdit(Partner $partner)
    {
        $this->resetForm();
        $this->editingPartner = $partner;
        
        $this->name = $partner->name;
        $this->user_id = $partner->user_id;
        $this->phone = $partner->phone;
        $this->role = $partner->role;
        $this->joined_at = $partner->joined_at?->format('Y-m-d');
        $this->is_active = $partner->is_active;
        $this->photo_path = $partner->photo_path;
        $this->line_id = $partner->contacts['line'] ?? null;
        $this->carrier_num = $partner->contacts['carrier_num'] ?? null;

        $this->partnerModal = true;
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:50',
            'user_id' => 'required|exists:users,id',
            'photo' => 'nullable|image|max:2048',
        ]);

        $data = [
            'name' => $this->name,
            'user_id' => $this->user_id,
            'phone' => $this->phone,
            'role' => $this->role,
            'joined_at' => $this->joined_at ?: null,
            'is_active' => $this->is_active,
            'contacts' => [
                'line' => $this->line_id,
                'carrier_num' => $this->carrier_num,
            ],
        ];

        if ($this->photo) {
			$path = $this->photo->store('partners', 'public');
			$data['photo_path'] = $path;
		}

        if ($this->editingPartner) {
            $this->editingPartner->update($data);
            $this->success('成員資料更新完成');
        } else {
            Partner::create($data);
            $this->success('新家庭成員建立完成');
        }

        $this->partnerModal = false;
        $this->resetForm();
    }

    public function delete(Partner $partner)
    {
        $partner->delete();
        $this->success('成員已被虛擬刪除，歷史賬本流水不受影響');
    }

    public function closeDrawer()
    {
        $this->partnerModal = false;
        $this->resetForm();
    }

    private function resetForm()
    {
        $this->reset(['editingPartner', 'name', 'user_id', 'phone', 'role', 'joined_at', 'line_id', 'carrier_num', 'photo', 'photo_path']);
        $this->is_active = true;
    }
}