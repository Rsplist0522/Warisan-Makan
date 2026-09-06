<?php

namespace App\Http\Controllers;

use App\Services\HeritageShopCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatController extends Controller
{
    private const MAX_MATCHED_SHOPS = 4;
    private const MAX_HISTORY_TURNS = 3;

    /**
     * Common words that should not influence shop matching.
     */
    private const STOPWORDS = [
        'the', 'a', 'an', 'is', 'are', 'was', 'were', 'in', 'on', 'at', 'to',
        'for', 'of', 'and', 'or', 'what', 'where', 'who', 'when', 'how', 'do',
        'does', 'can', 'you', 'me', 'about', 'tell', 'any', 'there', 'this',
        'that', 'shop', 'shops', 'restaurant', 'stall', 'store', 'place',
        'food', 'eat', 'find', 'recommend', 'show', 'list', 'navigate', 'go',
        'want', 'please', 'give', 'some', 'something', 'near', 'nearby',
        'looking', 'like', 'would', 'could', 'help', 'with', 'it', 'its',
        'they', 'them', 'their', 'one', 'ones', 'more', 'best', 'good',
    ];

    /**
     * Food-related terms that help identify food discovery questions.
     * Broad location/category terms have been removed to keep boosts focused.
     */
    private const DISH_HINTS = [
        'cendol', 'ais kacang', 'nasi lemak', 'hokkien mee', 'laksa',
        'roti canai', 'teh tarik', 'kopi', 'satay', 'nasi', 'mee', 'kuih',
        'curry', 'rendang', 'sate', 'char kway teow', 'kuey teow', 'ice',
        'coffee', 'tea', 'noodle', 'rice', 'pancake', 'roti', 'murtabak',
        'apam', 'durian', 'sambal', 'ikan bakar', 'asam', 'dessert',
        'drink', 'beverage', 'snack', 'street food',
    ];

    public function __construct(
        private HeritageShopCatalog $catalog
    ) {}

    /**
     * Handle a chatbot request.
     */
    public function respond(Request $request)
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:500'],
            'history' => ['nullable', 'array', 'max:10'],
        ]);

        $message = trim($validated['message']);

        // Keep only the latest few turns and ensure each entry has valid structure
        $rawHistory = array_slice($validated['history'] ?? [], -(self::MAX_HISTORY_TURNS * 2));
        $history = array_values(array_filter($rawHistory, function ($item) {
            return isset($item['role'], $item['content']) &&
                   in_array($item['role'], ['user', 'assistant'], true);
        }));

        try {
            /*
             * Include previous user messages when finding relevant shops.
             * This helps with follow-up questions while avoiding assistant
             * responses from influencing the search.
             */
            $searchText = $message;

            foreach ($history as $turn) {
                if (!is_array($turn) || empty($turn['content'])) {
                    continue;
                }

                // Only use user messages for shop matching
                if (($turn['role'] ?? '') === 'user') {
                    $searchText .= ' ' . strip_tags((string) $turn['content']);
                }
            }

            $matchedShops = $this->matchShops($searchText);
        } catch (\Throwable $e) {
            Log::error('ChatController Matching Error: ' . $e->getMessage());
            $matchedShops = [];
        }

        $apiKey = config('services.chatbox_groq.key');
        $model = config('services.chatbox_groq.model', 'openai/gpt-oss-20b');
        $endpoint = config('services.chatbox_groq.endpoint', 'https://api.groq.com/openai/v1/chat/completions');

        if (empty($apiKey)) {
            return response()->json([
                'reply' => 'The Warisan Makan Assistant is temporarily unavailable. You can still explore the [Heritage Catalog](/heritage-shops), [Food Trails](/foodtrails), [Food Passport](/foodPassport), or [Blind Box](/blind-box).',
                'mode' => 'error',
            ]);
        }

        try {
            $reply = $this->callGroq($message, $history, $matchedShops, $apiKey, $model, $endpoint);

            return response()->json([
                'reply' => $reply,
                'mode' => 'live',
            ]);
        } catch (\Throwable $e) {
            Log::error('Chatbox AI Error: ' . $e->getMessage());

            return response()->json([
                'reply' => 'I’m having trouble connecting to the assistant right now. You can explore the [Heritage Catalog](/heritage-shops), discover [Food Trails](/foodtrails), check your [Food Passport](/foodPassport), or try the [Blind Box](/blind-box) for a random heritage-food discovery.',
                'mode' => 'error',
            ]);
        }
    }

    /**
     * Find heritage shops relevant to the user's message.
     */
    private function matchShops(string $message): array
    {
        // Use Unicode-safe lowercasing and tokenisation
        $lowerMessage = mb_strtolower($message, 'UTF-8');

        $words = collect(preg_split('/\W+/u', $lowerMessage))
            ->filter(fn ($word) => strlen($word) >= 2 && !in_array($word, self::STOPWORDS, true))
            ->unique()
            ->values();

        if ($words->isEmpty()) {
            return [];
        }

        return collect($this->catalog->all())
            ->map(function (array $shop) use ($words, $lowerMessage) {
                $haystackParts = [];

                foreach (['name', 'category', 'state', 'description', 'address', 'food_items'] as $field) {
                    $value = $shop[$field] ?? '';

                    if (is_array($value)) {
                        $value = collect($value)->flatten()->implode(' ');
                    }

                    if (is_string($value) || is_numeric($value)) {
                        $haystackParts[] = (string) $value;
                    }
                }

                $haystack = mb_strtolower(implode(' ', array_filter($haystackParts)), 'UTF-8');
                $tokens = preg_split('/\W+/u', $haystack);

                // Basic token match and partial match scoring
                $score = $words->reduce(function (int $carry, string $word) use ($haystack, $tokens) {
                    if (in_array($word, $tokens, true)) {
                        return $carry + 3;
                    }
                    if (mb_strpos($haystack, $word) !== false) {
                        return $carry + 1;
                    }
                    return $carry;
                }, 0);

                /*
                 * Give food-related words slightly more importance
                 * because users commonly ask for a specific dish.
                 */
                foreach (self::DISH_HINTS as $hint) {
                    if (mb_strpos($lowerMessage, $hint) !== false && mb_strpos($haystack, $hint) !== false) {
                        $score += 3;
                    }
                }

                // Bonus when the entire user message appears verbatim in the shop data
                if (mb_strlen($lowerMessage) >= 4 && mb_strpos($haystack, $lowerMessage) !== false) {
                    $score += 5;
                }

                return ['shop' => $shop, 'score' => $score];
            })
            ->filter(fn ($entry) => $entry['score'] > 0)
            ->sortByDesc('score')
            ->take(self::MAX_MATCHED_SHOPS)
            ->pluck('shop')
            ->values()
            ->all();
    }

    /**
     * Send the user's message and relevant catalog information to Groq.
     */
    private function callGroq(
        string $message,
        array $history,
        array $matchedShops,
        string $apiKey,
        string $model,
        string $endpoint
    ): string {
        $shopContext = collect($matchedShops)
            ->map(function (array $shop) {
                $foodItems = $shop['food_items'] ?? [];

                if (is_array($foodItems)) {
                    $foodItems = collect($foodItems)->flatten()->implode(', ');
                }

                return sprintf(
                    "- %s (%s, %s)\n  Address: %s\n  Food: %s\n  Description: %s",
                    $shop['name'] ?? 'Shop',
                    $shop['category'] ?? 'Food',
                    $shop['state'] ?? 'Malaysia',
                    $shop['address'] ?? 'Address not available',
                    $foodItems ?: 'Food information not available',
                    $shop['description'] ?? 'No description available'
                );
            })
            ->implode("\n");

        if (empty($shopContext)) {
            $shopContext = 'No matching heritage shops were found in the catalog for this message.';
        }

        $systemPrompt = <<<PROMPT
You are the Warisan Makan Assistant.

Your role is to help users explore Malaysian heritage food and understand
the features available in the Warisan Makan system.

Be friendly, useful, concise, natural, and polite. Always try to help the
user move toward something useful instead of simply saying "I don't know"
or "I'm not sure."

IMPORTANT GENERAL RULES:

1. NEVER invent heritage shop names, food items, addresses, locations,
   prices, fees, vouchers, rewards, or system capabilities.

2. ONLY recommend a specific heritage shop when that shop appears in the
   SHOP CONTEXT below.

3. If the SHOP CONTEXT does not contain a suitable shop, do not invent
   one. Explain that no matching shop was found in the current catalog
   and suggest an appropriate Warisan Makan feature.

4. You may answer general questions about Malaysian heritage food using
   general knowledge, but do not present general knowledge as information
   from the Warisan Makan catalog.

5. Keep normal responses around 2-4 sentences unless the user asks for
   more detail.

6. Do not force links into every response. Only provide links relevant
   to the user's request.

7. Do not claim that Warisan Makan has functions that are not explicitly
   described below.

WARISAN MAKAN USER FEATURES:

A. HERITAGE CATALOG

Users can browse and search Malaysian heritage shops and explore their
available information.

Link:
[Heritage Catalog](/heritage-shops)

Use this when users want to browse, search, or learn about heritage
shops, food, locations, menus, or available heritage-food information.

B. SUBMIT A SHOP

Users can submit a heritage shop to Warisan Makan.

Link:
[Submit a Shop](/community-contributions/create)

Use this when users want to contribute or submit a heritage shop.

Only describe this as a submission feature. Do not claim that submissions
are automatically approved.

C. USER PROFILE

Users can view and manage their Warisan Makan profile.

Link:
[User Profile](/profile)

Only provide this link when the user asks about their profile, account,
or profile information.

D. FOOD PASSPORT AND BADGES

Food Passport allows users to explore their food discovery progress,
including shop check-ins and badges/achievements.

Link:
[Food Passport](/foodPassport)

Use this when users ask about Food Passport, check-ins, badges,
achievements, or their food exploration progress.

E. FOOD TRAILS

Food Trails allows users to explore a route between a starting
destination and an ending destination.

Users can enter their starting and ending destinations, and heritage
food shops along the route can be explored.

Link:
[Food Trails](/foodtrails)

Use this when users want to plan a food journey, explore a route,
find heritage shops along a route, or understand how Food Trails works.

Do not claim that Food Trails provides transportation booking or other
functions that are not stated here.

F. BLIND BOX

Blind Box is a FREE random heritage-food discovery feature.

Blind Box is mainly intended for users who:
- do not know what they want to eat
- do not have a specific destination
- want a surprise
- want to randomly discover heritage food or shops
- want to explore different available food or shop options using the Blind Box 

Link:
[Blind Box](/blind-box)

VERY IMPORTANT BLIND BOX RULES:

- There is NO entry fee.
- There is NO payment required.
- There are NO vouchers.
- There is NO voucher redemption.
- There are NO monetary rewards.
- Never say that users need to pay to use Blind Box.
- Never say that Blind Box provides a voucher.
- Never describe Blind Box as a paid service.

Blind Box should NOT replace normal heritage-shop recommendations.

For example:

User: "Where can I eat nasi lemak?"

First use suitable shops from SHOP CONTEXT.

If there is a matching shop, recommend it.

You MAY additionally say:
"If you'd like to discover different heritage-food options instead,
you can also try the [Blind Box](/blind-box)."

Do NOT immediately send the user to Blind Box simply because they asked
where to eat.

User: "I don't know what to eat."

You MAY recommend Blind Box.

User: "Surprise me."

You MAY recommend Blind Box.

User: "I don't know where to go."

You MAY suggest Blind Box for random food discovery or Food Trails if
the user is interested in exploring a route.

NAVIGATION BEHAVIOUR:

Available user-feature links:

[Heritage Catalog](/heritage-shops)
[Submit a Shop](/community-contributions/create)
[User Profile](/profile)
[Food Passport](/foodPassport)
[Food Trails](/foodtrails)
[Blind Box](/blind-box)

Use only the link that is relevant to the current request.

SPECIFIC FOOD QUESTIONS:

If the user asks for a specific food such as nasi lemak, laksa,
cendol, roti canai, or another dish:

1. Check SHOP CONTEXT.
2. Recommend matching shops only when they appear in SHOP CONTEXT.
3. If no suitable shop is available, do not invent one.
4. Politely explain that no matching shop was found in the current
   catalog.
5. Suggest the [Heritage Catalog](/heritage-shops) for further browsing.
6. You may also suggest the [Blind Box](/blind-box) if the user wants to
discover different food or shops rather than search for one specific
dish.

LOCATION QUESTIONS:

If the user asks for a shop in a particular location:

1. Use only shops from SHOP CONTEXT.
2. Do not invent nearby businesses.
3. If no suitable shop is available, explain that the current catalog
   does not contain a matching result.
4. Suggest the [Heritage Catalog](/heritage-shops) for more browsing.

BROAD FOOD DISCOVERY:

For questions such as:
- "What should I eat?"
- "Recommend something."
- "I don't know what to eat."
- "Give me something interesting."
- "Surprise me."

Help the user discover something.

If suitable shops are in SHOP CONTEXT, recommend them.

If there are no suitable shops and the user wants random discovery,
suggest the free [Blind Box](/blind-box).

FOOD TRAIL QUESTIONS:

If the user asks how Food Trails works, explain that they can enter a
starting destination and an ending destination, then explore heritage
food shops that are available along the route.

Direct them to:
[Food Trails](/foodtrails)

FOLLOW-UP QUESTIONS:

Use the conversation history to understand follow-up questions.

For example:

User: "Tell me about Bunn Choon."
Assistant: provides available catalog information.

User: "Where is it?"
Assistant: use the previously discussed shop when it is present in
SHOP CONTEXT and provide its available address.

Do not invent information that is not available.

UNRELATED QUESTIONS:

If a question is unrelated to Warisan Makan, answer briefly and
naturally when possible.

Do not repeatedly force Warisan Makan links into unrelated conversations.

If appropriate, gently return the conversation to a relevant feature.

NO MATCHING SHOP:

If SHOP CONTEXT says:
"No matching heritage shops were found in the catalog for this message."

Do not invent a shop.

Instead, respond naturally and helpfully.

For example:
"I couldn't find a matching heritage shop in the current catalog. You
can browse the [Heritage Catalog](/heritage-shops) for more options, or
try the [Blind Box](/blind-box) if you'd like a random heritage-food
discovery."

Do not always use exactly the same wording.

SHOP CONTEXT:

{$shopContext}
PROMPT;

        $response = Http::withToken($apiKey)
            ->timeout(15)
            ->post($endpoint, [
                'model' => $model,
                'messages' => array_merge(
                    [['role' => 'system', 'content' => $systemPrompt]],
                    $history,
                    [['role' => 'user', 'content' => $message]]
                ),
                'temperature' => 0.3,
                'max_tokens' => 400,
            ]);

        if (!$response->successful()) {
            Log::warning('Groq API failed: ' . $response->body());
            throw new \RuntimeException('Groq API error: ' . $response->status());
        }

        return $response->json('choices.0.message.content')
            ?? 'I’m unable to generate a response right now, but you can explore the [Heritage Catalog](/heritage-shops), [Food Trails](/foodtrails), or [Blind Box](/blind-box) for more discoveries.';
    }
}