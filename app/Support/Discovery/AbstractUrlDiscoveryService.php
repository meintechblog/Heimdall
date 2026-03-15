<?php

namespace App\Support\Discovery;

use App\Item;
use App\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Request;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

abstract class AbstractUrlDiscoveryService
{
    protected const DEFAULT_COLOUR = '#161b1f';

    abstract public function label(): string;

    abstract protected function sourceKey(): string;

    abstract protected function scanCandidates(): array;

    public function candidates(): array
    {
        return $this->filterExistingUrls($this->cachedCandidates());
    }

    public function createItemFromCandidate(string $candidateId): array
    {
        $candidate = collect($this->candidates())
            ->firstWhere('id', $candidateId);

        abort_if($candidate === null, HttpResponse::HTTP_NOT_FOUND, 'Discovery candidate not found.');

        $existingItem = $this->existingItemForUrl($candidate['url']);

        if ($existingItem) {
            $this->forgetCandidate($candidateId);

            return [
                'created' => false,
                'item' => [
                    'id' => $existingItem->id,
                    'title' => $existingItem->title,
                    'url' => $existingItem->url,
                ],
            ];
        }

        $currentUser = User::currentUser();
        $item = Item::create([
            'title' => $candidate['title'],
            'url' => $candidate['url'],
            'colour' => $candidate['colour'] ?? self::DEFAULT_COLOUR,
            'icon' => $candidate['icon'] ?? null,
            'pinned' => 1,
            'order' => 0,
            'type' => 0,
            'class' => null,
            'user_id' => $currentUser->getId(),
            'appid' => null,
        ]);

        $tagId = (int) ($candidate['tagId'] ?? 0);

        if ($tagId > 0) {
            $item->parents()->sync([$tagId]);
        }

        $this->forgetCandidate($candidateId);

        return [
            'created' => true,
            'item' => [
                'id' => $item->id,
                'title' => $item->title,
                'url' => $item->url,
            ],
        ];
    }

    protected function cachedCandidates(): array
    {
        $cached = Cache::get($this->cacheKey());

        if (is_array($cached)) {
            return $cached;
        }

        $lock = Cache::lock($this->lockKey(), max(10, (int) config("app.discovery.{$this->sourceKey()}.cache_ttl_seconds", 900)));

        if (! $lock->get()) {
            return is_array($cached) ? $cached : [];
        }

        try {
            $candidates = $this->scanCandidates();
            Cache::put($this->cacheKey(), $candidates, now()->addSeconds((int) config("app.discovery.{$this->sourceKey()}.cache_ttl_seconds", 900)));

            return $candidates;
        } finally {
            $lock->release();
        }
    }

    protected function candidateHosts(): array
    {
        $configuredHosts = array_filter((array) config("app.discovery.{$this->sourceKey()}.hosts", []));

        if ($configuredHosts !== []) {
            return array_values(array_unique(array_map([$this, 'normalizeHost'], $configuredHosts)));
        }

        $baseHost = $this->discoverBaseHost();

        if (! is_string($baseHost) || ! filter_var($baseHost, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return [];
        }

        $octets = explode('.', $baseHost);
        $prefix = implode('.', array_slice($octets, 0, 3));
        $hosts = [];

        for ($suffix = 1; $suffix <= 254; $suffix += 1) {
            $host = "{$prefix}.{$suffix}";

            if ($host === $baseHost) {
                continue;
            }

            $hosts[] = $host;
        }

        return $hosts;
    }

    protected function discoverBaseHost(): ?string
    {
        $configHost = parse_url((string) config('app.url'), PHP_URL_HOST);

        if (is_string($configHost) && filter_var($configHost, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return $this->normalizeHost($configHost);
        }

        $requestHost = Request::getHost();

        if (is_string($requestHost) && filter_var($requestHost, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return $this->normalizeHost($requestHost);
        }

        return null;
    }

    protected function filterExistingUrls(array $candidates): array
    {
        $existingUrlKeys = $this->existingUrlKeys();

        return array_values(array_filter($candidates, function (array $candidate) use ($existingUrlKeys): bool {
            return ! in_array($this->normalizeBaseUrl($candidate['url'] ?? null), $existingUrlKeys, true);
        }));
    }

    protected function existingUrlKeys(): array
    {
        return Item::query()
            ->where('type', 0)
            ->pluck('url')
            ->map(fn ($url) => $this->normalizeBaseUrl($url))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    protected function existingItemForUrl(string $url): ?Item
    {
        $needle = $this->normalizeBaseUrl($url);

        return Item::query()
            ->where('type', 0)
            ->get()
            ->first(function (Item $item) use ($needle): bool {
                return $this->normalizeBaseUrl($item->url) === $needle;
            });
    }

    protected function forgetCandidate(string $candidateId): void
    {
        $remainingCandidates = array_values(array_filter($this->cachedCandidates(), static function (array $candidate) use ($candidateId): bool {
            return $candidate['id'] !== $candidateId;
        }));

        Cache::put($this->cacheKey(), $remainingCandidates, now()->addSeconds((int) config("app.discovery.{$this->sourceKey()}.cache_ttl_seconds", 900)));
    }

    protected function defaultIconUrl(): string
    {
        $configUrl = rtrim((string) config('app.url'), '/');
        $configHost = parse_url($configUrl, PHP_URL_HOST);

        if (
            $configUrl !== ''
            && is_string($configHost)
            && $configHost !== ''
            && strtolower($configHost) !== 'localhost'
        ) {
            return "{$configUrl}/img/heimdall-icon-small.png";
        }

        $scheme = Request::getScheme();
        $host = Request::getHttpHost();

        if (is_string($host) && $host !== '') {
            return "{$scheme}://{$host}/img/heimdall-icon-small.png";
        }

        return 'http://localhost/img/heimdall-icon-small.png';
    }

    protected function normalizeHost(string $host): string
    {
        return strtolower(trim($host));
    }

    protected function normalizeBaseUrl(?string $url): ?string
    {
        if (! is_string($url) || trim($url) === '') {
            return null;
        }

        $normalizedUrl = str_contains($url, '://') ? $url : 'http://'.ltrim($url, '/');
        $parts = parse_url($normalizedUrl);

        if (! is_array($parts)) {
            return null;
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? 'http'));
        $host = strtolower((string) ($parts['host'] ?? ''));
        $port = isset($parts['port']) ? (int) $parts['port'] : null;

        if ($host === '') {
            return null;
        }

        return $port !== null ? "{$scheme}://{$host}:{$port}" : "{$scheme}://{$host}";
    }

    protected function cacheKey(): string
    {
        return 'discovery:'.$this->sourceKey().':candidates';
    }

    protected function lockKey(): string
    {
        return 'discovery:'.$this->sourceKey().':scan';
    }
}
