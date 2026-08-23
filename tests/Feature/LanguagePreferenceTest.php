<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Tests\TestCase;

class LanguagePreferenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_saved_language_is_applied_to_a_module_outside_the_dashboard_route_group(): void
    {
        $user = User::factory()->create(['language' => 'zh']);

        $response = $this->actingAs($user)->get('/blind-box');

        $response->assertOk();
        $response->assertSee('传统店铺探索');
        $this->assertSame('zh', App::getLocale());
    }

    public function test_invalid_or_missing_language_uses_english(): void
    {
        $user = User::factory()->create(['language' => 'jp']);

        $this->actingAs($user)->get('/blind-box');

        $this->assertSame('en', App::getLocale());
    }
}
