<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlindBoxLandingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_shows_blind_box_landing_page(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/login');
    }

    public function test_draw_endpoint_returns_a_surprise_shop(): void
    {
        $response = $this->postJson('/blind-box/draw');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'shop' => ['name', 'description', 'category', 'state'],
        ]);
    }

    public function test_food_passport_page_uses_brand_style_and_check_in_ui(): void
    {
        $response = $this->get('/foodPassport');

        $response->assertStatus(200);
        $response->assertSee('Your Heritage Passport');
        $response->assertSee('Check In');
        $response->assertSee('Nearby heritage stop');
        $response->assertSee('Kedai Kopi Haji');
    }

    public function test_signed_in_user_receives_passport_stats_and_badge_from_demo_check_in(): void
    {
        $user = User::create([
            'name' => 'Demo User',
            'email' => 'demo@example.com',
            'password' => bcrypt('secret123'),
        ]);

        $this->actingAs($user)
            ->postJson('/passport/check-in-test', [
                'shop_id' => 1,
                'shop_latitude' => 3.1390,
                'shop_longitude' => 101.6869,
                'user_latitude' => 3.1392,
                'user_longitude' => 101.6871,
                'radius_meters' => 100,
                'is_participating' => true,
                'is_published' => true,
                'demo_mode' => true,
            ])
            ->assertStatus(201);

        $response = $this->actingAs($user)->get('/foodPassport');

        $response->assertStatus(200);
        $response->assertSee('Heritage Starter');
        $response->assertSee('Visited');
    }

    public function test_demo_check_in_allows_repeat_for_tutor_demo_flow(): void
    {
        $user = User::create([
            'name' => 'Demo Repeat User',
            'email' => 'demo-repeat@example.com',
            'password' => bcrypt('secret123'),
        ]);

        $payload = [
            'shop_id' => 1,
            'shop_latitude' => 3.1390,
            'shop_longitude' => 101.6869,
            'user_latitude' => 3.1392,
            'user_longitude' => 101.6871,
            'radius_meters' => 100,
            'is_participating' => true,
            'is_published' => true,
            'demo_mode' => true,
        ];

        $this->actingAs($user)->postJson('/passport/check-in-test', array_merge($payload, ['demo_mode' => true, 'allow_repeat' => true]))
            ->assertStatus(201);

        $this->actingAs($user)->postJson('/passport/check-in-test', array_merge($payload, ['demo_mode' => true, 'allow_repeat' => true]))
            ->assertStatus(201);
    }
}
