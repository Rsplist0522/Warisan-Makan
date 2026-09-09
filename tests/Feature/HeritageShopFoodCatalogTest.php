<?php

namespace Tests\Feature;

use App\Models\HeritageFoodItem;
use App\Models\HeritageShop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HeritageShopFoodCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_the_food_catalog_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $shop = HeritageShop::create(['shop_name' => 'Catalog Kitchen', 'publish_status' => HeritageShop::STATUS_DRAFT]);

        $this->actingAs($admin)->get(route('admin.heritage-shops.food-items.index', $shop))
            ->assertOk()
            ->assertSee('Food catalog')
            ->assertSee('Catalog Kitchen')
            ->assertSee('New food item');
    }

    public function test_admin_can_create_food_item_with_photo_and_public_page_renders_it(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);
        $shop = HeritageShop::create([
            'shop_name' => 'Kedai Warisan',
            'heritage_story' => 'A family recipe passed through generations.',
            'publish_status' => HeritageShop::STATUS_PUBLISHED,
        ]);

        $response = $this->withHeritageCsrf()->actingAs($admin)->post(route('admin.heritage-shops.food-items.store', $shop), [
            'name' => 'Kuih Lapis',
            'description' => 'Layered steamed rice cake.',
            'category' => 'Kuih',
            'heritage_significance' => 'Prepared for family gatherings and festive visits.',
            'availability' => 'Weekends',
            'price' => 'RM 5.00',
            'display_order' => 1,
            'is_active' => '1',
            'image' => UploadedFile::fake()->image('kuih-lapis.jpg', 900, 700),
        ]);

        $item = HeritageFoodItem::query()->firstOrFail();
        $response->assertRedirect();
        $this->assertSame($shop->id, $item->heritage_shop_id);
        $this->assertSame('Kuih Lapis', $item->name);
        $this->assertNotNull($item->image_path);
        $this->assertTrue(Storage::disk('public')->exists($item->image_path));
        $this->assertStringContainsString('Kuih Lapis', (string) $this->actingAs($admin)->get(route('heritage-shops.show', ['id' => $shop->id]))->getContent());
        $this->actingAs($admin)->get(route('heritage-shops.food-images.show', [$shop, $item]))->assertOk();
    }

        public function test_food_catalog_rejects_a_dish_photo_over_the_shared_one_mb_limit(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $shop = HeritageShop::create(['shop_name' => 'Photo Limit Kitchen', 'publish_status' => HeritageShop::STATUS_DRAFT]);

        $response = $this->withHeritageCsrf()->actingAs($admin)->from(route('admin.heritage-shops.food-items.index', $shop))
            ->post(route('admin.heritage-shops.food-items.store', $shop), [
                'name' => 'Oversized Dish Photo',
                'image' => UploadedFile::fake()->image('oversized-dish.jpg')->size((int) config('heritage_shop.max_image_kb') + 1),
            ]);

        $response->assertRedirect(route('admin.heritage-shops.food-items.index', $shop))
            ->assertSessionHasErrors('image');
        $this->assertDatabaseMissing('heritage_food_items', ['name' => 'Oversized Dish Photo']);
    }

    public function test_admin_can_update_hide_restore_and_archive_food_item(): void

    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);
        $shop = HeritageShop::create(['shop_name' => 'Old Town Kitchen', 'publish_status' => HeritageShop::STATUS_PUBLISHED]);
        $item = $shop->foodItems()->create(['name' => 'Nasi Dagang', 'description' => 'Rice with fish curry.', 'is_active' => true]);

        $this->withHeritageCsrf()->actingAs($admin)->put(route('admin.heritage-shops.food-items.update', [$shop, $item]), [
            'name' => 'Nasi Dagang Special',
            'description' => 'Rice with a family fish curry recipe.',
            'price' => 'RM 10.00',
            'is_active' => '1',
        ])->assertRedirect();
        $this->assertDatabaseHas('heritage_food_items', ['id' => $item->id, 'name' => 'Nasi Dagang Special']);

        $this->withHeritageCsrf()->actingAs($admin)->patch(route('admin.heritage-shops.food-items.toggle', [$shop, $item]))->assertRedirect();
        $this->assertDatabaseHas('heritage_food_items', ['id' => $item->id, 'is_active' => false]);

        $this->withHeritageCsrf()->actingAs($admin)->delete(route('admin.heritage-shops.food-items.destroy', [$shop, $item]))->assertRedirect();
        $this->assertSoftDeleted('heritage_food_items', ['id' => $item->id]);
    }

        public function test_public_card_actions_open_distinct_profile_and_menu_routes(): void
    {
        $shop = HeritageShop::create([
            'shop_name' => 'Distinct Action Kitchen',
            'heritage_story' => 'A profile story for the card-action test.',
            'publish_status' => HeritageShop::STATUS_PUBLISHED,
        ]);
        $shop->foodItems()->create([
            'name' => 'Signature Heritage Dish',
            'description' => 'A verified signature dish.',
            'heritage_significance' => 'A documented family food tradition.',
            'is_active' => true,
        ]);

        $list = $this->get(route('heritage-shops.index'));
        $list->assertOk()
            ->assertSee('View details')
            ->assertSee('Explore menu &amp; stories', false)
            ->assertSee(route('heritage-shops.show', ['id' => $shop->id]), false)
            ->assertSee(route('heritage-shops.menu', $shop), false);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('heritage-shops.show', ['id' => $shop->id]))
            ->assertOk()
            ->assertSee('Heritage information')
            ->assertSee('Heritage foods &amp; menu', false);

        $this->actingAs($user)
            ->get(route('heritage-shops.menu', $shop))
            ->assertOk()
            ->assertSee('Menu &amp; heritage stories', false)
            ->assertSee('Signature Heritage Dish')
            ->assertSee('Back to Distinct Action Kitchen profile')
            ->assertDontSee('Ask the story behind this place');
    }

    public function test_published_food_item_has_a_dedicated_story_page(): void

    {
        Storage::fake('public');
        $shop = HeritageShop::create(['shop_name' => 'Story Kitchen', 'publish_status' => HeritageShop::STATUS_PUBLISHED]);
        $item = $shop->foodItems()->create([
            'name' => 'Laksa Warisan',
            'description' => 'A fragrant family recipe with a slow-prepared broth.',
            'category' => 'Noodles',
            'heritage_significance' => 'The recipe is taught from one generation to the next.',
            'availability' => 'Saturday mornings',
            'price' => 'RM 9.00',
            'image_path' => 'heritage-shops/food-items/laksa-warisan.jpg',
            'is_active' => true,
        ]);
        Storage::disk('public')->put($item->image_path, 'image-bytes');
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('heritage-shops.food-items.show', [$shop, $item]))
            ->assertOk()
            ->assertSee('Laksa Warisan')
            ->assertSee('Why this dish matters')
            ->assertSee('The recipe is taught from one generation to the next.')
            ->assertSee('Explore full menu')
            ->assertSee('data-image-enlarge', false)
            ->assertSee('data-image-lightbox', false);

        $this->actingAs($user)
            ->get(route('heritage-shops.show', ['id' => $shop->id]))
            ->assertOk()
            ->assertSee('data-image-enlarge', false)
            ->assertSee('data-image-lightbox', false);

        $this->actingAs($user)
            ->get(route('heritage-shops.menu', $shop))
            ->assertOk()
            ->assertSee('data-image-enlarge', false)
            ->assertSee('data-image-lightbox', false);
    }

    public function test_food_item_photo_and_record_are_private_when_shop_is_not_published(): void
    {
        Storage::fake('public');
        $shop = HeritageShop::create(['shop_name' => 'Draft Kitchen', 'publish_status' => HeritageShop::STATUS_DRAFT]);
        $item = $shop->foodItems()->create(['name' => 'Draft Dish', 'image_path' => 'heritage-shops/food-items/draft.jpg', 'is_active' => true]);
        Storage::disk('public')->put($item->image_path, 'image-bytes');

        $user = User::factory()->create();

        $this->actingAs($user)->get(route('heritage-shops.show', ['id' => $shop->id]))->assertNotFound();
        $this->actingAs($user)->get(route('heritage-shops.food-images.show', [$shop, $item]))->assertNotFound();
    }

    public function test_food_item_operations_are_scoped_to_the_current_shop(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $shopA = HeritageShop::create(['shop_name' => 'Shop A', 'publish_status' => HeritageShop::STATUS_PUBLISHED]);
        $shopB = HeritageShop::create(['shop_name' => 'Shop B', 'publish_status' => HeritageShop::STATUS_PUBLISHED]);
        $item = $shopA->foodItems()->create(['name' => 'Protected Dish', 'is_active' => true]);

        $this->withHeritageCsrf()->actingAs($admin)->put(route('admin.heritage-shops.food-items.update', [$shopB, $item]), ['name' => 'Tampered'])->assertNotFound();
        $this->withHeritageCsrf()->actingAs($admin)->delete(route('admin.heritage-shops.food-items.destroy', [$shopB, $item]))->assertNotFound();
        $this->assertDatabaseHas('heritage_food_items', ['id' => $item->id, 'name' => 'Protected Dish']);
    }

    public function test_public_detail_shows_an_honest_empty_food_catalog_state(): void
    {
        $shop = HeritageShop::create(['shop_name' => 'No Menu Yet', 'publish_status' => HeritageShop::STATUS_PUBLISHED]);

        $this->actingAs(User::factory()->create())
            ->get(route('heritage-shops.show', ['id' => $shop->id]))
            ->assertOk()
            ->assertSee('The menu is still being documented')
            ->assertSee('0 recorded items');
    }

    private function withHeritageCsrf(): self
    {
        $token = 'heritage-food-catalog-test-csrf-token';

        return $this->withSession(['_token' => $token])->withHeader('X-CSRF-TOKEN', $token);
    }

    public function test_admin_dashboard_has_a_clickable_heritage_shop_entry(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        HeritageShop::create(['shop_name' => 'Dashboard Shop', 'publish_status' => HeritageShop::STATUS_PUBLISHED]);

        $this->actingAs($admin)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Heritage Shops')
            ->assertSee(route('admin.heritage-shops.index'), false)
            ->assertSee('Active HeritageShop');
    }
}
