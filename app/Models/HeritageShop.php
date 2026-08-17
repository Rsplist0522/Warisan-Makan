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
        'publish_status',
        'slug',
        'country',
        'participating_since',
        'highlight',
        'source_url',
    ];

    protected $casts = [
        'operating_hours' => 'array',
        'food_items' => 'array',
        'establishment_year' => 'integer',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    public function sourceContribution()
    {
        return $this->belongsTo(HeritageShopContribution::class, 'source_contribution_id');
    }

    public function correctionRequests()
    {
        return $this->hasMany(CorrectionRequest::class);
    }

    public function media()
    {
        return $this->morphMany(Media::class, 'attachable')->orderBy('display_order');
    }

    public function primaryMedia()
    {
        return $this->morphOne(Media::class, 'attachable')->where('is_primary', true);
    }

    public function getLocationAttribute(?string $value): ?string
    {
        return trim(implode(', ', array_filter([
            $this->address,
            $this->city,
            $this->state,
        ])));
    }

    public function getOperatingHoursAttribute($value)
    {
        if (is_array($value)) {
            return implode('; ', array_filter($value));
        }

        return $value;
    }
}
