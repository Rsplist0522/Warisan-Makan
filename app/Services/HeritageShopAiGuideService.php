<?php

namespace App\Services;

use App\Models\HeritageShop;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class HeritageShopAiGuideService
{
    public function answer(HeritageShop $shop, string $question): array
    {
        $facts = $this->verifiedFacts($shop);
        $apiKey = config('services.groq.api_key');

        if (blank($apiKey)) {
            return $this->fallback($facts);
        }

        try {
            $response = Http::withToken($apiKey)
                ->acceptJson()
                ->timeout(20)
                ->retry(2, 250)
                ->post(config('services.groq.endpoint'), [
                    'model' => config('services.groq.model', 'llama-3.3-70b-versatile'),
                    'temperature' => 0.2,
                    'response_format' => ['type' => 'json_object'],
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'You are the HeritageShop visitor guide for Warisan Makan. Answer the visitor question using only the verified facts supplied below. Do not invent history, prices, ingredients, opening hours, accessibility, halal status, transport details, or recommendations that are not supported by the facts. If the facts do not answer the question, say so clearly and suggest contacting the shop or checking the registered source. Keep the tone warm, concise, culturally respectful, and useful. Return JSON only with exactly these keys: answer (string), highlights (array of strings), confidence (string: verified or limited).',
                        ],
                        [
                            'role' => 'user',
                            'content' => "Visitor question:\n{$question}\n\nVerified HeritageShop facts:\n" . json_encode($facts, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                        ],
                    ],
                ]);

            if ($response->failed()) {
                return $this->fallback($facts);
            }

            $content = $response->json('choices.0.message.content');
            $payload = is_string($content) ? json_decode($content, true) : null;

            if (! is_array($payload) || blank($payload['answer'] ?? null)) {
                return $this->fallback($facts);
            }

            return [
                'status' => 'ready',
                'answer' => Str::limit(trim((string) $payload['answer']), 1200, ''),
                'highlights' => collect($payload['highlights'] ?? [])
                    ->filter(fn ($item) => is_string($item) && trim($item) !== '')
                    ->map(fn (string $item) => Str::limit(trim($item), 180, ''))
                    ->take(4)
                    ->values()
                    ->all(),
                'confidence' => ($payload['confidence'] ?? 'limited') === 'verified' ? 'verified' : 'limited',
                'source_note' => 'Grounded in the verified profile shown on this page.',
            ];
        } catch (\Throwable) {
            return $this->fallback($facts);
        }
    }

    private function verifiedFacts(HeritageShop $shop): array
    {
        $normalizedItems = $shop->relationLoaded('activeFoodItems')
            ? $shop->activeFoodItems
            : $shop->activeFoodItems()->get();

        $menu = $normalizedItems->isNotEmpty()
            ? $normalizedItems->map(fn ($item): array => array_filter([
                'name' => $item->name,
                'price' => $item->price,
                'description' => $item->description,
                'category' => $item->category,
                'heritage_significance' => $item->heritage_significance,
                'availability' => $item->availability,
            ], fn ($value) => filled($value)))->take(12)->values()->all()
            : collect(is_array($shop->food_items) ? $shop->food_items : [])
                ->filter(fn ($item) => is_array($item) && filled($item['name'] ?? null) && ($item['is_active'] ?? true) !== false)
                ->map(fn (array $item): array => array_filter([
                    'name' => (string) ($item['name'] ?? ''),
                    'price' => $item['price'] ?? null,
                    'description' => $item['desc'] ?? $item['description'] ?? null,
                    'category' => $item['category'] ?? null,
                    'heritage_significance' => $item['heritage_significance'] ?? null,
                    'availability' => $item['availability'] ?? null,
                ], fn ($value) => filled($value)))
                ->take(12)
                ->values()
                ->all();

        return array_filter([
            'shop_name' => $shop->shop_name,
            'category' => $shop->primary_food_category,
            'heritage_story' => $shop->heritage_story,
            'establishment_year' => $shop->establishment_year,
            'founder' => $shop->founder_name,
            'founder_background' => $shop->founder_background,
            'current_owner' => $shop->current_owner_name,
            'current_owner_details' => $shop->current_owner_details,
            'address' => $shop->address,
            'city' => $shop->city,
            'state' => $shop->state,
            'postal_code' => $shop->postal_code,
            'operating_hours' => $shop->operating_hours,
            'contact_number' => $shop->contact_number,
            'menu' => $menu,
            'registered_source_url' => $shop->source_url,
        ], fn ($value) => filled($value) || $value === []);
    }

    private function fallback(array $facts): array
    {
        $name = (string) ($facts['shop_name'] ?? 'This heritage shop');
        $story = trim((string) ($facts['heritage_story'] ?? ''));
        $location = collect([$facts['city'] ?? null, $facts['state'] ?? null])->filter()->implode(', ');
        $menuNames = collect($facts['menu'] ?? [])
            ->pluck('name')
            ->filter()
            ->take(3)
            ->implode(', ');

        $answer = "Here is a verified snapshot of {$name}.";
        if ($story !== '') {
            $answer .= ' '.$story;
        }
        if ($location !== '') {
            $answer .= " It is listed in {$location}.";
        }
        if ($menuNames !== '') {
            $answer .= " Recorded menu highlights include {$menuNames}.";
        }

        return [
            'status' => 'fallback',
            'answer' => Str::limit($answer, 1200, ''),
            'highlights' => collect([
                $facts['category'] ?? null,
                $facts['operating_hours'] ?? null,
                $facts['address'] ?? null,
            ])->filter()->map(fn ($item) => Str::limit((string) $item, 180, ''))->take(4)->values()->all(),
            'confidence' => 'verified',
            'source_note' => 'AI is unavailable right now; this answer uses saved verified profile facts only.',
        ];
    }
}
