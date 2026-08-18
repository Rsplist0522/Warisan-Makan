<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;

class ShopCrawlerService
{
    public function crawl(string $url): array
    {
        $parsed = parse_url($url);

        if (! is_array($parsed) || empty($parsed['host']) || ! in_array(strtolower((string) ($parsed['scheme'] ?? '')), ['http', 'https'], true)) {
            throw new InvalidArgumentException('Please provide a valid http or https URL to crawl.');
        }

        $response = Http::accept('text/html')->timeout(15)->get($url);
        if ($response->failed() || trim((string) $response->body()) === '') {
            throw new InvalidArgumentException('The provided URL could not be fetched. Please check that the website is accessible and publicly viewable.');
        }

        $html = $response->body();
        $payload = $this->extractFromHtml($url, $html);

        if ($this->shouldEnhanceWithAi()) {
            $payload = $this->enhanceWithAi($url, $payload, $html);
        }

        return $payload;
    }

    public function extractFromHtml(string $url, string $html): array
    {
        $title = $this->extractTag($html, 'title');
        $description = $this->extractMeta($html, ['property' => 'og:description', 'name' => 'description'])
            ?? $this->extractMeta($html, ['name' => 'twitter:description'])
            ?? $this->extractTextSnippet($html, 220);

        $jsonLd = $this->extractJsonLd($html);
        $name = $this->firstNonEmpty([
            $jsonLd['name'] ?? null,
            $this->extractMeta($html, ['property' => 'og:title', 'name' => 'title']),
            $title,
        ]);

        $address = $this->buildAddress($jsonLd, $html);
        $contactNumber = $this->firstNonEmpty([
            $jsonLd['telephone'] ?? null,
            $this->extractMeta($html, ['property' => 'business:contact_data:phone_number'])
        ]);
        $category = $this->firstNonEmpty([
            $jsonLd['servesCuisine'][0] ?? null,
            $jsonLd['category'] ?? null,
            $this->extractMeta($html, ['property' => 'og:site_name'])
        ]);

        $menu = $this->extractMenu($html, $jsonLd);

        $images = $this->extractImages($html, $jsonLd);

        $heritageStory = $description ?: 'This record was auto-filled from the source website and should be reviewed before publishing.';

        return [
            'name' => $name ?: 'Crawled Heritage Shop',
            'description' => $heritageStory,
            'heritage_story' => $heritageStory,
            'menu' => $menu,
            'images' => $images,
            'address' => $address ?: 'Address not detected from the source website.',
            'contact_number' => $contactNumber ?: '+603-0000 0000',
            'source_url' => $url,
            'city' => $this->extractCityFromAddress($address),
            'state' => $this->extractStateFromAddress($address),
            'postal_code' => $this->extractPostalCodeFromAddress($address),
            'primary_food_category' => $category ?: 'Traditional Cuisine',
            'operating_hours' => $this->extractHours($html),
            'food_items' => self::menuToFoodItems($menu),
            'crawler_token' => Str::uuid()->toString(),
        ];
    }

    private function extractJsonLd(string $html): array
    {
        preg_match_all('/<script[^>]*type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/is', $html, $matches);
        $json = [];

        foreach ($matches[1] ?? [] as $block) {
            $decoded = json_decode(preg_replace('/\s+/', ' ', trim($block)), true);
            if (! is_array($decoded)) {
                continue;
            }

            if (isset($decoded['@type']) && is_string($decoded['@type'])) {
                $json = array_merge($json, $decoded);
            }

            if (isset($decoded['itemListElement']) && is_array($decoded['itemListElement'])) {
                $first = $decoded['itemListElement'][0] ?? [];
                if (isset($first['item']) && is_array($first['item'])) {
                    $json = array_merge($json, $first['item']);
                }
            }
        }

        return $json;
    }

    private function extractTag(string $html, string $tag): ?string
    {
        preg_match('/<'.$tag.'[^>]*>(.*?)<\/'.$tag.'>/is', $html, $matches);
        return $matches[1] ?? null;
    }

    private function extractMeta(string $html, array $selectors): ?string
    {
        $patterns = [];

        foreach ($selectors as $key => $value) {
            $patterns[] = '/<meta[^>]+'.$key.'=["\']'.preg_quote($value, '/').'["\'][^>]+content=["\']([^"\']+)["\'][^>]*>/is';
            $patterns[] = '/<meta[^>]+content=["\']([^"\']+)["\'][^>]+'.$key.'=["\']'.preg_quote($value, '/').'["\'][^>]*>/is';
        }

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                return trim(html_entity_decode($matches[1] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            }
        }

        return null;
    }

    private function extractTextSnippet(string $html, int $maxLength): ?string
    {
        $plain = preg_replace('/<script.*?<\/script>/is', ' ', $html);
        $plain = preg_replace('/<style.*?<\/style>/is', ' ', $plain);
        $plain = trim(strip_tags($plain));
        $plain = preg_replace('/\s+/', ' ', $plain);

        if (! is_string($plain) || trim($plain) === '') {
            return null;
        }

        $snippet = trim(mb_substr($plain, 0, $maxLength, 'UTF-8'));

        return $snippet !== '' ? $snippet : null;
    }

    private function buildAddress(array $jsonLd, string $html): ?string
    {
        $address = $jsonLd['address'] ?? null;

        if (is_array($address)) {
            $street = trim((string) ($address['streetAddress'] ?? ''));
            $city = trim((string) ($address['addressLocality'] ?? ''));
            $region = trim((string) ($address['addressRegion'] ?? ''));
            $postal = trim((string) ($address['postalCode'] ?? ''));

            $parts = array_filter([$street, $city, $region], fn ($value) => $value !== '');
            if (! empty($parts)) {
                $joined = implode(', ', $parts);
                return $postal !== '' ? trim($joined . ' ' . $postal) : $joined;
            }
        }

        if (is_string($address) && trim($address) !== '') {
            return trim($address);
        }

        preg_match('/(?:Address|Alamat)[:\s]+([^<]{3,200})/is', $html, $matches);
        if (! empty($matches[1])) {
            return trim(strip_tags($matches[1]));
        }

        return null;
    }

    private function extractMenu(string $html, array $jsonLd): array
    {
        $items = [];

        $menuNode = $this->extractMenuJsonLd($jsonLd);
        if (is_array($menuNode) && ! empty($menuNode)) {
            foreach ($menuNode as $entry) {
                if (! is_array($entry)) {
                    continue;
                }

                $name = $entry['name'] ?? $entry['item'] ?? null;
                $price = $entry['price'] ?? ($entry['offers']['price'] ?? null);
                $description = $entry['description'] ?? null;

                if (! empty($name)) {
                    $items[] = [
                        'name' => (string) $name,
                        'price' => is_scalar($price) ? (string) $price : null,
                        'description' => is_scalar($description) ? (string) $description : null,
                    ];
                }
            }
        }

        preg_match_all('/<([^\s>]+)[^>]*class=["\'][^"\']*(?:menu|food|dish|item)[^"\']*["\'][^>]*>(.*?)<\/\1>/is', $html, $blocks, PREG_SET_ORDER);

        foreach ($blocks as $block) {
            $content = trim(strip_tags($block[2]));
            if ($content === '') {
                continue;
            }

            preg_match('/([A-Za-z0-9&\s\-\(\)\/]+?)(?:\s*[:\-]?\s*)(RM\s*\d+[.,]?\d*|\$\d+[.,]?\d*|\d+\.\d{2})/i', $content, $match);
            if (! empty($match[1])) {
                $items[] = [
                    'name' => trim($match[1]),
                    'price' => trim($match[2]),
                    'description' => null,
                ];
            }
        }

        if (empty($items)) {
            preg_match_all('/<span[^>]*class=["\'][^"\']*(?:name|title)[^"\']*["\'][^>]*>(.*?)<\/span>/is', $html, $nameMatches, PREG_SET_ORDER);
            preg_match_all('/<span[^>]*class=["\'][^"\']*(?:price|cost)[^"\']*["\'][^>]*>(.*?)<\/span>/is', $html, $priceMatches, PREG_SET_ORDER);
            preg_match_all('/<span[^>]*class=["\'][^"\']*(?:description|desc)[^"\']*["\'][^>]*>(.*?)<\/span>/is', $html, $descMatches, PREG_SET_ORDER);

            $max = max(count($nameMatches), count($priceMatches), count($descMatches));
            for ($i = 0; $i < $max; $i++) {
                $name = trim(strip_tags($nameMatches[$i][1] ?? ''));
                $price = trim(strip_tags($priceMatches[$i][1] ?? ''));
                $description = trim(strip_tags($descMatches[$i][1] ?? ''));

                if ($name !== '') {
                    $items[] = ['name' => $name, 'price' => $price !== '' ? $price : null, 'description' => $description !== '' ? $description : null];
                }
            }
        }

        return array_values(array_filter($items, fn ($item) => ! empty($item['name'])));
    }

    private function shouldEnhanceWithAi(): bool
    {
        return filter_var(config('services.groq.enhancement_enabled', true), FILTER_VALIDATE_BOOL)
            && filled(config('services.groq.api_key'));
    }

    private function enhanceWithAi(string $url, array $payload, string $html): array
    {
        $apiKey = config('services.groq.api_key');
        if (blank($apiKey)) {
            return $payload;
        }

        $pageText = $this->preparePageText($html);
        $requestBody = [
            'model' => config('services.groq.model', 'llama-3.3-70b-versatile'),
            'temperature' => 0.2,
            'response_format' => ['type' => 'json_object'],
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You extract structured facts for Malaysian heritage food shops. Use only the supplied webpage text and extracted data; never infer or invent facts. Return one JSON object with exactly these optional keys: name, description, heritage_story, address, city, state, postal_code, contact_number, primary_food_category, operating_hours, establishment_year, founder_name, founder_background, current_owner_name, current_owner_details, menu. Use null for unknown scalar values and [] for an unknown menu. Replace generic crawler placeholders only when the page provides a specific fact. Keep prose concise. Return JSON only, without markdown.',
                ],
                [
                    'role' => 'user',
                    'content' => "Website URL: {$url}\n\nPage content:\n{$pageText}\n\nExisting extracted data:\n" . json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
                ],
            ],
        ];

        try {
            $response = Http::withToken($apiKey)
                ->acceptJson()
                ->timeout(30)
                ->post(config('services.groq.endpoint'), $requestBody);
        } catch (\Throwable $exception) {
            Log::warning('Heritage crawler AI enhancement request failed.', ['message' => $exception->getMessage()]);
            return $payload;
        }

        if ($response->failed()) {
            Log::warning('Heritage crawler AI enhancement was rejected.', ['status' => $response->status(), 'body' => Str::limit($response->body(), 500)]);
            return $payload;
        }

        $content = $response->json('choices.0.message.content');
        if (! is_string($content)) {
            return $payload;
        }

        $decoded = json_decode($content, true);
        if (! is_array($decoded)) {
            return $payload;
        }

        $enhanced = $payload;

        foreach (['name', 'description', 'heritage_story', 'address', 'city', 'state', 'postal_code', 'contact_number', 'primary_food_category', 'operating_hours', 'founder_name', 'founder_background', 'current_owner_name', 'current_owner_details'] as $field) {
            $candidate = $decoded[$field] ?? null;
            if ($this->shouldUseAiValue($enhanced[$field] ?? $enhanced['description'] ?? null, $candidate, $field)) {
                $enhanced[$field] = $candidate;
            }
        }

        $year = $decoded['establishment_year'] ?? null;
        if (is_numeric($year) && (int) $year >= 1000 && (int) $year <= (int) now()->year) {
            $enhanced['establishment_year'] = (int) $year;
        }

        if (! empty($decoded['description']) && empty($enhanced['heritage_story'])) {
            $enhanced['heritage_story'] = $decoded['description'];
        }

        if (! empty($decoded['heritage_story']) && empty($enhanced['description'])) {
            $enhanced['description'] = $decoded['heritage_story'];
        }

        if (! empty($enhanced['heritage_story']) && empty($enhanced['description'])) {
            $enhanced['description'] = $enhanced['heritage_story'];
        }

        if (! empty($enhanced['description']) && empty($enhanced['heritage_story'])) {
            $enhanced['heritage_story'] = $enhanced['description'];
        }

        $menu = $this->normalizeAiMenu($decoded['menu'] ?? $decoded['food_items'] ?? []);
        if ($this->shouldReplaceMenu($enhanced['menu'] ?? [], $menu)) {
            $enhanced['menu'] = $menu;
            $enhanced['food_items'] = self::menuToFoodItems($menu);
        }

        return $enhanced;
    }

    private function shouldUseAiValue(mixed $currentValue, mixed $candidateValue, string $field): bool
    {
        $candidate = trim((string) ($candidateValue ?? ''));
        if ($candidate === '') {
            return false;
        }

        $current = trim((string) ($currentValue ?? ''));
        if ($current === '') {
            return true;
        }

        $placeholderKeywords = [
            'crawled heritage shop',
            'address not detected',
            'this record was auto-filled',
            'traditional cuisine',
            'auto-filled from the source website',
            'not detected from the source website',
            '+603-0000 0000',
            '0000 0000',
        ];

        $normalizedCurrent = strtolower($current);
        foreach ($placeholderKeywords as $keyword) {
            if (str_contains($normalizedCurrent, $keyword)) {
                return true;
            }
        }

        if ($field === 'description' && strlen($candidate) > strlen($current)) {
            return true;
        }

        if ($field === 'name' && strlen($candidate) > strlen($current)) {
            return true;
        }

        if (in_array($field, ['address', 'city', 'state', 'postal_code', 'contact_number', 'primary_food_category', 'operating_hours'], true)) {
            return strlen($candidate) >= strlen($current);
        }

        return false;
    }

    private function shouldReplaceMenu(array $currentMenu, array $candidateMenu): bool
    {
        if (empty($candidateMenu)) {
            return false;
        }

        if (empty($currentMenu)) {
            return true;
        }

        $currentCount = count($currentMenu);
        $candidateCount = count($candidateMenu);
        if ($candidateCount > $currentCount) {
            return true;
        }

        foreach ($candidateMenu as $candidate) {
            $name = trim((string) ($candidate['name'] ?? ''));
            if ($name !== '' && ! collect($currentMenu)->contains(fn ($item) => str_contains(strtolower((string) ($item['name'] ?? '')), strtolower($name)))) {
                return true;
            }
        }

        return false;
    }

    private function preparePageText(string $html): string
    {
        $text = preg_replace('/<script.*?<\/script>/is', ' ', $html);
        $text = preg_replace('/<style.*?<\/style>/is', ' ', $text);
        $text = strip_tags($text);
        $text = preg_replace('/\s+/', ' ', (string) $text);

        $text = trim((string) $text);

        // Large pages otherwise exceed Groq's context limit and quietly fall
        // back to the basic scraper.
        return $text !== '' ? Str::limit($text, 18000, '') : 'No readable page text was found.';
    }

    private function normalizeAiMenu(mixed $menu): array
    {
        if (! is_array($menu) || $menu === []) {
            return [];
        }

        $normalized = [];

        foreach ($menu as $entry) {
            if (is_string($entry)) {
                $normalized[] = ['name' => $entry, 'price' => null, 'description' => null];
                continue;
            }

            if (! is_array($entry)) {
                continue;
            }

            $name = trim((string) ($entry['name'] ?? $entry['item'] ?? ''));
            $price = trim((string) ($entry['price'] ?? $entry['cost'] ?? '')) ?: null;
            $description = trim((string) ($entry['description'] ?? $entry['desc'] ?? '')) ?: null;

            if ($name === '') {
                continue;
            }

            $normalized[] = ['name' => $name, 'price' => $price, 'description' => $description];
        }

        return $normalized;
    }

    private function extractMenuJsonLd(array $jsonLd): array
    {
        $items = [];
        $candidates = [
            $jsonLd['hasMenu'] ?? null,
            $jsonLd['menu'] ?? null,
            $jsonLd['offers'] ?? null,
            $jsonLd['itemListElement'] ?? null,
        ];

        foreach ($candidates as $candidate) {
            if (! is_array($candidate)) {
                continue;
            }

            if (isset($candidate['name'])) {
                $items[] = $candidate;
            }

            foreach ($candidate as $value) {
                if (is_array($value) && (isset($value['name']) || isset($value['item']))) {
                    $items[] = $value;
                }
            }
        }

        return $items;
    }

    private function extractImages(string $html, array $jsonLd): array
    {
        $images = [];

        if (isset($jsonLd['image']) && is_array($jsonLd['image'])) {
            foreach ($jsonLd['image'] as $image) {
                if (is_string($image) && filter_var($image, FILTER_VALIDATE_URL)) {
                    $images[] = $image;
                }
                if (is_array($image) && ! empty($image['url'])) {
                    $images[] = $image['url'];
                }
            }
        }

        preg_match_all('/<img[^>]+src=["\']([^"\']+)["\'][^>]*>/is', $html, $matches);
        foreach ($matches[1] ?? [] as $src) {
            if (filter_var($src, FILTER_VALIDATE_URL) && ! in_array($src, $images, true)) {
                $images[] = $src;
            }
        }

        return array_values(array_slice(array_filter($images), 0, 6));
    }

    private function extractHours(string $html): ?string
    {
        preg_match('/(?:Opening|Operating|Business)\s*(?:Hours|Hour|Time)[:\s]+([^<]{2,200})/is', $html, $matches);
        if (! empty($matches[1])) {
            return trim(strip_tags($matches[1]));
        }

        preg_match('/([A-Za-z]{3,9}\s*-\s*[A-Za-z]{3,9}[,:\s].{2,80})/i', $html, $matches);
        return ! empty($matches[1]) ? trim(strip_tags($matches[1])) : null;
    }

    private function extractCityFromAddress(?string $address): ?string
    {
        if (! is_string($address) || trim($address) === '') {
            return null;
        }

        preg_match('/,\s*([^,]+?),\s*[^,]+?\s*\d{4,5}$/', $address, $matches);
        if (! empty($matches[1])) {
            return trim($matches[1]);
        }

        preg_match('/,\s*([^,]+?)$/', $address, $matches);
        return ! empty($matches[1]) ? trim($matches[1]) : null;
    }

    private function extractStateFromAddress(?string $address): ?string
    {
        if (! is_string($address) || trim($address) === '') {
            return null;
        }

        preg_match('/,\s*([^,]+),\s*\d{4,5}$/', $address, $matches);
        return ! empty($matches[1]) ? trim($matches[1]) : null;
    }

    private function extractPostalCodeFromAddress(?string $address): ?string
    {
        if (! is_string($address) || trim($address) === '') {
            return null;
        }

        preg_match('/(\d{4,5})/', $address, $matches);
        return ! empty($matches[1]) ? trim($matches[1]) : null;
    }

    private static function menuToFoodItems(array $menu): array
    {
        return array_map(function (array $item): array {
            return [
                'name' => $item['name'] ?? 'Unnamed item',
                'price' => $item['price'] ?? null,
                'desc' => $item['description'] ?? null,
            ];
        }, $menu);
    }

    private function firstNonEmpty(array $values): mixed
    {
        foreach ($values as $value) {
            if (is_array($value)) {
                continue;
            }

            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }

            if ($value !== null && $value !== '') {
                return $value;
            }
        }

        return null;
    }
}
