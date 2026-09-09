<?php

namespace Tests\Feature;

use App\Models\HeritageShop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FoodPassportWhatsappSharingTest extends TestCase
{
    use RefreshDatabase;

    public function test_food_passport_badge_pages_offer_native_mobile_sharing_with_whatsapp_fallbacks(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $shop = HeritageShop::query()->create([
            'shop_name' => 'PWA Share Test Shop',
            'publish_status' => HeritageShop::STATUS_PUBLISHED,
        ]);

        foreach ([
            route('passport.index'),
            route('passport.statistics'),
            route('passport.shop', $shop),
        ] as $url) {
            $response = $this->actingAs($user)->get($url);

            $response->assertOk()
                ->assertSee('Share to WhatsApp')
                ->assertSee('navigator.share', false)
                ->assertSee('navigator.canShare({ files: [file] })', false)
                ->assertSee('https://wa.me/?text=', false)
                ->assertSee('https://web.whatsapp.com/send?text=', false);
        }
    }
}
