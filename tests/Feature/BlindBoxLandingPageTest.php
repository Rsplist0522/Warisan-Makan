<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\HeritageShop;
use App\Models\PassportStamp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlindBoxLandingPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create(['role' => 'user']));
    }

    public function test_blind_box_page_is_publicly_accessible(): void
    {
        $response = $this->get('/blind-box');

        $response->assertStatus(200);
        $response->assertSee('Blind Box Recommendation');
    }

    public function test_period_banner_is_displayed(): void
    {
        $response = $this->get('/blind-box');

        $response->assertStatus(200);
        $response->assertSee('One surprise draw per period');
    }

    public function test_draw_endpoint_returns_a_surprise_shop(): void
    {
        $response = $this->postJson('/blind-box/draw');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'shop' => ['name', 'description', 'category', 'state', 'year', 'image'],
            'period',
        ]);

        $category = $response->json('shop.category');
        $this->assertContains($category, ['Main Dishes', 'Desserts', 'Drinks']);
    }

    public function test_second_draw_in_same_period_is_limited(): void
    {
        $this->postJson('/blind-box/draw')->assertStatus(200);
        $this->postJson('/blind-box/draw')
            ->assertStatus(429)
            ->assertJsonStructure(['error']);
    }

    public function test_state_filter_narrows_the_shop_list(): void
    {
        $response = $this->get('/blind-box?state=Penang');

        $response->assertStatus(200);
        $response->assertSee('Nasi Lemak Seri Warisan');
        $response->assertDontSee('Kampung Kuih Mak Cik');
    }

    public function test_category_filter_narrows_the_shop_list(): void
    {
        $response = $this->get('/blind-box?category=Desserts');

        $response->assertStatus(200);
        $response->assertSee('Kampung Kuih Mak Cik');
        $response->assertDontSee('Nasi Lemak Seri Warisan');
    }

    public function test_food_passport_page_uses_brand_style_and_check_in_ui(): void
    {
        $this->createShop(['shop_name' => 'Real Heritage Kitchen']);

        $response = $this->get('/foodPassport');

        $response->assertStatus(200);
        $response->assertSee('Your Heritage Passport');
        $response->assertSee('Check In');
        $response->assertSee('Nearby heritage stop');
        $response->assertSee('Real Heritage Kitchen');
        $response->assertDontSee('Radius (m)');
        $response->assertDontSee('Shop ID');
    }

    public function test_food_passport_sidebar_uses_check_in_and_statistics_labels(): void
    {
        $this->createShop(['shop_name' => 'Sidebar Heritage Kitchen']);

        $response = $this->get('/foodPassport');

        $response->assertStatus(200);
        $response->assertSee('data-hash-target="check-in"', false);
        $response->assertSee('data-hash-target="passport-progress"', false);
        $response->assertSee('data-hash-target="leaderboard"', false);
        $response->assertSee('Passport Statistics', false);
        $response->assertSee('Leaderboard', false);
    }

    public function test_signed_in_user_receives_passport_stats_and_badge_from_real_shop_check_in(): void
    {
        $shop = $this->createShop();
        $user = User::create([
            'name' => 'Passport User',
            'email' => 'passport@example.com',
            'password' => bcrypt('secret123'),
        ]);

        $this->actingAs($user)
            ->postJson('/passport/check-in', [
                'shop_id' => $shop->id,
                'user_latitude' => 3.1392,
                'user_longitude' => 101.6871,
            ])
            ->assertStatus(201);

        $response = $this->actingAs($user)->get('/foodPassport');

        $response->assertStatus(200);
        $response->assertSee('Heritage Starter');
        $response->assertSee('Visited');
    }

    public function test_real_check_in_rejects_a_duplicate_visit(): void
    {
        $shop = $this->createShop();
        $user = User::create([
            'name' => 'Repeat User',
            'email' => 'repeat@example.com',
            'password' => bcrypt('secret123'),
        ]);

        $payload = [
            'shop_id' => $shop->id,
            'user_latitude' => 3.1392,
            'user_longitude' => 101.6871,
        ];

        $this->actingAs($user)->postJson('/passport/check-in', $payload)
            ->assertStatus(201);

        $this->actingAs($user)->postJson('/passport/check-in', $payload)
            ->assertStatus(409);
    }

    public function test_visited_locations_are_paginated_in_pages_of_five(): void
    {
        $user = User::create([
            'name' => 'History User',
            'email' => 'history@example.com',
            'password' => bcrypt('secret123'),
        ]);

        for ($index = 1; $index <= 6; $index++) {
            $shop = $this->createShop(['shop_name' => 'Heritage Shop ' . $index]);
            PassportStamp::create([
                'user_id' => $user->id,
                'shop_id' => $shop->id,
                'stamp_datetime' => now()->subMinutes($index),
                'gps_latitude' => 3.1392,
                'gps_longitude' => 101.6871,
            ]);
        }

        $firstPage = $this->actingAs($user)->get('/foodPassport');
        $this->assertSame(5, substr_count($firstPage->getContent(), 'data-visited-location'));
        $firstPage->assertSee('Page 1');

        $secondPage = $this->actingAs($user)->get('/foodPassport?visited_page=2');
        $this->assertSame(1, substr_count($secondPage->getContent(), 'data-visited-location'));
        $secondPage->assertSee('Heritage Shop 6');
    }

    private function createShop(array $attributes = []): HeritageShop
    {
        return HeritageShop::create(array_merge([
            'shop_name' => 'Kedai Real',
            'founder_name' => 'Real Founder',
            'latitude' => 3.1390,
            'longitude' => 101.6869,
            'publish_status' => 'published',
        ], $attributes));
    }
}
