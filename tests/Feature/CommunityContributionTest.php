<?php

namespace Tests\Feature;

use App\Models\HeritageShopContribution;
use Tests\TestCase;

class CommunityContributionTest extends TestCase
{
    public function test_home_page_displays_the_submission_form(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Submit Heritage Shop');
        $response->assertSee('Heritage story');
    }

    public function test_draft_submission_is_saved_and_redirected(): void
    {
        $response = $this->post(route('community-contribution.store'), [
            'submission_action' => 'draft',
        ]);

        $response->assertRedirect(route('home'));
        $response->assertSessionHas('status', 'Heritage shop draft saved successfully.');
        $this->assertDatabaseHas('heritage_shop_contributions', [
            'status' => HeritageShopContribution::STATUS_DRAFT,
        ]);
    }
}
