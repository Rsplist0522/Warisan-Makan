<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\BlindBoxCatalogManager;
use App\Services\HeritageShopCatalog;
use Illuminate\Http\Request;

class BlindBoxController extends Controller
{
    public function __construct(private BlindBoxCatalogManager $catalog)
    {
    }

    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $category = trim((string) $request->query('category', ''));
        $matches = fn (array $shop): bool => ($search === ''
                || str_contains(strtolower($shop['name']), strtolower($search))
                || str_contains(strtolower($shop['state']), strtolower($search)))
            && ($category === '' || $shop['category'] === $category);

        $insideShops = array_values(array_filter($this->catalog->managedShops(), $matches));
        $availableShops = array_values(array_filter($this->catalog->availableShops(), function (array $shop) use ($search): bool {
            return $search === ''
                || str_contains(strtolower($shop['name']), strtolower($search))
                || str_contains(strtolower($shop['state']), strtolower($search));
        }));

        return view('admin.blind-box-items.index', [
            'insideShops' => $insideShops,
            'availableShops' => $availableShops,
            'search' => $search,
            'category' => $category,
            'categories' => HeritageShopCatalog::CATEGORIES,
            'totalShops' => count($this->catalog->managedShops()),
            'activeShops' => count($this->catalog->activeShops()),
        ]);
    }

    public function edit(int $shop)
    {
        return view('admin.blind-box-items.edit', [
            'shop' => $this->catalog->findManagedShop($shop),
            'categories' => HeritageShopCatalog::CATEGORIES,
        ]);
    }

    public function add(Request $request, int $sourceShop)
    {
        $validated = $request->validate([
            'category' => ['required', 'in:' . implode(',', HeritageShopCatalog::CATEGORIES)],
        ]);

        $this->catalog->addSourceShop($sourceShop, $validated['category']);

        return redirect()->route('admin.blind-box-items.index')
            ->with('status', 'Shop added to the Blind Box recommendation pool.');
    }

    public function update(Request $request, int $shop)
    {
        $validated = $request->validate([
            'category' => ['required', 'in:' . implode(',', HeritageShopCatalog::CATEGORIES)],
        ]);

        $this->catalog->updateShop($shop, $validated['category']);

        return redirect()->route('admin.blind-box-items.index')
            ->with('status', 'Blind Box shop settings saved.');
    }

    /**
     * Remove a shop from the Blind Box pool. The shop moves back to the
     * "Shops not yet selected" list on the left, where it can be re-added.
     */
    public function toggle(int $shop)
    {
        $this->catalog->toggleShop($shop);

        return redirect()->route('admin.blind-box-items.index')
            ->with('status', 'Shop removed from the recommendation pool — it now appears under "Shops not yet selected".');
    }
}
