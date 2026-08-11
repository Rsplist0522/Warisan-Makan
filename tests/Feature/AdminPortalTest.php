<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_visiting_admin_is_redirected_to_login(): void
    {
        $this->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_public_login_only_shows_user_google_sign_in(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('User sign in')
            ->assertSee('Sign in with Google')
            ->assertDontSee('Administrator access')
            ->assertDontSee('password123');
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
            ->assertSee('Heritage Registry')
            ->assertSee('Coming soon')
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
            ->assertSee('Review Submission')
            ->assertSee('Admin History')
            ->assertSee('Heritage Registry')
            ->assertSee('soon');
    }

    public function test_placeholder_modules_show_a_coming_soon_state(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.modules.show', 'food-map'))
            ->assertOk()
            ->assertSee('Food Map')
            ->assertSee('Coming soon')
            ->assertSee('planned map and discovery module');
    }

    public function test_database_seeder_creates_community_contribution_demo_records(): void
    {
        $this->seed();

        $this->assertDatabaseHas('heritage_shop_contributions', [
            'shop_name' => 'Hameediyah Restaurant',
            'establishment_year' => 1907,
            'status' => 'pending_review',
        ]);
        $this->assertDatabaseHas('heritage_shop_contributions', [
            'shop_name' => 'Sek Yuen Restaurant',
            'establishment_year' => 1948,
            'status' => 'approved',
        ]);
        $this->assertDatabaseHas('heritage_shop_contributions', [
            'shop_name' => 'Durbar at FMS',
            'establishment_year' => 1906,
            'status' => 'rejected',
        ]);
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
