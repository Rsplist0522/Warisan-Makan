<?php

namespace Tests\Feature;

use Tests\TestCase;

class BlindBoxLandingPageTest extends TestCase
{
    public function test_blind_box_page_is_publicly_accessible(): void
    {
        $response = $this->get('/blind-box');

        $response->assertStatus(200);
        $response->assertSee('Blind Box Recommendation');
    }

    public function test_draw_endpoint_returns_a_surprise_shop(): void
    {
        $response = $this->postJson('/blind-box/draw');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'shop' => ['name', 'description', 'category', 'state', 'year', 'image'],
        ]);
    }

    public function test_second_draw_in_same_period_is_limited(): void
    {
        $this->postJson('/blind-box/draw')->assertStatus(200);
        $this->postJson('/blind-box/draw')
            ->assertStatus(429)
            ->assertJsonStructure(['error']);
    }

    public function test_history_page_is_accessible(): void
    {
        $response = $this->get('/blind-box/history');

        $response->assertStatus(200);
        $response->assertSee('Blind Box History');
    }
}
