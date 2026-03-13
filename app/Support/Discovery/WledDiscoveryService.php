<?php

namespace App\Support\Discovery;

use App\Application;
use App\Item;
use App\User;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class WledDiscoveryService
{
    protected const APP_ID = 'ac894a3a9399f135f6eb87f27fb742c71189cc86';
    protected const CACHE_KEY = 'discovery:wled:candidates';
    protected const LOCK_KEY = 'discovery:wled:scan';
    protected const DEFAULT_COLOUR = '#161b1f';

    public function label(): string
    {
        return 'WLED';
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
        $application = Application::single(self::APP_ID);
        $item = Item::create([
            'title' => $candidate['title'],
            'url' => $candidate['url'],
            'colour' => $candidate['colour'] ?? self::DEFAULT_COLOUR,
            'icon' => $candidate['icon'] ?? $this->ensureIconPath(),
            'pinned' => 1,
            'order' => 0,
            'type' => 0,
            'class' => $application ? Application::classFromName($application->name) : null,
            'user_id' => $currentUser->getId(),
            'appid' => self::APP_ID,
        ]);

        $item->parents()->sync([$candidate['tagId'] ?? $this->targetTagId()]);
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

        $lock = Cache::lock(self::LOCK_KEY, max(10, (int) config('app.discovery.wled.cache_ttl_seconds', 900)));

        if (! $lock->get()) {
            return is_array($cached) ? $cached : [];
        }

        try {
            $candidates = $this->scanCandidates();
            Cache::put(self::CACHE_KEY, $candidates, now()->addSeconds((int) config('app.discovery.wled.cache_ttl_seconds', 900)));

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
        $icon = $this->ensureIconPath();
        $tagId = $this->targetTagId();

        foreach (array_chunk($hosts, max(1, (int) config('app.discovery.wled.chunk_size', 24))) as $chunk) {
            $responses = Http::pool(function (Pool $pool) use ($chunk) {
                $requests = [];

                foreach ($chunk as $host) {
                    $requests[] = $pool
                        ->as($host)
                        ->timeout((float) config('app.discovery.wled.timeout_seconds', 0.8))
                        ->connectTimeout((float) config('app.discovery.wled.connect_timeout_seconds', 0.4))
                        ->acceptJson()
                        ->get("http://{$host}/json/info");
                }

                return $requests;
            });

            foreach ($chunk as $host) {
                $response = $responses[$host] ?? null;

                if (! $response instanceof Response || ! $response->successful()) {
                    continue;
                }

                $payload = $response->json();

                if (! is_array($payload)) {
                    continue;
                }

                $url = "http://{$host}";
                $title = trim((string) ($payload['name'] ?? ''));
                $version = trim((string) ($payload['ver'] ?? ''));

                $candidates[] = [
                    'id' => sha1($url),
                    'source' => 'wled',
                    'sourceLabel' => $this->label(),
                    'title' => $title !== '' ? $title : "WLED {$host}",
                    'subtitle' => $version !== '' ? "WLED {$version}" : 'WLED',
                    'url' => $url,
                    'host' => $host,
                    'appId' => self::APP_ID,
                    'icon' => $icon,
                    'iconUrl' => $this->iconUrl($icon),
                    'tagId' => $tagId,
                    'colour' => self::DEFAULT_COLOUR,
                ];
            }
        }

        usort($candidates, static function (array $left, array $right): int {
            return [$left['title'], $left['host']] <=> [$right['title'], $right['host']];
        });

        return $candidates;
    }

    protected function candidateHosts(): array
    {
        $configuredHosts = array_filter((array) config('app.discovery.wled.hosts', []));

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

        Cache::put(self::CACHE_KEY, $remainingCandidates, now()->addSeconds((int) config('app.discovery.wled.cache_ttl_seconds', 900)));
    }

    protected function targetTagId(): int
    {
        $tag = Item::query()
            ->where('type', 1)
            ->where(function ($query) {
                $query->where('url', 'wled')
                    ->orWhere('title', 'WLED');
            })
            ->orderByDesc('pinned')
            ->first();

        return $tag ? (int) $tag->id : 0;
    }

    protected function ensureIconPath(): ?string
    {
        $iconPath = 'icons/wled.png';

        if (Storage::disk('public')->exists($iconPath)) {
            return $iconPath;
        }

        try {
            $application = Application::getApp(self::APP_ID);

            if ($application && method_exists($application, 'icon')) {
                return $application->icon();
            }
        } catch (\Throwable) {
            return null;
        }

        return Storage::disk('public')->exists($iconPath) ? $iconPath : null;
    }

    protected function iconUrl(?string $iconPath): string
    {
        if (is_string($iconPath) && $iconPath !== '' && Storage::disk('public')->exists($iconPath)) {
            return asset('storage/'.$iconPath);
        }

        return asset('/img/heimdall-icon-small.png');
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
