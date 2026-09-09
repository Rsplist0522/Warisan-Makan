<?php

namespace Tests\Feature;

use App\Models\HeritageShop;
use App\Models\PassportStamp;
use App\Models\User;
use App\Services\ShopCrawlerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HeritageShopAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_crawl_endpoint_returns_structured_json_for_valid_url(): void
    {
        Http::fake([
            'https://example.com/*' => Http::response(<<<'HTML'
                <html>
                    <head>
                        <title>Warisan Nasi Lemak</title>
                        <meta name="description" content="A heritage family restaurant serving classic Malaysian dishes since 1968." />
                        <script type="application/ld+json">
                        {
                            "@context": "https://schema.org",
                            "@type": "Restaurant",
                            "name": "Warisan Nasi Lemak",
                            "address": {
                                "streetAddress": "12 Jalan Warisan",
                                "addressLocality": "Kuala Lumpur",
                                "addressRegion": "Wilayah Persekutuan",
                                "postalCode": "50200"
                            },
                            "telephone": "+603-5555 1234",
                            "servesCuisine": ["Malay"],
                            "image": ["https://images.example.com/food1.jpg", "https://images.example.com/food2.jpg"]
                        }
                        </script>
                    </head>
                    <body>
                        <div class="menu-item">
                            <span class="name">Nasi Lemak</span>
                            <span class="price">RM 12.00</span>
                            <span class="description">Classic curry rice with sambal.</span>
                        </div>
                    </body>
                </html>
                HTML, 200),
            'https://images.example.com/*' => Http::response('fake-image-content', 200, ['Content-Type' => 'image/jpeg']),
        ]);

        $admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@example.com',
        ]);

        $this->actingAs($admin);

        $csrfToken = 'heritage-shop-test-csrf-token';
        $response = $this->withSession(['_token' => $csrfToken])
            ->withHeader('X-CSRF-TOKEN', $csrfToken)
            ->postJson(route('admin.heritage-shops.crawl'), [
                'url' => 'https://example.com/heritage-shop',
            ]);

        $response->assertOk()
            ->assertJsonStructure([
                'name',
                'description',
                'heritage_story',
                'menu',
                'images',
                'address',
            ])
            ->assertJsonPath('name', 'Warisan Nasi Lemak')
            ->assertJsonPath('heritage_story', 'A heritage family restaurant serving classic Malaysian dishes since 1968.')
            ->assertJsonPath('address', '12 Jalan Warisan, Kuala Lumpur, Wilayah Persekutuan 50200');
    }

    public function test_crawler_extracts_business_details_from_html_content(): void
    {
        $html = <<<'HTML'
        <html>
            <head>
                <title>Warong Nasi Lemak Kampung</title>
                <meta property="og:description" content="A heritage family restaurant serving classic Malaysian dishes since 1968." />
                <script type="application/ld+json">
                {
                  "@context": "https://schema.org",
                  "@type": "Restaurant",
                  "name": "Warong Nasi Lemak Kampung",
                  "address": {
                    "streetAddress": "12 Jalan Warisan",
                    "addressLocality": "Kuala Lumpur",
                    "addressRegion": "Wilayah Persekutuan",
                    "postalCode": "50200"
                  },
                  "telephone": "+603-5555 1234",
                  "servesCuisine": ["Malay"],
                  "priceRange": "RM 10 - RM 30"
                }
                </script>
            </head>
            <body>
                <h1>Warong Nasi Lemak Kampung</h1>
                <div class="menu-item">
                    <span class="name">Nasi Lemak</span>
                    <span class="price">RM 12.00</span>
                    <span class="description">Classic curry rice with sambal.</span>
                </div>
                <div class="menu-item">
                    <span class="name">Ayam Goreng</span>
                    <span class="price">RM 15.00</span>
                    <span class="description">Crispy fried chicken.</span>
                </div>
            </body>
        </html>
        HTML;

        $service = new ShopCrawlerService;
        $data = $service->extractFromHtml('https://example.com/restaurant', $html);

        $this->assertSame('Warong Nasi Lemak Kampung', $data['name']);
        $this->assertStringContainsString('heritage family restaurant', strtolower($data['description']));
        $this->assertSame('12 Jalan Warisan, Kuala Lumpur, Wilayah Persekutuan 50200', $data['address']);
        $this->assertNotEmpty($data['menu']);
        $this->assertSame('Nasi Lemak', $data['menu'][0]['name']);
    }

    public function test_crawler_leaves_unknown_values_empty_instead_of_using_fake_defaults(): void
    {
        $service = new ShopCrawlerService;
        $data = $service->extractFromHtml('https://example.com/restaurant', '<html><head><title>Warisan Cafe</title></head><body></body></html>');

        $this->assertSame('Warisan Cafe', $data['name']);
        $this->assertNull($data['address']);
        $this->assertNull($data['contact_number']);
        $this->assertNull($data['primary_food_category']);
        $this->assertNull($data['heritage_story']);
    }

    public function test_crawler_researches_missing_details_and_only_uses_returned_sources(): void
    {
        config()->set('services.tavily.api_key', 'test-search-key');
        config()->set('services.tavily.endpoint', 'https://search.test/api');
        config()->set('services.groq.api_key', 'test-ai-key');
        config()->set('services.groq.endpoint', 'https://ai.test/chat');
        config()->set('services.groq.model', 'test-model');

        Http::fake([
            'https://example.com/*' => Http::response('<html><head><title>Warisan Cafe</title></head><body>Welcome.</body></html>'),
            'https://search.test/*' => Http::response([
                'results' => [[
                    'title' => 'Warisan Cafe directory listing',
                    'url' => 'https://directory.example/warisan-cafe',
                    'raw_content' => 'Warisan Cafe is at 8 Jalan Makan, 50000 Kuala Lumpur. Tel: +603-2222 3333.',
                ]],
            ]),
            'https://ai.test/*' => Http::response([
                'choices' => [['message' => ['content' => json_encode([
                    'address' => '8 Jalan Makan, 50000 Kuala Lumpur',
                    'city' => 'Kuala Lumpur',
                    'postal_code' => '50000',
                    'contact_number' => '+603-2222 3333',
                    'founder_name' => null,
                ])]]],
            ]),
        ]);

        $data = (new ShopCrawlerService)->crawl('https://example.com/warisan-cafe');

        $this->assertSame('8 Jalan Makan, 50000 Kuala Lumpur', $data['address']);
        $this->assertSame('+603-2222 3333', $data['contact_number']);
        $this->assertCount(1, $data['research_sources']);
        $this->assertSame('Web research found 1 source(s). Suggestions are limited to supported facts.', $data['research_status']);
        $this->assertSame('Web research — review the sources below', $data['field_sources']['address']);
    }

    public function test_crawler_reads_graph_json_ld_headings_and_real_hours_without_capturing_viewport_metadata(): void
    {
        $html = <<<'HTML'
        <html><head>
            <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
            <script type="application/ld+json">{
                "@context":"https://schema.org", "@graph":[{
                    "@type":"Restaurant", "name":"Kedai Warisan", "telephone":"+604-123 4567",
                    "openingHoursSpecification":[{"dayOfWeek":["Monday","Tuesday"],"opens":"09:00","closes":"17:00"}],
                    "address":{"streetAddress":"1 Jalan Lama","addressLocality":"George Town","addressRegion":"Penang","postalCode":"10000"}
                }]
            }</script>
        </head><body><h1>Kedai Warisan</h1></body></html>
        HTML;

        $data = (new ShopCrawlerService)->extractFromHtml('https://example.com/shop', $html);

        $this->assertSame('Kedai Warisan', $data['name']);
        $this->assertSame('+604-123 4567', $data['contact_number']);
        $this->assertSame('1 Jalan Lama, George Town, Penang 10000', $data['address']);
        $this->assertSame('Monday, Tuesday 09:00–17:00', $data['operating_hours']);
    }

    public function test_shop_detail_uses_only_saved_food_items_and_never_crawls_live(): void
    {
        Http::fake([
            'https://example.com/heritage-shop' => Http::response(<<<'HTML'
                <html>
                    <head>
                        <title>Heritage Noodle House</title>
                    </head>
                    <body>
                        <div class="menu-item">
                            <span class="name">Nasi Lemak</span>
                            <span class="price">RM 12.00</span>
                            <span class="description">Coconut rice with sambal and anchovies.</span>
                        </div>
                        <div class="menu-item">
                            <span class="name">Ayam Goreng</span>
                            <span class="price">RM 15.00</span>
                            <span class="description">Classic fried chicken with house spice.</span>
                        </div>
                    </body>
                </html>
                HTML, 200),
        ]);

        $shop = HeritageShop::create([
            'shop_name' => 'Heritage Noodle House',
            'country' => 'Malaysia',
            'primary_food_category' => 'Malay',
            'heritage_story' => 'A family-run restaurant preserving classic recipes.',
            'source_url' => 'https://example.com/heritage-shop',
            'publish_status' => HeritageShop::STATUS_PUBLISHED,
        ]);

        $this->actingAs(User::factory()->create())
            ->get(route('heritage-shops.show', ['id' => $shop->id]))
            ->assertOk()
            ->assertDontSee('Nasi Lemak')
            ->assertDontSee('Ayam Goreng')
            ->assertSee('The menu is still being documented');

        Http::assertNothingSent();
    }

    public function test_crawl_rejects_canonical_duplicate_source_before_fetching(): void
    {
        $existing = HeritageShop::create([
            'shop_name' => 'Existing Heritage Cafe',
            'source_url' => 'https://example.com/restaurants/warisan',
            'publish_status' => HeritageShop::STATUS_DRAFT,
        ]);
        $admin = User::factory()->create(['role' => 'admin']);
        Http::fake();

        $csrfToken = 'heritage-admin-test-token';
        $response = $this->actingAs($admin)->withSession(['_token' => $csrfToken])
            ->withHeader('X-CSRF-TOKEN', $csrfToken)
            ->postJson(route('admin.heritage-shops.crawl'), [
                'url' => 'https://EXAMPLE.com/restaurants/warisan/',
            ]);

        $response->assertStatus(409)
            ->assertJsonPath('existing_shop_id', $existing->id)
            ->assertJsonPath('existing_shop_url', route('admin.heritage-shops.edit', $existing));
        Http::assertNothingSent();
    }

    public function test_normal_shop_save_rejects_a_canonical_duplicate_source(): void
    {
        HeritageShop::create([
            'shop_name' => 'Existing Heritage Cafe',
            'source_url' => 'https://example.com/restaurants/warisan',
            'publish_status' => HeritageShop::STATUS_DRAFT,
        ]);
        $admin = User::factory()->create(['role' => 'admin']);

        $csrfToken = 'heritage-admin-test-token';
        $response = $this->actingAs($admin)->withSession(['_token' => $csrfToken])
            ->withHeader('X-CSRF-TOKEN', $csrfToken)
            ->from(route('admin.heritage-shops.create'))
            ->post(route('admin.heritage-shops.store'), [
                'shop_name' => 'Duplicate Cafe',
                'source_url' => 'https://EXAMPLE.com/restaurants/warisan/',
                'publish_status' => HeritageShop::STATUS_DRAFT,
            ]);

        $response->assertRedirect(route('admin.heritage-shops.create'))
            ->assertSessionHasErrors('source_url');
        $this->assertDatabaseCount('heritage_shops', 1);
    }

    public function test_admin_can_delete_an_unreferenced_shop_and_its_module_images(): void
    {
        Storage::fake('public');
        $shop = HeritageShop::create([
            'shop_name' => 'Temporary Heritage Cafe',
            'publish_status' => HeritageShop::STATUS_DRAFT,
        ]);
        Storage::disk('public')->put('heritage-shops/gallery.jpg', 'gallery-bytes');
        Storage::disk('public')->put('heritage-shops/food.jpg', 'food-bytes');
        $shop->images()->create(['path' => 'heritage-shops/gallery.jpg', 'is_primary' => true]);
        $shop->foodItems()->create(['name' => 'Temporary Dish', 'image_path' => 'heritage-shops/food.jpg', 'is_active' => false]);
        $admin = User::factory()->create(['role' => 'admin']);

        $csrfToken = 'heritage-admin-test-token';
        $response = $this->actingAs($admin)->withSession(['_token' => $csrfToken])
            ->withHeader('X-CSRF-TOKEN', $csrfToken)
            ->delete(route('admin.heritage-shops.destroy', $shop));

        $response->assertRedirect(route('admin.heritage-shops.index'));
        $this->assertDatabaseMissing('heritage_shops', ['id' => $shop->id]);
        $this->assertDatabaseMissing('shop_images', ['shop_id' => $shop->id]);
        $this->assertDatabaseMissing('heritage_food_items', ['heritage_shop_id' => $shop->id]);
        $this->assertFalse(Storage::disk('public')->exists('heritage-shops/gallery.jpg'));
        $this->assertFalse(Storage::disk('public')->exists('heritage-shops/food.jpg'));
    }

    public function test_admin_delete_is_blocked_when_passport_history_exists(): void
    {
        $shop = HeritageShop::create([
            'shop_name' => 'Visited Heritage Cafe',
            'publish_status' => HeritageShop::STATUS_PUBLISHED,
        ]);
        $user = User::factory()->create();
        PassportStamp::create([
            'user_id' => $user->id,
            'shop_id' => $shop->id,
            'stamp_datetime' => now(),
            'gps_latitude' => 3.139,
            'gps_longitude' => 101.6869,
        ]);
        $admin = User::factory()->create(['role' => 'admin']);

        $csrfToken = 'heritage-admin-test-token';
        $response = $this->actingAs($admin)->withSession(['_token' => $csrfToken])
            ->withHeader('X-CSRF-TOKEN', $csrfToken)
            ->from(route('admin.heritage-shops.index'))
            ->delete(route('admin.heritage-shops.destroy', $shop));

        $response->assertRedirect(route('admin.heritage-shops.index'))
            ->assertSessionHas('error');
        $this->assertDatabaseHas('heritage_shops', ['id' => $shop->id]);
    }

    public function test_manual_gallery_upload_uses_the_configured_two_mb_limit(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $csrfToken = 'heritage-admin-test-token';
        $response = $this->actingAs($admin)->withSession(['_token' => $csrfToken])
            ->withHeader('X-CSRF-TOKEN', $csrfToken)
            ->from(route('admin.heritage-shops.create'))
            ->post(route('admin.heritage-shops.store'), [
                'shop_name' => 'Oversized Photo Cafe',
                'publish_status' => HeritageShop::STATUS_DRAFT,
                'images' => [UploadedFile::fake()->image('oversized.jpg')->size((int) config('heritage_shop.max_image_kb') + 1)],
            ]);

        $response->assertRedirect(route('admin.heritage-shops.create'))
            ->assertSessionHasErrors('images.0');
    }

    public function test_public_profile_renders_legacy_raw_hours_as_readable_rows(): void
    {
        $shop = HeritageShop::create([
            'shop_name' => 'Readable Hours Cafe',
            'operating_hours' => ['raw' => 'Sunday 11:30–14:30; Tuesday 17:30–22:30'],
            'publish_status' => HeritageShop::STATUS_PUBLISHED,
        ]);

        $this->actingAs(User::factory()->create())
            ->get(route('heritage-shops.show', ['id' => $shop->id]))
            ->assertOk()
            ->assertSee('Operating information')
            ->assertSee('Sunday', false)
            ->assertSee('11:30–14:30', false)
            ->assertSee('Tuesday', false)
            ->assertSee('17:30–22:30', false)
            ->assertDontSee('&quot;raw&quot;', false);
    }

    public function test_admin_save_stores_operating_hours_as_clean_lines(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $csrfToken = 'heritage-hours-save-test-token';

        $response = $this->actingAs($admin)->withSession(['_token' => $csrfToken])
            ->withHeader('X-CSRF-TOKEN', $csrfToken)
            ->post(route('admin.heritage-shops.store'), [
                'shop_name' => 'Clean Hours Cafe',
                'publish_status' => HeritageShop::STATUS_DRAFT,
                'operating_hours' => "Monday: 09:00–17:00\nFriday: 10:00–20:00",
            ]);

        $shop = HeritageShop::query()->where('shop_name', 'Clean Hours Cafe')->firstOrFail();
        $response->assertRedirect(route('admin.heritage-shops.edit', $shop));
        $this->assertSame(['Monday: 09:00–17:00', 'Friday: 10:00–20:00'], $shop->operating_hours);
        $this->assertStringNotContainsString('raw', json_encode($shop->operating_hours));
    }

    public function test_admin_form_reopens_hours_without_a_raw_json_wrapper(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $shop = HeritageShop::create([
            'shop_name' => 'Hours Form Cafe',
            'operating_hours' => ['raw' => 'Monday 09:00–17:00; Friday 10:00–20:00'],
            'publish_status' => HeritageShop::STATUS_DRAFT,
        ]);

        $this->actingAs($admin)->get(route('admin.heritage-shops.edit', $shop))
            ->assertOk()
            ->assertSee('Monday: 09:00–17:00', false)
            ->assertSee('Friday: 10:00–20:00', false)
            ->assertDontSee('&quot;raw&quot;', false);
    }

    public function test_published_shop_requires_story_address_and_city(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $csrfToken = 'heritage-required-fields-test-token';

        $response = $this->actingAs($admin)->withSession(['_token' => $csrfToken])
            ->withHeader('X-CSRF-TOKEN', $csrfToken)
            ->from(route('admin.heritage-shops.create'))
            ->post(route('admin.heritage-shops.store'), [
                'shop_name' => 'Incomplete Published Cafe',
                'publish_status' => HeritageShop::STATUS_PUBLISHED,
            ]);

        $response->assertRedirect(route('admin.heritage-shops.create'))
            ->assertSessionHasErrors(['heritage_story', 'address', 'city']);
        $this->assertDatabaseMissing('heritage_shops', ['shop_name' => 'Incomplete Published Cafe']);
    }

    public function test_permitted_list_discovery_previews_same_host_shops_without_saving(): void
    {
        Http::fake([
            'https://authorized.example/directory' => Http::response(<<<'HTML'
                <a href="/shops/one">Warisan Cafe</a>
                <a href="/shops/two">Heritage Kitchen</a>
                <a href="https://outside.example/shop">Outside link</a>
                <a href="/about">About us</a>
                HTML, 200),
            'https://authorized.example/shops/one' => Http::response('<html><head><title>Warisan Cafe</title></head><body><h1>Warisan Cafe</h1></body></html>'),
            'https://authorized.example/shops/two' => Http::response('<html><head><title>Heritage Kitchen</title></head><body><h1>Heritage Kitchen</h1></body></html>'),
        ]);
        $admin = User::factory()->create(['role' => 'admin']);

        $csrfToken = 'heritage-admin-test-token';
        $response = $this->actingAs($admin)->withSession(['_token' => $csrfToken])
            ->withHeader('X-CSRF-TOKEN', $csrfToken)
            ->postJson(route('admin.heritage-shops.discover'), [
                'url' => 'https://authorized.example/directory/',
                'limit' => 6,
            ]);

        $response->assertOk()
            ->assertJsonPath('candidate_count', 2)
            ->assertJsonCount(2, 'items')
            ->assertJsonPath('items.0.can_import', true)
            ->assertJsonPath('items.1.can_import', true);
        $this->assertDatabaseCount('heritage_shops', 0);
    }

    public function test_list_import_skips_existing_sources_and_creates_new_records_as_drafts(): void
    {
        HeritageShop::create([
            'shop_name' => 'Already Imported',
            'source_url' => 'https://authorized.example/shops/one',
            'publish_status' => HeritageShop::STATUS_DRAFT,
        ]);
        $admin = User::factory()->create(['role' => 'admin']);

        $csrfToken = 'heritage-admin-test-token';
        $response = $this->actingAs($admin)->withSession(['_token' => $csrfToken])
            ->withHeader('X-CSRF-TOKEN', $csrfToken)
            ->post(route('admin.heritage-shops.discover.import'), [
                'list_url' => 'https://authorized.example/directory',
                'items' => [
                    ['name' => 'Duplicate Result', 'source_url' => 'https://AUTHORIZED.example/shops/one/'],
                    ['name' => 'New Heritage Kitchen', 'source_url' => 'https://authorized.example/shops/two/', 'food_items' => json_encode([['name' => 'Old Recipe']])],
                ],
            ]);

        $response->assertRedirect(route('admin.heritage-shops.index'))
            ->assertSessionHas('success');
        $this->assertDatabaseCount('heritage_shops', 2);
        $this->assertDatabaseHas('heritage_shops', [
            'shop_name' => 'New Heritage Kitchen',
            'source_url' => 'https://authorized.example/shops/two',
            'publish_status' => HeritageShop::STATUS_DRAFT,
        ]);
        $this->assertDatabaseHas('heritage_food_items', ['name' => 'Old Recipe']);
    }

    public function test_restricted_directory_discovery_returns_a_clear_user_facing_error(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $csrfToken = 'heritage-admin-test-token';
        $this->actingAs($admin)->withSession(['_token' => $csrfToken])
            ->withHeader('X-CSRF-TOKEN', $csrfToken)
            ->postJson(route('admin.heritage-shops.discover'), [
                'url' => 'https://www.tripadvisor.com/Restaurants-g298570-Kuala_Lumpur.html',
            ])->assertStatus(422)
            ->assertJsonPath('message', fn (string $message): bool => str_contains($message, 'not supported'));
    }
}
