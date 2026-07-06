<?php
// app/Models/Category.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'shop_id',
        'parent_id',
        'name',
        'type',
        'icon',
        'sort_order',
    ];

    protected $casts = [
        'shop_id' => 'integer',
        'parent_id' => 'integer',
        'sort_order' => 'integer',
		'is_active' => 'boolean',
    ];

    /**
     * 獲取父級大分類（自關聯：屬於某個大分類）
     */
    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /**
     * 獲取子級小分類（自關聯：擁有很多子分類）
     */
    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id')->orderBy('sort_order', 'asc');
    }
}