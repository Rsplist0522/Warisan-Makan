<?php

namespace Tests\Feature;

use App\Models\SiteBranding;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BrandingLogoTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_logo_is_served_and_used_by_shared_sidebars(): void
    {
        SiteBranding::create([
            'logo_data' => base64_encode('test-logo-data'),
            'logo_mime_type' => 'image/png',
            'logo_filename' => 'warisan-logo.png',
        ]);

        $this->get(route('brand.logo'))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png')
            ->assertContent('test-logo-data');

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee(route('brand.logo'), false);

        $user = User::factory()->create(['role' => 'user']);
        $this->actingAs($user)
            ->get(route('home'))
            ->assertOk()
            ->assertSee(route('brand.logo'), false);
    }
}
