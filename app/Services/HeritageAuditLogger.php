<?php

namespace App\Services;

use App\Models\HeritageAdminAudit;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Throwable;

class HeritageAuditLogger
{
    private const SAFE_KEYS = [
        'shop_name',
        'primary_food_category',
        'establishment_year',
        'founder_name',
        'current_owner_name',
        'contact_number',
        'address',
        'city',
        'state',
        'postal_code',
        'latitude',
        'longitude',
        'source_url',
        'publish_status',
        'name',
        'category',
        'availability',
        'price',
        'is_active',
        'display_order',
    ];

    /**
     * Audit logging is deliberately non-fatal: a metadata failure must not
     * undo a successfully committed administrator operation.
     */
    public function record(User $administrator, Model $entity, string $action, array $oldValues = [], array $newValues = []): void
    {
        try {
            HeritageAdminAudit::query()->create([
                'user_id' => $administrator->getKey(),
                'entity_type' => $entity::class,
                'entity_id' => $entity->getKey(),
                'action' => $action,
                'old_values' => $this->safeValues($oldValues),
                'new_values' => $this->safeValues($newValues),
            ]);
        } catch (Throwable $exception) {
            Log::warning('Heritage administrator audit could not be recorded.', [
                'action' => $action,
                'entity_type' => $entity::class,
                'entity_id' => $entity->getKey(),
                'administrator_id' => $administrator->getKey(),
                'exception' => $exception::class,
            ]);
        }
    }

    private function safeValues(array $values): array
    {
        return collect($values)->only(self::SAFE_KEYS)->map(function (mixed $value): mixed {
            if (is_string($value)) {
                return mb_substr($value, 0, 2000);
            }

            return is_scalar($value) || $value === null ? $value : null;
        })->all();
    }
}
