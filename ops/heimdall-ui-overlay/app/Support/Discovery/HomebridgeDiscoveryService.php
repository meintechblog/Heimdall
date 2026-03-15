<?php

namespace App\Support\Discovery;

use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class HomebridgeDiscoveryService extends AbstractUrlDiscoveryService
{
    public function label(): string
    {
        return 'Homebridge';
    }

    protected function sourceKey(): string
    {
        return 'homebridge';
    }

    protected function scanCandidates(): array
    {
        $hosts = $this->candidateHosts();

        if ($hosts === []) {
            return [];
        }

        $candidates = [];

        foreach (array_chunk($hosts, max(1, (int) config('app.discovery.homebridge.chunk_size', 8))) as $chunk) {
            $responses = Http::pool(function (Pool $pool) use ($chunk) {
                return array_map(function (string $host) use ($pool) {
                    return $pool
                        ->as($host)
                        ->timeout((float) config('app.discovery.homebridge.timeout_seconds', 0.8))
                        ->connectTimeout((float) config('app.discovery.homebridge.connect_timeout_seconds', 0.25))
                        ->get("http://{$host}:8581/");
                }, $chunk);
            });

            foreach ($chunk as $host) {
                $response = $responses[$host] ?? null;

                if (! $response instanceof Response || ! $response->successful()) {
                    continue;
                }

                $body = strtolower((string) $response->body());

                if (! str_contains($body, '<title>homebridge</title>')) {
                    continue;
                }

                $url = "http://{$host}:8581";

                $candidates[] = [
                    'id' => sha1('homebridge:'.$url),
                    'source' => 'homebridge',
                    'sourceLabel' => $this->label(),
                    'title' => "Homebridge {$host}:8581",
                    'subtitle' => 'Homebridge UI',
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
