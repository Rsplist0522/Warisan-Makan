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

        $this->get(route('guest.continue'))
            ->assertRedirect(route('user.dashboard'));

        $this->get(route('guest.continue'))
            ->assertRedirect(route('user.dashboard'));

        $this->get(route('heritage-shops.index'))
            ->assertOk()
            ->assertSee($published->shop_name)
            ->assertDontSee($draft->shop_name)
            ->assertDontSee($archived->shop_name);

        $this->get(route('heritage-shops.show', ['id' => $published->id]))
            ->assertRedirect(route('login'));

        $this->actingAs(User::factory()->create())
            ->get(route('heritage-shops.show', ['id' => $published->id]))
            ->assertOk()
            ->assertSee($published->shop_name);

        $this->actingAs(User::factory()->create())
            ->get(route('heritage-shops.show', ['id' => $draft->id]))
            ->assertNotFound();

        $this->actingAs(User::factory()->create())
            ->get(route('heritage-shops.show', ['id' => $archived->id]))
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
        ]))->assertRedirect(route('login'));

        $this->actingAs(User::factory()->create())
            ->get(route('heritage-shops.images.show', [
                'heritageShop' => $draft->id,
                'image' => $image->id,
            ]))->assertNotFound();
    }

    public function test_public_listing_formats_mixed_operating_hours_shapes(): void
    {
        HeritageShop::create([
            'shop_name' => 'Structured Hours Cafe',
            'publish_status' => HeritageShop::STATUS_PUBLISHED,
            'operating_hours' => [
                ['day' => 'Monday', 'open' => '10:00', 'close' => '22:00', 'closed' => false],
                ['day' => 'Tuesday', 'open' => null, 'close' => null, 'closed' => true],
            ],
        ]);
        HeritageShop::create([
            'shop_name' => 'Flat Hours Cafe',
            'publish_status' => HeritageShop::STATUS_PUBLISHED,
            'operating_hours' => ['Daily 9:30am - 7:30pm'],
        ]);
        HeritageShop::create([
            'shop_name' => 'Legacy Raw Hours Cafe',
            'publish_status' => HeritageShop::STATUS_PUBLISHED,
            'operating_hours' => ['raw' => json_encode(['raw' => 'Sunday 11:30-14:30; Monday Closed'])],
        ]);
        HeritageShop::create([
            'shop_name' => 'No Hours Cafe',
            'publish_status' => HeritageShop::STATUS_PUBLISHED,
            'operating_hours' => null,
        ]);

        $this->get(route('guest.continue'))
            ->assertRedirect(route('user.dashboard'));

        $this->get(route('heritage-shops.index'))
            ->assertOk()
            ->assertSee('Structured Hours Cafe')
            ->assertSee('Monday: 10:00')
            ->assertSee('Tuesday: Closed')
            ->assertSee('Daily 9:30am - 7:30pm')
            ->assertSee('Sunday: 11:30-14:30')
            ->assertSee('No Hours Cafe')
            ->assertDontSee('>Array<', false);
    }
}
