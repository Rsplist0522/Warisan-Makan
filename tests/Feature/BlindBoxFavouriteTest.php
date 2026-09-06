<?php

namespace Tests\Feature;

use App\Models\BlindBoxDraw;
use App\Models\BlindBoxFavourite;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlindBoxFavouriteTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_save_a_draw_and_view_it_in_blind_box_favourites(): void
    {
        $user = User::factory()->create();
        $draw = BlindBoxDraw::create([
            'user_id' => $user->id, 'period' => 'morning', 'period_date' => today(),
            'shop_name' => 'Saved Heritage Cafe', 'category' => 'Drinks', 'state' => 'Penang',
        ]);

        $this->actingAs($user)
            ->postJson(route('blind-box.favourites.store'), ['draw_id' => $draw->id])
            ->assertOk()
            ->assertJsonPath('message', 'Saved to favourites.');

        $this->assertDatabaseHas('blind_box_favourites', [
            'user_id' => $user->id,
            'shop_name' => 'Saved Heritage Cafe',
            'removed_at' => null,
        ]);

        $this->actingAs($user)->get(route('blind-box.favourites'))
            ->assertOk()
            ->assertSee('Saved Heritage Cafe')
            ->assertSee('Explore Trails')
            ->assertSee('View Details');
    }

    public function test_favourite_can_be_removed_and_saved_again(): void
    {
        $user = User::factory()->create();
        $draw = BlindBoxDraw::create([
            'user_id' => $user->id, 'period' => 'afternoon', 'period_date' => today(),
            'shop_name' => 'One Way Favourite',
        ]);
        $favourite = BlindBoxFavourite::create([
            'user_id' => $user->id, 'blind_box_draw_id' => $draw->id,
            'shop_name' => $draw->shop_name,
        ]);

        $this->actingAs($user)
            ->delete(route('blind-box.favourites.destroy', $favourite))
            ->assertRedirect(route('blind-box.favourites'));

        $this->actingAs($user)
            ->postJson(route('blind-box.favourites.store'), ['draw_id' => $draw->id])
            ->assertOk()
            ->assertJsonPath('message', 'Saved to favourites.')
            ->assertJsonPath('is_favourited', true);

        $this->assertDatabaseHas('blind_box_favourites', [
            'user_id' => $user->id,
            'shop_name' => $draw->shop_name,
            'removed_at' => null,
        ]);
    }

    public function test_saving_an_active_favourite_removes_it(): void
    {
        $user = User::factory()->create();
        $draw = BlindBoxDraw::create([
            'user_id' => $user->id, 'period' => 'evening', 'period_date' => today(),
            'shop_name' => 'Toggle Favourite',
        ]);

        $this->actingAs($user)
            ->postJson(route('blind-box.favourites.store'), ['draw_id' => $draw->id])
            ->assertJsonPath('is_favourited', true);

        $this->actingAs($user)
            ->postJson(route('blind-box.favourites.store'), ['draw_id' => $draw->id])
            ->assertOk()
            ->assertJsonPath('message', 'Removed from favourites.')
            ->assertJsonPath('is_favourited', false);

        $this->assertDatabaseMissing('blind_box_favourites', [
            'user_id' => $user->id,
            'shop_name' => $draw->shop_name,
            'removed_at' => null,
        ]);
    }
}
