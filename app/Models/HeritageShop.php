<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

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

    /**
     * Normalize legacy raw strings and structured schedule arrays into rows
     * suitable for a public profile or an admin form. This keeps the JSON
     * storage shape intact while preventing raw JSON from leaking into UI.
     *
     * @return array<int, array{label: ?string, value: string}>
     */
    public function operatingHoursRows(): array
    {
        $hours = $this->normalizeOperatingHoursValue($this->operating_hours);

        if (is_string($hours)) {
            return $this->parseOperatingHoursText($hours);
        }

        if (! is_array($hours)) {
            return [];
        }

        if (isset($hours['raw']) && is_scalar($hours['raw'])) {
            return $this->parseOperatingHoursText((string) $hours['raw']);
        }

        $rows = [];
        foreach ($hours as $key => $value) {
            if (is_array($value)) {
                $label = filled($value['day'] ?? null)
                    ? ucwords((string) $value['day'])
                    : (! is_numeric($key) ? ucwords(str_replace(['_', '-'], ' ', (string) $key)) : null);
                $closed = filter_var($value['closed'] ?? false, FILTER_VALIDATE_BOOLEAN);
                $timeRange = $closed
                    ? 'Closed'
                    : implode('–', array_filter([
                        filled($value['open'] ?? null) ? trim((string) $value['open']) : null,
                        filled($value['close'] ?? null) ? trim((string) $value['close']) : null,
                    ]));
                $timeRange = $timeRange !== '' ? $timeRange : trim((string) ($value['hours'] ?? $value['value'] ?? ''));

                if ($timeRange !== '') {
                    $rows[] = ['label' => $label, 'value' => $timeRange];
                }

                continue;
            }

            if (! is_scalar($value) || trim((string) $value) === '' || $key === 'raw') {
                continue;
            }

            $text = trim((string) $value);
            if (is_numeric($key)) {
                foreach ($this->parseOperatingHoursText($text) as $parsedRow) {
                    $rows[] = $parsedRow;
                }
                continue;
            }

            $label = ucwords(str_replace(['_', '-'], ' ', (string) $key));
            $rows[] = ['label' => $label, 'value' => $text];
        }

        return $rows;
    }

    public function operatingHoursText(): string
    {
        return implode("\n", array_map(
            static fn (array $row): string => $row['label'] ? $row['label'].': '.$row['value'] : $row['value'],
            $this->operatingHoursRows(),
        ));
    }

    public function operatingHoursSummary(): string
    {
        return implode('; ', array_map(
            static fn (array $row): string => $row['label'] ? $row['label'].': '.$row['value'] : $row['value'],
            $this->operatingHoursRows(),
        ));
    }

    private function normalizeOperatingHoursValue(mixed $hours, int $depth = 0): mixed
    {
        if ($depth > 5) {
            return $hours;
        }

        if ($hours instanceof Collection) {
            $hours = $hours->all();
        }

        if (is_string($hours)) {
            $decoded = json_decode($hours, true);

            if (json_last_error() === JSON_ERROR_NONE && (is_array($decoded) || is_string($decoded))) {
                return $this->normalizeOperatingHoursValue($decoded, $depth + 1);
            }

            return $hours;
        }

        if (is_array($hours)
            && count($hours) === 1
            && array_key_exists('raw', $hours)
            && (is_scalar($hours['raw']) || is_array($hours['raw']))) {
            return $this->normalizeOperatingHoursValue($hours['raw'], $depth + 1);
        }

        return $hours;
    }

    /**
     * @return array<int, array{label: ?string, value: string}>
     */
    private function parseOperatingHoursText(string $hours): array
    {
        $parts = preg_split('/\\s*(?:;|\\r\\n|\\n|\\r)\\s*/u', trim($hours), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return array_values(array_filter(array_map(function (string $part): array {
            $part = trim(preg_replace('/\\s+/u', ' ', $part) ?? $part);
            if (preg_match('/^(Monday|Tuesday|Wednesday|Thursday|Friday|Saturday|Sunday|Mon|Tue|Wed|Thu|Fri|Sat|Sun)\\s*[:,-]?\\s*(.+)$/iu', $part, $matches)) {
                return [
                    'label' => ucfirst(strtolower($matches[1])),
                    'value' => trim($matches[2]),
                ];
            }

            return ['label' => null, 'value' => $part];
        }, $parts), static fn (array $row): bool => $row['value'] !== ''));
    }
}
