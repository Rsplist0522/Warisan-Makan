<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContributionVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'heritage_shop_contribution_id',
        'user_id',
        'version_number',
        'reason',
        'snapshot',
    ];

    protected function casts(): array
    {
        return [
            'snapshot' => 'array',
            'version_number' => 'integer',
        ];
    }

    public function contribution()
    {
        return $this->belongsTo(HeritageShopContribution::class, 'heritage_shop_contribution_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
