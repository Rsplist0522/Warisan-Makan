<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CorrectionRequest extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_UNDER_REVIEW = 'under_review';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_NEEDS_INFORMATION = 'needs_information';

    protected $fillable = [
        'user_id',
        'heritage_shop_id',
        'field_name',
        'current_value',
        'suggested_value',
        'reason',
        'status',
        'admin_comment',
        'additional_information',
        'reviewed_by_user_id',
        'review_started_at',
        'reviewed_at',
    ];

    protected static function booted(): void
    {
        static::creating(function (CorrectionRequest $correctionRequest): void {
            if (blank($correctionRequest->public_id)) {
                $correctionRequest->public_id = (string) Str::uuid();
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
            'review_started_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_UNDER_REVIEW,
            self::STATUS_APPROVED,
            self::STATUS_REJECTED,
            self::STATUS_NEEDS_INFORMATION,
        ];
    }

    public static function allowedFields(): array
    {
        return [
            'shop_name' => 'Shop name',
            'primary_food_category' => 'Primary food category',
            'establishment_year' => 'Establishment year',
            'founder_name' => 'Founder name',
            'founder_background' => 'Founder background',
            'current_owner_name' => 'Current owner name',
            'current_owner_details' => 'Current owner details',
            'heritage_story' => 'Heritage story',
            'operating_hours' => 'Operating hours',
            'food_items' => 'Food items',
            'contact_number' => 'Contact number',
            'address' => 'Address',
            'city' => 'City',
            'state' => 'State',
            'postal_code' => 'Postal code',
            'latitude' => 'Latitude',
            'longitude' => 'Longitude',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function heritageShop()
    {
        return $this->belongsTo(HeritageShop::class);
    }

    public function reviewedBy()
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    public function moderationActivities()
    {
        return $this->hasMany(ModerationActivity::class)->latest();
    }

    public function media()
    {
        return $this->morphMany(Media::class, 'attachable')->orderBy('display_order');
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => __('Pending'),
            self::STATUS_UNDER_REVIEW => __('Under Review'),
            self::STATUS_APPROVED => __('Approved'),
            self::STATUS_REJECTED => __('Rejected'),
            self::STATUS_NEEDS_INFORMATION => __('Needs Information'),
            default => str($this->status)->replace('_', ' ')->title()->toString(),
        };
    }

    public function fieldLabel(): string
    {
        return self::allowedFields()[$this->field_name] ?? str($this->field_name)->replace('_', ' ')->title()->toString();
    }

    public function canReceiveAdditionalInformationFrom(User $user): bool
    {
        return (int) $this->user_id === (int) $user->id
            && $this->status === self::STATUS_NEEDS_INFORMATION;
    }

    public function heritageShopUpdatePayload(): array
    {
        $value = trim(strip_tags($this->suggested_value));

        $payload = match ($this->field_name) {
            'shop_name' => ['shop_name' => $value],
            'primary_food_category' => ['primary_food_category' => $value],
            'establishment_year' => ['establishment_year' => is_numeric($value) ? (int) $value : null],
            'founder_name' => ['founder_name' => $value],
            'heritage_story' => ['heritage_story' => $value],
            'latitude', 'longitude' => [$this->field_name => is_numeric($value) ? (float) $value : null],
            default => [$this->field_name => $value],
        };

        return $payload;
    }
}
