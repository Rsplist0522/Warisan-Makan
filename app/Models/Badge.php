<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Badge extends Model
{
    protected $table = 'badges';

    protected $primaryKey = 'badge_id';

    protected $fillable = [
        'badge_name',
        'description',
        'icon',
        'criteria_type',
        'criteria_value',
        'points',
        'is_active',
    ];

    public function userBadges()
    {
        return $this->hasMany(UserBadge::class, 'badge_id', 'badge_id');
    }
}
