<?php

namespace Tests\Feature;

use App\Models\HeritageShop;
use App\Models\ShopImage;
use App\Models\User;
use App\Services\HeritageShopImageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HeritageShopHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_upload_is_saved_to_the_configured_disk_and_retrievable_by_a_guest(): void
    {
        Storage::fake('public');
        $this->actingAs($this->admin());

        $response = $this->withCsrf()->post(route('admin.heritage-shops.store'), [
            ...$this->publishedShopPayload('Gallery Test Shop'),
            'images' => [UploadedFile::fake()->image('front.jpg', 120, 120)],
        ]);

        $response->assertRedirect();

        $shop = HeritageShop::query()->where('shop_name', 'Gallery Test Shop')->firstOrFail();
        $image = $shop->images()->firstOrFail();

        $this->assertSame('heritage-shops', dirname($image->path));
        $this->assertTrue(Storage::disk('public')->exists($image->path));

        auth()->guard()->logout();
        $this->get(route('guest.continue'))
            ->assertRedirect(route('user.dashboard'));
        $this->get(route('heritage-shops.images.show', [
            'heritageShop' => $shop->id,
            'image' => $image->id,
        ]))
            ->assertOk()
            ->assertHeader('content-type', 'image/jpeg');

        $this->actingAs(User::factory()->create())
            ->get(route('heritage-shops.images.show', [
                'heritageShop' => $shop->id,
                'image' => $image->id,
            ]))
            ->assertOk()
            ->assertHeader('content-type', 'image/jpeg');
    }

    public function test_new_images_use_the_configured_shared_disk_instead_of_the_legacy_public_disk(): void
    {
        config()->set('heritage_shop.image_disk', 'r2');
        Storage::fake('r2');
        Storage::fake('public');

        $path = app(HeritageShopImageService::class)->store(
            UploadedFile::fake()->image('shared-gallery.webp', 160, 160),
        );

        $this->assertStringStartsWith('heritage-shops/', $path);
        $this->assertTrue(Storage::disk('r2')->exists($path));
        $this->assertFalse(Storage::disk('public')->exists($path));
    }

    public function test_legacy_public_images_remain_readable_after_disk_migration(): void
    {
        config()->set('heritage_shop.image_disk', 'r2');
        Storage::fake('r2');
        Storage::fake('public');
        $legacyPath = 'heritage-shops/legacy-front.jpg';
        Storage::disk('public')->put($legacyPath, 'legacy-image-bytes');

        $shop = HeritageShop::create($this->publishedShopPayload('Legacy Gallery Shop'));
        $image = $shop->images()->create(['path' => $legacyPath, 'is_primary' => true]);

        $this->get(route('heritage-shops.images.show', [
            'heritageShop' => $shop->id,
            'image' => $image->id,
        ]))->assertRedirect(route('login'));

        $this->actingAs(User::factory()->create())
            ->get(route('heritage-shops.images.show', [
                'heritageShop' => $shop->id,
                'image' => $image->id,
            ]))->assertOk();
    }

    public function test_guest_and_non_admin_cannot_use_the_heritage_shop_admin_area(): void
    {
        $this->get(route('admin.heritage-shops.index'))
            ->assertRedirect(route('admin.login'));

        $this->actingAs(User::factory()->create(['role' => 'user']))
            ->get(route('admin.heritage-shops.index'))
            ->assertForbidden();
    }

    public function test_published_shop_requires_public_profile_fields_and_rejects_svg_uploads(): void
    {
        $this->actingAs($this->admin());

        $response = $this->withCsrf()->post(route('admin.heritage-shops.store'), [
            'shop_name' => 'Invalid Published Shop',
            'publish_status' => HeritageShop::STATUS_PUBLISHED,
            'images' => [UploadedFile::fake()->create('unsafe.svg', 2, 'image/svg+xml')],
        ]);

        $response->assertSessionHasErrors(['heritage_story', 'address', 'city', 'images.0']);
        $this->assertDatabaseMissing('heritage_shops', ['shop_name' => 'Invalid Published Shop']);
    }

    public function test_remove_image_ids_are_scoped_to_the_current_shop(): void
    {
        Storage::fake('public');
        $this->actingAs($this->admin());

        $firstShop = HeritageShop::create($this->publishedShopPayload('First Gallery Shop'));
        $secondShop = HeritageShop::create($this->publishedShopPayload('Second Gallery Shop'));
        $firstPath = 'heritage-shops/first.jpg';
        $secondPath = 'heritage-shops/second.jpg';
        Storage::disk('public')->put($firstPath, 'first');
        Storage::disk('public')->put($secondPath, 'second');
        $firstImage = $firstShop->images()->create(['path' => $firstPath, 'is_primary' => true]);
        $secondImage = $secondShop->images()->create(['path' => $secondPath, 'is_primary' => true]);

        $this->withCsrf()->put(route('admin.heritage-shops.update', $firstShop), [
            ...$this->publishedShopPayload('First Gallery Shop'),
            'remove_images' => [$secondImage->id],
        ])->assertRedirect();

        $this->assertDatabaseHas('shop_images', ['id' => $firstImage->id, 'deleted_at' => null]);
        $this->assertDatabaseHas('shop_images', ['id' => $secondImage->id, 'deleted_at' => null]);
        $this->assertTrue(Storage::disk('public')->exists($firstPath));
        $this->assertTrue(Storage::disk('public')->exists($secondPath));
    }

    public function test_replacing_primary_image_removes_the_old_blob_and_preserves_primary_status(): void
    {
        Storage::fake('public');
        $this->actingAs($this->admin());

        $shop = HeritageShop::create($this->publishedShopPayload('Replacement Gallery Shop'));
        $oldPath = 'heritage-shops/old-front.jpg';
        Storage::disk('public')->put($oldPath, 'old');
        $oldImage = $shop->images()->create(['path' => $oldPath, 'is_primary' => true]);

        $this->withCsrf()->put(route('admin.heritage-shops.update', $shop), [
            ...$this->publishedShopPayload('Replacement Gallery Shop'),
            'replace_images' => [
                $oldImage->id => UploadedFile::fake()->image('new-front.jpg', 140, 140),
            ],
        ])->assertRedirect();

        $this->assertDatabaseMissing('shop_images', ['id' => $oldImage->id]);
        $this->assertFalse(Storage::disk('public')->exists($oldPath));
        $replacement = $shop->fresh()->images()->firstOrFail();
        $this->assertTrue($replacement->is_primary);
        $this->assertTrue(Storage::disk('public')->exists($replacement->path));
    }

    public function test_crawler_rejects_private_page_targets_before_making_a_request(): void
    {
        Http::fake();
        $this->actingAs($this->admin());

        $response = $this->withCsrf()->postJson(route('admin.heritage-shops.crawl'), [
            'url' => 'http://127.0.0.1/admin-secrets',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Local or private network URLs are not allowed.');
        Http::assertNothingSent();
    }

    public function test_crawler_skips_unsafe_and_invalid_image_payloads_without_crashing_the_import(): void
    {
        Http::fake([
            'https://example.com/*' => Http::response(<<<'HTML'
                <html><head><title>Safe Heritage Shop</title></head><body>
                    <img src="http://127.0.0.1/secret.png">
                    <img src="https://images.example.com/not-an-image.jpg">
                </body></html>
                HTML, 200),
            'https://images.example.com/*' => Http::response('not-an-image', 200, ['Content-Type' => 'image/jpeg']),
        ]);
        $this->actingAs($this->admin());

        $response = $this->withCsrf()->postJson(route('admin.heritage-shops.crawl'), [
            'url' => 'https://example.com/safe-shop',
        ]);

        $response->assertOk()->assertJsonPath('images', []);
        Http::assertSent(fn ($request): bool => $request->url() === 'https://example.com/safe-shop');
        Http::assertNotSent(fn ($request): bool => str_contains($request->url(), '127.0.0.1'));
    }

        public function test_crawler_rejects_remote_images_over_the_shared_one_mb_limit(): void
    {
        Storage::fake('public');
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=', true);
        $this->assertIsString($png);

        Http::fake([
            'https://example.com/*' => Http::response('<html><head><title>Large Image Heritage Shop</title></head><body><img src="https://images.example.com/large.png"></body></html>', 200),
            'https://images.example.com/*' => Http::response($png, 200, [
                'Content-Type' => 'image/png',
                'Content-Length' => (string) (config('heritage_shop.max_image_bytes') + 1),
            ]),
        ]);
        $this->actingAs($this->admin());

        $response = $this->withCsrf()->postJson(route('admin.heritage-shops.crawl'), [
            'url' => 'https://example.com/large-image-shop',
        ]);

        $response->assertOk()->assertJsonPath('images', []);
        $this->assertSame([], glob(Storage::disk('public')->path('heritage-shops/crawler/*')) ?: []);
    }

    public function test_crawler_accepts_valid_image_bytes_with_mime_parameters_and_persists_them(): void

    {
        Storage::fake('public');
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=', true);
        $this->assertIsString($png);

        Http::fake([
            'https://example.com/*' => Http::response('<html><head><title>Image Heritage Shop</title></head><body><img src="https://images.example.com/front.png"></body></html>', 200),
            'https://images.example.com/*' => Http::response($png, 200, ['Content-Type' => 'image/png; charset=binary']),
        ]);
        $this->actingAs($this->admin());

        $response = $this->withCsrf()->postJson(route('admin.heritage-shops.crawl'), [
            'url' => 'https://example.com/image-shop',
        ]);

        $response->assertOk();
        $path = $response->json('images.0');
        $this->assertIsString($path);
        $this->assertTrue(Storage::disk('public')->exists($path));
    }

    private function admin(): User
    {
        return User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);
    }

    private function withCsrf(): self
    {
        $token = 'heritage-shop-hardening-csrf-token';

        return $this->withSession(['_token' => $token])
            ->withHeader('X-CSRF-TOKEN', $token);
    }

    private function publishedShopPayload(string $name): array
    {
        return [
            'shop_name' => $name,
            'primary_food_category' => 'Traditional Food',
            'heritage_story' => 'A verified family recipe preserved across generations.',
            'address' => '1 Heritage Lane',
            'city' => 'Kuala Lumpur',
            'state' => 'Kuala Lumpur',
            'publish_status' => HeritageShop::STATUS_PUBLISHED,
        ];
    }
}
