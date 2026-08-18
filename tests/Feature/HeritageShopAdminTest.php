<?php

namespace Tests\Feature;

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
}
