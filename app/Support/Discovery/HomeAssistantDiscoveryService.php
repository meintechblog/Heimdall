<?php

namespace App\Support\Discovery;

use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class HomeAssistantDiscoveryService extends AbstractUrlDiscoveryService
{
    public function label(): string
    {
        return 'Home Assistant';
    }

    protected function sourceKey(): string
    {
        return 'homeassistant';
    }

    protected function scanCandidates(): array
    {
        $hosts = $this->candidateHosts();

        if ($hosts === []) {
            return [];
        }

        $candidates = [];

        foreach (array_chunk($hosts, max(1, (int) config('app.discovery.homeassistant.chunk_size', 8))) as $chunk) {
            $responses = Http::pool(function (Pool $pool) use ($chunk) {
                return array_map(function (string $host) use ($pool) {
                    return $pool
                        ->as($host)
                        ->timeout((float) config('app.discovery.homeassistant.timeout_seconds', 0.8))
                        ->connectTimeout((float) config('app.discovery.homeassistant.connect_timeout_seconds', 0.25))
                        ->get("http://{$host}:8123/api/");
                }, $chunk);
            });

            foreach ($chunk as $host) {
                $response = $responses[$host] ?? null;

                if (! $response instanceof Response || $response->status() !== 401) {
                    continue;
                }

                $url = "http://{$host}:8123";

                $candidates[] = [
                    'id' => sha1('homeassistant:'.$url),
                    'source' => 'homeassistant',
                    'sourceLabel' => $this->label(),
                    'title' => "Home Assistant {$host}:8123",
                    'subtitle' => 'Home Assistant',
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
