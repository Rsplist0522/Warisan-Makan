<?php

namespace App\Models;

use App\Models\User;
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
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
