<?php

namespace App\Services;

use App\Models\BlindBoxManagedShop;
use Illuminate\Support\Facades\Cache;

class BlindBoxCatalogManager
{
    /**
     * Retained as the cache namespace used by callers that invalidate the
     * catalogue between requests. The catalogue is currently database-backed.
     */
    public const CACHE_KEY = 'blind_box_catalog';

    public function __construct(private HeritageShopCatalog $catalog) {}

    public function sourceShops(): array
    {
        return collect($this->catalog->all())
            ->values()
            ->map(fn (array $shop, int $index): array => array_merge($shop, [
                // This ID identifies an item within the selectable source
                // catalogue. It must exist even for sample records, which
                // are not backed by a HeritageShop database row.
                'source_id' => $index + 1,
                // Preserve the actual HeritageShop key separately for a
                // Blind Box draw's public detail link.
                'heritage_shop_id' => $shop['source_id'] ?? null,
                'stable_key' => strtolower((string) ($shop['name'] ?? '')),
            ]))
            ->all();
    }

    public function managedShops(): array
    {
        $sourceShops = $this->sourceShops();
        $sourceByKey = collect($sourceShops)->keyBy('stable_key');
        $managed = BlindBoxManagedShop::all();

        if ($managed->isEmpty() && ! Cache::has(self::CACHE_KEY) && isset($sourceShops[0])) {
            Cache::forget(self::CACHE_KEY.'.removed');
            $starter = $sourceShops[0];
            BlindBoxManagedShop::create([
                'source_key' => $starter['stable_key'],
                'shop_name' => $starter['name'],
                'category' => $starter['category'],
            ]);
            $managed = BlindBoxManagedShop::all();
        }

        Cache::forever(self::CACHE_KEY, true);

        return $managed->map(function (BlindBoxManagedShop $dbShop) use ($sourceByKey) {
            $key = $dbShop->source_key;
            if (!$sourceByKey->has($key)) { return null; }
            $source = $sourceByKey[$key];
            return array_merge($source, [
                'id' => $dbShop->id,
                'source_id' => $source['source_id'],
                'category' => $dbShop->category,
                'active' => true,
            ]);
        })->filter()->values()->all();
    }

    public function activeShops(): array { return $this->managedShops(); }

    public function availableShops(): array
    {
        $managedKeys = collect($this->managedShops())->pluck('stable_key')->all();
        return collect($this->sourceShops())
            ->filter(fn (array $shop) => !in_array($shop['stable_key'], $managedKeys, true))
            ->values()->all();
    }

    public function findManagedShop(int $id): ?array { return collect($this->managedShops())->first(fn (array $shop) => (int) $shop['id'] === $id); }

    public function addSourceShop(int $sourceId, string $category): array
    {
        $sourceShop = collect($this->sourceShops())->first(fn (array $shop) => (int) $shop['source_id'] === $sourceId);
        abort_unless($sourceShop !== null, 404);
        $managed = BlindBoxManagedShop::updateOrCreate(['source_key' => $sourceShop['stable_key']], ['shop_name' => $sourceShop['name'], 'category' => $category]);
        return array_merge($sourceShop, ['id' => $managed->id, 'category' => $category]);
    }

    public function updateShop(int $id, string $category): array
    {
        $managed = BlindBoxManagedShop::findOrFail($id);
        $managed->update(['category' => $category]);
        return $this->findManagedShop($id);
    }

    public function toggleShop(int $id): array
    {
        $managed = BlindBoxManagedShop::find($id);
        if ($managed) {
            Cache::put(self::CACHE_KEY.'.removed', array_values(array_unique([
                ...Cache::get(self::CACHE_KEY.'.removed', []),
                $managed->source_key,
            ])));
            $managed->delete();
        }
        return [];
    }

    public function isRemoved(array $shop): bool
    {
        return in_array(
            strtolower((string) ($shop['name'] ?? '')),
            Cache::get(self::CACHE_KEY.'.removed', []),
            true,
        );
    }
}
