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

    private const STOPWORDS = ['the','a','an','is','are','was','were','in','on','at','to','for','of','and','or','what','where','who','when','how','do','does','can','you','me','about','tell','any','there','this','that','shop','shops','restaurant','stall','store','place','food','eat','find','recommend','show','list','navigate','go','want'];
    
    private const DISH_HINTS = ['cendol','ais kacang','nasi lemak','hokkien mee','laksa','roti canai','teh tarik','kopi','satay','nasi','mee','kuih','curry','rendang','sate','char kway teow','kuey teow','ice','coffee','tea','noodle','rice','pancake','roti','murtabak','apam','durian','sambal','ikan bakar','asam','dessert','drink','beverage','snack','street food','heritage','traditional'];

    public function __construct(private HeritageShopCatalog $catalog) {}

    public function respond(Request $request)
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:500'],
            'history' => ['nullable', 'array', 'max:10'],
        ]);

        $message = trim($validated['message']);
        $history = array_slice($validated['history'] ?? [], -(self::MAX_HISTORY_TURNS * 2));

        try {
            $matchedShops = $this->matchShops($message);
        } catch (\Throwable $e) {
            Log::error('ChatController Matching Error: ' . $e->getMessage());
            return response()->json(['reply' => 'Error searching catalog.', 'mode' => 'error']);
        }

        $apiKey = config('services.chatbox_groq.key');
        $model = config('services.chatbox_groq.model', 'openai/gpt-oss-20b');
        $endpoint = config('services.chatbox_groq.endpoint', 'https://api.groq.com/openai/v1/chat/completions' );

        if (empty($apiKey)) {
            return response()->json(['reply' => 'AI not configured.', 'mode' => 'error']);
        }

        try {
            $reply = $this->callGroq($message, $history, $matchedShops, $apiKey, $model, $endpoint);
            return response()->json(['reply' => $reply, 'mode' => 'live']);
        } catch (\Throwable $e) {
            Log::error('Chatbox AI Error: ' . $e->getMessage());
            return response()->json(['reply' => 'Assistant unavailable. Please try again.', 'mode' => 'error']);
        }
    }

    private function matchShops(string $message): array
    {
        $words = collect(preg_split('/[^a-z0-9]+/i', strtolower($message)))
            ->filter(fn ($w) => strlen($w) >= 2 && !in_array($w, self::STOPWORDS, true))
            ->unique()->values();

        if ($words->isEmpty()) return [];

        return collect($this->catalog->all())->map(function (array $shop) use ($words) {
            $haystackParts = [];
            foreach (['name', 'category', 'state', 'description', 'address', 'food_items'] as $field) {
                $value = $shop[$field] ?? '';
                if (is_array($value)) $value = collect($value)->flatten()->implode(' ');
                if (is_string($value) || is_numeric($value)) $haystackParts[] = (string) $value;
            }
            $haystack = strtolower(implode(' ', array_filter($haystackParts)));
            $tokens = preg_split('/[^a-z0-9]+/', $haystack);
            
            $score = $words->reduce(function (int $carry, string $word) use ($haystack, $tokens) {
                if (in_array($word, $tokens, true)) return $carry + 3;
                if (str_contains($haystack, $word)) return $carry + 1;
                return $carry;
            }, 0);

            return ['shop' => $shop, 'score' => $score];
        })->filter(fn ($e) => $e['score'] > 0)->sortByDesc('score')->take(self::MAX_MATCHED_SHOPS)->pluck('shop')->values()->all();
    }

    private function callGroq(string $message, array $history, array $matchedShops, string $apiKey, string $model, string $endpoint): string
    {
        $shopContext = collect($matchedShops)->map(fn ($s) => sprintf("- %s (%s, %s): %s", $s['name'] ?? 'Shop', $s['category'] ?? 'Food', $s['state'] ?? 'Malaysia', $s['description'] ?? 'No desc.'))->implode("\n");

        $systemPrompt = "You are the WarisanMakan Assistant. Help users explore Malaysian heritage food.

        STRICT RULES:
        1. ONLY recommend shops from the Context below.
        2. NEVER invent shop names or addresses.
        3. ALWAYS use full Markdown links for navigation:
           - [Heritage Catalog](/heritage-shops)
           - [Blind Box](/blind-box)
           - [Submit a Shop](/community-contributions/create)
           - [Food Passport](/foodPassport)
           - [Food Trails](/foodtrails)
        4. Keep replies short (2-3 sentences) and warm.

        SHOP CONTEXT:
        {$shopContext}";

        $response = Http::withToken($apiKey)->timeout(15)->post($endpoint, [
            'model' => $model,
            'messages' => array_merge([['role' => 'system', 'content' => $systemPrompt]], $history, [['role' => 'user', 'content' => $message]]),
            'temperature' => 0.4,
            'max_tokens' => 300,
        ]);

        if (!$response->successful()) {
            Log::warning('Groq API failed: ' . $response->body());
            throw new \RuntimeException('Groq API error: ' . $response->status());
        }

        return $response->json('choices.0.message.content') ?? "I'm not sure how to answer that.";
    }
}
