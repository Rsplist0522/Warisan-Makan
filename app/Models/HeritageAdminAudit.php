<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HeritageAdminAudit extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'entity_type',
        'entity_id',
        'action',
        'old_values',
        'new_values',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];
}
