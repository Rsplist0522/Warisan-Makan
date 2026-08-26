<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BlindBoxDraw extends Model
{
    use HasFactory;

    protected $table = 'blind_box_draws';

    protected $fillable = [
        'user_id',
        'period',
        'shop_name',
        'category',
        'state',
        'year',
        'description',
        'image',
        'halal',
    ];

    protected $casts = [
        'halal' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Latest draws for a given (date-keyed) period, e.g. "2026-08-19_evening".
     */
    public function scopeForPeriod($query, string $period)
    {
        return $query->where('period', $period);
    }

    /**
     * Whether the user has already drawn in the given period.
     */
    public function scopeAlreadyDrew($query, int $userId, string $period)
    {
        return $query->where('user_id', $userId)->where('period', $period);
    }
}
