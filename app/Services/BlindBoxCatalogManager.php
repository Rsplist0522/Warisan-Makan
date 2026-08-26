<?php

namespace App\Services;

use App\Models\BlindBoxManagedShop;

class BlindBoxCatalogManager
{
    public function __construct(private HeritageShopCatalog $catalog) {}

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
        $sourceByKey = collect($sourceShops)->keyBy('stable_key');
        $managed = BlindBoxManagedShop::all();

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
        if ($managed) { $managed->delete(); }
        return [];
    }
}
