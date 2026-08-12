<?php

namespace Tests\Feature;

use Tests\TestCase;

class BlindBoxLandingPageTest extends TestCase
{
    public function test_homepage_shows_blind_box_landing_page(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Blind Box Recommendation');
    }

    public function test_draw_endpoint_returns_a_surprise_shop(): void
    {
        $response = $this->postJson('/blind-box/draw');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'shop' => ['name', 'description', 'category', 'state'],
        ]);
    }
}
