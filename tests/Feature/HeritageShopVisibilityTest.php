<?php

namespace Tests\Feature;

use App\Models\HeritageShop;
use App\Models\ShopImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HeritageShopVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_discovery_and_details_exclude_draft_and_archived_records(): void
    {
        $published = HeritageShop::create([
            'shop_name' => 'Published Heritage Cafe',
            'publish_status' => HeritageShop::STATUS_PUBLISHED,
        ]);
        $draft = HeritageShop::create([
            'shop_name' => 'Draft Heritage Cafe',
            'publish_status' => HeritageShop::STATUS_DRAFT,
        ]);
        $archived = HeritageShop::create([
            'shop_name' => 'Archived Heritage Cafe',
            'publish_status' => HeritageShop::STATUS_ARCHIVED,
        ]);

        $this->get(route('heritage-shops.index'))
            ->assertOk()
            ->assertSee($published->shop_name)
            ->assertDontSee($draft->shop_name)
            ->assertDontSee($archived->shop_name);

        $this->get(route('heritage-shops.show', ['id' => $published->id]))
            ->assertForbidden();

        $this->actingAs(User::factory()->create())
            ->get(route('heritage-shops.show', ['id' => $published->id]))
            ->assertOk()
            ->assertSee($published->shop_name);

        $this->get(route('heritage-shops.show', ['id' => $draft->id]))
            ->assertNotFound();

        $this->get(route('heritage-shops.show', ['id' => $archived->id]))
            ->assertNotFound();
    }

    public function test_public_image_route_rejects_an_image_from_a_non_published_shop(): void
    {
        $draft = HeritageShop::create([
            'shop_name' => 'Private Heritage Cafe',
            'publish_status' => HeritageShop::STATUS_DRAFT,
        ]);
        $image = ShopImage::create([
            'shop_id' => $draft->id,
            'path' => 'heritage-shops/private.jpg',
            'is_primary' => true,
        ]);

        $this->get(route('heritage-shops.images.show', [
            'heritageShop' => $draft->id,
            'image' => $image->id,
        ]))->assertNotFound();
    }
}
