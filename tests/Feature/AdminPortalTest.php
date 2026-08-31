<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Badge;
use App\Models\UserBadge;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_visiting_admin_is_redirected_to_login(): void
    {
        $this->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_public_login_shows_google_and_guest_options(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('User sign in')
            ->assertSee('Sign in with Google')
            ->assertSee('Continue as Guest')
            ->assertDontSee('Administrator access')
            ->assertDontSee('password123');
    }

    public function test_guest_option_enters_the_existing_user_dashboard(): void
    {
        $this->get(route('guest.continue'))
            ->assertRedirect(route('user.dashboard'))
            ->assertSessionHas('guest_mode', true);

        $this->get(route('user.dashboard'))
            ->assertOk()
            ->assertSee('Guest Mode')
            ->assertSee('Community Contribution')
            ->assertSee('Heritage Shop Tracking')
            ->assertSee('Food Passport & Achievement')
            ->assertSee('Food Trail & Navigation')
            ->assertSee('Blind Box Recommendation');
    }

    public function test_admin_login_page_is_separate_and_does_not_show_default_credentials(): void
    {
        $this->get(route('admin.login'))
            ->assertOk()
            ->assertSee('Admin sign in')
            ->assertSee('Username')
            ->assertDontSee('Sign in with Google')
            ->assertDontSee('password123');
    }

    public function test_database_seeder_creates_the_default_admin_credentials(): void
    {
        $this->seed();

        $admin = User::where('username', 'admin')->firstOrFail();

        $this->assertSame('admin', $admin->role);
        $this->assertTrue(Hash::check('password123', $admin->password));
    }

    public function test_default_admin_can_log_in_and_open_the_dashboard(): void
    {
        $this->seed();

        $response = $this->post(route('admin.login.submit'), [
            'username' => 'admin',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs(User::where('username', 'admin')->firstOrFail());

        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Community Contribution')
            ->assertSee('Food Passport')
            ->assertSee('Food Trails')
            ->assertDontSee('Coming soon')
            ->assertDontSee('Review Queue')
            ->assertSee(route('admin.community-contributions.submissions'), false);
    }

    public function test_admin_navigation_scopes_review_functions_to_community_contribution(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.community-contributions.submissions'))
            ->assertOk()
            ->assertSee('Community Contribution')
            ->assertSee('Review Queue')
            ->assertDontSee('Review Submission')
            ->assertSee('Correction Requests')
            ->assertSee('Admin History')
            ->assertSee('Food Passport')
            ->assertDontSee('Coming soon');
    }

    public function test_admin_can_open_the_food_passport_badge_manager(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.badges.index'))
            ->assertOk()
            ->assertSee('Food Passport')
            ->assertSee('Achievement badges');
    }

    public function test_admin_dashboard_lists_user_management_module(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Users & Roles')
            ->assertSee(route('admin.users.index'), false);
    }

    public function test_admin_can_activate_and_deactivate_regular_users(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);

        $this->actingAs($admin)
            ->post(route('admin.users.toggle-status', $user))
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('status', 'User account deactivated.');

        $this->assertSame('inactive', $user->fresh()->status);
        $this->assertNotNull($user->fresh()->deactivated_at);

        $this->actingAs($admin)
            ->post(route('admin.users.toggle-status', $user))
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('status', 'User account activated.');

        $this->assertSame('active', $user->fresh()->status);
        $this->assertNull($user->fresh()->deactivated_at);
    }

    public function test_admin_can_create_edit_and_toggle_an_unawarded_badge(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('admin.badges.store'), [
                'badge_name' => 'First Story',
                'description' => 'Visit one heritage shop',
                'icon' => '★',
                'criteria_type' => 'visits',
                'criteria_value' => 1,
                'points' => 10,
                'is_active' => 1,
            ])
            ->assertRedirect();

        $badge = Badge::where('badge_name', 'First Story')->firstOrFail();
        $this->assertTrue((bool) $badge->is_active);

        $this->actingAs($admin)
            ->put(route('admin.badges.update', $badge), [
                'badge_name' => 'First Heritage Story',
                'description' => 'Visit one heritage shop',
                'icon' => '✦',
                'criteria_type' => 'visits',
                'criteria_value' => 1,
                'points' => 15,
                'is_active' => 1,
            ])
            ->assertRedirect(route('admin.badges.edit', $badge));

        $this->assertSame('First Heritage Story', $badge->fresh()->badge_name);

        $this->actingAs($admin)
            ->patch(route('admin.badges.toggle', $badge))
            ->assertRedirect(route('admin.badges.index'))
            ->assertSessionHas('status', 'Badge deactivated successfully.');

        $this->assertFalse((bool) $badge->fresh()->is_active);

        $this->actingAs($admin)
            ->patch(route('admin.badges.toggle', $badge))
            ->assertSessionHas('status', 'Badge activated successfully.');

        $this->assertTrue((bool) $badge->fresh()->is_active);
    }

    public function test_admin_cannot_deactivate_an_awarded_badge(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $badge = Badge::create([
            'badge_name' => 'Awarded Badge',
            'description' => 'Already earned',
            'criteria_type' => 'visits',
            'criteria_value' => 1,
            'points' => 10,
            'is_active' => true,
        ]);
        UserBadge::create(['user_id' => $user->id, 'badge_id' => $badge->badge_id, 'earned_at' => now()]);

        $this->actingAs($admin)
            ->patch(route('admin.badges.toggle', $badge))
            ->assertRedirect(route('admin.badges.index'))
            ->assertSessionHasErrors(['badge' => 'Deactivation failed. Badge has already been awarded to users']);

        $this->assertTrue((bool) $badge->fresh()->is_active);
    }

    public function test_blocked_user_cannot_access_member_routes(): void
    {
        $user = User::factory()->create(['role' => 'user', 'status' => 'inactive']);

        $this->actingAs($user)
            ->get(route('home'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('login');
    }

    public function test_profile_photo_upload_uses_public_storage_url_for_profile_and_dashboard(): void
    {
        Storage::fake('public');

        $user = User::factory()->create([
            'name' => 'Aisha Noor',
            'role' => 'user',
            'status' => 'active',
        ]);

        $imagePath = tempnam(sys_get_temp_dir(), 'profile-test-');
        file_put_contents($imagePath, base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p9sAAAAASUVORK5CYII='
        ));
        $file = new UploadedFile($imagePath, 'profile.png', 'image/png', null, true);

        $this->actingAs($user)
            ->from(route('profile.edit'))
            ->post(route('profile.update'), [
                'name' => 'Aisha Noor',
                'email' => $user->email,
                'phone' => '0123456789',
                'city' => 'Kuala Lumpur',
                'bio' => 'Food heritage enthusiast',
                'profile_photo' => $file,
            ])
            ->assertRedirect(route('profile.show'));

        $user->refresh();

        $this->assertNotNull($user->profile_photo);
        $this->assertStringContainsString('/storage/', $user->profilePhotoUrl());

        $this->get(route('profile.show'))
            ->assertOk()
            ->assertSee('/storage/', false);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('/storage/', false);
    }

    public function test_database_seeder_only_creates_the_default_admin_account(): void
    {
        $this->seed();

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('heritage_shop_contributions', 0);
        $this->assertDatabaseCount('heritage_shops', 0);
    }

    public function test_invalid_admin_credentials_are_rejected(): void
    {
        $this->seed();

        $this->from(route('admin.login'))
            ->post(route('admin.login.submit'), [
                'username' => 'admin',
                'password' => 'wrong-password',
            ])
            ->assertRedirect(route('admin.login'))
            ->assertSessionHasErrors('username');

        $this->assertGuest();
    }

    public function test_non_admin_cannot_use_the_admin_credentials_form(): void
    {
        User::factory()->create([
            'username' => 'regular-user',
            'password' => 'regular-password',
            'role' => 'user',
        ]);

        $this->post(route('admin.login.submit'), [
            'username' => 'regular-user',
            'password' => 'regular-password',
        ])->assertSessionHasErrors('username');

        $this->assertGuest();
    }

    public function test_authenticated_non_admin_cannot_open_admin_login_page(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)
            ->get(route('admin.login'))
            ->assertForbidden();
    }

    public function test_authenticated_non_admin_cannot_open_the_dashboard(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    public function test_authenticated_admin_is_kept_out_of_the_user_dashboard(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('home'))
            ->assertRedirect(route('admin.dashboard'));
    }
}
