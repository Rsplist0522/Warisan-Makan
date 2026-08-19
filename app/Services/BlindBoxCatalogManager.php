<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

/**
 * Manages which source shops participate in the Blind Box recommendation pool.
 *
 * Key stability rule: every shop is identified by a STABLE key — its `name`,
 * never by its position in a list. The HeritageShopCatalog can return DB rows
 * in any order (queries have no guaranteed order), so relying on array index
 * would make different shops silently swap identities between requests — which
 * is exactly how a removed shop could vanish from both columns.
 *
 * The shop name is used as the stable key because it is present on both DB rows
 * and fake samples, and is unique per record in practice. If names can ever
 * collide, extend mapShop() to include a `primary_id` and key on that instead.
 */
class BlindBoxCatalogManager
{
    // Bumped to v6: shops are now identified by a stable key (lowercased name)
    // instead of array position, so reordering of the source list can no longer
    // corrupt the pool or make removed shops vanish.
    public const CACHE_KEY = 'blind_box.admin.catalog.v6';

    // Stable keys (lowercased shop names) of the shops that start inside the
    // Blind Box. Keep empty to start with a fresh pool the admin builds via UI,
    // or list names here to pre-include specific shops, e.g.:
    //   private const DEFAULT_INCLUDED_SOURCE_IDS = ['a taste of malaysia', ...];
    private const DEFAULT_INCLUDED_SOURCE_IDS = [];

    public function __construct(private HeritageShopCatalog $catalog)
    {
    }

    /**
     * All source shops with a STABLE identifier. The shop's name (lowercased)
     * is used as the identity key, and each shop is also given a stable integer
     * source_id assigned in deterministic alphabetical order — so the same shop
     * always gets the same source_id on every request, no matter how the
     * underlying query orders its rows.
     */
    public function sourceShops(): array
    {
        return collect($this->catalog->all())
            ->sortBy(fn (array $shop): string => strtolower((string) ($shop['name'] ?? '')))
            ->values()
            ->map(fn (array $shop, int $index): array => array_merge($shop, [
                'source_id' => $index + 1,
                'stable_key' => strtolower((string) ($shop['name'] ?? '')),
            ]))
            ->all();
    }

    public function managedShops(): array
    {
        $sourceShops = $this->sourceShops();

        // Build the pool keyed by stable key. Matching by stable_key instead of
        // position guarantees that reordering or insertion in the source list
        // (e.g. a new DB row appearing) never attaches a cached identity to a
        // different shop.
        $sourceByKey = collect($sourceShops)->keyBy('stable_key');

        $pools = Cache::rememberForever(self::CACHE_KEY, function () use ($sourceByKey): array {
            return $sourceByKey
                ->only(self::DEFAULT_INCLUDED_SOURCE_IDS)
                ->map(fn (array $shop): array => array_merge($shop, [
                    'id' => $shop['source_id'],
                    'category' => 'Main Dishes',
                ]))
                ->values()
                ->all();
        });

        // Re-sync: every cached entry must still match a current source shop by
        // its stable key. Shops that no longer exist in the catalog are dropped,
        // and any stale positional source_ids are refreshed so the cached pool
        // always uses the current deterministic IDs.
        $shops = collect($pools)
            ->filter(fn (array $shop): bool => $sourceByKey->has($shop['stable_key'] ?? ''))
            ->map(fn (array $shop): array => array_merge($sourceByKey[$shop['stable_key']], [
                'id' => $shop['id'] ?? $shop['source_id'],
                'category' => $shop['category'],
            ]))
            ->values()
            ->all();

        return collect($shops)
            ->values()
            ->map(fn (array $shop, int $index): array => array_merge($shop, [
                'id' => (int) ($shop['id'] ?? $index + 1),
                'source_id' => (int) ($shop['source_id'] ?? $shop['id'] ?? $index + 1),
                'active' => true,
            ]))
            ->all();
    }

    /**
     * All managed shops are active (the pool has no inactive state anymore).
     */
    public function activeShops(): array
    {
        return $this->managedShops();
    }

    /**
     * LEFT column: source shops that are not currently managed in the pool.
     * Because source_id is stable, a removed shop will always reappear here.
     */
    public function availableShops(): array
    {
        $insideIds = collect($this->managedShops())
            ->pluck('source_id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        return array_values(array_filter(
            $this->sourceShops(),
            fn (array $shop): bool => ! in_array((int) $shop['source_id'], $insideIds, true)
        ));
    }

    public function findManagedShop(int $shopId): ?array
    {
        return collect($this->managedShops())
            ->first(fn (array $shop): bool => (int) $shop['id'] === $shopId);
    }

    public function addSourceShop(int $sourceId, string $category): array
    {
        // Look up the pending shop by its stable source_id. Because source IDs
        // are assigned in deterministic alphabetical order, the same shop gets
        // the same ID on every request; re-syncing against the live catalog
        // also catches any remaining drift after DB changes.
        $sourceShop = collect($this->availableShops())
            ->first(fn (array $shop): bool => (int) $shop['source_id'] === $sourceId);

        abort_unless($sourceShop !== null, 404, 'Shop not found in the pending list — the catalog was refreshed since this page loaded. Reload and try again.');

        $shop = array_merge($sourceShop, [
            'id' => $sourceId,
            'category' => $category,
        ]);

        $shops = $this->managedShops();
        $shops[] = $shop;
        $this->save($shops);

        return $shop;
    }

    public function updateShop(int $shopId, string $category): array
    {
        $shops = $this->managedShops();
        $index = collect($shops)->search(fn (array $shop): bool => (int) $shop['id'] === $shopId);

        abort_unless($index !== false, 404);

        $shops[$index]['category'] = $category;
        $this->save($shops);

        return $shops[$index];
    }

    /**
     * Remove a shop from the Blind Box pool entirely. The shop returns to the
     * "Shops Pending Selection" list on the LEFT, where it can be re-added later.
     * Because identification is by stable source_id, the shop is guaranteed to
     * reappear on the left after removal.
     */
    public function toggleShop(int $shopId): array
    {
        $shops = $this->managedShops();
        $removed = collect($shops)
            ->filter(fn (array $shop): bool => (int) $shop['id'] !== $shopId)
            ->values()
            ->all();

        abort_unless(count($removed) === count($shops) - 1, 404);

        $this->save($removed);

        return [];
    }

    private function save(array $shops): void
    {
        Cache::forever(self::CACHE_KEY, array_values($shops));
    }
}
