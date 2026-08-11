<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ModerationActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'heritage_shop_contribution_id',
        'actor_user_id',
        'action',
        'from_status',
        'to_status',
        'comment',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function contribution()
    {
        return $this->belongsTo(HeritageShopContribution::class, 'heritage_shop_contribution_id');
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
