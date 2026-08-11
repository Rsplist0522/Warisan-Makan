<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HeritageShopContribution extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PENDING_REVIEW = 'pending_review';

    public const STATUS_UNDER_REVIEW = 'under_review';

    public const STATUS_REVISION_REQUIRED = 'revision_required';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_WITHDRAWN = 'withdrawn';

    public const STATUS_DELETED = 'deleted';

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
        'reviewed_by_user_id',
        'review_started_at',
        'admin_feedback',
        'resubmitted_at',
        'withdrawn_at',
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
            'review_started_at' => 'datetime',
            'resubmitted_at' => 'datetime',
            'withdrawn_at' => 'datetime',
            'approved_at' => 'datetime',
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

    public function reviewedBy()
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    public function versions()
    {
        return $this->hasMany(ContributionVersion::class)->orderByDesc('version_number');
    }

    public function moderationActivities()
    {
        return $this->hasMany(ModerationActivity::class)->latest();
    }

    public function canBeEditedBy(User $user): bool
    {
        return (int) $this->user_id === (int) $user->id
            && in_array($this->status, [self::STATUS_DRAFT, self::STATUS_REVISION_REQUIRED], true);
    }

    public function canBeWithdrawnBy(User $user): bool
    {
        return (int) $this->user_id === (int) $user->id
            && $this->status === self::STATUS_PENDING_REVIEW
            && $this->review_started_at === null;
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_PENDING_REVIEW => 'Pending Review',
            self::STATUS_UNDER_REVIEW => 'Under Review',
            self::STATUS_REVISION_REQUIRED => 'Revision Required',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_REJECTED => 'Rejected',
            self::STATUS_WITHDRAWN => 'Withdrawn',
            self::STATUS_DELETED => 'Deleted',
            default => str($this->status)->replace('_', ' ')->title()->toString(),
        };
    }

    public function recordVersion(?User $user, string $reason): ContributionVersion
    {
        $nextVersion = ((int) $this->versions()->max('version_number')) + 1;

        return $this->versions()->create([
            'user_id' => $user?->id,
            'version_number' => $nextVersion,
            'reason' => $reason,
            'snapshot' => $this->only([
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
            ]),
        ]);
    }

    public function approve(?User $admin = null): HeritageShop
    {
        $this->forceFill([
            'status' => self::STATUS_APPROVED,
            'approved_by_user_id' => $admin?->id,
            'approved_at' => now(),
            'rejection_reason' => null,
        ])->save();

        return HeritageShop::updateOrCreate(['source_contribution_id' => $this->id], [
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
