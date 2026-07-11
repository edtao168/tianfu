<?php // [個人記帳系統] app/Models/Partner.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Partner extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'photo_path',
        'phone',
        'role',
		'contacts',
		'joined_at',
        'is_active',
    ];

    protected $casts = [
        'contacts' => 'array',
		'joined_at' => 'date',
		'is_active' => 'boolean',
        'deleted_at' => 'datetime',
    ];

    /**
     * 反向關聯到登入帳號 (User)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 💡 輔助方法：獲取成員頭像，若未上傳則自動退回系統預設頭像
     */
    public function getAvatarUrl(): string
    {
        if ($this->avatar_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($this->avatar_path)) {
            return \Illuminate\Support\Facades\Storage::url($this->avatar_path);
        }
        
        // 如果沒有上傳專屬圖片，預設先吃你根目錄的 me.jpg 
        return asset('me.jpg');
    }
	
	/**
     * 💡 厚 Model 擴充：安全獲取特定的家庭聯絡資訊，避免 undefined index 報錯
     */
    public function getContact(string $key, ?string $default = null): ?string
    {
        return $this->contacts[$key] ?? $default;
    }

    /**
     * 💡 獲取偏好的通知管道 (例如預設回傳 Line)
     */
    public function getPreferredNotificationChannel(): string
    {
        return $this->getContact('notify_channel', 'line');
    }
}