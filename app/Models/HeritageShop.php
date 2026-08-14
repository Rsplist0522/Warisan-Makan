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
        'location',
        'country',
        'category',
        'description',
        'participating_since',
        'highlight',
        'source_url',
    ];

    protected $casts = [
        'operating_hours' => 'array',
        'food_items' => 'array',
        'supporting_media' => 'array',
        'establishment_year' => 'integer',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    public function sourceContribution()
    {
        return $this->belongsTo(HeritageShopContribution::class, 'source_contribution_id');
    }

    public function getNameAttribute(?string $value): ?string
    {
        return $value ?: $this->shop_name;
    }

    public function getCategoryAttribute(?string $value): ?string
    {
        return $value ?: $this->primary_food_category;
    }

    public function getFounderAttribute(?string $value): ?string
    {
        return $value ?: $this->founder_name;
    }

    public function getDescriptionAttribute(?string $value): ?string
    {
        return $value ?: $this->heritage_story;
    }

    public function getLocationAttribute(?string $value): ?string
    {
        if (!empty($value)) {
            return $value;
        }

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
