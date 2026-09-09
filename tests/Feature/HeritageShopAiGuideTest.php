<?php

namespace Tests\Feature;

use App\Models\HeritageShop;
use App\Models\User;
use App\Services\HeritageShopAiGuideService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class HeritageShopAiGuideTest extends TestCase
{
    use RefreshDatabase;

    public function test_published_shop_ai_guide_returns_grounded_structured_answer(): void
    {
        config()->set('services.groq.api_key', 'test-ai-key');
        config()->set('services.groq.endpoint', 'https://ai.test/chat');
        config()->set('services.groq.model', 'test-model');
        Http::fake([
            'https://ai.test/*' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => json_encode([
                            'answer' => 'This shop is known for preserving a family recipe tradition.',
                            'highlights' => ['Traditional Food', 'Kuala Lumpur'],
                            'confidence' => 'verified',
                        ]),
                    ],
                ]],
            ]),
        ]);

        $shop = HeritageShop::create([
            'shop_name' => 'AI Heritage Cafe',
            'primary_food_category' => 'Traditional Food',
            'heritage_story' => 'A family recipe tradition preserved across generations.',
            'city' => 'Kuala Lumpur',
            'publish_status' => HeritageShop::STATUS_PUBLISHED,
        ]);

        $response = $this->actingAs(User::factory()->create())
            ->withCsrf()
            ->postJson(route('heritage-shops.ai-guide', $shop), [
            'question' => 'What makes this shop special?',
        ]);

        $response->assertOk()
            ->assertJsonPath('status', 'ready')
            ->assertJsonPath('confidence', 'verified')
            ->assertJsonPath('answer', 'This shop is known for preserving a family recipe tradition.')
            ->assertJsonPath('highlights.0', 'Traditional Food');

        Http::assertSent(function ($request): bool {
            $body = json_encode($request->data());

            return str_contains($request->url(), 'ai.test')
                && str_contains($body, 'AI Heritage Cafe')
                && str_contains($body, 'family recipe tradition')
                && ! str_contains($body, 'password');
        });
    }

    public function test_ai_guide_does_not_answer_for_draft_or_archived_shops(): void
    {
        Http::fake();
        $draft = HeritageShop::create([
            'shop_name' => 'Private Heritage Cafe',
            'publish_status' => HeritageShop::STATUS_DRAFT,
        ]);

        $this->actingAs(User::factory()->create())
            ->withCsrf()
            ->postJson(route('heritage-shops.ai-guide', $draft), [
            'question' => 'Tell me about this shop.',
        ])->assertNotFound();

        Http::assertNothingSent();
    }

    public function test_ai_guide_falls_back_to_verified_facts_when_ai_is_unavailable(): void
    {
        config()->set('services.groq.api_key', null);
        $shop = HeritageShop::create([
            'shop_name' => 'Fallback Heritage Cafe',
            'primary_food_category' => 'Kuih',
            'heritage_story' => 'A village recipe collection shared with the community.',
            'city' => 'Penang',
            'food_items' => [['name' => 'Kuih Lapis', 'price' => 'RM 5']],
            'publish_status' => HeritageShop::STATUS_PUBLISHED,
        ]);

        $response = $this->actingAs(User::factory()->create())
            ->withCsrf()
            ->postJson(route('heritage-shops.ai-guide', $shop), [
            'question' => 'What is this place?',
        ]);

        $response->assertOk()
            ->assertJsonPath('status', 'fallback')
            ->assertJsonPath('confidence', 'verified')
            ->assertJsonPath('highlights.0', 'Kuih');
        $this->assertStringContainsString('Fallback Heritage Cafe', $response->json('answer'));
    }

    public function test_ai_guide_validates_question_length_and_required_content(): void
    {
        $shop = HeritageShop::create([
            'shop_name' => 'Validation Heritage Cafe',
            'publish_status' => HeritageShop::STATUS_PUBLISHED,
        ]);

        $missingQuestion = $this->actingAs(User::factory()->create())
            ->withCsrf()
            ->postJson(route('heritage-shops.ai-guide', $shop), []);
        $this->assertSame(302, $missingQuestion->status());

        $oversizedQuestion = $this->actingAs(User::factory()->create())
            ->withCsrf()
            ->postJson(route('heritage-shops.ai-guide', $shop), [
            'question' => str_repeat('x', 501),
        ]);
        $this->assertSame(302, $oversizedQuestion->status());
    }

    public function test_guest_cannot_invoke_the_ai_guide(): void
    {
        $shop = HeritageShop::create([
            'shop_name' => 'Protected AI Heritage Cafe',
            'publish_status' => HeritageShop::STATUS_PUBLISHED,
        ]);

        $this->withCsrf()->postJson(route('heritage-shops.ai-guide', $shop), [
            'question' => 'What is this place?',
        ])->assertRedirect(route('login'));
    }

    private function withCsrf(): self
    {
        $token = 'heritage-ai-guide-test-csrf-token';

        return $this->withSession(['_token' => $token])
            ->withHeader('X-CSRF-TOKEN', $token);
    }
}
