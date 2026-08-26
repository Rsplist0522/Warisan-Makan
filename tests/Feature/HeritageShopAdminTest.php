<?php

namespace Tests\Feature;

use App\Models\HeritageShop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
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

        $response = $this->postJson(route('admin.heritage-shops.crawl'), [
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

        $service = new \App\Services\ShopCrawlerService();
        $data = $service->extractFromHtml('https://example.com/restaurant', $html);

        $this->assertSame('Warong Nasi Lemak Kampung', $data['name']);
        $this->assertStringContainsString('heritage family restaurant', strtolower($data['description']));
        $this->assertSame('12 Jalan Warisan, Kuala Lumpur, Wilayah Persekutuan 50200', $data['address']);
        $this->assertNotEmpty($data['menu']);
        $this->assertSame('Nasi Lemak', $data['menu'][0]['name']);
    }

    public function test_crawler_leaves_unknown_values_empty_instead_of_using_fake_defaults(): void
    {
        $service = new \App\Services\ShopCrawlerService();
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

        $data = (new \App\Services\ShopCrawlerService())->crawl('https://example.com/warisan-cafe');

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

        $data = (new \App\Services\ShopCrawlerService())->extractFromHtml('https://example.com/shop', $html);

        $this->assertSame('Kedai Warisan', $data['name']);
        $this->assertSame('+604-123 4567', $data['contact_number']);
        $this->assertSame('1 Jalan Lama, George Town, Penang 10000', $data['address']);
        $this->assertSame('Monday, Tuesday 09:00–17:00', $data['operating_hours']);
    }

    public function test_shop_detail_prefers_live_crawled_menu_over_generic_fallbacks(): void
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

        $this->get(route('heritage-shops.show', ['id' => $shop->id]))
            ->assertOk()
            ->assertSee('Nasi Lemak')
            ->assertSee('Ayam Goreng')
            ->assertDontSee('Signature Heritage Dish');
    }
}
