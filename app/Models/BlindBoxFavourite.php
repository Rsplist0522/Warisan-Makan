<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlindBoxFavourite extends Model
{
    protected $fillable = [
        'user_id', 'blind_box_draw_id', 'shop_source_id', 'shop_name', 'category',
        'state', 'year', 'description', 'image', 'address', 'removed_at',
    ];

    protected $casts = ['removed_at' => 'datetime'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function draw(): BelongsTo
    {
        return $this->belongsTo(BlindBoxDraw::class, 'blind_box_draw_id');
    }
}
