<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ShopImage extends Model
{
    use HasFactory, SoftDeletes;

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
