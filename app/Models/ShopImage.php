<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;

class ShopImage extends Model
{
    use HasFactory, SoftDeletes;

    protected static function booted(): void
    {
        static::creating(function (ShopImage $image): void {
            $maximum = max(1, (int) config('heritage_shop.max_gallery_images', 10));
            $existingCount = static::query()->where('shop_id', $image->shop_id)->count();

            if ($existingCount >= $maximum) {
                throw ValidationException::withMessages([
                    'images' => "This shop can contain a maximum of {$maximum} gallery images.",
                ]);
            }

            if ($existingCount === 0) {
                $image->is_primary = true;
            }
        });

        static::saved(function (ShopImage $image): void {
            if ($image->is_primary) {
                static::query()
                    ->where('shop_id', $image->shop_id)
                    ->whereKeyNot($image->getKey())
                    ->update(['is_primary' => false]);
            }
        });

        static::deleted(function (ShopImage $image): void {
            if ($image->is_primary) {
                static::query()->where('shop_id', $image->shop_id)->orderBy('id')->first()?->update(['is_primary' => true]);
            }
        });
    }

    protected $fillable = [
        'shop_id',
        'path',
        'is_primary',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    public function shop()
    {
        return $this->belongsTo(HeritageShop::class);
    }
}
