<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HeritageShop extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_ARCHIVED = 'archived';

    /**
     * Legacy records may still contain `approved`, but it is not a public
     * publication state. The status migration converts those records.
     */
    public const STATUS_APPROVED_LEGACY = 'approved';

    public const PUBLIC_STATUSES = [
        self::STATUS_PUBLISHED,
    ];

    public const ADMIN_STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_PUBLISHED,
        self::STATUS_ARCHIVED,
        self::STATUS_APPROVED_LEGACY,
    ];

    public function scopePublished($query)
    {
        return $query->whereIn($this->getTable().'.publish_status', self::PUBLIC_STATUSES);
    }

    public function isPubliclyVisible(): bool
    {
        return in_array($this->publish_status, self::PUBLIC_STATUSES, true);
    }

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

    public function images()
    {
        return $this->hasMany(ShopImage::class, 'shop_id')->orderBy('is_primary', 'desc')->orderBy('id');
    }

    public function foodItems()
    {
        return $this->hasMany(HeritageFoodItem::class, 'heritage_shop_id')->orderBy('display_order')->orderBy('id');
    }

    public function activeFoodItems()
    {
        return $this->foodItems()->where('is_active', true);
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
