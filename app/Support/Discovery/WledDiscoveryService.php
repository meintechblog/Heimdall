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
        return $this->cachedCandidates();
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
        $config = [
            'wled_identity' => $candidate['identity'] ?? [],
            'wled_preferred_url' => $candidate['url'],
        ];
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
            'description' => json_encode($config),
        ]);

        $tagId = (int) ($candidate['tagId'] ?? $this->targetTagId());

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
        $hosts = $this->candidateHosts();

        if ($hosts === []) {
            return [];
        }

        $candidateMap = [];
        $icon = $this->ensureIconPath();
        $tagId = $this->targetTagId();

        foreach (array_chunk($hosts, max(1, (int) config('app.discovery.wled.chunk_size', 4))) as $chunk) {
            $responses = Http::pool(function (Pool $pool) use ($chunk) {
                $requests = [];

                foreach ($chunk as $host) {
                    $requests[] = $pool
                        ->as($host.':info')
                        ->timeout((float) config('app.discovery.wled.timeout_seconds', 0.8))
                        ->connectTimeout((float) config('app.discovery.wled.connect_timeout_seconds', 0.4))
                        ->acceptJson()
                        ->get("http://{$host}/json/info");
                    $requests[] = $pool
                        ->as($host.':cfg')
                        ->timeout((float) config('app.discovery.wled.timeout_seconds', 0.8))
                        ->connectTimeout((float) config('app.discovery.wled.connect_timeout_seconds', 0.4))
                        ->acceptJson()
                        ->get("http://{$host}/json/cfg");
                }

                return $requests;
            });

            foreach ($chunk as $host) {
                $response = $responses[$host.':info'] ?? null;
                $configResponse = $responses[$host.':cfg'] ?? null;

                if (! $response instanceof Response || ! $response->successful()) {
                    continue;
                }

                $payload = $response->json();

                if (! is_array($payload)) {
                    continue;
                }

                if (! $this->isWledPayload($payload)) {
                    continue;
                }

                $configPayload = ($configResponse instanceof Response && $configResponse->successful())
                    ? $configResponse->json()
                    : [];

                if (! is_array($configPayload)) {
                    $configPayload = [];
                }

                $identity = $this->identityForHost($host, $payload, $configPayload);
                $candidateKey = $this->candidateKey($identity, $host);
                $url = $this->preferredUrlForIdentity($identity);
                $title = $this->candidateTitle($payload, $identity);
                $version = trim((string) ($payload['ver'] ?? ''));

                if (! isset($candidateMap[$candidateKey])) {
                    $candidateMap[$candidateKey] = [
                        'id' => sha1('wled:'.$candidateKey),
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
                        'identity' => $identity,
                    ];

                    continue;
                }

                $candidateMap[$candidateKey]['host'] = $candidateMap[$candidateKey]['identity']['aliases'][0] ?? $candidateMap[$candidateKey]['host'];
                $candidateMap[$candidateKey]['identity'] = $this->mergeIdentity(
                    $candidateMap[$candidateKey]['identity'],
                    $identity
                );
                $candidateMap[$candidateKey]['url'] = $this->preferredUrlForIdentity($candidateMap[$candidateKey]['identity']);
            }
        }

        $candidates = [];

        foreach (array_values($candidateMap) as $candidate) {
            $existingItem = $this->existingItemForIdentity($candidate['identity']);

            if ($existingItem) {
                $this->syncIdentityMetadata($existingItem, $candidate['identity']);
                continue;
            }

            $candidate['host'] = $candidate['identity']['aliases'][0] ?? $candidate['host'];
            $candidate['url'] = $this->preferredUrlForIdentity($candidate['identity']);
            $candidates[] = $candidate;
        }

        usort($candidates, static function (array $left, array $right): int {
            return [$left['title'], $left['host']] <=> [$right['title'], $right['host']];
        });

        return $candidates;
    }

    protected function isWledPayload(array $payload): bool
    {
        if (isset($payload['room']) && ! isset($payload['ver']) && ! isset($payload['vid']) && ! isset($payload['leds'])) {
            return false;
        }

        return isset($payload['ver']) || isset($payload['vid']) || isset($payload['leds']) || isset($payload['fxcount']);
    }

    protected function candidateTitle(array $payload, array $identity): string
    {
        $mdnsName = trim((string) ($identity['mdns'] ?? ''));

        if ($mdnsName !== '') {
            return $mdnsName;
        }

        $title = trim((string) ($payload['name'] ?? ''));

        return $title !== '' ? $title : 'WLED';
    }

    protected function identityForHost(string $host, array $payload, array $configPayload): array
    {
        $mac = $this->normalizeMac((string) ($payload['mac'] ?? ''));
        $mdns = strtolower(trim((string) data_get($configPayload, 'id.mdns', '')));
        $aliases = [$host];

        $reportedIp = trim((string) ($payload['ip'] ?? ''));

        if ($reportedIp !== '') {
            $aliases[] = $reportedIp;
        }

        if ($mdns !== '') {
            $aliases[] = $mdns;
            $aliases[] = $mdns.'.local';
        }

        foreach ((array) data_get($configPayload, 'nw.ins', []) as $networkInterface) {
            $ipAddress = data_get($networkInterface, 'ip');

            if (! is_array($ipAddress) || count($ipAddress) !== 4) {
                continue;
            }

            $aliases[] = implode('.', array_map('intval', $ipAddress));
        }

        $normalizedAliases = $this->normalizeAliases($aliases);

        return [
            'mac' => $mac,
            'mdns' => $mdns,
            'aliases' => $normalizedAliases,
        ];
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

    protected function existingItemForIdentity(array $identity): ?Item
    {
        return Item::query()
            ->where('type', 0)
            ->get()
            ->first(function (Item $item) use ($identity): bool {
                return $this->itemMatchesIdentity($item, $identity);
            });
    }

    protected function itemMatchesIdentity(Item $item, array $identity): bool
    {
        $storedIdentity = $this->itemIdentity($item);
        $candidateMac = $this->normalizeMac((string) ($identity['mac'] ?? ''));
        $storedMac = $this->normalizeMac((string) ($storedIdentity['mac'] ?? ''));

        if ($candidateMac !== '' && $storedMac !== '' && $candidateMac === $storedMac) {
            return true;
        }

        $candidateAliases = $this->normalizeAliases((array) ($identity['aliases'] ?? []));
        $storedAliases = $this->normalizeAliases(array_merge(
            [$this->extractHost($item->url)],
            (array) ($storedIdentity['aliases'] ?? [])
        ));

        return array_intersect($candidateAliases, $storedAliases) !== [];
    }

    protected function itemIdentity(Item $item): array
    {
        $config = json_decode($item->description ?? '{}', true);
        $identity = data_get($config, 'wled_identity', []);

        return is_array($identity) ? $identity : [];
    }

    protected function syncIdentityMetadata(Item $item, array $identity): void
    {
        $description = json_decode($item->description ?? '{}', true);

        if (! is_array($description)) {
            $description = [];
        }

        $mergedIdentity = $this->mergeIdentity(
            $this->itemIdentity($item),
            $identity
        );

        $description['wled_identity'] = $mergedIdentity;
        $description['wled_preferred_url'] = $description['wled_preferred_url']
            ?? $this->preferredUrlForIdentity($mergedIdentity);

        $item->forceFill([
            'description' => json_encode($description),
            'appid' => $item->appid ?: self::APP_ID,
        ])->save();
    }

    protected function mergeIdentity(array $left, array $right): array
    {
        $mac = $this->normalizeMac((string) ($left['mac'] ?? ''));

        if ($mac === '') {
            $mac = $this->normalizeMac((string) ($right['mac'] ?? ''));
        }

        $mdns = trim((string) ($left['mdns'] ?? ''));

        if ($mdns === '') {
            $mdns = trim((string) ($right['mdns'] ?? ''));
        }

        return [
            'mac' => $mac,
            'mdns' => strtolower($mdns),
            'aliases' => $this->normalizeAliases(array_merge(
                (array) ($left['aliases'] ?? []),
                (array) ($right['aliases'] ?? [])
            )),
        ];
    }

    protected function preferredUrlForIdentity(array $identity): string
    {
        $aliases = $this->normalizeAliases((array) ($identity['aliases'] ?? []));
        $mdns = trim((string) ($identity['mdns'] ?? ''));

        if ($mdns !== '') {
            return 'http://'.$mdns.'.local';
        }

        $host = $aliases[0] ?? 'localhost';

        return 'http://'.$host;
    }

    protected function candidateKey(array $identity, string $host): string
    {
        if (($identity['mac'] ?? '') !== '') {
            return 'mac:'.$identity['mac'];
        }

        if (($identity['mdns'] ?? '') !== '') {
            return 'mdns:'.$identity['mdns'];
        }

        return 'host:'.$host;
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

        if ($this->hasValidIcon($iconPath)) {
            return $iconPath;
        }

        $remoteIcon = rtrim((string) config('app.appsource'), '/').'/icons/wled.png';

        try {
            $response = Http::timeout(5)->connectTimeout(2)->get($remoteIcon);

            if ($response->successful()) {
                Storage::disk('public')->put($iconPath, $response->body());
            }
        } catch (\Throwable) {
            // Fall back to the default icon below.
        }

        return $this->hasValidIcon($iconPath) ? $iconPath : null;
    }

    protected function iconUrl(?string $iconPath): string
    {
        $baseUrl = $this->baseUrl();

        if (is_string($iconPath) && $iconPath !== '' && Storage::disk('public')->exists($iconPath)) {
            return "{$baseUrl}/storage/{$iconPath}";
        }

        return "{$baseUrl}/img/heimdall-icon-small.png";
    }

    protected function baseUrl(): string
    {
        $configUrl = rtrim((string) config('app.url'), '/');
        $configHost = parse_url($configUrl, PHP_URL_HOST);

        if (
            $configUrl !== ''
            && is_string($configHost)
            && $configHost !== ''
            && strtolower($configHost) !== 'localhost'
        ) {
            return $configUrl;
        }

        $scheme = Request::getScheme();
        $host = Request::getHttpHost();

        if (is_string($host) && $host !== '') {
            return "{$scheme}://{$host}";
        }

        return $configUrl !== '' ? $configUrl : 'http://localhost';
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

    protected function normalizeAliases(array $aliases): array
    {
        $normalized = array_values(array_unique(array_filter(array_map(function ($alias) {
            if (! is_string($alias) || trim($alias) === '') {
                return null;
            }

            $parsedHost = parse_url($alias, PHP_URL_HOST);

            if (is_string($parsedHost) && $parsedHost !== '') {
                return $this->normalizeHost($parsedHost);
            }

            return $this->normalizeHost($alias);
        }, $aliases))));

        sort($normalized);

        return $normalized;
    }

    protected function normalizeMac(string $mac): string
    {
        return strtolower(str_replace([':', '-'], '', trim($mac)));
    }

    protected function hasValidIcon(string $iconPath): bool
    {
        if (! Storage::disk('public')->exists($iconPath)) {
            return false;
        }

        $contents = Storage::disk('public')->get($iconPath);

        return $contents !== '' && str_starts_with($contents, "\x89PNG");
    }
}
