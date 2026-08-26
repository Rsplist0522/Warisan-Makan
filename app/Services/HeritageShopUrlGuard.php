<?php

namespace App\Services;

use RuntimeException;

class HeritageShopUrlGuard
{
    /**
     * Reject URLs that could make the server fetch local or private network
     * resources. Public hostnames remain supported, including domains that do
     * not resolve in a test environment.
     */
    public function assertAllowed(string $url): void
    {
        $parts = parse_url($url);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower(trim((string) ($parts['host'] ?? ''), '[]'));

        if (! is_array($parts) || $host === '' || ! in_array($scheme, ['http', 'https'], true)) {
            throw new RuntimeException('Please provide a valid public http or https URL.');
        }

        if (isset($parts['user']) || isset($parts['pass'])) {
            throw new RuntimeException('URLs with embedded credentials are not allowed.');
        }

        if ($this->isBlockedHostName($host)) {
            throw new RuntimeException('Local or private network URLs are not allowed.');
        }

        if (filter_var($host, FILTER_VALIDATE_IP) !== false && ! $this->isPublicIp($host)) {
            throw new RuntimeException('Local or private network URLs are not allowed.');
        }

        foreach ($this->resolveHost($host) as $ip) {
            if (! $this->isPublicIp($ip)) {
                throw new RuntimeException('The URL resolves to a local or private network address.');
            }
        }
    }

    private function isBlockedHostName(string $host): bool
    {
        return $host === 'localhost'
            || str_ends_with($host, '.localhost')
            || str_ends_with($host, '.local')
            || str_ends_with($host, '.internal')
            || str_ends_with($host, '.home.arpa');
    }

    private function resolveHost(string $host): array
    {
        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return [$host];
        }

        $records = @dns_get_record($host, DNS_A | DNS_AAAA);
        if (! is_array($records)) {
            return [];
        }

        return collect($records)
            ->flatMap(fn (array $record): array => array_values(array_filter([
                $record['ip'] ?? null,
                $record['ipv6'] ?? null,
            ], 'is_string')))
            ->values()
            ->all();
    }

    private function isPublicIp(string $ip): bool
    {
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE,
        ) !== false;
    }
}
