<?php

namespace Tests\Feature;

use App\Models\HeritageShop;
use App\Models\HeritageShopContribution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CommunityContributionTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('home'))->assertRedirect(route('login'));
    }

    public function test_submission_form_uses_internal_css_and_displays_media_field(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('home'));

        $response->assertOk();
        $response->assertSee('Submit Heritage Shop');
        $response->assertSee('Heritage story');
        $response->assertSee('name="supporting_media[]"', false);
        $response->assertDontSee('Admin dashboard');
        $response->assertDontSee('Admin history');
        $response->assertSee('<style>', false);
    }

    public function test_draft_submission_is_saved_for_the_authenticated_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('community-contribution.store'), [
            'submission_action' => 'draft',
            'shop_name' => 'Capital Café',
        ]);

        $response->assertRedirect(route('community-contribution.drafts'));
        $this->assertDatabaseHas('heritage_shop_contributions', [
            'user_id' => $user->id,
            'shop_name' => 'Capital Café',
            'status' => HeritageShopContribution::STATUS_DRAFT,
        ]);
        $this->assertDatabaseCount('contribution_versions', 1);
    }

    public function test_complete_submission_uploads_media_and_stores_database_record(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $data = $this->validContributionData();
        $data['supporting_media'] = [UploadedFile::fake()->image('capital-cafe.jpg')];

        $response = $this->actingAs($user)->post(route('community-contribution.store'), $data);

        $contribution = HeritageShopContribution::firstOrFail();
        $response->assertRedirect(route('community-contribution.contributions.show', $contribution));
        $this->assertSame(HeritageShopContribution::STATUS_PENDING_REVIEW, $contribution->status);
        $this->assertSame($user->id, $contribution->user_id);
        $this->assertNotNull($contribution->submitted_at);
        $this->assertCount(1, $contribution->supporting_media);
        $this->assertTrue(
            Storage::disk('public')->exists($contribution->supporting_media[0]),
            'The uploaded supporting media file was not stored on the public disk.'
        );
    }

    public function test_user_can_edit_and_submit_a_complete_draft(): void
    {
        $user = User::factory()->create();
        $draft = HeritageShopContribution::create([
            ...$this->modelContributionData(),
            'user_id' => $user->id,
            'status' => HeritageShopContribution::STATUS_DRAFT,
        ]);

        $this->actingAs($user)
            ->put(route('community-contribution.update', $draft), [
                ...$this->validContributionData(),
                'shop_name' => 'Updated Capital Café',
            ])
            ->assertRedirect(route('community-contribution.contributions.show', $draft));

        $draft->refresh();
        $this->assertSame('Updated Capital Café', $draft->shop_name);
        $this->assertSame(HeritageShopContribution::STATUS_PENDING_REVIEW, $draft->status);
        $this->assertDatabaseCount('contribution_versions', 1);
    }

    public function test_user_cannot_open_another_users_draft(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $draft = HeritageShopContribution::create([
            'user_id' => $owner->id,
            'status' => HeritageShopContribution::STATUS_DRAFT,
        ]);

        $this->actingAs($otherUser)
            ->get(route('community-contribution.edit', $draft))
            ->assertForbidden();
    }

    public function test_user_can_withdraw_only_before_review_starts(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);
        $contribution = HeritageShopContribution::create([
            ...$this->modelContributionData(),
            'user_id' => $user->id,
            'status' => HeritageShopContribution::STATUS_PENDING_REVIEW,
            'submitted_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.community-contributions.start-review', $contribution))
            ->assertSessionHasNoErrors();

        $this->actingAs($user)
            ->post(route('community-contribution.contributions.withdraw', $contribution))
            ->assertSessionHasErrors('withdraw');

        $this->assertDatabaseHas('heritage_shop_contributions', [
            'id' => $contribution->id,
            'status' => HeritageShopContribution::STATUS_UNDER_REVIEW,
        ]);
    }

    public function test_user_can_withdraw_a_pending_submission(): void
    {
        $user = User::factory()->create();
        $contribution = HeritageShopContribution::create([
            ...$this->modelContributionData(),
            'user_id' => $user->id,
            'status' => HeritageShopContribution::STATUS_PENDING_REVIEW,
            'submitted_at' => now(),
        ]);

        $this->actingAs($user)
            ->post(route('community-contribution.contributions.withdraw', $contribution))
            ->assertRedirect(route('community-contribution.contributions.show', $contribution));

        $this->assertDatabaseHas('heritage_shop_contributions', [
            'id' => $contribution->id,
            'status' => HeritageShopContribution::STATUS_WITHDRAWN,
        ]);
        $this->assertDatabaseHas('moderation_activities', [
            'heritage_shop_contribution_id' => $contribution->id,
            'action' => 'withdrawn_by_user',
        ]);
    }

    public function test_administrator_can_request_revision_and_user_is_notified(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);
        $contribution = HeritageShopContribution::create([
            ...$this->modelContributionData(),
            'user_id' => $user->id,
            'status' => HeritageShopContribution::STATUS_PENDING_REVIEW,
            'submitted_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.community-contributions.start-review', $contribution));
        $this->actingAs($admin)
            ->post(route('admin.community-contributions.moderate', $contribution), [
                'moderation_action' => 'request_revision',
                'feedback' => 'Please add clearer information about the founder.',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('heritage_shop_contributions', [
            'id' => $contribution->id,
            'status' => HeritageShopContribution::STATUS_REVISION_REQUIRED,
        ]);
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $user->id,
        ]);
    }

    public function test_administrator_can_approve_and_create_a_heritage_shop_record(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);
        $contribution = HeritageShopContribution::create([
            ...$this->modelContributionData(),
            'user_id' => $user->id,
            'status' => HeritageShopContribution::STATUS_UNDER_REVIEW,
            'reviewed_by_user_id' => $admin->id,
            'review_started_at' => now(),
            'submitted_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.community-contributions.moderate', $contribution), [
                'moderation_action' => 'approve',
                'feedback' => 'Information verified.',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('heritage_shop_contributions', [
            'id' => $contribution->id,
            'status' => HeritageShopContribution::STATUS_APPROVED,
            'approved_by_user_id' => $admin->id,
        ]);
        $this->assertDatabaseHas('heritage_shops', [
            'source_contribution_id' => $contribution->id,
            'shop_name' => 'Capital Café',
        ]);
        $this->assertSame(1, HeritageShop::count());
    }

    public function test_non_administrator_cannot_access_admin_functions(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.community-contributions.submissions'))
            ->assertForbidden();
    }

    private function validContributionData(): array
    {
        return [
            'submission_action' => 'submit',
            'contribution_title' => 'Capital Café heritage contribution',
            'shop_name' => 'Capital Café',
            'primary_food_category' => 'Hainanese cuisine',
            'establishment_year' => 1956,
            'founder_name' => 'Founder Name',
            'founder_background' => 'The founder established the café after learning family recipes.',
            'current_owner_name' => 'Current Owner',
            'current_owner_details' => 'The current owner preserves the original menu.',
            'heritage_story' => 'The café has served generations of customers in Kuala Lumpur.',
            'contact_number' => '+60 3-1234 5678',
            'address' => '213, Jalan Tuanku Abdul Rahman',
            'city' => 'Kuala Lumpur',
            'state' => 'Wilayah Persekutuan Kuala Lumpur',
            'postal_code' => '50100',
            'food_items' => [[
                'name' => 'Hainanese chicken chop',
                'desc' => 'A long-standing family recipe.',
            ]],
        ];
    }

    private function modelContributionData(): array
    {
        $data = $this->validContributionData();
        unset($data['submission_action']);

        return $data;
    }
}
