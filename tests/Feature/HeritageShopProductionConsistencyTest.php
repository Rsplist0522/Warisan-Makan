<?php

namespace Tests\Feature;

use App\Models\HeritageAdminAudit;
use App\Models\HeritageShop;
use App\Models\User;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HeritageShopProductionConsistencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_administrators_can_mutate_heritage_shop_records(): void
    {
        $shop = HeritageShop::query()->create($this->draftPayload('Protected Admin Shop'));
        $regularUser = User::factory()->create(['role' => 'user']);

        $this->withCsrf()
            ->post(route('admin.heritage-shops.store'), $this->draftPayload('Guest Attempt'))
            ->assertRedirect(route('admin.login'));

        $this->withCsrf()->actingAs($regularUser)
            ->post(route('admin.heritage-shops.store'), $this->draftPayload('User Create Attempt'))
            ->assertForbidden();
        $this->withCsrf()->actingAs($regularUser)
            ->put(route('admin.heritage-shops.update', $shop), [
                ...$this->publishedPayload('User Publish Attempt'),
                'version' => $shop->version,
            ])
            ->assertForbidden();
        $this->withCsrf()->actingAs($regularUser)
            ->delete(route('admin.heritage-shops.destroy', $shop))
            ->assertForbidden();

        $this->assertDatabaseHas('heritage_shops', [
            'id' => $shop->id,
            'shop_name' => 'Protected Admin Shop',
            'publish_status' => HeritageShop::STATUS_DRAFT,
        ]);
    }

    public function test_shop_duplicate_detection_normalizes_case_and_whitespace_but_allows_a_different_location(): void
    {
        $admin = $this->admin();
        HeritageShop::query()->create([
            'shop_name' => 'Warisan   Kitchen',
            'address' => '12  Jalan Lama',
            'city' => 'George Town',
            'state' => 'Penang',
            'publish_status' => HeritageShop::STATUS_DRAFT,
        ]);

        foreach ([
            ['Warisan   Kitchen', '12  Jalan Lama', 'George Town', 'Penang'],
            ['warisan kitchen', '12 jalan lama', 'george town', 'penang'],
            ['  Warisan Kitchen  ', '  12   Jalan Lama ', ' George  Town ', ' Penang '],
        ] as [$name, $address, $city, $state]) {
            $this->withCsrf()->actingAs($admin)
                ->post(route('admin.heritage-shops.store'), [
                    ...$this->draftPayload($name, $address, $city, $state),
                ])
                ->assertSessionHasErrors([
                    'shop_name' => 'A heritage shop with the same name and location already exists.',
                ]);
        }

        $this->withCsrf()->actingAs($admin)
            ->post(route('admin.heritage-shops.store'), $this->draftPayload(
                'Warisan Kitchen',
                '88 Jalan Baharu',
                'Ipoh',
                'Perak',
            ))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('heritage_shops', 2);
    }

    public function test_same_shop_can_be_updated_without_rejecting_its_own_identity(): void
    {
        $admin = $this->admin();
        $shop = HeritageShop::query()->create($this->draftPayload('Self Edit Cafe'));

        $this->withCsrf()->actingAs($admin)
            ->put(route('admin.heritage-shops.update', $shop), [
                ...$this->draftPayload('Self Edit Cafe'),
                'version' => $shop->version,
                'heritage_story' => 'The same identity now has a more complete history.',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('heritage_shops', [
            'id' => $shop->id,
            'heritage_story' => 'The same identity now has a more complete history.',
        ]);
    }

    public function test_database_unique_key_protects_shop_identity_from_races(): void
    {
        HeritageShop::query()->create($this->draftPayload('Race Safe Cafe'));

        $this->expectException(QueryException::class);
        HeritageShop::query()->create($this->draftPayload(' race   safe cafe '));
    }

    public function test_discovered_import_rejects_duplicate_identity_even_with_a_new_source_url(): void
    {
        $admin = $this->admin();
        HeritageShop::query()->create($this->draftPayload('Imported Heritage Cafe'));

        $this->withCsrf()->actingAs($admin)
            ->post(route('admin.heritage-shops.discover.import'), [
                'list_url' => 'https://authorized.example/directory',
                'items' => [[
                    ...$this->draftPayload(' imported   heritage cafe '),
                    'source_url' => 'https://authorized.example/shops/new-source',
                ]],
            ])
            ->assertRedirect(route('admin.heritage-shops.index'))
            ->assertSessionHas('success', fn (string $message): bool => str_contains($message, 'Skipped duplicates'));

        $this->assertDatabaseCount('heritage_shops', 1);
    }

    public function test_import_validates_each_tampered_record_and_only_stores_valid_drafts(): void
    {
        $admin = $this->admin();
        $valid = [
            ...$this->draftPayload('Valid Imported Kitchen'),
            'source_url' => 'https://authorized.example/shops/valid',
            'establishment_year' => '1968',
            'contact_number' => '+603-5555 1234',
            'postal_code' => '50000',
            'food_items' => json_encode([['name' => 'Family Noodles', 'price' => 'RM 8']]),
        ];

        $invalidRecords = [
            [...$valid, 'shop_name' => 'Future Year', 'source_url' => 'https://authorized.example/shops/future', 'establishment_year' => now()->year + 1],
            [...$valid, 'shop_name' => 'Bad Phone', 'source_url' => 'https://authorized.example/shops/phone', 'contact_number' => 'call-me<script>'],
            [...$valid, 'shop_name' => 'Bad URL', 'source_url' => 'javascript:alert(1)'],
            [...$valid, 'shop_name' => str_repeat('X', 256), 'source_url' => 'https://authorized.example/shops/long'],
            [...$valid, 'shop_name' => 'Wrong Host', 'source_url' => 'https://attacker.example/shops/wrong'],
        ];

        $this->withCsrf()->actingAs($admin)
            ->post(route('admin.heritage-shops.discover.import'), [
                'list_url' => 'https://authorized.example/directory',
                'items' => [$valid, ...$invalidRecords],
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('heritage_shops', 1);
        $this->assertDatabaseHas('heritage_shops', [
            'shop_name' => 'Valid Imported Kitchen',
            'publish_status' => HeritageShop::STATUS_DRAFT,
        ]);
        $this->assertDatabaseHas('heritage_food_items', ['name' => 'Family Noodles']);
    }

    public function test_address_between_256_and_500_characters_is_stored(): void
    {
        $admin = $this->admin();
        $address = str_repeat('A', 400);

        $this->withCsrf()->actingAs($admin)
            ->post(route('admin.heritage-shops.store'), $this->draftPayload('Long Address Cafe', $address))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('heritage_shops', ['shop_name' => 'Long Address Cafe', 'address' => $address]);
    }

    public function test_manual_admin_write_enforces_all_field_boundaries_server_side(): void
    {
        $admin = $this->admin();

        $this->withCsrf()->actingAs($admin)
            ->post(route('admin.heritage-shops.store'), [
                'shop_name' => str_repeat('N', 256),
                'primary_food_category' => str_repeat('C', 256),
                'establishment_year' => now()->year + 1,
                'contact_number' => 'not-a-malaysian-phone',
                'address' => str_repeat('A', 501),
                'city' => str_repeat('I', 101),
                'state' => str_repeat('S', 101),
                'postal_code' => 'ABCDE',
                'latitude' => 91,
                'longitude' => -181,
                'source_url' => 'ftp://example.com/shop',
                'publish_status' => 'untrusted-status',
                'food_items' => array_fill(0, 51, ['name' => 'Dish']),
            ])
            ->assertSessionHasErrors([
                'shop_name',
                'primary_food_category',
                'establishment_year',
                'contact_number',
                'address',
                'city',
                'state',
                'postal_code',
                'latitude',
                'longitude',
                'source_url',
                'publish_status',
                'food_items',
            ]);

        $this->assertDatabaseCount('heritage_shops', 0);
    }

    public function test_quick_item_editor_updates_normalized_rows_and_clearing_it_hides_without_destroying_rich_data(): void
    {
        Storage::fake('public');
        $admin = $this->admin();
        $shop = HeritageShop::query()->create($this->publishedPayload('Quick Sync Kitchen'));
        Storage::disk('public')->put('heritage-shops/front.jpg', 'front');
        $shop->images()->create(['path' => 'heritage-shops/front.jpg', 'is_primary' => true]);
        $item = $shop->foodItems()->create([
            'name' => 'Family Curry',
            'description' => 'Original description',
            'category' => 'Curry',
            'heritage_significance' => 'A recipe taught across four generations.',
            'availability' => 'Weekends',
            'image_path' => 'heritage-shops/food-items/curry.jpg',
            'is_active' => true,
        ]);

        $this->withCsrf()->actingAs($admin)
            ->put(route('admin.heritage-shops.update', $shop), [
                ...$this->publishedPayload('Quick Sync Kitchen'),
                'version' => $shop->fresh()->version,
                'food_items' => [[
                    'id' => $item->id,
                    'name' => 'Family Curry Special',
                    'price' => 'RM 12',
                    'desc' => 'Updated quick description',
                ]],
            ])
            ->assertSessionHasNoErrors();

        $item->refresh();
        $this->assertSame('Family Curry Special', $item->name);
        $this->assertSame('Curry', $item->category);
        $this->assertSame('A recipe taught across four generations.', $item->heritage_significance);
        $this->assertSame('heritage-shops/food-items/curry.jpg', $item->image_path);

        $this->withCsrf()->actingAs($admin)
            ->put(route('admin.heritage-shops.update', $shop), [
                ...$this->publishedPayload('Quick Sync Kitchen'),
                'version' => $shop->fresh()->version,
                'food_items' => [['name' => '', 'price' => '', 'desc' => '']],
            ])
            ->assertSessionHasNoErrors();

        $item->refresh();
        $this->assertFalse($item->is_active);
        $this->assertSame('A recipe taught across four generations.', $item->heritage_significance);
        $this->actingAs(User::factory()->create())
            ->get(route('heritage-shops.show', ['id' => $shop->id]))
            ->assertOk()
            ->assertDontSee('Family Curry Special');
    }

    public function test_publication_requires_a_valid_image_and_rejects_removing_the_last_one(): void
    {
        Storage::fake('public');
        $admin = $this->admin();

        $this->withCsrf()->actingAs($admin)
            ->post(route('admin.heritage-shops.store'), $this->publishedPayload('No Image Cafe'))
            ->assertSessionHasErrors('images');

        $this->withCsrf()->actingAs($admin)
            ->post(route('admin.heritage-shops.store'), [
                ...$this->publishedPayload('Valid Published Cafe'),
                'images' => [UploadedFile::fake()->image('front.jpg', 120, 120)],
            ])
            ->assertSessionHasNoErrors();

        $shop = HeritageShop::query()->where('shop_name', 'Valid Published Cafe')->firstOrFail();
        $image = $shop->images()->firstOrFail();

        $this->withCsrf()->actingAs($admin)
            ->put(route('admin.heritage-shops.update', $shop), [
                ...$this->publishedPayload('Valid Published Cafe'),
                'version' => $shop->version,
                'remove_images' => [$image->id],
            ])
            ->assertSessionHasErrors('images');

        $this->assertDatabaseHas('shop_images', ['id' => $image->id, 'deleted_at' => null]);
        $this->assertTrue(Storage::disk('public')->exists($image->path));
    }

    public function test_reviewed_stored_crawler_image_can_satisfy_publication_requirement(): void
    {
        Storage::fake('public');
        $admin = $this->admin();
        $path = 'heritage-shops/crawler/reviewed.jpg';
        Storage::disk('public')->put($path, 'reviewed-image');

        $this->withCsrf()->actingAs($admin)
            ->post(route('admin.heritage-shops.store'), [
                ...$this->publishedPayload('Crawler Image Cafe'),
                'crawler_images' => [$path],
            ])
            ->assertSessionHasNoErrors();

        $shop = HeritageShop::query()->where('shop_name', 'Crawler Image Cafe')->firstOrFail();
        $this->assertDatabaseHas('shop_images', ['shop_id' => $shop->id, 'path' => $path]);
    }

    public function test_draft_may_be_incomplete_and_legacy_approved_cannot_be_written(): void
    {
        $admin = $this->admin();

        $this->withCsrf()->actingAs($admin)
            ->post(route('admin.heritage-shops.store'), [
                'shop_name' => 'Incomplete Draft',
                'publish_status' => HeritageShop::STATUS_DRAFT,
            ])
            ->assertSessionHasNoErrors();

        $this->withCsrf()->actingAs($admin)
            ->post(route('admin.heritage-shops.store'), [
                'shop_name' => 'Legacy Status Shop',
                'publish_status' => HeritageShop::STATUS_APPROVED_LEGACY,
            ])
            ->assertSessionHasErrors('publish_status');

        $this->assertDatabaseMissing('heritage_shops', ['shop_name' => 'Legacy Status Shop']);
    }

    public function test_food_item_duplicates_are_scoped_by_shop_and_normalized_for_create_and_update(): void
    {
        $admin = $this->admin();
        $firstShop = HeritageShop::query()->create($this->draftPayload('First Menu Shop'));
        $secondShop = HeritageShop::query()->create($this->draftPayload('Second Menu Shop', '2 Heritage Lane'));

        $this->withCsrf()->actingAs($admin)
            ->post(route('admin.heritage-shops.food-items.store', $firstShop), ['name' => 'Nasi   Lemak'])
            ->assertSessionHasNoErrors();
        $firstItem = $firstShop->foodItems()->firstOrFail();

        foreach (['Nasi Lemak', 'nasi lemak', '  NASI   LEMAK  '] as $duplicateName) {
            $this->withCsrf()->actingAs($admin)
                ->post(route('admin.heritage-shops.food-items.store', $firstShop), ['name' => $duplicateName])
                ->assertSessionHasErrors([
                    'name' => 'A food item with the same name already exists for this shop.',
                ]);
        }

        $this->withCsrf()->actingAs($admin)
            ->post(route('admin.heritage-shops.food-items.store', $secondShop), ['name' => 'nasi lemak'])
            ->assertSessionHasNoErrors();

        $this->withCsrf()->actingAs($admin)
            ->put(route('admin.heritage-shops.food-items.update', [$firstShop, $firstItem]), [
                'name' => 'Nasi Lemak',
                'is_active' => '1',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(1, $firstShop->foodItems()->count());
        $this->assertSame(1, $secondShop->foodItems()->count());
    }

    public function test_database_unique_key_protects_food_item_name_from_races(): void
    {
        $shop = HeritageShop::query()->create($this->draftPayload('Food Race Shop'));
        $shop->foodItems()->create(['name' => 'Kuih Lapis']);

        $this->expectException(QueryException::class);
        $shop->foodItems()->create(['name' => '  kuih   lapis ']);
    }

    public function test_food_item_update_cannot_take_another_item_name_and_deleted_names_can_be_reused(): void
    {
        $admin = $this->admin();
        $shop = HeritageShop::query()->create($this->draftPayload('Rename Safe Menu'));
        $first = $shop->foodItems()->create(['name' => 'Laksa']);
        $second = $shop->foodItems()->create(['name' => 'Nasi Lemak']);

        $this->withCsrf()->actingAs($admin)
            ->put(route('admin.heritage-shops.food-items.update', [$shop, $first]), ['name' => ' nasi   lemak '])
            ->assertSessionHasErrors('name');
        $this->assertSame('Laksa', $first->fresh()->name);

        $this->withCsrf()->actingAs($admin)
            ->delete(route('admin.heritage-shops.food-items.destroy', [$shop, $second]))
            ->assertSessionHasNoErrors();
        $this->withCsrf()->actingAs($admin)
            ->post(route('admin.heritage-shops.food-items.store', $shop), ['name' => 'NASI LEMAK'])
            ->assertSessionHasNoErrors();

        $this->assertSame(2, $shop->foodItems()->count());
    }

    public function test_stale_administrator_update_is_rejected_and_input_is_retained(): void
    {
        $admin = $this->admin();
        $shop = HeritageShop::query()->create($this->draftPayload('Concurrent Cafe'));
        $staleVersion = $shop->version;

        $shop->update(['heritage_story' => 'Saved by another administrator.']);

        $this->withCsrf()->actingAs($admin)
            ->from(route('admin.heritage-shops.edit', $shop))
            ->put(route('admin.heritage-shops.update', $shop), [
                ...$this->draftPayload('My Unsaved Concurrent Name'),
                'version' => $staleVersion,
            ])
            ->assertRedirect(route('admin.heritage-shops.edit', $shop))
            ->assertSessionHasErrors([
                'version' => 'This shop was updated by another administrator. Review the latest version before saving your changes.',
            ])
            ->assertSessionHasInput('shop_name', 'My Unsaved Concurrent Name');

        $this->assertDatabaseHas('heritage_shops', [
            'id' => $shop->id,
            'shop_name' => 'Concurrent Cafe',
            'heritage_story' => 'Saved by another administrator.',
        ]);
    }

    public function test_heritage_admin_actions_are_audited_without_sensitive_payloads(): void
    {
        Storage::fake('public');
        $admin = $this->admin();

        $this->withCsrf()->actingAs($admin)->post(route('admin.heritage-shops.store'), [
            ...$this->draftPayload('Audited Cafe'),
            'images' => [UploadedFile::fake()->image('audit.jpg', 100, 100)],
        ]);
        $shop = HeritageShop::query()->where('shop_name', 'Audited Cafe')->firstOrFail();

        $this->withCsrf()->actingAs($admin)->put(route('admin.heritage-shops.update', $shop), [
            ...$this->publishedPayload('Audited Cafe'),
            'version' => $shop->version,
        ])->assertSessionHasNoErrors();

        $this->withCsrf()->actingAs($admin)->post(route('admin.heritage-shops.food-items.store', $shop), [
            'name' => 'Audited Dish',
            'is_active' => '1',
        ])->assertSessionHasNoErrors();
        $foodItem = $shop->foodItems()->firstOrFail();

        $this->withCsrf()->actingAs($admin)->put(route('admin.heritage-shops.food-items.update', [$shop, $foodItem]), [
            'name' => 'Audited Dish Updated',
            'is_active' => '1',
        ])->assertSessionHasNoErrors();
        $this->withCsrf()->actingAs($admin)->delete(route('admin.heritage-shops.food-items.destroy', [$shop, $foodItem]))->assertSessionHasNoErrors();
        $this->withCsrf()->actingAs($admin)->delete(route('admin.heritage-shops.destroy', $shop))->assertSessionHasNoErrors();

        foreach ([
            'heritage_shop.created',
            'heritage_shop.updated',
            'heritage_shop.published',
            'heritage_shop.deleted',
            'heritage_food_item.created',
            'heritage_food_item.updated',
            'heritage_food_item.deleted',
        ] as $action) {
            $this->assertDatabaseHas('heritage_admin_audits', [
                'user_id' => $admin->id,
                'action' => $action,
            ]);
        }

        $this->assertFalse(collect(HeritageAdminAudit::query()->get())->contains(
            fn (HeritageAdminAudit $audit): bool => array_key_exists('password', $audit->new_values ?? [])
                || array_key_exists('token', $audit->new_values ?? []),
        ));
    }

    public function test_heritage_text_is_escaped_and_sql_looking_search_is_bound_safely(): void
    {
        Storage::fake('public');
        $admin = $this->admin();
        $shopName = "O'Reilly ' OR 1=1 -- <script>alert(1)</script>";
        $story = '"><img src=x onerror=alert(1)>';

        $this->withCsrf()->actingAs($admin)->post(route('admin.heritage-shops.store'), [
            ...$this->publishedPayload($shopName),
            'heritage_story' => $story,
            'images' => [UploadedFile::fake()->image('safe.jpg', 100, 100)],
        ])->assertSessionHasNoErrors();

        $shop = HeritageShop::query()->where('shop_name', $shopName)->firstOrFail();
        $response = $this->actingAs(User::factory()->create())
            ->get(route('heritage-shops.show', ['id' => $shop->id]));
        $response->assertOk()
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertSee('&quot;&gt;&lt;img src=x onerror=alert(1)&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertDontSee('<img src=x onerror=alert(1)>', false);

        $this->get(route('heritage-shops.index', ['search' => "' OR 1=1 --"]))
            ->assertOk();
        $this->assertDatabaseCount('heritage_shops', 1);
    }

    public function test_unsafe_legacy_source_url_is_not_rendered_as_a_link(): void
    {
        $shop = HeritageShop::query()->create([
            ...$this->publishedPayload('Legacy Unsafe Source'),
            'source_url' => 'javascript:alert(1)',
        ]);

        $this->actingAs(User::factory()->create())
            ->get(route('heritage-shops.show', ['id' => $shop->id]))
            ->assertOk()
            ->assertDontSee('href="javascript:alert(1)"', false);
    }

    public function test_public_listing_paginates_twenty_records_and_preserves_filters(): void
    {
        foreach (range(1, 25) as $index) {
            HeritageShop::query()->create([
                ...$this->publishedPayload('Pagination Shop '.str_pad((string) $index, 2, '0', STR_PAD_LEFT)),
                'primary_food_category' => 'Noodles',
                'state' => 'Penang',
            ]);
        }

        $response = $this->get(route('heritage-shops.index', [
            'search' => 'Pagination Shop',
            'category' => 'Noodles',
            'state' => 'Penang',
            'sort' => 'name_desc',
        ]));

        $response->assertOk();
        $shops = $response->viewData('shops');
        $this->assertSame(20, $shops->count());
        $this->assertSame(25, $shops->total());
        $this->assertStringContainsString('category=Noodles', $shops->nextPageUrl());
        $this->assertStringContainsString('state=Penang', $shops->nextPageUrl());
    }

    public function test_public_search_and_filters_match_saved_name_keyword_category_and_state(): void
    {
        HeritageShop::query()->create([
            ...$this->publishedPayload('Needle Heritage Cafe'),
            'heritage_story' => 'A rare charcoal technique is preserved here.',
            'primary_food_category' => 'Noodles',
            'state' => 'Penang',
        ]);
        HeritageShop::query()->create([
            ...$this->publishedPayload('Other Heritage Cafe'),
            'heritage_story' => 'A different family story.',
            'primary_food_category' => 'Kuih',
            'state' => 'Johor',
        ]);

        foreach (['Needle', 'charcoal'] as $search) {
            $this->get(route('heritage-shops.index', ['search' => $search]))
                ->assertOk()
                ->assertSee('Needle Heritage Cafe')
                ->assertDontSee('Other Heritage Cafe');
        }

        $this->get(route('heritage-shops.index', ['category' => 'Noodles', 'state' => 'Penang']))
            ->assertOk()
            ->assertSee('Needle Heritage Cafe')
            ->assertDontSee('Other Heritage Cafe');
    }

    public function test_profile_queries_only_the_selected_shop_and_never_calls_an_external_source(): void
    {
        foreach (range(1, 30) as $index) {
            HeritageShop::query()->create([
                ...$this->publishedPayload('Profile Query Shop '.$index),
                'source_url' => 'https://example.com/shops/'.$index,
            ]);
        }
        $selected = HeritageShop::query()->firstOrFail();
        $selected->foodItems()->create(['name' => 'Saved Dish', 'is_active' => true]);
        Http::fake();
        $queries = [];
        DB::listen(function (QueryExecuted $query) use (&$queries): void {
            $sql = strtolower($query->sql);
            if (str_starts_with(ltrim($sql), 'select') && str_contains($sql, 'heritage_shops')) {
                $queries[] = $sql;
            }
        });

        $this->actingAs(User::factory()->create())
            ->get(route('heritage-shops.show', ['id' => $selected->id]))
            ->assertOk()
            ->assertSee('Saved Dish');

        $this->assertCount(1, $queries);
        Http::assertNothingSent();
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'status' => 'active']);
    }

    private function withCsrf(): self
    {
        $token = 'heritage-production-consistency-token';

        return $this->withSession(['_token' => $token])->withHeader('X-CSRF-TOKEN', $token);
    }

    private function draftPayload(
        string $name,
        string $address = '1 Heritage Lane',
        string $city = 'Kuala Lumpur',
        string $state = 'Kuala Lumpur',
    ): array {
        return [
            'shop_name' => $name,
            'address' => $address,
            'city' => $city,
            'state' => $state,
            'publish_status' => HeritageShop::STATUS_DRAFT,
        ];
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
}
