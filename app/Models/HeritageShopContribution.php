<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class HeritageShopContribution extends Model
{
    use HasFactory, SoftDeletes;

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
        'submission_token',
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

    protected static function booted(): void
    {
        static::creating(function (HeritageShopContribution $contribution): void {
            if (blank($contribution->public_id)) {
                $contribution->public_id = (string) Str::uuid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    protected function casts(): array
    {
        return [
            'operating_hours' => 'array',
            'food_items' => 'array',
            'submitted_at' => 'datetime',
            'review_started_at' => 'datetime',
            'resubmitted_at' => 'datetime',
            'withdrawn_at' => 'datetime',
            'approved_at' => 'datetime',
            'deleted_at' => 'datetime',
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

    public function media()
    {
        return $this->morphMany(Media::class, 'attachable')->orderBy('display_order');
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
            self::STATUS_DRAFT => __('Draft'),
            self::STATUS_PENDING_REVIEW => __('Pending Review'),
            self::STATUS_UNDER_REVIEW => __('Under Review'),
            self::STATUS_REVISION_REQUIRED => __('Revision Required'),
            self::STATUS_APPROVED => __('Approved'),
            self::STATUS_REJECTED => __('Rejected'),
            self::STATUS_WITHDRAWN => __('Withdrawn'),
            self::STATUS_DELETED => __('Deleted'),
            default => str($this->status)->replace('_', ' ')->title()->toString(),
        };
    }

    public function formatDateTime(?DateTimeInterface $date, string $fallback = 'Not available'): string
    {
        if ($date === null) {
            return $fallback;
        }

        $localDate = $date instanceof Carbon
            ? $date->copy()
            : Carbon::instance($date);

        return $localDate
            ->timezone(config('app.display_timezone', 'Asia/Kuala_Lumpur'))
            ->format('d M Y, g:i A');
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

        $shop = HeritageShop::updateOrCreate(['source_contribution_id' => $this->id], [
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
            'publish_status' => HeritageShop::STATUS_PUBLISHED,
        ]);

        $existingKeys = $shop->media()->pluck('r2_object_key')->all();
        $this->media()->get()->each(function (Media $media) use ($shop, $existingKeys): void {
            if (in_array($media->r2_object_key, $existingKeys, true)) {
                return;
            }

            $shop->media()->create([
                'uploaded_by_user_id' => $media->uploaded_by_user_id,
                'media_type' => $media->media_type,
                'r2_object_key' => $media->r2_object_key,
                'original_name' => $media->original_name,
                'mime_type' => $media->mime_type,
                'file_size_bytes' => $media->file_size_bytes,
                'caption' => $media->caption,
                'display_order' => $media->display_order,
                'is_primary' => $media->is_primary,
            ]);
        });

        return $shop;
    }
}
