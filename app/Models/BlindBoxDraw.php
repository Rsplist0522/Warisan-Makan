<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlindBoxDraw extends Model
{
    protected $fillable = [
        'user_id',
        'period',
        'period_date',
        'shop_name',
        'category',
        'state',
        'year',
        'description',
        'image',
        'address',
        'shop_source_id',
        'drawn_at',
    ];

    protected $casts = [
        'period_date' => 'date',
        'drawn_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Shape this record the same way the old session-based $drawData /
     * $currentDraw array looked, so the blade view and JS don't need to
     * change at all.
     */
    public function toDrawArray(): array
    {
        return [
            'draw_id' => $this->id,
            'period' => $this->period,
            'name' => $this->shop_name,
            'shop_name' => $this->shop_name,
            'category' => $this->category,
            'state' => $this->state,
            'year' => $this->year,
            'description' => $this->description,
            'image' => $this->image,
            'address' => $this->address,
            'id' => $this->shop_source_id,
            'drawn_at' => optional($this->drawn_at)->toDateTimeString(),
        ];
    }
}
