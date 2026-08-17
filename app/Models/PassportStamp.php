<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PassportStamp extends Model
{
    protected $table = 'passport_stamps';

    protected $primaryKey = 'stamp_id';

    protected $fillable = [
        'user_id',
        'shop_id',
        'stamp_datetime',
        'gps_latitude',
        'gps_longitude',
    ];

    protected $casts = [
        'stamp_datetime' => 'datetime',
        'gps_latitude' => 'float',
        'gps_longitude' => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
