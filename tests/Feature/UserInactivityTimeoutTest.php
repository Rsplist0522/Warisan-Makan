<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class UserInactivityTimeoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('session.user_inactivity_timeout', 300);
        config()->set('session.admin_inactivity_timeout', 300);
    }

    public function test_active_user_requests_refresh_the_inactivity_timer(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $activeAt = now()->subMinutes(4)->timestamp;

        $response = $this->actingAs($user)
            ->withSession(['user_last_activity' => $activeAt])
            ->get(route('foodtrails.index'));

        $response->assertOk();
        $this->assertGreaterThan($activeAt, session('user_last_activity'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_user_is_logged_out_after_five_minutes_of_inactivity(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($user)
            ->withSession(['user_last_activity' => now()->subMinutes(5)->timestamp])
            ->get(route('foodtrails.index'));

        $response->assertStatus(401)
            ->assertSee('Session Expired')
            ->assertSee('Your session has expired due to inactivity. Please sign in again to continue.')
            ->assertSee('/login', false)
            ->assertDontSee('/admin-login', false)
            ->assertSee('data-session-expired-ok', false);
        $this->assertGuest();
    }

    public function test_active_user_can_open_heritage_shop_listing_and_refreshes_activity(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $activeAt = now()->subMinutes(4)->timestamp;

        $response = $this->actingAs($user)
            ->withSession(['user_last_activity' => $activeAt])
            ->get(route('heritage-shops.index', ['state' => 'Penang']));

        $response->assertOk();
        $this->assertAuthenticatedAs($user);
        $this->assertGreaterThan($activeAt, session('user_last_activity'));
    }

    public function test_expired_user_cannot_enter_heritage_shop_listing_or_filters(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($user)
            ->withSession(['user_last_activity' => now()->subMinutes(5)->timestamp])
            ->get(route('heritage-shops.index', ['state' => 'Penang']));

        $response->assertStatus(401)
            ->assertSee('Session Expired')
            ->assertSee('/login', false);
        $this->assertGuest();
    }

    public function test_guest_mode_can_access_heritage_shop_listing_and_filters(): void
    {
        $this->get(route('guest.continue'))
            ->assertRedirect(route('user.dashboard'));

        $response = $this->get(route('heritage-shops.index', ['state' => 'Penang']));

        $response->assertOk();
        $this->assertGuest();
        $this->assertTrue((bool) session('guest_mode'));
    }

    public function test_stale_user_is_logged_out_from_the_dashboard(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($user)
            ->withSession(['user_last_activity' => now()->subMinutes(5)->timestamp])
            ->get(route('user.dashboard'));

        $response->assertStatus(401)
            ->assertSee('Session Expired')
            ->assertSee('/login', false);
        $this->assertGuest();
    }

    public function test_expired_json_request_returns_unauthorized_response(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($user)
            ->withSession(['user_last_activity' => now()->subMinutes(5)->timestamp])
            ->getJson(route('foodtrails.index'));

        $response->assertUnauthorized();
        $response->assertJsonPath('session_expired', true);
        $response->assertJsonPath('login_url', route('login'));
        $this->assertGuest();
    }

    public function test_activity_heartbeat_refreshes_an_active_user_session(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $activeAt = now()->subMinutes(4)->timestamp;

        $response = $this->actingAs($user)
            ->withSession(['user_last_activity' => $activeAt])
            ->postJson(route('session.activity'));

        $response->assertNoContent();
        $this->assertAuthenticatedAs($user);
        $this->assertGreaterThan($activeAt, session('user_last_activity'));
    }

    public function test_activity_heartbeat_cannot_revive_an_expired_user_session(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($user)
            ->withSession(['user_last_activity' => now()->subMinutes(5)->timestamp])
            ->postJson(route('session.activity'));

        $response->assertUnauthorized()
            ->assertJsonPath('session_expired', true)
            ->assertJsonPath('login_url', route('login'));
        $this->assertGuest();
    }

    public function test_guest_mode_cannot_use_the_authenticated_activity_heartbeat(): void
    {
        $this->get(route('guest.continue'));

        $response = $this->postJson(route('session.activity'));

        $response->assertRedirect(route('login'));
        $this->assertGuest();
        $this->assertTrue((bool) session('guest_mode'));
    }

    public function test_admin_activity_uses_a_separate_timestamp_and_resets_it(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $activeAt = now()->subMinutes(4)->timestamp;
        $userActivity = now()->subMinutes(10)->timestamp;

        $response = $this->actingAs($admin)
            ->withSession([
                'user_last_activity' => $userActivity,
                'admin_last_activity' => $activeAt,
            ])
            ->get(route('admin.dashboard'));

        $response->assertOk();
        $this->assertAuthenticatedAs($admin);
        $this->assertGreaterThan($activeAt, session('admin_last_activity'));
        $this->assertSame($userActivity, session('user_last_activity'));
    }

    public function test_admin_is_logged_out_after_five_minutes_of_inactivity(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)
            ->withSession(['admin_last_activity' => now()->subMinutes(5)->timestamp])
            ->get(route('admin.dashboard'));

        $response->assertStatus(401)
            ->assertSee('Session Expired')
            ->assertSee('/admin-login', false)
            ->assertDontSee('/login', false);
        $this->assertGuest();
    }

    public function test_expired_admin_heartbeat_returns_the_admin_login_url(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)
            ->withSession(['admin_last_activity' => now()->subMinutes(5)->timestamp])
            ->postJson(route('session.activity'));

        $response->assertUnauthorized()
            ->assertJsonPath('session_expired', true)
            ->assertJsonPath('login_url', route('admin.login'));
        $this->assertGuest();
    }

    public function test_remembered_admin_bypasses_inactivity_timeout(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)
            ->withSession([
                'admin_remember' => true,
                'admin_last_activity' => now()->subMinutes(10)->timestamp,
            ])
            ->get(route('admin.dashboard'));

        $response->assertOk();
        $this->assertAuthenticatedAs($admin);
        $this->assertNull(session('admin_last_activity'));
    }

    public function test_remembered_admin_heartbeat_keeps_the_existing_bypass(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)
            ->withSession([
                'admin_remember' => true,
                'admin_last_activity' => now()->subMinutes(10)->timestamp,
            ])
            ->postJson(route('session.activity'));

        $response->assertNoContent();
        $this->assertAuthenticatedAs($admin);
        $this->assertNull(session('admin_last_activity'));
    }

    public function test_guest_mode_is_not_given_an_authenticated_user_timeout(): void
    {
        $response = $this->get(route('guest.continue'));

        $response->assertRedirect(route('user.dashboard'));
        $this->assertGuest();
        $this->assertTrue((bool) session('guest_mode'));
    }

    public function test_guest_mode_can_still_open_the_dashboard(): void
    {
        $response = $this->get(route('guest.continue'));

        $response->assertRedirect(route('user.dashboard'));
        $this->get(route('user.dashboard'))->assertOk();
        $this->assertGuest();
    }
}
