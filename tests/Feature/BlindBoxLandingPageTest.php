<?php

namespace Tests\Feature;

use App\Models\User;
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

        // Category must be one of the 3-value set: Main Dishes / Desserts / Drinks
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
        // Sample data includes a Penang shop (Nasi Lemak Seri Warisan).
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
}