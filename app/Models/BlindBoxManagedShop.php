<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BlindBoxManagedShop extends Model
{
    use HasFactory;

    protected $table = 'blind_box_managed_shops';

    protected $fillable = [
        'source_key',
        'shop_name',
        'category',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
