<?php

namespace Tests\Feature;

use App\Models\CorrectionRequest;
use App\Models\HeritageShop;
use App\Models\HeritageShopContribution;
use App\Models\Media;
use App\Models\ShopImage;
use App\Models\User;
use App\Notifications\ContributionStatusChanged;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
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

        $response = $this->actingAs($user)->get(route('community-contribution.create'));

        $response->assertOk();
        $response->assertSee('Submit Heritage Shop');
        $response->assertSee('Heritage story');
        $response->assertSee('name="supporting_media[]"', false);
        $response->assertDontSee('Admin dashboard');
        $response->assertDontSee('Admin history');
        $response->assertSee('<style>', false);
    }

    public function test_submission_form_has_back_to_home_outside_the_main_card(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('community-contribution.create'));

        $response->assertOk();
        $response->assertSee('Back to Home');
        $response->assertSee('href="'.route('home').'"', false);
        $response->assertSee('Back to My Contributions');

        $content = $response->getContent();
        $backToHomePosition = strpos($content, 'Back to Home');
        $mainCardPosition = strpos($content, '<main class="page-shell">');

        $this->assertIsInt($backToHomePosition);
        $this->assertIsInt($mainCardPosition);
        $this->assertLessThan($mainCardPosition, $backToHomePosition);
    }

    public function test_shared_user_contribution_pages_have_one_global_back_to_home_link(): void
    {
        $user = User::factory()->create();
        $draft = HeritageShopContribution::create([
            ...$this->modelContributionData(),
            'user_id' => $user->id,
            'status' => HeritageShopContribution::STATUS_DRAFT,
        ]);
        $contribution = HeritageShopContribution::create([
            ...$this->modelContributionData(),
            'user_id' => $user->id,
            'status' => HeritageShopContribution::STATUS_PENDING_REVIEW,
            'submitted_at' => now(),
        ]);
        $revision = HeritageShopContribution::create([
            ...$this->modelContributionData(),
            'user_id' => $user->id,
            'status' => HeritageShopContribution::STATUS_REVISION_REQUIRED,
            'submitted_at' => now(),
            'admin_feedback' => 'Please add clearer information.',
        ]);
        $shop = HeritageShop::create([
            'shop_name' => 'Capital Cafe',
            'contact_number' => '+60 3-1111 1111',
            'publish_status' => 'approved',
        ]);
        $correctionRequest = CorrectionRequest::create([
            'user_id' => $user->id,
            'heritage_shop_id' => $shop->id,
            'field_name' => 'contact_number',
            'current_value' => '+60 3-1111 1111',
            'suggested_value' => '+60 3-2222 2222',
            'reason' => 'The shop published its updated phone number.',
            'status' => CorrectionRequest::STATUS_PENDING,
        ]);

        $routes = [
            route('community-contribution.create'),
            route('community-contribution.drafts'),
            route('community-contribution.contributions'),
            route('community-contribution.contributions.show', $contribution),
            route('community-contribution.edit', $draft),
            route('community-contribution.edit', $revision),
            route('community-contribution.correction-requests'),
            route('community-contribution.correction-requests.show', $correctionRequest),
            route('heritage-shops.correction-requests.create', $shop),
        ];

        foreach ($routes as $route) {
            $response = $this->actingAs($user)->get($route);

            $response->assertOk();
            $response->assertSee('href="'.route('home').'"', false);

            $content = $response->getContent();
            $this->assertSame(1, substr_count($content, 'Back to Home'), "Unexpected Back to Home count for [{$route}].");
            $this->assertLessThan(
                strpos($content, '<main class="page-shell">'),
                strpos($content, 'Back to Home'),
                "Back to Home should render before the main content for [{$route}]."
            );
        }
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

    public function test_empty_draft_submission_is_rejected_server_side(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('community-contribution.store'), [
                'submission_action' => 'draft',
                'contribution_title' => '   ',
                'shop_name' => '',
            ])
            ->assertSessionHasErrors('draft');

        $this->assertDatabaseCount('heritage_shop_contributions', 0);
        $this->assertDatabaseCount('contribution_versions', 0);
    }

    public function test_draft_submission_accepts_title_as_minimum_meaningful_content(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('community-contribution.store'), [
                'submission_action' => 'draft',
                'contribution_title' => 'Nasi kandar heritage note',
            ])
            ->assertRedirect(route('community-contribution.drafts'));

        $this->assertDatabaseHas('heritage_shop_contributions', [
            'user_id' => $user->id,
            'contribution_title' => 'Nasi kandar heritage note',
            'shop_name' => null,
            'status' => HeritageShopContribution::STATUS_DRAFT,
        ]);
    }

    public function test_incomplete_final_submission_is_rejected_without_creating_a_contribution(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('community-contribution.store'), [
                'submission_action' => 'submit',
                'contribution_title' => 'Only a title is not complete enough',
            ])
            ->assertSessionHasErrors([
                'shop_name',
                'primary_food_category',
                'establishment_year',
                'founder_name',
                'founder_background',
                'current_owner_name',
                'heritage_story',
                'address',
            ]);

        $this->assertDatabaseCount('heritage_shop_contributions', 0);
        $this->assertDatabaseCount('contribution_versions', 0);
    }

    public function test_contributor_payload_cannot_mass_assign_ownership_or_moderation_fields(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($user)
            ->post(route('community-contribution.store'), [
                ...$this->validContributionData(),
                'user_id' => $otherUser->id,
                'owner_id' => $otherUser->id,
                'status' => HeritageShopContribution::STATUS_APPROVED,
                'approved_at' => now()->toDateTimeString(),
                'review_started_at' => now()->toDateTimeString(),
                'reviewed_by_user_id' => $admin->id,
                'moderated_by' => $admin->id,
            ])
            ->assertSessionHasNoErrors();

        $contribution = HeritageShopContribution::firstOrFail();

        $this->assertSame($user->id, $contribution->user_id);
        $this->assertSame(HeritageShopContribution::STATUS_PENDING_REVIEW, $contribution->status);
        $this->assertNull($contribution->approved_at);
        $this->assertNull($contribution->review_started_at);
        $this->assertNull($contribution->reviewed_by_user_id);
    }

    public function test_contributor_update_payload_cannot_mass_assign_ownership_or_moderation_fields(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);
        $draft = HeritageShopContribution::create([
            ...$this->modelContributionData(),
            'user_id' => $user->id,
            'status' => HeritageShopContribution::STATUS_DRAFT,
        ]);

        $this->actingAs($user)
            ->put(route('community-contribution.update', $draft), [
                ...$this->validContributionData(),
                'user_id' => $otherUser->id,
                'owner_id' => $otherUser->id,
                'status' => HeritageShopContribution::STATUS_APPROVED,
                'approved_at' => now()->toDateTimeString(),
                'review_started_at' => now()->toDateTimeString(),
                'reviewed_by_user_id' => $admin->id,
                'moderated_by' => $admin->id,
            ])
            ->assertSessionHasNoErrors();

        $draft->refresh();

        $this->assertSame($user->id, $draft->user_id);
        $this->assertSame(HeritageShopContribution::STATUS_PENDING_REVIEW, $draft->status);
        $this->assertNull($draft->approved_at);
        $this->assertNull($draft->review_started_at);
        $this->assertNull($draft->reviewed_by_user_id);
    }

    public function test_complete_submission_uploads_media_and_stores_database_record(): void
    {
        Storage::fake(config('filesystems.media_disk'));
        $user = User::factory()->create();
        $data = $this->validContributionData();
        $data['supporting_media'] = [UploadedFile::fake()->create('capital-cafe.jpg', 64, 'image/jpeg')];

        $response = $this->actingAs($user)->post(route('community-contribution.store'), $data);

        $contribution = HeritageShopContribution::firstOrFail();
        $response->assertRedirect(route('community-contribution.contributions.show', $contribution));
        $this->assertSame(HeritageShopContribution::STATUS_PENDING_REVIEW, $contribution->status);
        $this->assertSame($user->id, $contribution->user_id);
        $this->assertNotNull($contribution->submitted_at);
        $media = $contribution->media()->firstOrFail();
        $this->assertSame('image', $media->media_type);
        $this->assertSame($user->id, $media->uploaded_by_user_id);
        $this->assertTrue(
            Storage::disk(config('filesystems.media_disk'))->exists($media->r2_object_key),
            'The uploaded supporting media file was not stored on the configured media disk.'
        );
    }

    public function test_repeated_create_submission_with_same_token_does_not_duplicate_record(): void
    {
        $user = User::factory()->create();
        $data = [
            ...$this->validContributionData(),
            'submission_token' => '2a656f83-8ed6-4d20-9797-2ef95d54ea11',
        ];

        $firstResponse = $this->actingAs($user)->post(route('community-contribution.store'), $data);
        $contribution = HeritageShopContribution::firstOrFail();
        $firstResponse->assertRedirect(route('community-contribution.contributions.show', $contribution));

        $this->actingAs($user)
            ->post(route('community-contribution.store'), $data)
            ->assertRedirect(route('community-contribution.contributions.show', $contribution));

        $this->assertDatabaseCount('heritage_shop_contributions', 1);
        $this->assertDatabaseCount('contribution_versions', 1);
    }

    public function test_operating_hours_are_saved_and_reloaded_in_the_form(): void
    {
        $user = User::factory()->create();
        $data = [
            ...$this->validContributionData(),
            'submission_action' => 'draft',
            'operating_hours' => [
                ['day' => 'Monday', 'open' => '09:00', 'close' => '17:30', 'closed' => '0'],
                ['day' => 'Tuesday', 'open' => '', 'close' => '', 'closed' => '1'],
            ],
        ];

        $this->actingAs($user)
            ->post(route('community-contribution.store'), $data)
            ->assertSessionHasNoErrors();

        $contribution = HeritageShopContribution::firstOrFail();

        $this->assertSame(HeritageShopContribution::STATUS_DRAFT, $contribution->status);
        $this->assertSame([
            ['day' => 'Monday', 'open' => '09:00', 'close' => '17:30', 'closed' => false],
            ['day' => 'Tuesday', 'open' => null, 'close' => null, 'closed' => true],
        ], $contribution->operating_hours);

        $this->actingAs($user)
            ->get(route('community-contribution.edit', $contribution))
            ->assertOk()
            ->assertSee('value="09:00"', false)
            ->assertSee('value="17:30"', false)
            ->assertSee('operating_hours[1][closed]" value="1" type="checkbox" checked', false);
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

    public function test_user_cannot_update_another_users_draft_or_change_ownership(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $draft = HeritageShopContribution::create([
            'user_id' => $owner->id,
            'status' => HeritageShopContribution::STATUS_DRAFT,
            'contribution_title' => 'Owner draft',
        ]);

        $this->actingAs($otherUser)
            ->put(route('community-contribution.update', $draft), [
                ...$this->validContributionData(),
                'user_id' => $otherUser->id,
                'shop_name' => 'Hijacked shop',
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('heritage_shop_contributions', [
            'id' => $draft->id,
            'user_id' => $owner->id,
            'shop_name' => null,
        ]);
    }

    public function test_owner_can_delete_own_draft(): void
    {
        Storage::fake(config('filesystems.media_disk'));
        $user = User::factory()->create();
        $draft = HeritageShopContribution::create([
            ...$this->modelContributionData(),
            'user_id' => $user->id,
            'status' => HeritageShopContribution::STATUS_DRAFT,
        ]);

        $this->actingAs($user)
            ->delete(route('community-contribution.drafts.destroy', $draft))
            ->assertRedirect(route('community-contribution.drafts'));

        $this->assertSoftDeleted('heritage_shop_contributions', ['id' => $draft->id]);
    }

    public function test_another_user_cannot_view_or_modify_private_contribution_routes(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $draft = HeritageShopContribution::create([
            ...$this->modelContributionData(),
            'user_id' => $owner->id,
            'status' => HeritageShopContribution::STATUS_DRAFT,
        ]);
        $pending = HeritageShopContribution::create([
            ...$this->modelContributionData(),
            'user_id' => $owner->id,
            'status' => HeritageShopContribution::STATUS_PENDING_REVIEW,
            'submitted_at' => now(),
        ]);

        $this->actingAs($otherUser)
            ->get(route('community-contribution.contributions.show', $draft))
            ->assertForbidden();

        $this->actingAs($otherUser)
            ->get(route('community-contribution.edit', $draft))
            ->assertForbidden();

        $this->actingAs($otherUser)
            ->put(route('community-contribution.update', $draft), $this->validContributionData())
            ->assertForbidden();

        $this->actingAs($otherUser)
            ->delete(route('community-contribution.drafts.destroy', $draft))
            ->assertForbidden();

        $this->actingAs($otherUser)
            ->post(route('community-contribution.drafts.submit', $draft))
            ->assertForbidden();

        $this->actingAs($otherUser)
            ->post(route('community-contribution.contributions.withdraw', $pending))
            ->assertForbidden();

        $this->assertDatabaseHas('heritage_shop_contributions', [
            'id' => $draft->id,
            'user_id' => $owner->id,
            'status' => HeritageShopContribution::STATUS_DRAFT,
        ]);
        $this->assertDatabaseHas('heritage_shop_contributions', [
            'id' => $pending->id,
            'user_id' => $owner->id,
            'status' => HeritageShopContribution::STATUS_PENDING_REVIEW,
        ]);
    }

    public function test_user_cannot_edit_own_contribution_after_review_starts_or_approval(): void
    {
        $user = User::factory()->create();
        $underReview = HeritageShopContribution::create([
            ...$this->modelContributionData(),
            'user_id' => $user->id,
            'status' => HeritageShopContribution::STATUS_UNDER_REVIEW,
            'submitted_at' => now(),
            'review_started_at' => now(),
        ]);
        $approved = HeritageShopContribution::create([
            ...$this->modelContributionData(),
            'user_id' => $user->id,
            'status' => HeritageShopContribution::STATUS_APPROVED,
            'submitted_at' => now(),
            'approved_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('community-contribution.edit', $underReview))
            ->assertForbidden();

        $this->actingAs($user)
            ->put(route('community-contribution.update', $approved), [
                ...$this->validContributionData(),
                'shop_name' => 'Changed after approval',
            ])
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

    public function test_approval_notification_uses_database_and_mail_channels(): void
    {
        Notification::fake();

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
            ])
            ->assertSessionHasNoErrors();

        $contribution->refresh();
        $this->assertSame(HeritageShopContribution::STATUS_APPROVED, $contribution->status);

        Notification::assertSentTo($user, ContributionStatusChanged::class, function (ContributionStatusChanged $notification, array $channels) use ($user, $contribution): bool {
            $mail = $notification->toMail($user);

            return $channels === ['database', 'mail']
                && $mail->subject === 'WarisanMakan Contribution Approved'
                && $mail->actionText === 'View Contribution'
                && parse_url($mail->actionUrl, PHP_URL_PATH) === "/community-contributions/{$contribution->public_id}";
        });
    }

    public function test_rejection_notification_uses_mail_and_includes_feedback(): void
    {
        Notification::fake();

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
                'moderation_action' => 'reject',
                'feedback' => 'The submitted details duplicate another shop record.',
            ])
            ->assertSessionHasNoErrors();

        $contribution->refresh();
        $this->assertSame(HeritageShopContribution::STATUS_REJECTED, $contribution->status);

        Notification::assertSentTo($user, ContributionStatusChanged::class, function (ContributionStatusChanged $notification, array $channels) use ($user): bool {
            $mail = $notification->toMail($user);

            return $channels === ['database', 'mail']
                && $mail->subject === 'WarisanMakan Contribution Update - Rejected'
                && in_array('Reason / Administrator Feedback:', $mail->introLines, true)
                && in_array('The submitted details duplicate another shop record.', $mail->introLines, true);
        });
    }

    public function test_revision_required_notification_uses_mail_and_links_to_public_edit_route(): void
    {
        Notification::fake();

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

        $this->actingAs($admin)
            ->post(route('admin.community-contributions.moderate', $contribution), [
                'moderation_action' => 'request_revision',
                'feedback' => 'Please add clearer information about the founder.',
            ])
            ->assertSessionHasNoErrors();

        $contribution->refresh();
        $this->assertSame(HeritageShopContribution::STATUS_REVISION_REQUIRED, $contribution->status);

        Notification::assertSentTo($user, ContributionStatusChanged::class, function (ContributionStatusChanged $notification, array $channels) use ($user, $contribution): bool {
            $mail = $notification->toMail($user);

            return $channels === ['database', 'mail']
                && $mail->subject === 'WarisanMakan Contribution Requires Revision'
                && $mail->actionText === 'Review and Resubmit Contribution'
                && parse_url($mail->actionUrl, PHP_URL_PATH) === "/community-contributions/{$contribution->public_id}/edit"
                && in_array('Administrator Feedback:', $mail->introLines, true)
                && in_array('Please add clearer information about the founder.', $mail->introLines, true);
        });
    }

    public function test_moderation_notification_is_sent_only_to_contribution_owner(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);
        $contribution = HeritageShopContribution::create([
            ...$this->modelContributionData(),
            'user_id' => $owner->id,
            'status' => HeritageShopContribution::STATUS_UNDER_REVIEW,
            'reviewed_by_user_id' => $admin->id,
            'review_started_at' => now(),
            'submitted_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.community-contributions.moderate', $contribution), [
                'moderation_action' => 'approve',
            ])
            ->assertSessionHasNoErrors();

        Notification::assertSentTo($owner, ContributionStatusChanged::class);
        Notification::assertNotSentTo($otherUser, ContributionStatusChanged::class);
    }

    public function test_moderation_email_url_uses_public_id_not_numeric_id(): void
    {
        Notification::fake();

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
            ])
            ->assertSessionHasNoErrors();

        Notification::assertSentTo($user, ContributionStatusChanged::class, function (ContributionStatusChanged $notification) use ($user, $contribution): bool {
            $path = parse_url($notification->toMail($user)->actionUrl, PHP_URL_PATH);

            return $path === "/community-contributions/{$contribution->public_id}"
                && $path !== "/community-contributions/{$contribution->id}";
        });
    }

    public function test_one_moderation_action_creates_one_notification_without_duplicates(): void
    {
        Notification::fake();

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
            ])
            ->assertSessionHasNoErrors();

        Notification::assertSentToTimes($user, ContributionStatusChanged::class, 1);
        Notification::assertSentTo($user, ContributionStatusChanged::class, function (ContributionStatusChanged $notification, array $channels): bool {
            return array_count_values($channels)['database'] === 1
                && array_count_values($channels)['mail'] === 1;
        });
    }

    public function test_opening_revision_notification_marks_it_read_and_removes_it_from_unread_list(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);
        $contribution = HeritageShopContribution::create([
            ...$this->modelContributionData(),
            'user_id' => $user->id,
            'status' => HeritageShopContribution::STATUS_PENDING_REVIEW,
            'submitted_at' => now(),
        ]);

        $this->requestRevision($admin, $contribution);

        $notification = $user->notifications()->firstOrFail();
        $this->assertNull($notification->read_at);

        $this->actingAs($user)
            ->get(route('community-contribution.contributions'))
            ->assertOk()
            ->assertSee('Revision requested');

        $this->actingAs($user)
            ->get(route('community-contribution.contributions.show', $contribution))
            ->assertOk();

        $this->assertNotNull($notification->fresh()->read_at);

        $this->actingAs($user)
            ->get(route('community-contribution.contributions'))
            ->assertOk()
            ->assertDontSee('Revision requested');
    }

    public function test_resubmitting_revision_marks_old_revision_notification_read_without_duplicate(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);
        $contribution = HeritageShopContribution::create([
            ...$this->modelContributionData(),
            'user_id' => $user->id,
            'status' => HeritageShopContribution::STATUS_PENDING_REVIEW,
            'submitted_at' => now(),
        ]);

        $this->requestRevision($admin, $contribution);
        $notification = $user->notifications()->firstOrFail();
        $this->assertNull($notification->read_at);

        $this->actingAs($user)
            ->put(route('community-contribution.update', $contribution), [
                ...$this->validContributionData(),
                'heritage_story' => 'The revised story includes administrator-requested proof and contact context.',
            ])
            ->assertRedirect(route('community-contribution.contributions.show', $contribution));

        $contribution->refresh();
        $this->assertSame(HeritageShopContribution::STATUS_PENDING_REVIEW, $contribution->status);
        $this->assertNotNull($contribution->resubmitted_at);
        $this->assertNotNull($notification->fresh()->read_at);
        $this->assertSame(1, $user->notifications()->count());
        $this->assertDatabaseHas('contribution_versions', [
            'heritage_shop_contribution_id' => $contribution->id,
            'reason' => 'resubmitted',
        ]);
    }

    public function test_admin_deleted_submission_stays_visible_in_admin_and_user_history(): void
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
            ->post(route('admin.community-contributions.moderate', $contribution), [
                'moderation_action' => 'delete',
                'deletion_reason' => 'Spam',
            ])
            ->assertRedirect(route('admin.community-contributions.submissions'));

        $contribution->refresh();
        $this->assertSame(HeritageShopContribution::STATUS_DELETED, $contribution->status);
        $this->assertSoftDeleted('heritage_shop_contributions', ['id' => $contribution->id]);
        $this->assertDatabaseHas('moderation_activities', [
            'heritage_shop_contribution_id' => $contribution->id,
            'action' => 'deleted',
            'to_status' => HeritageShopContribution::STATUS_DELETED,
            'comment' => 'Spam',
        ]);
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $user->id,
            'data->status' => HeritageShopContribution::STATUS_DELETED,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.community-contributions.history'))
            ->assertOk()
            ->assertSee('Deleted')
            ->assertSee('Delete reason: Spam');

        $this->actingAs($user)
            ->get(route('community-contribution.contributions'))
            ->assertOk()
            ->assertSee('Deleted')
            ->assertSee('Deleted by admin: Spam');

        $this->actingAs($user)
            ->get(route('community-contribution.contributions.show', $contribution))
            ->assertOk()
            ->assertSee('Deleted by administrator')
            ->assertSee('Spam');
    }

    public function test_contribution_timestamps_are_displayed_in_malaysia_time(): void
    {
        $user = User::factory()->create();
        $contribution = HeritageShopContribution::create([
            ...$this->modelContributionData(),
            'user_id' => $user->id,
            'status' => HeritageShopContribution::STATUS_PENDING_REVIEW,
            'submitted_at' => Carbon::parse('2026-08-18 09:42:00', 'UTC'),
        ]);

        $this->actingAs($user)
            ->get(route('community-contribution.contributions'))
            ->assertOk()
            ->assertSee('Submitted: 18 Aug 2026, 5:42 PM');

        $this->actingAs($user)
            ->get(route('community-contribution.contributions.show', $contribution))
            ->assertOk()
            ->assertSee('Submitted 18 Aug 2026, 5:42 PM');
    }

    public function test_revision_required_detail_shows_current_feedback_as_active(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);
        $contribution = HeritageShopContribution::create([
            ...$this->modelContributionData(),
            'user_id' => $user->id,
            'status' => HeritageShopContribution::STATUS_REVISION_REQUIRED,
            'submitted_at' => Carbon::parse('2026-08-19 02:34:00', 'UTC'),
            'admin_feedback' => 'Please upload supporting evidence.',
        ]);
        $contribution->moderationActivities()->create([
            'actor_user_id' => $admin->id,
            'action' => 'request_revision',
            'from_status' => HeritageShopContribution::STATUS_UNDER_REVIEW,
            'to_status' => HeritageShopContribution::STATUS_REVISION_REQUIRED,
            'comment' => 'Please upload supporting evidence.',
        ]);

        $this->actingAs($user)
            ->get(route('community-contribution.contributions.show', $contribution))
            ->assertOk()
            ->assertSee('Revision Required')
            ->assertSee('<section class="status-banner error">', false)
            ->assertSee('<strong>Administrator feedback</strong>', false)
            ->assertSee('Please upload supporting evidence.')
            ->assertSee('Revise and resubmit')
            ->assertDontSee('Previous administrator feedback')
            ->assertDontSee('No action is currently required.');
    }

    public function test_pending_after_resubmission_shows_previous_feedback_as_historical(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);
        $contribution = HeritageShopContribution::create([
            ...$this->modelContributionData(),
            'user_id' => $user->id,
            'status' => HeritageShopContribution::STATUS_PENDING_REVIEW,
            'submitted_at' => Carbon::parse('2026-08-19 02:34:00', 'UTC'),
            'resubmitted_at' => Carbon::parse('2026-08-29 11:04:00', 'UTC'),
            'admin_feedback' => 'upload the support',
        ]);
        $contribution->moderationActivities()->create([
            'actor_user_id' => $admin->id,
            'action' => 'request_revision',
            'from_status' => HeritageShopContribution::STATUS_UNDER_REVIEW,
            'to_status' => HeritageShopContribution::STATUS_REVISION_REQUIRED,
            'comment' => 'upload the support',
        ]);

        $response = $this->actingAs($user)
            ->get(route('community-contribution.contributions.show', $contribution))
            ->assertOk()
            ->assertSee('Resubmitted 29 Aug 2026, 7:04 PM')
            ->assertSee('Originally submitted 19 Aug 2026, 10:34 AM')
            ->assertSee('<section class="status-banner neutral">', false)
            ->assertSee('Previous administrator feedback')
            ->assertSee('upload the support')
            ->assertSee('Addressed in the latest resubmission.')
            ->assertDontSee('<strong>Administrator feedback</strong>', false)
            ->assertDontSee('Revise and resubmit');

        $this->assertStringContainsString('Withdraw submission', $response->getContent());
    }

    public function test_under_review_after_resubmission_shows_previous_feedback_as_historical(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);
        $contribution = HeritageShopContribution::create([
            ...$this->modelContributionData(),
            'user_id' => $user->id,
            'status' => HeritageShopContribution::STATUS_UNDER_REVIEW,
            'submitted_at' => Carbon::parse('2026-08-19 02:34:00', 'UTC'),
            'resubmitted_at' => Carbon::parse('2026-08-29 11:04:00', 'UTC'),
            'review_started_at' => Carbon::parse('2026-08-29 11:08:00', 'UTC'),
            'reviewed_by_user_id' => $admin->id,
            'admin_feedback' => 'upload the support',
        ]);
        $revisionRequest = $contribution->moderationActivities()->create([
            'actor_user_id' => $admin->id,
            'action' => 'request_revision',
            'from_status' => HeritageShopContribution::STATUS_UNDER_REVIEW,
            'to_status' => HeritageShopContribution::STATUS_REVISION_REQUIRED,
            'comment' => 'upload the support',
        ]);
        $revisionRequest->forceFill([
            'created_at' => Carbon::parse('2026-08-29 11:04:00', 'UTC'),
            'updated_at' => Carbon::parse('2026-08-29 11:04:00', 'UTC'),
        ])->save();
        $reviewStarted = $contribution->moderationActivities()->create([
            'actor_user_id' => $admin->id,
            'action' => 'review_started',
            'from_status' => HeritageShopContribution::STATUS_PENDING_REVIEW,
            'to_status' => HeritageShopContribution::STATUS_UNDER_REVIEW,
        ]);
        $reviewStarted->forceFill([
            'created_at' => Carbon::parse('2026-08-29 11:08:00', 'UTC'),
            'updated_at' => Carbon::parse('2026-08-29 11:08:00', 'UTC'),
        ])->save();

        $this->actingAs($user)
            ->get(route('community-contribution.contributions.show', $contribution))
            ->assertOk()
            ->assertSee('Under Review')
            ->assertSee('Resubmitted 29 Aug 2026, 7:04 PM')
            ->assertSee('Originally submitted 19 Aug 2026, 10:34 AM')
            ->assertSee('Previous administrator feedback')
            ->assertSee('upload the support')
            ->assertSee('No action is currently required.')
            ->assertSee('Review Started')
            ->assertSee('29 Aug 2026, 7:08 PM')
            ->assertDontSee('<strong>Administrator feedback</strong>', false)
            ->assertDontSee('Revise and resubmit');
    }

    public function test_full_revision_resubmission_flow_renders_under_review_feedback_as_historical(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);

        try {
            Carbon::setTestNow(Carbon::parse('2026-08-19 02:34:00', 'UTC'));
            $this->actingAs($user)
                ->post(route('community-contribution.store'), $this->validContributionData())
                ->assertSessionHasNoErrors();

            $contribution = HeritageShopContribution::firstOrFail();

            Carbon::setTestNow(Carbon::parse('2026-08-20 03:00:00', 'UTC'));
            $this->actingAs($admin)
                ->post(route('admin.community-contributions.start-review', $contribution))
                ->assertSessionHasNoErrors();

            $this->actingAs($admin)
                ->post(route('admin.community-contributions.moderate', $contribution), [
                    'moderation_action' => 'request_revision',
                    'feedback' => 'upload the support',
                ])
                ->assertSessionHasNoErrors();

            Carbon::setTestNow(Carbon::parse('2026-08-29 11:04:00', 'UTC'));
            $this->actingAs($user)
                ->put(route('community-contribution.update', $contribution), [
                    ...$this->validContributionData(),
                    'heritage_story' => 'The revised story includes newly uploaded support.',
                ])
                ->assertRedirect(route('community-contribution.contributions.show', $contribution));

            Carbon::setTestNow(Carbon::parse('2026-08-29 11:08:00', 'UTC'));
            $this->actingAs($admin)
                ->post(route('admin.community-contributions.start-review', $contribution->fresh()))
                ->assertSessionHasNoErrors();
        } finally {
            Carbon::setTestNow();
        }

        $this->actingAs($user)
            ->get(route('community-contribution.contributions.show', $contribution->fresh()))
            ->assertOk()
            ->assertSee('Under Review')
            ->assertSee('Resubmitted 29 Aug 2026, 7:04 PM')
            ->assertSee('Originally submitted 19 Aug 2026, 10:34 AM')
            ->assertSee('Previous administrator feedback')
            ->assertSee('upload the support')
            ->assertSee('No action is currently required.')
            ->assertSee('Review Started')
            ->assertSee('29 Aug 2026, 7:08 PM')
            ->assertDontSee('<strong>Administrator feedback</strong>', false)
            ->assertDontSee('Revise and resubmit');
    }

    public function test_under_review_before_revision_has_no_feedback_banner(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);
        $contribution = HeritageShopContribution::create([
            ...$this->modelContributionData(),
            'user_id' => $user->id,
            'status' => HeritageShopContribution::STATUS_UNDER_REVIEW,
            'submitted_at' => Carbon::parse('2026-08-19 02:34:00', 'UTC'),
            'review_started_at' => Carbon::parse('2026-08-20 03:00:00', 'UTC'),
            'reviewed_by_user_id' => $admin->id,
        ]);

        $this->actingAs($user)
            ->get(route('community-contribution.contributions.show', $contribution))
            ->assertOk()
            ->assertSee('Submitted 19 Aug 2026, 10:34 AM')
            ->assertDontSee('<strong>Administrator feedback</strong>', false)
            ->assertDontSee('Previous administrator feedback')
            ->assertSee('No action is currently required.');
    }

    public function test_multiple_revision_cycles_use_latest_revision_feedback_as_current(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);
        $contribution = HeritageShopContribution::create([
            ...$this->modelContributionData(),
            'user_id' => $user->id,
            'status' => HeritageShopContribution::STATUS_REVISION_REQUIRED,
            'submitted_at' => Carbon::parse('2026-08-19 02:34:00', 'UTC'),
            'resubmitted_at' => Carbon::parse('2026-08-29 11:04:00', 'UTC'),
            'admin_feedback' => 'Second cycle evidence is still missing.',
        ]);
        $firstRevision = $contribution->moderationActivities()->create([
            'actor_user_id' => $admin->id,
            'action' => 'request_revision',
            'from_status' => HeritageShopContribution::STATUS_UNDER_REVIEW,
            'to_status' => HeritageShopContribution::STATUS_REVISION_REQUIRED,
            'comment' => 'First cycle feedback was addressed.',
        ]);
        $firstRevision->forceFill([
            'created_at' => Carbon::parse('2026-08-20 03:00:00', 'UTC'),
            'updated_at' => Carbon::parse('2026-08-20 03:00:00', 'UTC'),
        ])->save();
        $secondRevision = $contribution->moderationActivities()->create([
            'actor_user_id' => $admin->id,
            'action' => 'request_revision',
            'from_status' => HeritageShopContribution::STATUS_UNDER_REVIEW,
            'to_status' => HeritageShopContribution::STATUS_REVISION_REQUIRED,
            'comment' => 'Second cycle evidence is still missing.',
        ]);
        $secondRevision->forceFill([
            'created_at' => Carbon::parse('2026-08-30 04:00:00', 'UTC'),
            'updated_at' => Carbon::parse('2026-08-30 04:00:00', 'UTC'),
        ])->save();

        $content = $this->actingAs($user)
            ->get(route('community-contribution.contributions.show', $contribution))
            ->assertOk()
            ->assertSee('Second cycle evidence is still missing.')
            ->assertSee('Revise and resubmit')
            ->assertDontSee('Previous administrator feedback')
            ->getContent();

        $this->assertStringContainsString('<strong>Administrator feedback</strong>', $content);
        $this->assertLessThan(
            strpos($content, 'Status activity'),
            strpos($content, 'Second cycle evidence is still missing.')
        );
    }

    public function test_approved_after_revision_keeps_old_revision_feedback_historical(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);
        $contribution = HeritageShopContribution::create([
            ...$this->modelContributionData(),
            'user_id' => $user->id,
            'status' => HeritageShopContribution::STATUS_APPROVED,
            'submitted_at' => Carbon::parse('2026-08-19 02:34:00', 'UTC'),
            'resubmitted_at' => Carbon::parse('2026-08-29 11:04:00', 'UTC'),
            'approved_at' => Carbon::parse('2026-08-30 05:00:00', 'UTC'),
            'approved_by_user_id' => $admin->id,
        ]);
        $contribution->moderationActivities()->create([
            'actor_user_id' => $admin->id,
            'action' => 'request_revision',
            'from_status' => HeritageShopContribution::STATUS_UNDER_REVIEW,
            'to_status' => HeritageShopContribution::STATUS_REVISION_REQUIRED,
            'comment' => 'Please upload supporting evidence.',
        ]);

        $this->actingAs($user)
            ->get(route('community-contribution.contributions.show', $contribution))
            ->assertOk()
            ->assertSee('Approved')
            ->assertSee('Previous administrator feedback')
            ->assertSee('Please upload supporting evidence.')
            ->assertDontSee('<strong>Administrator feedback</strong>', false)
            ->assertDontSee('Revise and resubmit');
    }

    public function test_rejected_after_revision_separates_rejection_reason_from_old_revision_feedback(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);
        $contribution = HeritageShopContribution::create([
            ...$this->modelContributionData(),
            'user_id' => $user->id,
            'status' => HeritageShopContribution::STATUS_REJECTED,
            'submitted_at' => Carbon::parse('2026-08-19 02:34:00', 'UTC'),
            'resubmitted_at' => Carbon::parse('2026-08-29 11:04:00', 'UTC'),
            'admin_feedback' => 'The submitted details duplicate another shop record.',
            'rejection_reason' => 'The submitted details duplicate another shop record.',
        ]);
        $contribution->moderationActivities()->create([
            'actor_user_id' => $admin->id,
            'action' => 'request_revision',
            'from_status' => HeritageShopContribution::STATUS_UNDER_REVIEW,
            'to_status' => HeritageShopContribution::STATUS_REVISION_REQUIRED,
            'comment' => 'Please upload supporting evidence.',
        ]);

        $this->actingAs($user)
            ->get(route('community-contribution.contributions.show', $contribution))
            ->assertOk()
            ->assertSee('Rejected')
            ->assertSee('Rejection reason')
            ->assertSee('The submitted details duplicate another shop record.')
            ->assertSee('Previous administrator feedback')
            ->assertSee('Please upload supporting evidence.')
            ->assertDontSee('<strong>Administrator feedback</strong>', false)
            ->assertDontSee('Revise and resubmit');
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
            'primary_food_category' => 'Hainanese cuisine',
            'publish_status' => HeritageShop::STATUS_PUBLISHED,
            'shop_name' => 'Capital Café',
        ]);
        $this->assertSame(1, HeritageShop::count());

        $this->get(route('heritage-shops.index'))
            ->assertOk()
            ->assertSee('Capital Caf');
    }

    public function test_admin_can_publish_one_selected_contribution_image_to_shop_images_on_approval(): void
    {
        Notification::fake();
        $this->fakeContributionAndShopImageDisks();

        $user = User::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);
        $contribution = $this->underReviewContribution($user, $admin);
        $media = $this->storedContributionMedia($contribution, 'image', 'selected-photo.jpg', 'image/jpeg', 'contribution-image-bytes');

        $this->actingAs($admin)
            ->post(route('admin.community-contributions.moderate', $contribution), [
                'moderation_action' => 'approve',
                'publish_media_ids' => [$media->id],
            ])
            ->assertSessionHasNoErrors();

        $shop = HeritageShop::where('source_contribution_id', $contribution->id)->firstOrFail();
        $image = $shop->images()->firstOrFail();

        $this->assertTrue($image->is_primary);
        $this->assertSame(
            "heritage-shops/community-contributions/{$shop->id}/{$contribution->id}/media-{$media->id}.jpg",
            $image->path
        );
        $this->assertNotSame($media->r2_object_key, $image->path);
        $this->assertTrue(Storage::disk('media-test')->exists($media->r2_object_key));
        $this->assertTrue(Storage::disk('heritage-test')->exists($image->path));
    }

    public function test_admin_can_publish_multiple_selected_contribution_images_on_approval(): void
    {
        Notification::fake();
        $this->fakeContributionAndShopImageDisks();

        $user = User::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);
        $contribution = $this->underReviewContribution($user, $admin);
        $first = $this->storedContributionMedia($contribution, 'image', 'front.jpg', 'image/jpeg', 'front-bytes');
        $second = $this->storedContributionMedia($contribution, 'image', 'menu.png', 'image/png', 'menu-bytes');

        $this->actingAs($admin)
            ->post(route('admin.community-contributions.moderate', $contribution), [
                'moderation_action' => 'approve',
                'publish_media_ids' => [$first->id, $second->id],
            ])
            ->assertSessionHasNoErrors();

        $shop = HeritageShop::where('source_contribution_id', $contribution->id)->firstOrFail();
        $images = $shop->images()->orderBy('id')->get();

        $this->assertCount(2, $images);
        $this->assertTrue($images[0]->is_primary);
        $this->assertFalse($images[1]->is_primary);
        $this->assertTrue(Storage::disk('heritage-test')->exists($images[0]->path));
        $this->assertTrue(Storage::disk('heritage-test')->exists($images[1]->path));
    }

    public function test_admin_can_approve_with_zero_selected_images(): void
    {
        Notification::fake();
        $this->fakeContributionAndShopImageDisks();

        $user = User::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);
        $contribution = $this->underReviewContribution($user, $admin);
        $this->storedContributionMedia($contribution, 'image', 'evidence.jpg', 'image/jpeg', 'evidence-bytes');

        $this->actingAs($admin)
            ->post(route('admin.community-contributions.moderate', $contribution), [
                'moderation_action' => 'approve',
            ])
            ->assertSessionHasNoErrors();

        $shop = HeritageShop::where('source_contribution_id', $contribution->id)->firstOrFail();

        $this->assertSame(HeritageShopContribution::STATUS_APPROVED, $contribution->fresh()->status);
        $this->assertSame(0, $shop->images()->count());
    }

    public function test_unselected_supporting_image_remains_evidence_and_is_not_published(): void
    {
        Notification::fake();
        $this->fakeContributionAndShopImageDisks();

        $user = User::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);
        $contribution = $this->underReviewContribution($user, $admin);
        $selected = $this->storedContributionMedia($contribution, 'image', 'selected.jpg', 'image/jpeg', 'selected-bytes');
        $unselected = $this->storedContributionMedia($contribution, 'image', 'unselected.jpg', 'image/jpeg', 'unselected-bytes');

        $this->actingAs($admin)
            ->post(route('admin.community-contributions.moderate', $contribution), [
                'moderation_action' => 'approve',
                'publish_media_ids' => [$selected->id],
            ])
            ->assertSessionHasNoErrors();

        $shop = HeritageShop::where('source_contribution_id', $contribution->id)->firstOrFail();

        $this->assertSame(2, $contribution->media()->count());
        $this->assertTrue(Storage::disk('media-test')->exists($unselected->r2_object_key));
        $this->assertSame(1, $shop->images()->count());
        $this->assertStringContainsString("media-{$selected->id}.jpg", $shop->images()->first()->path);
        $this->assertStringNotContainsString("media-{$unselected->id}.jpg", $shop->images()->first()->path);
    }

    public function test_non_image_media_id_cannot_be_published_on_approval(): void
    {
        Notification::fake();
        $this->fakeContributionAndShopImageDisks();

        $user = User::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);
        $contribution = $this->underReviewContribution($user, $admin);
        $video = $this->storedContributionMedia($contribution, 'video', 'walkthrough.mp4', 'video/mp4', 'video-bytes');

        $this->actingAs($admin)
            ->post(route('admin.community-contributions.moderate', $contribution), [
                'moderation_action' => 'approve',
                'publish_media_ids' => [$video->id],
            ])
            ->assertSessionHasErrors('publish_media_ids');

        $this->assertSame(HeritageShopContribution::STATUS_UNDER_REVIEW, $contribution->fresh()->status);
        $this->assertDatabaseCount('shop_images', 0);
        $this->assertDatabaseCount('heritage_shops', 0);
    }

    public function test_media_from_another_contribution_cannot_be_published(): void
    {
        Notification::fake();
        $this->fakeContributionAndShopImageDisks();

        $user = User::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);
        $contribution = $this->underReviewContribution($user, $admin);
        $otherContribution = $this->underReviewContribution($user, $admin);
        $otherMedia = $this->storedContributionMedia($otherContribution, 'image', 'other.jpg', 'image/jpeg', 'other-bytes');

        $this->actingAs($admin)
            ->post(route('admin.community-contributions.moderate', $contribution), [
                'moderation_action' => 'approve',
                'publish_media_ids' => [$otherMedia->id],
            ])
            ->assertSessionHasErrors('publish_media_ids');

        $this->assertSame(HeritageShopContribution::STATUS_UNDER_REVIEW, $contribution->fresh()->status);
        $this->assertDatabaseCount('shop_images', 0);
        $this->assertDatabaseCount('heritage_shops', 0);
    }

    public function test_reject_and_request_revision_ignore_selected_media_ids(): void
    {
        Notification::fake();
        $this->fakeContributionAndShopImageDisks();

        $admin = User::factory()->create(['role' => 'admin']);
        $rejectOwner = User::factory()->create();
        $revisionOwner = User::factory()->create();
        $rejectContribution = $this->underReviewContribution($rejectOwner, $admin);
        $revisionContribution = $this->underReviewContribution($revisionOwner, $admin);
        $rejectMedia = $this->storedContributionMedia($rejectContribution, 'image', 'reject.jpg', 'image/jpeg', 'reject-bytes');
        $revisionMedia = $this->storedContributionMedia($revisionContribution, 'image', 'revision.jpg', 'image/jpeg', 'revision-bytes');

        $this->actingAs($admin)
            ->post(route('admin.community-contributions.moderate', $rejectContribution), [
                'moderation_action' => 'reject',
                'feedback' => 'Not enough supporting detail.',
                'publish_media_ids' => [$rejectMedia->id],
            ])
            ->assertSessionHasNoErrors();

        $this->actingAs($admin)
            ->post(route('admin.community-contributions.moderate', $revisionContribution), [
                'moderation_action' => 'request_revision',
                'feedback' => 'Please provide clearer proof.',
                'publish_media_ids' => [$revisionMedia->id],
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('shop_images', 0);
        $this->assertDatabaseCount('heritage_shops', 0);
        $this->assertSame(HeritageShopContribution::STATUS_REJECTED, $rejectContribution->fresh()->status);
        $this->assertSame(HeritageShopContribution::STATUS_REVISION_REQUIRED, $revisionContribution->fresh()->status);
    }

    public function test_selected_image_does_not_replace_an_existing_primary_shop_image(): void
    {
        Notification::fake();
        $this->fakeContributionAndShopImageDisks();

        $user = User::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);
        $contribution = $this->underReviewContribution($user, $admin);
        $media = $this->storedContributionMedia($contribution, 'image', 'selected.jpg', 'image/jpeg', 'selected-bytes');
        $shop = HeritageShop::create([
            ...$this->modelContributionData(),
            'source_contribution_id' => $contribution->id,
            'publish_status' => HeritageShop::STATUS_PUBLISHED,
        ]);
        Storage::disk('heritage-test')->put('heritage-shops/existing-primary.jpg', 'existing-bytes');
        $existingPrimary = $shop->images()->create([
            'path' => 'heritage-shops/existing-primary.jpg',
            'is_primary' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.community-contributions.moderate', $contribution), [
                'moderation_action' => 'approve',
                'publish_media_ids' => [$media->id],
            ])
            ->assertSessionHasNoErrors();

        $this->assertTrue($existingPrimary->fresh()->is_primary);
        $this->assertSame(2, $shop->fresh()->images()->count());
        $this->assertFalse($shop->images()->where('path', '!=', $existingPrimary->path)->firstOrFail()->is_primary);
    }

    public function test_public_profile_uses_published_shop_images_from_selected_contribution_media(): void
    {
        Notification::fake();
        $this->fakeContributionAndShopImageDisks();

        $user = User::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);
        $contribution = $this->underReviewContribution($user, $admin);
        $selected = $this->storedContributionMedia($contribution, 'image', 'selected.jpg', 'image/jpeg', 'selected-bytes');
        $unselected = $this->storedContributionMedia($contribution, 'image', 'unselected.jpg', 'image/jpeg', 'unselected-bytes');

        $this->actingAs($admin)
            ->post(route('admin.community-contributions.moderate', $contribution), [
                'moderation_action' => 'approve',
                'publish_media_ids' => [$selected->id],
            ])
            ->assertSessionHasNoErrors();

        $shop = HeritageShop::where('source_contribution_id', $contribution->id)->firstOrFail();
        $image = $shop->images()->firstOrFail();

        $this->get(route('heritage-shops.show', ['id' => $shop->id]))
            ->assertOk()
            ->assertSee(route('heritage-shops.images.show', [$shop, $image]), false)
            ->assertDontSee('No image available');

        $this->assertStringContainsString("media-{$selected->id}.jpg", $image->path);
        $this->assertStringNotContainsString("media-{$unselected->id}.jpg", $image->path);
    }

    public function test_retrying_approval_does_not_duplicate_selected_shop_images(): void
    {
        Notification::fake();
        $this->fakeContributionAndShopImageDisks();

        $user = User::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);
        $contribution = $this->underReviewContribution($user, $admin);
        $media = $this->storedContributionMedia($contribution, 'image', 'selected.jpg', 'image/jpeg', 'selected-bytes');

        $payload = [
            'moderation_action' => 'approve',
            'publish_media_ids' => [$media->id],
        ];

        $this->actingAs($admin)
            ->post(route('admin.community-contributions.moderate', $contribution), $payload)
            ->assertSessionHasNoErrors();

        $this->actingAs($admin)
            ->post(route('admin.community-contributions.moderate', $contribution->fresh()), $payload)
            ->assertSessionHasErrors('moderation');

        $this->assertDatabaseCount('shop_images', 1);
    }

    public function test_missing_selected_source_image_fails_without_approving_or_creating_shop_image(): void
    {
        Notification::fake();
        $this->fakeContributionAndShopImageDisks();

        $user = User::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);
        $contribution = $this->underReviewContribution($user, $admin);
        $media = $contribution->media()->create([
            'uploaded_by_user_id' => $user->id,
            'media_type' => 'image',
            'r2_object_key' => "contributions/{$contribution->id}/missing.jpg",
            'original_name' => 'missing.jpg',
            'mime_type' => 'image/jpeg',
            'file_size_bytes' => 12,
            'display_order' => 0,
            'is_primary' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.community-contributions.moderate', $contribution), [
                'moderation_action' => 'approve',
                'publish_media_ids' => [$media->id],
            ])
            ->assertSessionHasErrors('publish_media_ids');

        $this->assertSame(HeritageShopContribution::STATUS_UNDER_REVIEW, $contribution->fresh()->status);
        $this->assertDatabaseCount('shop_images', 0);
        $this->assertDatabaseCount('heritage_shops', 0);
    }

    public function test_user_can_submit_correction_request_and_admin_can_approve_it(): void
    {
        Storage::fake(config('filesystems.media_disk'));
        $user = User::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);
        $shop = HeritageShop::create([
            'shop_name' => 'Capital Cafe',
            'contact_number' => '+60 3-1111 1111',
            'publish_status' => HeritageShop::STATUS_PUBLISHED,
        ]);

        $this->actingAs($user)
            ->post(route('heritage-shops.correction-requests.store', $shop), [
                'field_name' => 'contact_number',
                'current_value' => '+60 3-1111 1111',
                'suggested_value' => '+60 3-2222 2222',
                'reason' => 'The shop published its updated phone number.',
                'evidence' => [UploadedFile::fake()->create('phone-number.jpg', 64, 'image/jpeg')],
            ])
            ->assertSessionHasNoErrors();

        $correctionRequest = CorrectionRequest::firstOrFail();
        $this->assertSame(CorrectionRequest::STATUS_PENDING, $correctionRequest->status);
        $this->assertSame('+60 3-1111 1111', $correctionRequest->current_value);
        $this->assertCount(1, $correctionRequest->media);
        $this->assertDatabaseHas('moderation_activities', [
            'correction_request_id' => $correctionRequest->id,
            'action' => 'correction_submitted',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.community-contributions.correction-requests.start-review', $correctionRequest))
            ->assertSessionHasNoErrors();

        $this->actingAs($admin)
            ->post(route('admin.community-contributions.correction-requests.moderate', $correctionRequest), [
                'moderation_action' => 'approve',
                'admin_comment' => 'Verified against the uploaded evidence.',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('correction_requests', [
            'id' => $correctionRequest->id,
            'status' => CorrectionRequest::STATUS_APPROVED,
            'reviewed_by_user_id' => $admin->id,
        ]);
        $this->assertDatabaseHas('heritage_shops', [
            'id' => $shop->id,
            'contact_number' => '+60 3-2222 2222',
        ]);
        $this->assertDatabaseHas('moderation_activities', [
            'correction_request_id' => $correctionRequest->id,
            'action' => 'correction_approved',
        ]);
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $user->id,
        ]);
    }

    public function test_correction_request_payload_cannot_mass_assign_owner_or_review_fields(): void
    {
        Storage::fake(config('filesystems.media_disk'));
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);
        $shop = HeritageShop::create([
            'shop_name' => 'Capital Cafe',
            'contact_number' => '+60 3-1111 1111',
            'publish_status' => 'approved',
        ]);

        $this->actingAs($user)
            ->post(route('heritage-shops.correction-requests.store', $shop), [
                'field_name' => 'contact_number',
                'current_value' => 'Tampered current value',
                'suggested_value' => '+60 3-2222 2222',
                'reason' => 'The shop published its updated phone number.',
                'user_id' => $otherUser->id,
                'status' => CorrectionRequest::STATUS_APPROVED,
                'admin_comment' => 'Pretend this was approved.',
                'reviewed_by_user_id' => $admin->id,
                'review_started_at' => now()->toDateTimeString(),
                'reviewed_at' => now()->toDateTimeString(),
            ])
            ->assertSessionHasNoErrors();

        $correctionRequest = CorrectionRequest::firstOrFail();

        $this->assertSame($user->id, $correctionRequest->user_id);
        $this->assertSame(CorrectionRequest::STATUS_PENDING, $correctionRequest->status);
        $this->assertSame('+60 3-1111 1111', $correctionRequest->current_value);
        $this->assertNull($correctionRequest->admin_comment);
        $this->assertNull($correctionRequest->reviewed_by_user_id);
        $this->assertNull($correctionRequest->review_started_at);
        $this->assertNull($correctionRequest->reviewed_at);
    }

    public function test_user_can_provide_additional_information_for_correction_request(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);
        $shop = HeritageShop::create([
            'shop_name' => 'Capital Cafe',
            'publish_status' => HeritageShop::STATUS_PUBLISHED,
        ]);
        $correctionRequest = CorrectionRequest::create([
            'user_id' => $user->id,
            'heritage_shop_id' => $shop->id,
            'field_name' => 'address',
            'current_value' => 'Old address',
            'suggested_value' => 'New address',
            'reason' => 'The shop moved.',
            'status' => CorrectionRequest::STATUS_NEEDS_INFORMATION,
            'admin_comment' => 'Please provide the source of the new address.',
            'reviewed_by_user_id' => $admin->id,
            'reviewed_at' => now(),
        ]);

        $this->actingAs($user)
            ->post(route('community-contribution.correction-requests.additional-information', $correctionRequest), [
                'additional_information' => 'The new address is listed on the latest shop receipt.',
            ])
            ->assertRedirect(route('community-contribution.correction-requests.show', $correctionRequest));

        $this->assertDatabaseHas('correction_requests', [
            'id' => $correctionRequest->id,
            'status' => CorrectionRequest::STATUS_PENDING,
            'additional_information' => 'The new address is listed on the latest shop receipt.',
        ]);
        $this->assertDatabaseHas('moderation_activities', [
            'correction_request_id' => $correctionRequest->id,
            'action' => 'additional_information_provided',
        ]);
    }

    public function test_another_user_cannot_access_or_update_private_correction_request(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $shop = HeritageShop::create([
            'shop_name' => 'Capital Cafe',
            'publish_status' => 'approved',
        ]);
        $correctionRequest = CorrectionRequest::create([
            'user_id' => $owner->id,
            'heritage_shop_id' => $shop->id,
            'field_name' => 'address',
            'current_value' => 'Old address',
            'suggested_value' => 'New address',
            'reason' => 'The shop moved.',
            'status' => CorrectionRequest::STATUS_NEEDS_INFORMATION,
        ]);

        $this->actingAs($otherUser)
            ->get(route('community-contribution.correction-requests.show', $correctionRequest))
            ->assertForbidden();

        $this->actingAs($otherUser)
            ->post(route('community-contribution.correction-requests.additional-information', $correctionRequest), [
                'additional_information' => 'Trying to edit another user request.',
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('correction_requests', [
            'id' => $correctionRequest->id,
            'user_id' => $owner->id,
            'status' => CorrectionRequest::STATUS_NEEDS_INFORMATION,
            'additional_information' => null,
        ]);
    }

    public function test_non_administrator_cannot_access_admin_functions(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.community-contributions.submissions'))
            ->assertForbidden();
    }

    public function test_invalid_supporting_media_is_rejected_server_side(): void
    {
        Storage::fake(config('filesystems.media_disk'));
        $user = User::factory()->create();
        $data = $this->validContributionData();
        $data['supporting_media'] = [
            UploadedFile::fake()->create('not-an-image.txt', 1, 'text/plain'),
        ];

        $this->actingAs($user)
            ->post(route('community-contribution.store'), $data)
            ->assertSessionHasErrors('supporting_media.0');

        $this->assertDatabaseCount('heritage_shop_contributions', 0);
        $this->assertDatabaseCount('media', 0);
    }

    public function test_oversized_and_fake_extension_media_are_rejected_server_side(): void
    {
        Storage::fake(config('filesystems.media_disk'));
        $user = User::factory()->create();
        $oversized = $this->validContributionData();
        $oversized['supporting_media'] = [
            UploadedFile::fake()->create('too-large.jpg', 20481, 'image/jpeg'),
        ];

        $this->actingAs($user)
            ->post(route('community-contribution.store'), $oversized)
            ->assertSessionHasErrors('supporting_media.0');

        $fakeExtension = $this->validContributionData();
        $fakeExtension['supporting_media'] = [
            UploadedFile::fake()->create('not-really-an-image.jpg', 1, 'text/plain'),
        ];

        $this->actingAs($user)
            ->post(route('community-contribution.store'), $fakeExtension)
            ->assertSessionHasErrors('supporting_media.0');

        $this->assertDatabaseCount('heritage_shop_contributions', 0);
        $this->assertDatabaseCount('media', 0);
    }

    public function test_contribution_field_format_and_boundary_validation(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('community-contribution.store'), [
                ...$this->validContributionData(),
                'contribution_title' => str_repeat('A', 256),
                'establishment_year' => 999,
                'contact_number' => 'not-a-phone-number!',
                'latitude' => 91,
                'longitude' => 181,
            ])
            ->assertSessionHasErrors([
                'contribution_title',
                'establishment_year',
                'contact_number',
                'latitude',
                'longitude',
            ]);

        $this->actingAs($user)
            ->post(route('community-contribution.store'), [
                ...$this->validContributionData(),
                'establishment_year' => now()->year + 1,
            ])
            ->assertSessionHasErrors('establishment_year');

        $this->actingAs($user)
            ->post(route('community-contribution.store'), [
                ...$this->validContributionData(),
                'contribution_title' => str_repeat('B', 255),
                'establishment_year' => 1000,
                'latitude' => -90,
                'longitude' => 180,
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('heritage_shop_contributions', 1);
    }

    public function test_postal_code_only_accepts_digits(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('community-contribution.create'))
            ->assertOk()
            ->assertSee('inputmode="numeric"', false)
            ->assertSee('pattern="[0-9]*"', false);

        $this->actingAs($user)
            ->post(route('community-contribution.store'), [
                ...$this->validContributionData(),
                'postal_code' => 'five-zero-one-zero-zero',
            ])
            ->assertSessionHasErrors('postal_code');

        $this->assertDatabaseCount('heritage_shop_contributions', 0);

        $this->actingAs($user)
            ->post(route('community-contribution.store'), [
                ...$this->validContributionData(),
                'postal_code' => '05000',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('heritage_shop_contributions', [
            'postal_code' => '05000',
        ]);
    }

    private function fakeContributionAndShopImageDisks(): void
    {
        Storage::fake('media-test');
        Storage::fake('heritage-test');

        config()->set('filesystems.media_disk', 'media-test');
        config()->set('heritage_shop.image_disk', 'heritage-test');
    }

    private function underReviewContribution(User $user, User $admin): HeritageShopContribution
    {
        return HeritageShopContribution::create([
            ...$this->modelContributionData(),
            'user_id' => $user->id,
            'status' => HeritageShopContribution::STATUS_UNDER_REVIEW,
            'reviewed_by_user_id' => $admin->id,
            'review_started_at' => now(),
            'submitted_at' => now(),
        ]);
    }

    private function storedContributionMedia(
        HeritageShopContribution $contribution,
        string $mediaType,
        string $originalName,
        string $mimeType,
        string $contents
    ): Media {
        $disk = Storage::disk(config('filesystems.media_disk'));
        $r2ObjectKey = "contributions/{$contribution->id}/{$originalName}";
        $disk->put($r2ObjectKey, $contents);

        return $contribution->media()->create([
            'uploaded_by_user_id' => $contribution->user_id,
            'media_type' => $mediaType,
            'r2_object_key' => $r2ObjectKey,
            'original_name' => $originalName,
            'mime_type' => $mimeType,
            'file_size_bytes' => strlen($contents),
            'display_order' => 0,
            'is_primary' => false,
        ]);
    }

    public function test_valid_supporting_video_is_stored_as_video_media(): void
    {
        Storage::fake(config('filesystems.media_disk'));
        $user = User::factory()->create();
        $data = $this->validContributionData();
        $data['supporting_media'] = [
            UploadedFile::fake()->create('audit-video.mp4', 64, 'video/mp4'),
        ];

        $this->actingAs($user)
            ->post(route('community-contribution.store'), $data)
            ->assertSessionHasNoErrors();

        $media = HeritageShopContribution::firstOrFail()->media()->firstOrFail();

        $this->assertSame('video', $media->media_type);
        $this->assertSame('video/mp4', $media->mime_type);
        $this->assertTrue(Storage::disk(config('filesystems.media_disk'))->exists($media->r2_object_key));
    }

    public function test_new_contributions_receive_unique_public_ids(): void
    {
        $user = User::factory()->create();

        $first = HeritageShopContribution::create([
            ...$this->modelContributionData(),
            'user_id' => $user->id,
            'status' => HeritageShopContribution::STATUS_DRAFT,
        ]);
        $second = HeritageShopContribution::create([
            ...$this->modelContributionData(),
            'user_id' => $user->id,
            'status' => HeritageShopContribution::STATUS_PENDING_REVIEW,
            'submitted_at' => now(),
        ]);

        $this->assertUuidString($first->public_id);
        $this->assertUuidString($second->public_id);
        $this->assertNotSame($first->public_id, $second->public_id);
    }

    public function test_contribution_routes_use_public_id_instead_of_numeric_id(): void
    {
        $user = User::factory()->create();
        $contribution = HeritageShopContribution::create([
            ...$this->modelContributionData(),
            'user_id' => $user->id,
            'status' => HeritageShopContribution::STATUS_PENDING_REVIEW,
            'submitted_at' => now(),
        ]);
        $draft = HeritageShopContribution::create([
            ...$this->modelContributionData(),
            'user_id' => $user->id,
            'status' => HeritageShopContribution::STATUS_DRAFT,
        ]);

        $showPath = parse_url(route('community-contribution.contributions.show', $contribution), PHP_URL_PATH);
        $editPath = parse_url(route('community-contribution.edit', $draft), PHP_URL_PATH);

        $this->assertSame("/community-contributions/{$contribution->public_id}", $showPath);
        $this->assertSame("/community-contributions/{$draft->public_id}/edit", $editPath);
        $this->assertNotSame("/community-contributions/{$contribution->id}", $showPath);
        $this->assertNotSame("/community-contributions/{$draft->id}/edit", $editPath);

        $this->actingAs($user)
            ->get("/community-contributions/{$contribution->public_id}")
            ->assertOk()
            ->assertSee($contribution->contribution_title);

        $this->actingAs($user)
            ->get("/community-contributions/{$contribution->id}")
            ->assertNotFound();
    }

    public function test_invalid_contribution_route_identifiers_are_handled_safely(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/community-contributions/11111111-1111-4111-8111-111111111111')
            ->assertNotFound();

        $this->actingAs($user)
            ->get('/community-contributions/not-a-valid-public-id')
            ->assertNotFound();

        $this->actingAs($user)
            ->get('/community-contributions/not-a-valid-public-id/edit')
            ->assertNotFound();
    }

    public function test_admin_contribution_routes_use_public_id_instead_of_numeric_id(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create();
        $contribution = HeritageShopContribution::create([
            ...$this->modelContributionData(),
            'user_id' => $user->id,
            'status' => HeritageShopContribution::STATUS_PENDING_REVIEW,
            'submitted_at' => now(),
        ]);

        $showPath = parse_url(route('admin.community-contributions.show', $contribution), PHP_URL_PATH);

        $this->assertSame("/admin/community-contributions/{$contribution->public_id}", $showPath);
        $this->assertNotSame("/admin/community-contributions/{$contribution->id}", $showPath);

        $this->actingAs($admin)
            ->get("/admin/community-contributions/{$contribution->public_id}")
            ->assertOk()
            ->assertSee($contribution->contribution_title);

        $this->actingAs($admin)
            ->get("/admin/community-contributions/{$contribution->id}")
            ->assertNotFound();

        $this->actingAs($admin)
            ->get('/admin/community-contributions/not-a-valid-public-id')
            ->assertNotFound();
    }

    public function test_user_cannot_access_or_modify_another_users_contribution_by_uuid(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $draft = HeritageShopContribution::create([
            ...$this->modelContributionData(),
            'user_id' => $owner->id,
            'status' => HeritageShopContribution::STATUS_DRAFT,
        ]);
        $pending = HeritageShopContribution::create([
            ...$this->modelContributionData(),
            'user_id' => $owner->id,
            'status' => HeritageShopContribution::STATUS_PENDING_REVIEW,
            'submitted_at' => now(),
        ]);

        $this->assertStringContainsString($draft->public_id, route('community-contribution.edit', $draft));
        $this->assertStringContainsString($pending->public_id, route('community-contribution.contributions.show', $pending));

        $this->actingAs($otherUser)
            ->get("/community-contributions/{$pending->public_id}")
            ->assertForbidden();

        $this->actingAs($otherUser)
            ->put("/community-contributions/{$draft->public_id}", $this->validContributionData())
            ->assertForbidden();

        $this->assertDatabaseHas('heritage_shop_contributions', [
            'id' => $draft->id,
            'user_id' => $owner->id,
            'status' => HeritageShopContribution::STATUS_DRAFT,
        ]);
    }

    public function test_internal_contribution_relationships_still_use_numeric_ids(): void
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

        $version = $contribution->recordVersion($user, 'relationship_check');
        $shop = $contribution->approve($admin);

        $this->assertSame($contribution->id, $version->heritage_shop_contribution_id);
        $this->assertSame($contribution->id, $shop->source_contribution_id);
        $this->assertDatabaseHas('contribution_versions', [
            'heritage_shop_contribution_id' => $contribution->id,
            'reason' => 'relationship_check',
        ]);
    }

    public function test_correction_request_routes_use_public_id_instead_of_numeric_id(): void
    {
        $user = User::factory()->create();
        $shop = HeritageShop::create([
            'shop_name' => 'Capital Cafe',
            'publish_status' => 'approved',
        ]);
        $correctionRequest = CorrectionRequest::create([
            'user_id' => $user->id,
            'heritage_shop_id' => $shop->id,
            'field_name' => 'address',
            'current_value' => 'Old address',
            'suggested_value' => 'New address',
            'reason' => 'The shop moved.',
            'status' => CorrectionRequest::STATUS_PENDING,
        ]);

        $showPath = parse_url(route('community-contribution.correction-requests.show', $correctionRequest), PHP_URL_PATH);

        $this->assertUuidString($correctionRequest->public_id);
        $this->assertSame("/community-contributions/correction-requests/{$correctionRequest->public_id}", $showPath);
        $this->assertNotSame("/community-contributions/correction-requests/{$correctionRequest->id}", $showPath);

        $this->actingAs($user)
            ->get("/community-contributions/correction-requests/{$correctionRequest->public_id}")
            ->assertOk()
            ->assertSee('New address');

        $this->actingAs($user)
            ->get("/community-contributions/correction-requests/{$correctionRequest->id}")
            ->assertNotFound();
    }

    public function test_invalid_correction_request_route_identifiers_are_handled_safely(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/community-contributions/correction-requests/11111111-1111-4111-8111-111111111111')
            ->assertNotFound();

        $this->actingAs($user)
            ->get('/community-contributions/correction-requests/not-a-valid-public-id')
            ->assertNotFound();
    }

    public function test_admin_correction_request_routes_use_public_id_instead_of_numeric_id(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create();
        $shop = HeritageShop::create([
            'shop_name' => 'Capital Cafe',
            'publish_status' => 'approved',
        ]);
        $correctionRequest = CorrectionRequest::create([
            'user_id' => $user->id,
            'heritage_shop_id' => $shop->id,
            'field_name' => 'address',
            'current_value' => 'Old address',
            'suggested_value' => 'New address',
            'reason' => 'The shop moved.',
            'status' => CorrectionRequest::STATUS_PENDING,
        ]);

        $showPath = parse_url(route('admin.community-contributions.correction-requests.show', $correctionRequest), PHP_URL_PATH);

        $this->assertSame(
            "/admin/community-contributions/correction-requests/{$correctionRequest->public_id}",
            $showPath
        );
        $this->assertNotSame(
            "/admin/community-contributions/correction-requests/{$correctionRequest->id}",
            $showPath
        );

        $this->actingAs($admin)
            ->get("/admin/community-contributions/correction-requests/{$correctionRequest->public_id}")
            ->assertOk()
            ->assertSee('New address');

        $this->actingAs($admin)
            ->get("/admin/community-contributions/correction-requests/{$correctionRequest->id}")
            ->assertNotFound();

        $this->actingAs($admin)
            ->get('/admin/community-contributions/correction-requests/not-a-valid-public-id')
            ->assertNotFound();
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

    private function requestRevision(User $admin, HeritageShopContribution $contribution): void
    {
        $this->actingAs($admin)
            ->post(route('admin.community-contributions.start-review', $contribution))
            ->assertSessionHasNoErrors();

        $this->actingAs($admin)
            ->post(route('admin.community-contributions.moderate', $contribution), [
                'moderation_action' => 'request_revision',
                'feedback' => 'Please add clearer information about the founder.',
            ])
            ->assertSessionHasNoErrors();
    }

    private function assertUuidString(?string $value): void
    {
        $this->assertIsString($value);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
            $value
        );
    }
}
