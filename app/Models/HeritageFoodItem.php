<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HeritageFoodItem extends Model
{
    use HasFactory, SoftDeletes;

    protected static function booted(): void
    {
        static::saving(function (HeritageFoodItem $item): void {
            $item->normalized_name = self::normalizeName($item->name);
        });

        static::deleting(function (HeritageFoodItem $item): void {
            if (! $item->isForceDeleting()) {
                $item->forceFill([
                    'normalized_name' => '__deleted__'.$item->getKey().'_'.hash('sha256', self::normalizeName($item->name)),
                ])->saveQuietly();
            }
        });
    }

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

    public static function normalizeName(mixed $name): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/u', ' ', (string) $name) ?? (string) $name));
    }

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
