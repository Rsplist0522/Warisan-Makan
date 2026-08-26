<?php

namespace App\Http\Controllers;

use App\Services\HeritageShopCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatController extends Controller
{
    /**
     * Words that carry no matching value. Kept out so a message like "cendol
     * shop" still scores the cendol shop (the word "shop" is ignored, but
     * "cendol" scores).
     */
    private const STOPWORDS = [
        'the', 'a', 'an', 'is', 'are', 'was', 'were', 'in', 'on', 'at', 'to', 'for',
        'of', 'and', 'or', 'what', 'where', 'who', 'when', 'how', 'do', 'does',
        'can', 'you', 'me', 'about', 'tell', 'any', 'there', 'this', 'that',
        'shop', 'shops', 'restaurant', 'stall', 'store', 'place', 'food', 'eat',
    ];

    /**
     * Signal words that describe a dish/item rather than a shop. When the
     * user asks about one of these but NO shop in the catalog contains the
     * word, we fall back to dish knowledge instead of forcing a wrong match.
     */
    private const DISH_HINTS = [
        'cendol', 'ais kacang', 'nasi lemak', 'hokkien mee', 'laksa', 'roti canai',
        'teh tarik', 'kopi', 'satay', 'satay', 'nasi', 'mee', 'kuih', 'curry',
        'rendang', 'sate', 'mee', 'char kway teow', 'kuey teow', 'ice', 'coffee',
        'tea', 'noodle', 'rice', 'pancake', 'roti', 'murtabak', 'apam',
    ];

    private const MAX_MATCHED_SHOPS = 5;
    private const MAX_HISTORY_TURNS = 6;

    public function __construct(private HeritageShopCatalog $catalog)
    {
    }

    public function respond(Request $request)
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:500'],
            'history' => ['nullable', 'array', 'max:' . self::MAX_HISTORY_TURNS],
            'history.*.role' => ['required_with:history', 'string', 'in:user,assistant'],
            'history.*.content' => ['required_with:history', 'string', 'max:1000'],
        ]);

        $message = trim($validated['message']);
        $history = array_slice($validated['history'] ?? [], -self::MAX_HISTORY_TURNS);

        $matchedShops = $this->matchShops($message);

        $apiKey = config('services.groq.key');

        if (empty($apiKey)) {
            return response()->json([
                'reply' => $this->stubReply($message, $matchedShops),
                'matched_shop_count' => count($matchedShops),
                'mode' => 'stub',
            ]);
        }

        try {
            $reply = $this->callGroq($message, $history, $matchedShops);

            return response()->json([
                'reply' => $reply,
                'matched_shop_count' => count($matchedShops),
                'mode' => 'live',
            ]);
        } catch (\Throwable $e) {
            Log::warning('ChatController: Groq call failed. ' . $e->getMessage());

            return response()->json([
                'reply' => "Sorry, I couldn't reach the assistant right now. Please try again in a moment.",
                'matched_shop_count' => count($matchedShops),
                'mode' => 'error',
            ], 200);
        }
    }

    private function matchShops(string $message): array
    {
        // Split into tokens, keep any word of 2+ chars except pure stopwords.
        // e.g. "cendol shop" -> ['cendol', 'shop'] (shop filtered, 'cendol' kept)
        $words = collect(preg_split('/[^a-z0-9]+/i', strtolower($message)))
            ->filter(fn ($w) => strlen($w) >= 2 && !in_array($w, self::STOPWORDS, true))
            ->unique()
            ->values();

        $shops = $this->catalog->all();

        if ($words->isEmpty()) {
            // Greetings / pure stopword messages: give the AI the full catalog
            // so it can still be friendly and introduce the shops.
            return $shops;
        }

        // --- Scoring -------------------------------------------------------
        // 1. Exact whole-word token match in the haystack  -> 2 points
        //    (strongest signal — the word appears as a whole word)
        // 2. Partial/substring match                       -> 1 point
        //    (catches 'cendol' inside 'cendol warisan melaka')
        // 3. Malay/geographic aliases                      -> 1 point
        // --------------------------------------------------------------------

        return collect($shops)->map(function (array $shop) use ($words) {
            // The haystack only uses fields the catalog actually returns,
            // so real DB rows and fake samples score the same way.
            $haystack = strtolower(implode(' ', array_filter([
                $shop['name'] ?? '',
                $shop['category'] ?? '',
                $shop['state'] ?? '',
                $shop['description'] ?? '',
                $shop['address'] ?? '',
                is_array($shop['food_items'] ?? null) ? implode(' ', $shop['food_items']) : '',
            ])));

            // Individual haystack tokens for whole-word matching.
            $tokens = preg_split('/[^a-z0-9]+/', $haystack);

            $score = $words->reduce(function (int $carry, string $word) use ($haystack, $tokens) {
                // Exact whole-word token match (strongest signal).
                if (in_array($word, $tokens, true)) {
                    return $carry + 2;
                }
                // Substring match — catches 'cendol' in a shop name like
                // 'Cendol Warisan Melaka' even when tokenized differently.
                if (str_contains($haystack, $word)) {
                    return $carry + 1;
                }

                // Geographic aliases so "malaysia"/"malaya"/"kl" match states.
                $alias = [
                    'malaysia' => ['malaysia', 'malaya'],
                    'malaya'   => ['malaysia', 'malaya'],
                    'kl'       => ['federal territory of kuala lumpur', 'kuala lumpur'],
                ];
                foreach (($alias[$word] ?? []) as $needle) {
                    if (str_contains($haystack, $needle)) {
                        return $carry + 1;
                    }
                }

                return $carry;
            }, 0);

            return ['shop' => $shop, 'score' => $score];
        })->filter(fn ($entry) => $entry['score'] > 0)
          ->sortByDesc('score')
          ->take(self::MAX_MATCHED_SHOPS)
          ->pluck('shop')
          ->values()
          ->all();
    }

    /**
     * Whether the message asks about a specific dish/item rather than a shop.
     */
    private function isDishQuestion(string $message): bool
    {
        $lower = strtolower($message);

        foreach (self::DISH_HINTS as $hint) {
            if (str_contains($lower, $hint)) {
                return true;
            }
        }

        return false;
    }

    private function stubReply(string $message, array $matchedShops): string
    {
        if (empty($matchedShops)) {
            return "🤖 [Test mode — no AI key configured yet] I couldn't find a shop matching \"{$message}\" in the current catalog.";
        }

        $names = collect($matchedShops)->pluck('name')->implode(', ');

        return "🤖 [Test mode — no AI key configured yet] Based on your question, I found these matching shops: {$names}. A live AI key isn't configured yet, so this is a placeholder reply — the search/matching logic is already working though.";
    }

    private function callGroq(string $message, array $history, array $matchedShops): string
    {
        // NOTE: the default is only used when GROQ_MODEL is absent in .env.
        $model = config('services.groq.model', 'openai/gpt-oss-20b');
        $apiKey = config('services.groq.key');

        $allShops = $this->catalog->all();

        $shopContext = collect($matchedShops)->map(function (array $shop) {
            return sprintf(
                "- %s (%s, %s, est. %s): %s",
                $shop['name'] ?? 'Unknown',
                $shop['category'] ?? 'Heritage',
                $shop['state'] ?? 'Malaysia',
                $shop['year'] ?? 'n/a',
                $shop['description'] ?? ''
            );
        })->implode("\n");

        if (empty($matchedShops)) {
            // Nothing matched strictly. Two cases:
            //  a) Dish question with no catalog match  -> NO_SHOP_MATCH,
            //     but the AI may still share safe general knowledge about
            //     the dish itself.
            //  b) Unrelated question                  -> IRRELEVANT,
            //     the AI politely declines and restates the scope.
            $contextList = collect($allShops)->map(function (array $shop) {
                return sprintf(
                    "- %s (%s, %s)",
                    $shop['name'] ?? 'Unknown',
                    $shop['category'] ?? 'Heritage',
                    $shop['state'] ?? 'Malaysia'
                );
            })->implode("\n");

            $questionKind = $this->isDishQuestion($message) ? 'NO_SHOP_MATCH' : 'IRRELEVANT';
            $matchMode = "{$questionKind}:{$contextList}";
        } else {
            $matchMode = 'EXACT_MATCH';
        }

        $systemPrompt = <<<PROMPT
You are the WarisanMakan Heritage Shop Assistant, a friendly expert on
Malaysian heritage food shops.

Match mode: {$matchMode}

When the match mode is EXACT_MATCH, these are the shops that matched the
question — describe them:
{$shopContext}

Rules:
- In EXACT_MATCH mode, describe the listed shops warmly.
- In NO_SHOP_MATCH mode, the user asked about a dish (e.g. cendol, laksa,
  teh tarik) but NO shop in the catalog serves it. Do NOT recommend any
  shop. Instead say exactly that: no heritage shop in our catalog serves
  this dish, then share 1-2 sentences of brief, safe general knowledge
  about the dish itself (what it is, how it is made). Be friendly.
- In IRRELEVANT mode, the question is unrelated to heritage food shops
  (e.g. news, personal matters, other topics). Politely decline and say
  that WarisanMakan only provides information about Malaysian heritage
  shops and their food, and invite them to ask about a shop instead.
  Do NOT answer the off-topic question.
- If the message is just a greeting, warmly introduce WarisanMakan and
  mention one or two featured shops from the catalog.
- Base everything on the catalog provided; only add brief, safe general
  knowledge when a dish itself is asked about in NO_SHOP_MATCH mode.
- Keep replies short (2-4 sentences), warm, and friendly.
PROMPT;

        $messages = array_merge(
            [['role' => 'system', 'content' => $systemPrompt]],
            $history,
            [['role' => 'user', 'content' => $message]]
        );

        $response = Http::withToken($apiKey)
            ->timeout(15)
            ->post('https://api.groq.com/openai/v1/chat/completions', [
                'model' => $model,
                'messages' => $messages,
                'temperature' => 0.4,
                'max_tokens' => 300,
            ]);

        if (!$response->successful()) {
            throw new \RuntimeException('Groq API error: ' . $response->status() . ' ' . $response->body());
        }

        return $response->json('choices.0.message.content')
            ?? "Sorry, I didn't get a usable reply. Please try rephrasing your question.";
    }
}
