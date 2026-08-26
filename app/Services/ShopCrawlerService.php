<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;

class ShopCrawlerService
{
    public function __construct(private ?HeritageShopUrlGuard $urlGuard = null)
    {
        $this->urlGuard ??= new HeritageShopUrlGuard();
    }

        public function normalizeUrl(string $url): string
    {
        $parts = parse_url(trim($url));
        if (! is_array($parts) || empty($parts['host'])) {
            return trim($url);
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? 'https'));
        $host = strtolower((string) $parts['host']);
        $port = isset($parts['port']) ? ':'.(int) $parts['port'] : '';
        $path = rtrim((string) ($parts['path'] ?? ''), '/');
        $query = isset($parts['query']) && $parts['query'] !== '' ? '?'.$parts['query'] : '';

        return $scheme.'://'.$host.$port.($path !== '' ? $path : '').$query;
    }

    public function crawl(string $url): array

    {

        $parsed = parse_url($url);

                if (! is_array($parsed) || empty($parsed['host']) || ! in_array(strtolower((string) ($parsed['scheme'] ?? '')), ['http', 'https'], true)) {
            throw new InvalidArgumentException('Please provide a valid http or https URL to crawl.');
        }

        $this->urlGuard->assertAllowed($url);

        $response = Http::accept('text/html')->timeout(15)->get($url);

        if ($response->failed() || trim((string) $response->body()) === '') {
            throw new InvalidArgumentException('The provided URL could not be fetched. Please check that the website is accessible and publicly viewable.');
        }

        $html = $response->body();
        $payload = $this->extractFromHtml($url, $html);

        // Many restaurant sites keep their menu on a separate first-party
        // page. Follow one clearly labelled menu link before using research.
        if (empty($payload['menu']) && ($menuUrl = $this->findMenuUrl($html, $url))) {
            $payload = $this->mergeLinkedMenu($payload, $menuUrl);
        }

        // A chat model cannot browse by itself. Search first and pass only the
        // returned source excerpts to the model, keeping every suggestion
        // traceable to a page the administrator can review.
        $payload['research_sources'] = $this->searchForMissingDetails($payload);
        $payload['research_status'] = $this->researchStatus($payload['research_sources']);

        if ($this->shouldEnhanceWithAi()) {
            $payload = $this->enhanceWithAi($url, $payload, $html);
        }

        return $payload;
    }

        /**
     * Preview several shops from a permitted list page without saving anything.
     * The admin chooses which reviewed result to load into the normal shop form.
     */
    public function discoverFromList(string $url, int $limit = 6): array
    {
        $url = $this->normalizeUrl($url);
        $this->urlGuard->assertAllowed($url);

        $response = Http::accept('text/html')->timeout(15)->get($url);
        if ($response->failed() || trim((string) $response->body()) === '') {
            throw new InvalidArgumentException('The list page could not be fetched. Please check that it is publicly viewable and permitted for automated access.');
        }

        $candidateUrls = $this->candidateDetailUrls($url, $response->body());
        $items = [];
        $skipped = 0;

        foreach (array_slice($candidateUrls, 0, max(1, min($limit, 10))) as $detailUrl) {
            try {
                $detailResponse = Http::accept('text/html')->timeout(15)->get($detailUrl);
                if ($detailResponse->failed() || trim((string) $detailResponse->body()) === '') {
                    $skipped++;
                    continue;
                }

                $payload = $this->extractFromHtml($detailUrl, $detailResponse->body());
                if (blank($payload['name'] ?? null)) {
                    $skipped++;
                    continue;
                }

                $payload['source_url'] = $detailUrl;
                $payload['review_required'] = true;
                $items[] = $payload;
            } catch (\Throwable $exception) {
                Log::info('Heritage shop list discovery skipped a detail page.', [
                    'url' => $detailUrl,
                    'message' => $exception->getMessage(),
                ]);
                $skipped++;
            }
        }

        return [
            'source_url' => $url,
            'candidate_count' => count($candidateUrls),
            'items' => $items,
            'skipped_count' => $skipped,
            'review_required' => true,
        ];
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
            $this->extractHeading($html),
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

        $heritageStory = $description ?: null;

        return [
            'name' => $name,
            'description' => $heritageStory,
            'heritage_story' => $heritageStory,
            'menu' => $menu,
            'images' => $images,
            'address' => $address,
            'contact_number' => $contactNumber,
            'source_url' => $url,
            'city' => $this->extractCityFromAddress($address),
            'state' => $this->extractStateFromAddress($address),
            'postal_code' => $this->extractPostalCodeFromAddress($address),
            'primary_food_category' => $category,
            'operating_hours' => $this->extractHours($html, $jsonLd),
            'food_items' => self::menuToFoodItems($menu),
            'crawler_token' => Str::uuid()->toString(),
            'field_sources' => $this->pageFieldSources($url),
        ];
    }

        private function absoluteUrl(string $href, string $baseUrl): ?string
    {
        if (filter_var($href, FILTER_VALIDATE_URL)) {
            return $this->normalizeUrl($href);
        }

        $base = parse_url($baseUrl);
        if (! is_array($base) || empty($base['scheme']) || empty($base['host'])) {
            return null;
        }

        $origin = strtolower((string) $base['scheme']).'://'.strtolower((string) $base['host'])
            .(isset($base['port']) ? ':'.(int) $base['port'] : '');
        if (str_starts_with($href, '/')) {
            return $this->normalizeUrl($origin.$href);
        }

        $basePath = (string) ($base['path'] ?? '/');
        $directory = rtrim(str_replace('\\', '/', dirname($basePath)), '/');
        $resolved = $directory.'/'.$href;
        $segments = [];
        foreach (explode('/', $resolved) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }
            if ($segment === '..') {
                array_pop($segments);
                continue;
            }
            $segments[] = $segment;
        }

        return $this->normalizeUrl($origin.'/'.implode('/', $segments));
    }

    private function candidateDetailUrls(string $listUrl, string $html): array
    {
        preg_match_all("~<a[^>]+href=[\\\"']([^\\\"']+)[\\\"'][^>]*>(.*?)</a>~is", $html, $matches, PREG_SET_ORDER);
        $listParts = parse_url($listUrl);
        $listHost = strtolower((string) ($listParts['host'] ?? ''));
        $candidates = [];

        foreach ($matches as $match) {
            $href = html_entity_decode(trim((string) ($match[1] ?? '')), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $label = trim(strip_tags((string) ($match[2] ?? '')));
            if ($href === '' || str_starts_with($href, '#') || preg_match('/^(?:mailto|tel|javascript):/i', $href)) {
                continue;
            }

            $absolute = $this->absoluteUrl($href, $listUrl);
            if (! is_string($absolute) || ! filter_var($absolute, FILTER_VALIDATE_URL)) {
                continue;
            }

            $parts = parse_url($absolute);
            if (! is_array($parts) || strtolower((string) ($parts['host'] ?? '')) !== $listHost) {
                continue;
            }

            $path = strtolower((string) ($parts['path'] ?? ''));
            if ($absolute === $listUrl || preg_match('/\\.(?:jpg|jpeg|png|webp|gif|svg|pdf|css|js)$/i', $path)) {
                continue;
            }
            if (preg_match('/\\/(?:about|contact|privacy|terms|login|search|blog|news|category|tag|cart|checkout)(?:[\\/]|$)/i', $path)) {
                continue;
            }

            $signal = strtolower($label.' '.$path);
            if (! preg_match('/restaurant|shop|stall|cafe|café|kopitiam|warung|kedai|food|eat|dining|menu/', $signal)) {
                continue;
            }

            $normalized = $this->normalizeUrl($absolute);
            $candidates[$normalized] = true;
        }

        return array_keys($candidates);
    }

    private function searchForMissingDetails(array $payload): array

    {
        if (! $this->shouldResearchWeb() || ! $this->hasMissingResearchFields($payload)) {
            return [];
        }

        $name = $this->researchQuery($payload);

        try {
            $response = Http::acceptJson()->timeout(20)->post(config('services.tavily.endpoint'), [
                'api_key' => config('services.tavily.api_key'),
                'query' => $name.' Malaysia address contact opening hours menu founder heritage history',
                'search_depth' => 'advanced',
                'max_results' => 5,
                'include_answer' => false,
                'include_raw_content' => 'text',
            ]);
        } catch (\Throwable $exception) {
            Log::warning('Heritage crawler web research request failed.', ['message' => $exception->getMessage()]);
            return [];
        }

        if ($response->failed()) {
            Log::warning('Heritage crawler web research was rejected.', ['status' => $response->status()]);
            return [];
        }

        return collect($response->json('results', []))
            ->filter(fn ($result) => is_array($result) && filled($result['url'] ?? null))
            ->map(fn (array $result) => [
                'title' => Str::limit(trim((string) ($result['title'] ?? 'Untitled source')), 180, ''),
                'url' => $result['url'],
                'content' => Str::limit(trim((string) ($result['raw_content'] ?? $result['content'] ?? '')), 5000, ''),
            ])
            ->filter(fn (array $result) => $result['content'] !== '')
            ->take(5)
            ->values()
            ->all();
    }

    private function shouldResearchWeb(): bool
    {
        return filter_var(config('services.tavily.research_enabled', true), FILTER_VALIDATE_BOOL)
            && filled(config('services.tavily.api_key'));
    }

    private function hasMissingResearchFields(array $payload): bool
    {
        foreach (['address', 'city', 'state', 'postal_code', 'contact_number', 'primary_food_category', 'operating_hours', 'establishment_year', 'founder_name', 'founder_background', 'current_owner_name', 'current_owner_details', 'heritage_story'] as $field) {
            if (blank($payload[$field] ?? null)) {
                return true;
            }
        }

        return empty($payload['menu']);
    }

    private function pageFieldSources(string $url): array
    {
        return array_fill_keys(['name', 'description', 'heritage_story', 'address', 'city', 'state', 'postal_code', 'contact_number', 'primary_food_category', 'operating_hours'], $url);
    }

    private function researchQuery(array $payload): string
    {
        $name = trim((string) ($payload['name'] ?? ''));
        if ($name !== '') {
            return $name;
        }

        $path = parse_url((string) ($payload['source_url'] ?? ''), PHP_URL_PATH);
        $slug = trim(str_replace(['-', '_', '/'], ' ', (string) $path));

        return $slug !== '' ? $slug : (string) ($payload['source_url'] ?? '');
    }

    private function researchStatus(array $sources): string
    {
        if (! $this->shouldResearchWeb()) {
            return 'Web research is unavailable because TAVILY_API_KEY is not configured.';
        }

        if ($sources === []) {
            return 'Web research found no readable supporting sources for the missing fields.';
        }

        return 'Web research found '.count($sources).' source(s). Suggestions are limited to supported facts.';
    }

    private function extractJsonLd(string $html): array
    {
        preg_match_all('/<script[^>]*type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/is', $html, $matches);
        $nodes = [];

        foreach ($matches[1] ?? [] as $block) {
            $decoded = json_decode(trim($block), true);
            if (! is_array($decoded)) {
                continue;
            }
            $nodes = [...$nodes, ...$this->flattenJsonLdNodes($decoded)];
        }

        foreach ($nodes as $node) {
            $types = (array) ($node['@type'] ?? []);
            if (collect($types)->contains(fn ($type) => in_array(strtolower((string) $type), ['restaurant', 'foodestablishment', 'localbusiness'], true))) {
                return $node;
            }
        }

        return collect($nodes)->first(fn ($node) => filled($node['name'] ?? null)) ?? [];
    }

    private function flattenJsonLdNodes(array $data): array
    {
        $nodes = [];
        if (array_is_list($data)) {
            foreach ($data as $item) {
                if (is_array($item)) {
                    $nodes = [...$nodes, ...$this->flattenJsonLdNodes($item)];
                }
            }
            return $nodes;
        }

        if (isset($data['@type']) || isset($data['name'])) {
            $nodes[] = $data;
        }

        foreach ((array) ($data['@graph'] ?? []) as $item) {
            if (is_array($item)) {
                $nodes = [...$nodes, ...$this->flattenJsonLdNodes($item)];
            }
        }

        return $nodes;
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
        $plain = preg_replace('/<head.*?<\/head>/is', ' ', $html);
        $plain = preg_replace('/<script.*?<\/script>/is', ' ', $plain);
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

    private function extractHeading(string $html): ?string
    {
        preg_match('/<h1[^>]*>(.*?)<\/h1>/is', $html, $matches);
        $heading = trim(html_entity_decode(strip_tags($matches[1] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        return $heading !== '' ? $heading : null;
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
                    'content' => 'You extract structured facts for Malaysian heritage food shops. Use only facts explicitly supported by the supplied webpage text or web-research excerpts. Never infer, guess, combine facts from different businesses, or use your own knowledge. A search result title alone is not evidence. Keep an existing extracted value unless it is empty. Return one JSON object with exactly these optional keys: name, description, heritage_story, address, city, state, postal_code, contact_number, primary_food_category, operating_hours, establishment_year, founder_name, founder_background, current_owner_name, current_owner_details, menu. Use null for unknown scalar values and [] for an unknown menu. Keep prose concise. Return JSON only, without markdown.',
                ],
                [
                    'role' => 'user',
                    'content' => "Website URL: {$url}\n\nPage content:\n{$pageText}\n\nWeb-research sources (may be empty):\n" . json_encode($payload['research_sources'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n\nExisting extracted data:\n" . json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
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
            if ($this->shouldUseAiValue($enhanced[$field] ?? null, $candidate, $field)) {
                $enhanced[$field] = $candidate;
                $enhanced['field_sources'][$field] = ! empty($payload['research_sources'])
                    ? 'Web research — review the sources below'
                    : $url;
            }
        }

        $year = $decoded['establishment_year'] ?? null;
        if (is_numeric($year) && (int) $year >= 1000 && (int) $year <= (int) now()->year) {
            if (blank($enhanced['establishment_year'] ?? null)) {
                $enhanced['establishment_year'] = (int) $year;
                $enhanced['field_sources']['establishment_year'] = ! empty($payload['research_sources'])
                    ? 'Web research — review the sources below'
                    : $url;
            }
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
            $enhanced['field_sources']['menu'] = ! empty($payload['research_sources'])
                ? 'Web research — review the sources below'
                : $url;
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

        // The source page wins. This fallback exists only to complete unknown
        // fields, not to have an LLM rewrite a fact it has already extracted.
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

    private function findMenuUrl(string $html, string $baseUrl): ?string
    {
        preg_match_all('/<a[^>]+href=["\']([^"\']+)["\'][^>]*>(.*?)<\/a>/is', $html, $links, PREG_SET_ORDER);
        foreach ($links as $link) {
            $label = strtolower(trim(strip_tags($link[2])));
            if (! preg_match('/\b(menu|food menu|our food|order online)\b/', $label)) {
                continue;
            }

            $href = html_entity_decode(trim($link[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            if (filter_var($href, FILTER_VALIDATE_URL)) {
                return $href;
            }
            if (str_starts_with($href, '/')) {
                $parts = parse_url($baseUrl);
                return isset($parts['scheme'], $parts['host']) ? $parts['scheme'].'://'.$parts['host'].$href : null;
            }
        }

        return null;
    }

        private function mergeLinkedMenu(array $payload, string $menuUrl): array
    {
        try {
            $this->urlGuard->assertAllowed($menuUrl);
            $response = Http::accept('text/html')->timeout(15)->get($menuUrl);

            if ($response->failed()) {
                return $payload;
            }
            $menu = $this->extractMenu($response->body(), $this->extractJsonLd($response->body()));
            if (! empty($menu)) {
                $payload['menu'] = $menu;
                $payload['food_items'] = self::menuToFoodItems($menu);
                $payload['field_sources']['menu'] = $menuUrl;
            }
        } catch (\Throwable $exception) {
            Log::info('Heritage crawler could not fetch linked menu.', ['message' => $exception->getMessage()]);
        }

        return $payload;
    }

    private function extractHours(string $html, array $jsonLd): ?string
    {
        $schemaHours = $this->formatSchemaHours($jsonLd);
        if ($schemaHours !== null) {
            return $schemaHours;
        }

        $body = preg_replace('/<head.*?<\/head>|<script.*?<\/script>|<style.*?<\/style>/is', ' ', $html);
        $body = preg_replace('/<\/(?:p|div|li|br|h[1-6])[^>]*>/i', "\n", (string) $body);
        $body = html_entity_decode(strip_tags((string) $body), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $body = preg_replace('/[ \t]+/', ' ', (string) $body);

        foreach (preg_split('/\R+/', (string) $body) as $line) {
            $line = trim($line);
            if (preg_match('/\b(?:opening|operating|business)\s*(?:hours?|time)?\b.{0,100}\b(?:[01]?\d|2[0-3])(?::[0-5]\d)?\s*(?:am|pm)?\s*(?:-|–|to)\s*(?:[01]?\d|2[0-3])(?::[0-5]\d)?\s*(?:am|pm)?\b/i', $line)) {
                return Str::limit($line, 300, '');
            }
            if (preg_match('/\b(?:mon|tue|wed|thu|fri|sat|sun|daily)\w*\b.{0,50}\b(?:[01]?\d|2[0-3])(?::[0-5]\d)?\s*(?:am|pm)\s*(?:-|–|to)\s*(?:[01]?\d|2[0-3])(?::[0-5]\d)?\s*(?:am|pm)\b/i', $line)) {
                return Str::limit($line, 300, '');
            }
        }

        return null;
    }

    private function formatSchemaHours(array $jsonLd): ?string
    {
        $hours = $jsonLd['openingHours'] ?? $jsonLd['openingHoursSpecification'] ?? null;
        if (is_string($hours) && trim($hours) !== '') {
            return trim($hours);
        }
        if (! is_array($hours)) {
            return null;
        }

        $formatted = [];
        foreach (array_is_list($hours) ? $hours : [$hours] as $specification) {
            if (is_string($specification) && trim($specification) !== '') {
                $formatted[] = trim($specification);
                continue;
            }
            if (! is_array($specification)) {
                continue;
            }
            $days = $specification['dayOfWeek'] ?? null;
            $days = is_array($days) ? implode(', ', array_map(fn ($day) => str_replace('https://schema.org/', '', (string) $day), $days)) : str_replace('https://schema.org/', '', (string) $days);
            $opens = trim((string) ($specification['opens'] ?? ''));
            $closes = trim((string) ($specification['closes'] ?? ''));
            if ($opens !== '' && $closes !== '') {
                $formatted[] = trim($days.' '.$opens.'–'.$closes);
            }
        }

        return $formatted !== [] ? implode('; ', $formatted) : null;
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
