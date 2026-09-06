<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\BlindBoxCatalogManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class BlindBoxAdminTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::forget(BlindBoxCatalogManager::CACHE_KEY);
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    protected function tearDown(): void
    {
        Cache::forget(BlindBoxCatalogManager::CACHE_KEY);

        parent::tearDown();
    }

    public function test_admin_can_open_blind_box_management_with_separate_lists(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.blind-box-items.index'))
            ->assertOk()
            ->assertSee('Recommendation pool')
            ->assertSee('Shops Pending Selection')
            ->assertSee('Shops Included in the Blind Box');
    }

    public function test_non_admin_cannot_open_blind_box_management(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)
            ->get(route('admin.blind-box-items.index'))
            ->assertForbidden();
    }

    public function test_pending_shops_appear_on_the_left_and_included_shops_on_the_right(): void
    {
        // Kedai Kopi Pak Hassan is included, so it appears in the right column.
        $this->actingAs($this->admin)
            ->get(route('admin.blind-box-items.index'))
            ->assertSeeInOrder(['Shops Included in the Blind Box', 'Kedai Kopi Pak Hassan']);
    }

    public function test_admin_can_add_a_source_shop_by_selecting_only_its_category(): void
    {
        $sourceShop = app(BlindBoxCatalogManager::class)->availableShops()[0]['source_id'];

        $this->actingAs($this->admin)
            ->post(route('admin.blind-box-items.add', $sourceShop), [
                'category' => 'Main Dishes',
            ])
            ->assertRedirect(route('admin.blind-box-items.index'));

        // The newly added shop must appear in the right column after adding.
        $this->actingAs($this->admin)
            ->get(route('admin.blind-box-items.index'))
            ->assertSee('Shops Included in the Blind Box');
    }

    public function test_admin_can_edit_a_shop_category(): void
    {
        $shopId = app(BlindBoxCatalogManager::class)->managedShops()[0]['id'];

        $this->actingAs($this->admin)
            ->put(route('admin.blind-box-items.update', $shopId), [
                'category' => 'Desserts',
            ])
            ->assertRedirect(route('admin.blind-box-items.index'))
            ->assertSessionHas('status', 'Blind Box shop settings saved.');
    }

    public function test_admin_can_open_a_shop_edit_page(): void
    {
        $shopId = app(BlindBoxCatalogManager::class)->managedShops()[0]['id'];
        $shopName = app(BlindBoxCatalogManager::class)->managedShops()[0]['name'];

        $this->actingAs($this->admin)
            ->get(route('admin.blind-box-items.edit', $shopId))
            ->assertOk()
            ->assertSee('Recommendation settings')
            ->assertSee('Shop information is supplied by the source catalog')
            ->assertDontSee('Include in Blind Box reveals')
            ->assertSee($shopName);
    }

    public function test_removed_shop_returns_to_the_pending_list(): void
    {
        $shop = app(BlindBoxCatalogManager::class)->managedShops()[0];

        $this->actingAs($this->admin)
            ->patch(route('admin.blind-box-items.toggle', $shop['id']))
            ->assertRedirect(route('admin.blind-box-items.index'));

        // The shop must now appear in the LEFT (pending) column with an Add form,
        // and no longer appear in the included column.
        $response = $this->actingAs($this->admin)->get(route('admin.blind-box-items.index'));

        $response->assertSeeInOrder(['Shops Pending Selection', $shop['name']]);

        $includedSection = strstr($response->getContent(), 'Shops Included in the Blind Box') ?: '';
        $this->assertStringNotContainsString($shop['name'], $includedSection);
        $response->assertDontSee('Restore to reveals');
    }

    public function test_removed_shop_is_not_available_to_the_draw_pool(): void
    {
        $shop = app(BlindBoxCatalogManager::class)->managedShops()[0];

        $this->actingAs($this->admin)
            ->patch(route('admin.blind-box-items.toggle', $shop['id']))
            ->assertRedirect(route('admin.blind-box-items.index'));

        $response = $this->actingAs($this->admin)->get('/blind-box');
        $response->assertDontSee($shop['name']);
    }
}
