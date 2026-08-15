<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;
use App\Models\HeritageShop;
use Illuminate\Support\Str;
use Carbon\Carbon;

class CrawlHeritageShops extends Command
{
    protected $signature = 'crawl:heritage-shops {sourceOrUrl?} {--limit=100}';
    protected $description = 'Crawl configured sources for Malaysian heritage shops (30+ years)';

    protected Client $http;
    protected array $config;
    protected array $maxStringLengths = [
        'slug' => 255,
        'shop_name' => 255,
        'country' => 255,
        'primary_food_category' => 255,
        'founder_name' => 255,
        'address' => 255,
        'operating_hours' => 255,
        'source_url' => 255,
    ];

    public function __construct()
    {
        parent::__construct();
        $this->http = new Client([
            'timeout' => 15,
            'headers' => [
                'User-Agent' => 'WarisanMakanBot/1.0 (+contact@your-domain.example)'
            ],
        ]);
        $this->config = config('heritage_crawler');
    }

    public function handle()
    {
        $sourceOrUrl = $this->argument('sourceOrUrl');
        $sources = $this->resolveSource($sourceOrUrl, $this->config['sources'] ?? []);

        if (empty($sources)) {
            $this->error("Source or URL '{$sourceOrUrl}' not found in config. Add the URL to a source entry in config/heritage_crawler.php or use a source key.");
            return 1;
        }

        $limit = (int) $this->option('limit');
        $count = 0;
        foreach ($sources as $key => $site) {
            $this->info("Crawling source: $key");
            $seedUrls = (array) ($site['seed_urls'] ?? []);
            foreach ($seedUrls as $seed) {
                if (empty($site['list_selector']) || !empty($site['detail_page_mode'])) {
                    if ($count >= $limit) {
                        break;
                    }

                    $this->info("Parsing detail page: $seed");
                    $shopData = $this->parseDetail($seed, $site['fields']);

                    if (!$shopData) {
                        $this->warn("    parse failed: $seed");
                        continue;
                    }

                    $shopData['source_url'] = $seed;
                    $shopData = $this->normalizeShopData($shopData);

                    $locationText = $this->locationText($shopData);
                    $isMalaysia = $this->isMalaysia($shopData['country'], $locationText);
                    $minYears = (int) ($this->config['minimum_years'] ?? 30);
                    $okByAge = true;
                    if ($shopData['establishment_year']) {
                        $okByAge = (int)$shopData['establishment_year'] <= (int)(Carbon::now()->year - $minYears);
                    }

                    if (!$isMalaysia) {
                        $this->warn("    skipped (not Malaysia): {$shopData['shop_name']} - {$locationText}");
                        continue;
                    }
                    if (!$okByAge) {
                        $this->warn("    skipped (not >= {$minYears} years): {$shopData['shop_name']} - year: {$shopData['establishment_year']}");
                        continue;
                    }

                    HeritageShop::updateOrCreate(
                        ['source_url' => $shopData['source_url']],
                        array_filter($shopData)
                    );

                    $this->info("    saved: {$shopData['shop_name']}");
                    $count++;
                    sleep((int)($site['rate_limit_seconds'] ?? 1));
                    continue;
                }

                $next = $seed;
                while ($next && $count < $limit) {
                    $this->info("Fetching list page: $next");
                    try {
                        $res = $this->http->get($next);
                        $html = (string) $res->getBody();
                        $crawler = new Crawler($html, $site['base_uri'] ?? $next);

                        $crawler->filter($site['list_selector'])->each(function (Crawler $node) use ($site, &$count, &$limit, &$key) {
                            if ($count >= $limit) return;
                            $linkNode = $node->filter($site['detail_link_selector']);
                            if ($linkNode->count() === 0) return;
                            $href = $linkNode->attr('href');
                            $detailUrl = $this->absoluteUrl($href, $site['base_uri'] ?? null);

                            $this->info(" -> detail: $detailUrl");
                            $shopData = $this->parseDetail($detailUrl, $site['fields']);

                            if (!$shopData) {
                                $this->warn("    parse failed: $detailUrl");
                                return;
                            }

                            $shopData['source_url'] = $detailUrl;
                            $shopData = $this->normalizeShopData($shopData);

                            $locationText = $this->locationText($shopData);
                            $isMalaysia = $this->isMalaysia($shopData['country'], $locationText);
                            $minYears = (int) ($this->config['minimum_years'] ?? 30);
                            $okByAge = true;
                            if ($shopData['establishment_year']) {
                                $okByAge = (int)$shopData['establishment_year'] <= (int)(Carbon::now()->year - $minYears);
                            }

                            if (!$isMalaysia) {
                                $this->warn("    skipped (not Malaysia): {$shopData['shop_name']} - {$locationText}");
                                return;
                            }
                            if (!$okByAge) {
                                $this->warn("    skipped (not >= {$minYears} years): {$shopData['shop_name']} - year: {$shopData['establishment_year']}");
                                return;
                            }

                            HeritageShop::updateOrCreate(
                                ['source_url' => $shopData['source_url']],
                                array_filter($shopData)
                            );

                            $this->info("    saved: {$shopData['shop_name']}");
                            $count++;
                            sleep((int)($site['rate_limit_seconds'] ?? 1));
                        });

                        $next = null;
                        if (!empty($site['next_page_selector'])) {
                            $nextNode = $crawler->filter($site['next_page_selector']);
                            if ($nextNode->count()) {
                                $href = $nextNode->attr('href');
                                $next = $this->absoluteUrl($href, $site['base_uri'] ?? null);
                            }
                        } else {
                            $next = null;
                        }
                    } catch (\Throwable $e) {
                        $this->error("Request failed: " . $e->getMessage());
                        $next = null;
                    }
                }
            }
        }

        $this->info("Crawl done. Total saved: $count");
        return 0;
    }

    protected function resolveSource(?string $sourceOrUrl, array $sources): array
    {
        if (!$sourceOrUrl) {
            return $sources;
        }

        if (isset($sources[$sourceOrUrl])) {
            return [$sourceOrUrl => $sources[$sourceOrUrl]];
        }

        if (!filter_var($sourceOrUrl, FILTER_VALIDATE_URL)) {
            return [];
        }

        foreach ($sources as $key => $site) {
            foreach ((array) ($site['seed_urls'] ?? []) as $seedUrl) {
                if (rtrim($seedUrl, '/') === rtrim($sourceOrUrl, '/')) {
                    return [$key => $site];
                }
            }

            if (!empty($site['base_uri']) && str_starts_with($sourceOrUrl, rtrim($site['base_uri'], '/'))) {
                return [$key => $site];
            }
        }

        return [];
    }

    protected function parseDetail(string $url, array $fields): ?array
    {
        try {
            $res = $this->http->get($url);
            $html = (string) $res->getBody();
            $crawler = new Crawler($html, $url);
            $jsonLd = $this->parseJsonLd($crawler);

            $data = [];
            foreach ($fields as $key => $selector) {
                try {
                    if (str_starts_with($selector, 'static:')) {
                        $data[$key] = substr($selector, strlen('static:'));
                        continue;
                    }

                    if (str_starts_with($selector, 'jsonld:')) {
                        $data[$key] = $this->extractJsonLdValue($jsonLd, substr($selector, strlen('jsonld:')));
                        continue;
                    }

                    if (str_starts_with($selector, 'css:')) {
                        $selector = substr($selector, strlen('css:'));
                    }

                    $nodes = $crawler->filter($selector);
                    if ($nodes->count() === 0) {
                        $data[$key] = null;
                        continue;
                    }

                    $values = $nodes->each(function (Crawler $node) {
                        return trim(preg_replace('/\s+/', ' ', $node->text()));
                    });

                    $data[$key] = trim(implode("\n\n", array_filter($values)));
                } catch (\Exception $e) {
                    $data[$key] = null;
                }
            }

            return $data;
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function parseJsonLd(Crawler $crawler): array
    {
        $jsonLd = [];
        $crawler->filter('script[type="application/ld+json"]')->each(function (Crawler $node) use (&$jsonLd) {
            $text = trim($node->text());
            if ($text === '') {
                return;
            }

            $decoded = json_decode($text, true);
            if ($decoded !== null) {
                $jsonLd[] = $decoded;
            }
        });

        return $jsonLd;
    }

    protected function extractJsonLdValue(array $jsonLd, string $path): ?string
    {
        $parts = explode('.', $path);
        foreach ($jsonLd as $data) {
            $value = $this->resolveJsonPath($data, $parts);
            if ($value !== null) {
                if (is_array($value)) {
                    return trim(implode(', ', array_filter(array_map('strval', $value))));
                }
                return trim((string) $value);
            }
        }

        return null;
    }

    protected function resolveJsonPath(mixed $data, array $parts): mixed
    {
        $current = $data;

        foreach ($parts as $part) {
            if (!is_array($current)) {
                return null;
            }

            if (!array_key_exists($part, $current)) {
                return null;
            }

            $current = $current[$part];
        }

        return $current;
    }

    protected function normalizeShopData(array $shopData): array
    {
        foreach (['name', 'shop_name', 'location', 'address', 'country', 'category', 'primary_food_category', 'description', 'founder', 'founder_name', 'heritage_story', 'operating_hours', 'source_url'] as $field) {
            if (isset($shopData[$field])) {
                $shopData[$field] = trim((string) $shopData[$field]);
            }
        }

        foreach ($this->maxStringLengths as $field => $length) {
            if (!empty($shopData[$field]) && is_string($shopData[$field]) && mb_strlen($shopData[$field]) > $length) {
                $shopData[$field] = mb_substr($shopData[$field], 0, $length);
            }
        }

        $shopData['country'] = strtolower($shopData['country'] ?? '');
        $shopData['establishment_year'] = $this->extractYear($shopData['establishment_year'] ?? '');
        $shopData['shop_name'] = $shopData['shop_name'] ?? ($shopData['name'] ?? null);
        $shopData['primary_food_category'] = $shopData['primary_food_category'] ?? ($shopData['category'] ?? null);
        $shopData['founder_name'] = $shopData['founder_name'] ?? ($shopData['founder'] ?? null);
        $shopData['heritage_story'] = $shopData['heritage_story'] ?? ($shopData['description'] ?? null);
        $shopData['address'] = $shopData['address'] ?? ($shopData['location'] ?? null);

        unset(
            $shopData['name'],
            $shopData['location'],
            $shopData['category'],
            $shopData['description'],
            $shopData['founder']
        );

        return $shopData;
    }

    protected function locationText(array $shopData): string
    {
        return trim(implode(', ', array_filter([
            $shopData['address'] ?? null,
            $shopData['city'] ?? null,
            $shopData['state'] ?? null,
        ])));
    }

    protected function absoluteUrl(?string $href, ?string $base = null): ?string
    {
        if (empty($href)) return null;
        if (Str::startsWith($href, ['http://','https://'])) return $href;
        if ($base) return rtrim($base, '/') . '/' . ltrim($href, '/');
        return $href;
    }

    protected function extractYear(mixed $text): ?int
    {
        if (!$text) return null;
        if (preg_match('/(19|20)\d{2}/', (string)$text, $m)) {
            return (int)$m[0];
        }
        return null;
    }

    protected function isMalaysia(mixed $countryField, mixed $locationField): bool
    {
        $country = strtolower((string)$countryField);
        $location = strtolower((string)$locationField);
        if (str_contains($country, 'malaysia') || str_contains($location, 'malaysia')) return true;

        foreach ($this->config['malaysia_states'] as $state) {
            if (str_contains($location, $state)) return true;
        }
        return false;
    }
}
