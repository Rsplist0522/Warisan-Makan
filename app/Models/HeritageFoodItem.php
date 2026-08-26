<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HeritageFoodItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'heritage_food_items';

    protected $fillable = [
        'heritage_shop_id',
        'name',
        'description',
        'category',
        'heritage_significance',
        'availability',
        'price',
        'image_path',
        'is_active',
        'display_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'display_order' => 'integer',
    ];

    public function shop()
    {
        return $this->belongsTo(HeritageShop::class, 'heritage_shop_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('display_order')->orderBy('id');
    }

    public function isPubliclyVisible(): bool
    {
        return $this->is_active && $this->shop?->isPubliclyVisible() === true;
    }
}
