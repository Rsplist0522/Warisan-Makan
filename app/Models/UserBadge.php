<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserBadge extends Model
{
    protected $table = 'user_badges';

    protected $primaryKey = 'user_badge_id';

    protected $fillable = [
        'user_id',
        'badge_id',
        'earned_at',
    ];

    protected $casts = [
        'earned_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function badge()
    {
        return $this->belongsTo(Badge::class, 'badge_id', 'badge_id');
    }
}
