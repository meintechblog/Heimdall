<?php

namespace App\Support\Discovery;

use App\Item;
use App\User;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Request;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class MobotixDiscoveryService
{
    protected const CACHE_KEY = 'discovery:mobotix:candidates';
    protected const LOCK_KEY = 'discovery:mobotix:scan';
    protected const DEFAULT_COLOUR = '#161b1f';

    public function label(): string
    {
        return 'Mobotix';
    }

    public function candidates(): array
    {
        return $this->filterExistingHosts($this->cachedCandidates());
    }

    public function createItemFromCandidate(string $candidateId): array
    {
        $candidate = collect($this->candidates())
            ->firstWhere('id', $candidateId);

        abort_if($candidate === null, HttpResponse::HTTP_NOT_FOUND, 'Discovery candidate not found.');

        $existingItem = $this->existingItemForHost($candidate['host']);

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
        $cached = Cache::get(self::CACHE_KEY);

        if (is_array($cached)) {
            return $cached;
        }

        $lock = Cache::lock(self::LOCK_KEY, max(10, (int) config('app.discovery.mobotix.cache_ttl_seconds', 900)));

        if (! $lock->get()) {
            return is_array($cached) ? $cached : [];
        }

        try {
            $candidates = $this->scanCandidates();
            Cache::put(self::CACHE_KEY, $candidates, now()->addSeconds((int) config('app.discovery.mobotix.cache_ttl_seconds', 900)));

            return $candidates;
        } finally {
            $lock->release();
        }
    }

    protected function scanCandidates(): array
    {
        $hosts = array_values(array_diff($this->candidateHosts(), $this->existingHosts()));

        if ($hosts === []) {
            return [];
        }

        $candidates = [];

        foreach (array_chunk($hosts, max(1, (int) config('app.discovery.mobotix.chunk_size', 4))) as $chunk) {
            $responses = Http::pool(function (Pool $pool) use ($chunk) {
                $requests = [];

                foreach ($chunk as $host) {
                    $requests[] = $pool
                        ->as($host)
                        ->timeout((float) config('app.discovery.mobotix.timeout_seconds', 0.8))
                        ->connectTimeout((float) config('app.discovery.mobotix.connect_timeout_seconds', 0.4))
                        ->withoutRedirecting()
                        ->get("http://{$host}/");
                }

                return $requests;
            });

            foreach ($chunk as $host) {
                $response = $responses[$host] ?? null;

                if (! $response instanceof Response || ! $this->isMobotixResponse($response)) {
                    continue;
                }

                $candidates[] = [
                    'id' => sha1('mobotix:http://'.$host),
                    'source' => 'mobotix',
                    'sourceLabel' => $this->label(),
                    'title' => "Mobotix {$host}",
                    'subtitle' => 'Mobotix Camera',
                    'url' => "http://{$host}",
                    'host' => $host,
                    'icon' => null,
                    'iconUrl' => $this->defaultIconUrl(),
                    'tagId' => 0,
                    'colour' => self::DEFAULT_COLOUR,
                ];
            }
        }

        usort($candidates, static function (array $left, array $right): int {
            return [$left['title'], $left['host']] <=> [$right['title'], $right['host']];
        });

        return $candidates;
    }

    protected function isMobotixResponse(Response $response): bool
    {
        $location = strtolower(trim((string) $response->header('Location', '')));

        return $response->status() === 302 && str_contains($location, '/control/userimage.html');
    }

    protected function candidateHosts(): array
    {
        $configuredHosts = array_filter((array) config('app.discovery.mobotix.hosts', []));

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

    protected function filterExistingHosts(array $candidates): array
    {
        $existingHosts = $this->existingHosts();

        return array_values(array_filter($candidates, static function (array $candidate) use ($existingHosts): bool {
            return ! in_array($candidate['host'], $existingHosts, true);
        }));
    }

    protected function existingHosts(): array
    {
        return Item::query()
            ->where('type', 0)
            ->pluck('url')
            ->map(fn ($url) => $this->extractHost($url))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    protected function existingItemForHost(string $host): ?Item
    {
        return Item::query()
            ->where('type', 0)
            ->get()
            ->first(function (Item $item) use ($host): bool {
                return $this->extractHost($item->url) === $host;
            });
    }

    protected function forgetCandidate(string $candidateId): void
    {
        $remainingCandidates = array_values(array_filter($this->cachedCandidates(), static function (array $candidate) use ($candidateId): bool {
            return $candidate['id'] !== $candidateId;
        }));

        Cache::put(self::CACHE_KEY, $remainingCandidates, now()->addSeconds((int) config('app.discovery.mobotix.cache_ttl_seconds', 900)));
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

    protected function extractHost(?string $url): ?string
    {
        if (! is_string($url) || trim($url) === '') {
            return null;
        }

        $host = parse_url($url, PHP_URL_HOST);

        if (is_string($host) && $host !== '') {
            return $this->normalizeHost($host);
        }

        $host = parse_url('http://'.ltrim($url, '/'), PHP_URL_HOST);

        return is_string($host) && $host !== '' ? $this->normalizeHost($host) : null;
    }

    protected function normalizeHost(string $host): string
    {
        return strtolower(trim($host));
    }
}
