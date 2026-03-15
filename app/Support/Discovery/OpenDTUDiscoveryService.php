<?php

namespace App\Support\Discovery;

use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class OpenDTUDiscoveryService extends AbstractUrlDiscoveryService
{
    public function label(): string
    {
        return 'openDTU';
    }

    protected function sourceKey(): string
    {
        return 'opendtu';
    }

    protected function scanCandidates(): array
    {
        $hosts = $this->candidateHosts();

        if ($hosts === []) {
            return [];
        }

        $candidates = [];

        foreach (array_chunk($hosts, max(1, (int) config('app.discovery.opendtu.chunk_size', 8))) as $chunk) {
            $responses = Http::pool(function (Pool $pool) use ($chunk) {
                return array_map(function (string $host) use ($pool) {
                    return $pool
                        ->as($host)
                        ->timeout((float) config('app.discovery.opendtu.timeout_seconds', 0.8))
                        ->connectTimeout((float) config('app.discovery.opendtu.connect_timeout_seconds', 0.25))
                        ->acceptJson()
                        ->get("http://{$host}/api/livedata/status");
                }, $chunk);
            });

            foreach ($chunk as $host) {
                $response = $responses[$host] ?? null;

                if (! $response instanceof Response || ! $response->successful()) {
                    continue;
                }

                $payload = $response->json();

                if (! is_array($payload) || ! isset($payload['inverters']) || ! is_array($payload['inverters'])) {
                    continue;
                }

                $url = "http://{$host}";

                $candidates[] = [
                    'id' => sha1('opendtu:'.$url),
                    'source' => 'opendtu',
                    'sourceLabel' => $this->label(),
                    'title' => "openDTU {$host}",
                    'subtitle' => 'openDTU',
                    'url' => $url,
                    'host' => $host,
                    'icon' => null,
                    'iconUrl' => $this->defaultIconUrl(),
                    'tagId' => 0,
                    'colour' => self::DEFAULT_COLOUR,
                ];
            }
        }

        usort($candidates, static function (array $left, array $right): int {
            return [$left['title'], $left['url']] <=> [$right['title'], $right['url']];
        });

        return $this->filterExistingUrls($candidates);
    }
}
