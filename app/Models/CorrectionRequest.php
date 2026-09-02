<?php

namespace App\Models;

use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CorrectionRequest extends Model
{
    use HasFactory;

    public const OPERATING_HOUR_DAYS = [
        'Monday',
        'Tuesday',
        'Wednesday',
        'Thursday',
        'Friday',
        'Saturday',
        'Sunday',
    ];

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
        'created_at',
        'updated_at',
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

    public static function activeStatuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_UNDER_REVIEW,
            self::STATUS_NEEDS_INFORMATION,
        ];
    }

    public static function processedStatuses(): array
    {
        return [
            self::STATUS_APPROVED,
            self::STATUS_REJECTED,
        ];
    }

    public static function allowedFields(): array
    {
        return [
            'shop_name' => 'Shop name',
            'primary_food_category' => 'Primary food category',
            'establishment_year' => 'Establishment year',
            'founder_information' => 'Founder information',
            'current_owner_information' => 'Current owner / operator information',
            'heritage_story' => 'Heritage story',
            'operating_hours' => 'Operating hours',
            'contact_number' => 'Contact number',
            'address_location' => 'Address',
        ];
    }

    public static function structuredFieldTargets(): array
    {
        return [
            'founder_information' => [
                'founder_name' => 'Founder name',
                'founder_background' => 'Founder background',
            ],
            'current_owner_information' => [
                'current_owner_name' => 'Owner / Operator name',
                'current_owner_details' => 'Owner / Operator details',
            ],
            'address_location' => [
                'address' => 'Street address',
                'city' => 'City',
                'state' => 'State',
                'postal_code' => 'Postal code',
            ],
        ];
    }

    public static function currentPublishedValue(HeritageShop $heritageShop, string $field): string
    {
        return match ($field) {
            'founder_information' => self::labeledValues([
                'Founder name' => $heritageShop->founder_name,
                'Founder background' => $heritageShop->founder_background,
            ]),
            'current_owner_information' => self::labeledValues([
                'Owner / Operator name' => $heritageShop->current_owner_name,
                'Owner / Operator details' => $heritageShop->current_owner_details,
            ]),
            'address_location' => self::labeledValues([
                'Street address' => $heritageShop->address,
                'City' => $heritageShop->city,
                'State' => $heritageShop->state,
                'Postal code' => $heritageShop->postal_code,
            ]),
            'map_location' => self::labeledValues([
                'Address reference' => $heritageShop->location,
                'Marker position' => $heritageShop->latitude !== null && $heritageShop->longitude !== null
                    ? __('Coordinates are saved for this shop.')
                    : null,
            ]),
            'operating_hours' => self::operatingHoursDisplay(self::operatingHoursEditorValue($heritageShop), $heritageShop->operatingHoursText()),
            default => self::displayValue($heritageShop->{$field} ?? null),
        };
    }

    public static function operatingHoursEditorValue(HeritageShop $heritageShop): array
    {
        $hours = self::blankOperatingHoursEditorValue();
        $rawHours = $heritageShop->operating_hours;
        $structuredDays = [];

        if (is_array($rawHours)) {
            foreach ($rawHours as $row) {
                if (! is_array($row)) {
                    continue;
                }

                $day = self::normalizeDay($row['day'] ?? null);
                if ($day === null) {
                    continue;
                }

                $closed = filter_var($row['closed'] ?? false, FILTER_VALIDATE_BOOLEAN);
                $structuredDays[$day] = true;

                if ($closed) {
                    $hours[$day]['closed'] = true;
                    $hours[$day]['periods'] = [];
                    continue;
                }

                $open = self::normalizeTime($row['open'] ?? null);
                $close = self::normalizeTime($row['close'] ?? null);

                if ($open === null || $close === null) {
                    continue;
                }

                $hours[$day]['closed'] = false;
                $hours[$day]['periods'][] = ['open' => $open, 'close' => $close];
            }
        }

        foreach ($heritageShop->operatingHoursRows() as $row) {
            $day = self::normalizeDay($row['label'] ?? null);
            if ($day === null) {
                continue;
            }

            if (isset($structuredDays[$day])) {
                continue;
            }

            $value = trim((string) ($row['value'] ?? ''));
            if ($value === '') {
                continue;
            }

            if (strcasecmp($value, 'Closed') === 0) {
                $hours[$day]['closed'] = true;
                $hours[$day]['periods'] = [];
                continue;
            }

            $period = self::parseTimePeriod($value);
            if ($period === null || in_array($period, $hours[$day]['periods'], true)) {
                continue;
            }

            $hours[$day]['closed'] = false;
            $hours[$day]['periods'][] = $period;
        }

        return $hours;
    }

    public static function blankOperatingHoursEditorValue(): array
    {
        return collect(self::OPERATING_HOUR_DAYS)
            ->mapWithKeys(fn (string $day): array => [$day => [
                'closed' => false,
                'periods' => [],
            ]])
            ->all();
    }

    public static function operatingHoursDisplay(array $hours, ?string $fallback = null): string
    {
        $lines = [];

        foreach (self::OPERATING_HOUR_DAYS as $day) {
            $schedule = $hours[$day] ?? ['closed' => false, 'periods' => []];
            $periods = is_array($schedule['periods'] ?? null) ? $schedule['periods'] : [];
            $closed = filter_var($schedule['closed'] ?? false, FILTER_VALIDATE_BOOLEAN);

            if ($closed) {
                $lines[] = "{$day}: Closed";
                continue;
            }

            foreach ($periods as $period) {
                if (! is_array($period)) {
                    continue;
                }

                $open = self::normalizeTime($period['open'] ?? null);
                $close = self::normalizeTime($period['close'] ?? null);

                if ($open !== null && $close !== null) {
                    $lines[] = "{$day}: ".self::formatTime($open).' - '.self::formatTime($close);
                }
            }
        }

        if ($lines !== []) {
            return implode("\n", $lines);
        }

        return self::displayValue($fallback);
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

    public function fieldLabel(): string
    {
        if ($this->field_name === 'map_location') {
            return 'Map location (legacy)';
        }

        return self::allowedFields()[$this->field_name] ?? str($this->field_name)->replace('_', ' ')->title()->toString();
    }

    public function suggestedValueDisplay(): string
    {
        if ($this->field_name === 'map_location') {
            return $this->mapLocationSuggestedValueDisplay();
        }

        if ($this->field_name === 'operating_hours') {
            $values = json_decode((string) $this->suggested_value, true);

            if (is_array($values)) {
                return self::operatingHoursDisplay(self::editorValueFromRows($values));
            }
        }

        $targets = self::structuredFieldTargets()[$this->field_name] ?? null;

        if ($targets === null) {
            return self::displayValue($this->suggested_value);
        }

        $values = json_decode((string) $this->suggested_value, true);

        if (! is_array($values)) {
            return self::displayValue($this->suggested_value);
        }

        return collect($targets)
            ->filter(fn (string $label, string $field): bool => filled($values[$field] ?? null))
            ->map(fn (string $label, string $field): string => $label.': '.self::displayValue($values[$field] ?? null))
            ->values()
            ->join("\n") ?: __('Not provided');
    }

    public function canReceiveAdditionalInformationFrom(User $user): bool
    {
        return (int) $this->user_id === (int) $user->id
            && $this->status === self::STATUS_NEEDS_INFORMATION;
    }

    public function heritageShopUpdatePayload(): array
    {
        $value = trim(strip_tags($this->suggested_value));

        if (array_key_exists($this->field_name, self::structuredFieldTargets())) {
            $values = json_decode((string) $this->suggested_value, true);

            if (! is_array($values)) {
                return [];
            }

            return collect(self::structuredFieldTargets()[$this->field_name])
                ->keys()
                ->filter(fn (string $field): bool => filled($values[$field] ?? null))
                ->mapWithKeys(fn (string $field): array => [$field => trim(strip_tags((string) $values[$field]))])
                ->all();
        }

        $payload = match ($this->field_name) {
            'shop_name' => ['shop_name' => $value],
            'primary_food_category' => ['primary_food_category' => $value],
            'establishment_year' => ['establishment_year' => is_numeric($value) ? (int) $value : null],
            'heritage_story' => ['heritage_story' => $value],
            'operating_hours' => ['operating_hours' => $this->operatingHoursUpdateValue()],
            'contact_number' => ['contact_number' => $value],
            'map_location' => $this->mapLocationUpdatePayload(),
            'food_items', 'latitude', 'longitude' => [],
            'founder_name',
            'founder_background',
            'current_owner_name',
            'current_owner_details',
            'address',
            'city',
            'state',
            'postal_code' => [$this->field_name => $value],
            default => [],
        };

        return $payload;
    }

    private function mapLocationSuggestedValueDisplay(): string
    {
        $values = json_decode((string) $this->suggested_value, true);

        if (! is_array($values)) {
            return self::displayValue($this->suggested_value);
        }

        $display = [];

        if (filled($values['search_query'] ?? null)) {
            $display['Search address / landmark'] = $values['search_query'];
        }

        if (filled($values['location_note'] ?? null)) {
            $display['Location note'] = $values['location_note'];
        }

        if (self::validLatitude($values['latitude'] ?? null) && self::validLongitude($values['longitude'] ?? null)) {
            $display['Proposed marker'] = __('Coordinates selected on the map.');
        }

        return $display === []
            ? __('Not provided')
            : self::labeledValues($display);
    }

    private function mapLocationUpdatePayload(): array
    {
        $values = json_decode((string) $this->suggested_value, true);

        if (! is_array($values)) {
            return [];
        }

        $latitude = $values['latitude'] ?? null;
        $longitude = $values['longitude'] ?? null;

        if (! self::validLatitude($latitude) || ! self::validLongitude($longitude)) {
            return [];
        }

        return [
            'latitude' => (float) $latitude,
            'longitude' => (float) $longitude,
        ];
    }

    private function operatingHoursUpdateValue(): array
    {
        $values = json_decode((string) $this->suggested_value, true);

        if (! is_array($values)) {
            return [];
        }

        return collect($values)
            ->filter(fn ($row): bool => is_array($row) && self::normalizeDay($row['day'] ?? null) !== null)
            ->map(function (array $row): array {
                $closed = filter_var($row['closed'] ?? false, FILTER_VALIDATE_BOOLEAN);

                return [
                    'day' => self::normalizeDay($row['day'] ?? null),
                    'open' => $closed ? null : self::normalizeTime($row['open'] ?? null),
                    'close' => $closed ? null : self::normalizeTime($row['close'] ?? null),
                    'closed' => $closed,
                ];
            })
            ->filter(fn (array $row): bool => $row['closed'] || ($row['open'] !== null && $row['close'] !== null))
            ->values()
            ->all();
    }

    private static function editorValueFromRows(array $rows): array
    {
        $hours = self::blankOperatingHoursEditorValue();

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $day = self::normalizeDay($row['day'] ?? null);
            if ($day === null) {
                continue;
            }

            $closed = filter_var($row['closed'] ?? false, FILTER_VALIDATE_BOOLEAN);
            if ($closed) {
                $hours[$day]['closed'] = true;
                $hours[$day]['periods'] = [];
                continue;
            }

            $open = self::normalizeTime($row['open'] ?? null);
            $close = self::normalizeTime($row['close'] ?? null);

            if ($open === null || $close === null) {
                continue;
            }

            $hours[$day]['closed'] = false;
            $hours[$day]['periods'][] = ['open' => $open, 'close' => $close];
        }

        return $hours;
    }

    private static function labeledValues(array $values): string
    {
        return collect($values)
            ->map(fn ($value, string $label): string => $label.":\n".self::displayValue($value))
            ->values()
            ->join("\n\n");
    }

    private static function displayValue(mixed $value): string
    {
        if (is_array($value)) {
            if ($value === []) {
                return __('Not provided');
            }

            $text = collect($value)
                ->map(function ($item): string {
                    if (is_array($item)) {
                        return collect($item)
                            ->filter(fn ($value): bool => filled($value))
                            ->map(fn ($value, $key): string => str($key)->replace('_', ' ')->title().': '.$value)
                            ->join(', ');
                    }

                    return is_scalar($item) ? trim((string) $item) : '';
                })
                ->filter()
                ->join("\n");

            return filled($text) ? $text : __('Not provided');
        }

        if ($value === null || (is_string($value) && trim($value) === '')) {
            return __('Not provided');
        }

        return is_scalar($value) ? trim((string) $value) : __('Not provided');
    }

    private static function validLatitude(mixed $value): bool
    {
        return is_numeric($value) && (float) $value >= -90 && (float) $value <= 90;
    }

    private static function validLongitude(mixed $value): bool
    {
        return is_numeric($value) && (float) $value >= -180 && (float) $value <= 180;
    }

    private static function normalizeDay(mixed $day): ?string
    {
        if (! is_string($day) || trim($day) === '') {
            return null;
        }

        $normalized = strtolower(trim($day));
        $aliases = [
            'mon' => 'Monday',
            'monday' => 'Monday',
            'tue' => 'Tuesday',
            'tues' => 'Tuesday',
            'tuesday' => 'Tuesday',
            'wed' => 'Wednesday',
            'wednesday' => 'Wednesday',
            'thu' => 'Thursday',
            'thur' => 'Thursday',
            'thurs' => 'Thursday',
            'thursday' => 'Thursday',
            'fri' => 'Friday',
            'friday' => 'Friday',
            'sat' => 'Saturday',
            'saturday' => 'Saturday',
            'sun' => 'Sunday',
            'sunday' => 'Sunday',
        ];

        return $aliases[$normalized] ?? null;
    }

    private static function normalizeTime(mixed $time): ?string
    {
        if (! is_string($time) && ! is_numeric($time)) {
            return null;
        }

        $time = trim((string) $time);
        if ($time === '') {
            return null;
        }

        if (preg_match('/\A([01]?\d|2[0-3]):([0-5]\d)\z/', $time, $matches) === 1) {
            return str_pad($matches[1], 2, '0', STR_PAD_LEFT).':'.$matches[2];
        }

        if (preg_match('/\A(1[0-2]|0?\d)(?::([0-5]\d))?\s*(am|pm)\z/i', $time, $matches) === 1) {
            $hour = (int) $matches[1];
            $minute = $matches[2] ?? '00';
            $meridiem = strtolower($matches[3]);

            if ($meridiem === 'am' && $hour === 12) {
                $hour = 0;
            } elseif ($meridiem === 'pm' && $hour !== 12) {
                $hour += 12;
            }

            return str_pad((string) $hour, 2, '0', STR_PAD_LEFT).':'.$minute;
        }

        return null;
    }

    private static function parseTimePeriod(string $value): ?array
    {
        $pattern = '/\b(2[0-3]|1[0-9]|0?\d)(?::([0-5]\d))?\s*(am|pm)?\s*(?:-|–|—|to)\s*(2[0-3]|1[0-9]|0?\d)(?::([0-5]\d))?\s*(am|pm)?\b/i';

        if (preg_match($pattern, $value, $matches) !== 1) {
            return null;
        }

        $openSuffix = ($matches[3] ?? '') ?: ($matches[6] ?? '');
        $closeSuffix = ($matches[6] ?? '') ?: $openSuffix;
        $open = self::normalizeTime($matches[1].':'.(($matches[2] ?? '') ?: '00').$openSuffix);
        $close = self::normalizeTime($matches[4].':'.(($matches[5] ?? '') ?: '00').$closeSuffix);

        if ($open === null || $close === null) {
            return null;
        }

        return ['open' => $open, 'close' => $close];
    }

    private static function formatTime(string $time): string
    {
        [$hour, $minute] = array_map('intval', explode(':', $time));
        $suffix = $hour >= 12 ? 'PM' : 'AM';
        $displayHour = $hour % 12;
        $displayHour = $displayHour === 0 ? 12 : $displayHour;

        return sprintf('%02d:%02d %s', $displayHour, $minute, $suffix);
    }
}
