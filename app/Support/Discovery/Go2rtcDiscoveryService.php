<?php

namespace App\Support\Discovery;

use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class Go2rtcDiscoveryService extends AbstractUrlDiscoveryService
{
    public function label(): string
    {
        return 'go2rtc';
    }

    protected function sourceKey(): string
    {
        return 'go2rtc';
    }

    protected function scanCandidates(): array
    {
        $hosts = $this->candidateHosts();

        if ($hosts === []) {
            return [];
        }

        $candidates = [];

        foreach (array_chunk($hosts, max(1, (int) config('app.discovery.go2rtc.chunk_size', 8))) as $chunk) {
            $responses = Http::pool(function (Pool $pool) use ($chunk) {
                return array_map(function (string $host) use ($pool) {
                    return $pool
                        ->as($host)
                        ->timeout((float) config('app.discovery.go2rtc.timeout_seconds', 0.8))
                        ->connectTimeout((float) config('app.discovery.go2rtc.connect_timeout_seconds', 0.25))
                        ->acceptJson()
                        ->get("http://{$host}:1984/api");
                }, $chunk);
            });

            foreach ($chunk as $host) {
                $response = $responses[$host] ?? null;

                if (! $response instanceof Response || ! $response->successful()) {
                    continue;
                }

                $payload = $response->json();

                if (! is_array($payload) || ! isset($payload['version'], $payload['config_path'])) {
                    continue;
                }

                $url = "http://{$host}:1984";

                $candidates[] = [
                    'id' => sha1('go2rtc:'.$url),
                    'source' => 'go2rtc',
                    'sourceLabel' => $this->label(),
                    'title' => "go2rtc {$host}:1984",
                    'subtitle' => 'go2rtc '.trim((string) $payload['version']),
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
