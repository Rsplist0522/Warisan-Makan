<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HeritageShopContribution extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PENDING_REVIEW = 'pending_review';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'user_id',
        'contribution_title',
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
        'status',
        'submitted_at',
        'approved_by_user_id',
        'approved_at',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'operating_hours' => 'array',
            'food_items' => 'array',
            'supporting_media' => 'array',
            'submitted_at' => 'datetime',
            'establishment_year' => 'integer',
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    public function approve(?User $admin = null): HeritageShop
    {
        $this->forceFill([
            'status' => self::STATUS_APPROVED,
            'approved_by_user_id' => $admin?->id,
            'approved_at' => now(),
            'rejection_reason' => null,
        ])->save();

        return HeritageShop::create([
            'source_contribution_id' => $this->id,
            'shop_name' => $this->shop_name,
            'primary_food_category' => $this->primary_food_category,
            'establishment_year' => $this->establishment_year,
            'founder_name' => $this->founder_name,
            'founder_background' => $this->founder_background,
            'current_owner_name' => $this->current_owner_name,
            'current_owner_details' => $this->current_owner_details,
            'heritage_story' => $this->heritage_story,
            'operating_hours' => $this->operating_hours,
            'food_items' => $this->food_items,
            'contact_number' => $this->contact_number,
            'address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'postal_code' => $this->postal_code,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'supporting_media' => $this->supporting_media,
            'publish_status' => 'draft',
        ]);
    }
}