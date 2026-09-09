<?php

namespace Tests\Feature;

use App\Models\HeritageShop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HeritageShopImagePolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('heritage-policy-test');
        config()->set('heritage_shop.image_disk', 'heritage-policy-test');
        config()->set('heritage_shop.max_image_kb', 2048);
        config()->set('heritage_shop.max_image_bytes', 2048 * 1024);
        config()->set('heritage_shop.max_gallery_images', 10);
    }

    public function test_supported_gallery_image_formats_within_two_mb_are_accepted(): void
    {
        foreach (['jpg', 'jpeg', 'png', 'webp'] as $index => $extension) {
            $this->withCsrf()->actingAs($this->admin())
                ->post(route('admin.heritage-shops.store'), [
                    ...$this->publishedPayload('Format Shop '.$index),
                    'images' => [UploadedFile::fake()->image("front.{$extension}")->size(2048)],
                ])
                ->assertSessionHasNoErrors();
        }

        $this->assertDatabaseCount('shop_images', 4);
    }

    public function test_gallery_image_over_two_mb_and_unsupported_format_are_rejected(): void
    {
        $this->withCsrf()->actingAs($this->admin())
            ->post(route('admin.heritage-shops.store'), [
                ...$this->publishedPayload('Oversized Gallery Shop'),
                'images' => [UploadedFile::fake()->image('large.jpg')->size(2049)],
            ])
            ->assertSessionHasErrors('images.0');

        $this->withCsrf()->actingAs($this->admin())
            ->post(route('admin.heritage-shops.store'), [
                ...$this->publishedPayload('Unsupported Gallery Shop'),
                'images' => [UploadedFile::fake()->create('vector.svg', 10, 'image/svg+xml')],
            ])
            ->assertSessionHasErrors('images.0');
    }

    public function test_ten_total_images_are_accepted_and_an_eleventh_is_rejected(): void
    {
        $files = collect(range(1, 10))->map(fn (int $index) => UploadedFile::fake()->image("image-{$index}.jpg"))->all();
        $this->withCsrf()->actingAs($this->admin())
            ->post(route('admin.heritage-shops.store'), [
                ...$this->publishedPayload('Ten Image Shop'),
                'images' => $files,
            ])
            ->assertSessionHasNoErrors();

        $shop = HeritageShop::query()->where('shop_name', 'Ten Image Shop')->firstOrFail();
        $this->assertSame(10, $shop->images()->count());

        $this->withCsrf()->actingAs($this->admin())
            ->put(route('admin.heritage-shops.update', $shop), [
                ...$this->publishedPayload('Ten Image Shop'),
                'version' => $shop->version,
                'images' => [UploadedFile::fake()->image('eleventh.jpg')],
            ])
            ->assertSessionHasErrors('images');

        $this->assertSame(10, $shop->fresh()->images()->count());
    }

    public function test_replacement_is_net_zero_at_the_ten_image_limit(): void
    {
        $shop = $this->shopWithImages('Replacement Limit Shop', 10);
        $image = $shop->images()->firstOrFail();
        $oldPath = $image->path;

        $this->withCsrf()->actingAs($this->admin())
            ->put(route('admin.heritage-shops.update', $shop), [
                ...$this->publishedPayload($shop->shop_name),
                'version' => $shop->version,
                'replace_images' => [$image->id => UploadedFile::fake()->image('replacement.jpg')],
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(10, $shop->fresh()->images()->count());
        $this->assertNotSame($oldPath, $image->fresh()->path);
        $this->assertTrue($image->fresh()->is_primary);
        $this->assertSame(1, $shop->images()->where('is_primary', true)->count());
        $this->assertFalse(Storage::disk('heritage-policy-test')->exists($oldPath));
    }

    public function test_eight_existing_accepts_two_new_but_rejects_three_new(): void
    {
        $accepted = $this->shopWithImages('Eight Plus Two Shop', 8);

        $this->withCsrf()->actingAs($this->admin())
            ->put(route('admin.heritage-shops.update', $accepted), [
                ...$this->publishedPayload($accepted->shop_name),
                'version' => $accepted->version,
                'images' => [UploadedFile::fake()->image('ninth.jpg'), UploadedFile::fake()->image('tenth.jpg')],
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(10, $accepted->fresh()->images()->count());

        $rejected = $this->shopWithImages('Eight Plus Three Shop', 8);
        $this->withCsrf()->actingAs($this->admin())
            ->put(route('admin.heritage-shops.update', $rejected), [
                ...$this->publishedPayload($rejected->shop_name),
                'version' => $rejected->version,
                'images' => [
                    UploadedFile::fake()->image('new-one.jpg'),
                    UploadedFile::fake()->image('new-two.jpg'),
                    UploadedFile::fake()->image('new-three.jpg'),
                ],
            ])
            ->assertSessionHasErrors('images');

        $this->assertSame(8, $rejected->fresh()->images()->count());
    }

    public function test_remove_two_and_add_two_at_limit_succeeds(): void
    {
        $shop = $this->shopWithImages('Remove Add Limit Shop', 10);
        $removeIds = $shop->images()->orderBy('id')->limit(2)->pluck('id')->all();

        $this->withCsrf()->actingAs($this->admin())
            ->put(route('admin.heritage-shops.update', $shop), [
                ...$this->publishedPayload($shop->shop_name),
                'version' => $shop->version,
                'remove_images' => $removeIds,
                'images' => [
                    UploadedFile::fake()->image('new-one.jpg'),
                    UploadedFile::fake()->image('new-two.jpg'),
                ],
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(10, $shop->fresh()->images()->count());
    }

    public function test_nine_existing_plus_replacement_plus_two_new_is_rejected_as_eleven(): void
    {
        $shop = $this->shopWithImages('Eleven Final Shop', 9);
        $image = $shop->images()->firstOrFail();
        $oldPath = $image->path;

        $this->withCsrf()->actingAs($this->admin())
            ->put(route('admin.heritage-shops.update', $shop), [
                ...$this->publishedPayload($shop->shop_name),
                'version' => $shop->version,
                'replace_images' => [$image->id => UploadedFile::fake()->image('replacement.jpg')],
                'images' => [UploadedFile::fake()->image('new-one.jpg'), UploadedFile::fake()->image('new-two.jpg')],
            ])
            ->assertSessionHasErrors('images');

        $this->assertSame(9, $shop->fresh()->images()->count());
        $this->assertSame($oldPath, $image->fresh()->path);
        $this->assertTrue(Storage::disk('heritage-policy-test')->exists($oldPath));
    }

    public function test_removing_primary_selects_one_and_only_one_remaining_primary(): void
    {
        $shop = $this->shopWithImages('Primary Integrity Shop', 3);
        $primary = $shop->images()->where('is_primary', true)->firstOrFail();

        $this->withCsrf()->actingAs($this->admin())
            ->put(route('admin.heritage-shops.update', $shop), [
                ...$this->publishedPayload($shop->shop_name),
                'version' => $shop->version,
                'remove_images' => [$primary->id],
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(2, $shop->fresh()->images()->count());
        $this->assertSame(1, $shop->images()->where('is_primary', true)->count());
    }

    public function test_published_shop_cannot_remove_its_final_image(): void
    {
        $shop = $this->shopWithImages('Protected Final Image Shop', 1);
        $image = $shop->images()->firstOrFail();

        $this->withCsrf()->actingAs($this->admin())
            ->put(route('admin.heritage-shops.update', $shop), [
                ...$this->publishedPayload($shop->shop_name),
                'version' => $shop->version,
                'remove_images' => [$image->id],
            ])
            ->assertSessionHasErrors('images');

        $this->assertSame(1, $shop->fresh()->images()->count());
        $this->assertTrue(Storage::disk('heritage-policy-test')->exists($image->path));
    }

    public function test_draft_and_archived_shops_may_have_no_images(): void
    {
        foreach ([HeritageShop::STATUS_DRAFT, HeritageShop::STATUS_ARCHIVED] as $index => $status) {
            $this->withCsrf()->actingAs($this->admin())
                ->post(route('admin.heritage-shops.store'), [
                    ...$this->publishedPayload("Empty Gallery {$status} {$index}"),
                    'publish_status' => $status,
                ])
                ->assertSessionHasNoErrors();
        }

        $this->assertSame(2, HeritageShop::query()->whereDoesntHave('images')->count());
    }

    public function test_persisted_crawler_image_cannot_push_final_gallery_above_ten(): void
    {
        $shop = $this->shopWithImages('Crawler Gallery Limit Shop', 10);
        $crawlerPath = 'heritage-shops/crawler/extra.jpg';
        Storage::disk('heritage-policy-test')->put($crawlerPath, 'crawler-image');

        $this->withCsrf()->actingAs($this->admin())
            ->put(route('admin.heritage-shops.update', $shop), [
                ...$this->publishedPayload($shop->shop_name),
                'version' => $shop->version,
                'crawler_images' => [$crawlerPath],
            ])
            ->assertSessionHasErrors('images');

        $this->assertSame(10, $shop->fresh()->images()->count());
    }

    public function test_category_normalization_preserves_flexible_values_and_punctuation(): void
    {
        $this->withCsrf()->actingAs($this->admin())
            ->post(route('admin.heritage-shops.store'), [
                ...$this->publishedPayload('Flexible Category Shop'),
                'primary_food_category' => '  Nyonya/Peranakan   Kuih & Coffee  ',
                'images' => [UploadedFile::fake()->image('front.jpg')],
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('heritage_shops', [
            'shop_name' => 'Flexible Category Shop',
            'primary_food_category' => 'Nyonya/Peranakan Kuih & Coffee',
        ]);

        $this->get(route('heritage-shops.index', ['category' => 'Nyonya/Peranakan Kuih & Coffee']))
            ->assertOk()
            ->assertSee('Flexible Category Shop');
    }

    private function shopWithImages(string $name, int $count): HeritageShop
    {
        $shop = HeritageShop::query()->create($this->publishedPayload($name));
        foreach (range(1, $count) as $index) {
            $path = "heritage-shops/existing-{$shop->id}-{$index}.jpg";
            Storage::disk('heritage-policy-test')->put($path, "image-{$index}");
            $shop->images()->create(['path' => $path, 'is_primary' => $index === 1]);
        }

        return $shop->fresh();
    }

    private function publishedPayload(string $name): array
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

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'status' => 'active']);
    }

    private function withCsrf(): self
    {
        $token = 'heritage-image-policy-token';

        return $this->withSession(['_token' => $token])->withHeader('X-CSRF-TOKEN', $token);
    }
}
