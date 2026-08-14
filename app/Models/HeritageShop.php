<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HeritageShop extends Model
{
    use HasFactory;

    protected $table = 'heritage_shops';

    protected $fillable = [
        'source_contribution_id',
        'shop_name',
        'primary_food_category',
        'establishment_year',
        'founder_name',
        'founder_background',
        'current_owner_name',
        'current_owner_details',
        'heritage_story',
        'operating_hours',
        'food_items',
        'contact_number',
        'address',
        'city',
        'state',
        'postal_code',
        'latitude',
        'longitude',
        'supporting_media',
        'publish_status',
    ];

    protected function casts(): array
    {
        return [
            'operating_hours' => 'array',
            'food_items' => 'array',
            'supporting_media' => 'array',
            'establishment_year' => 'integer',
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    public function sourceContribution()
    {
        return $this->belongsTo(HeritageShopContribution::class, 'source_contribution_id');
    }
}
