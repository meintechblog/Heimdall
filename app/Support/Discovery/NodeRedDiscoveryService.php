<?php

namespace App\Support\Discovery;

use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class NodeRedDiscoveryService extends AbstractUrlDiscoveryService
{
    public function label(): string
    {
        return 'NodeRED';
    }

    protected function sourceKey(): string
    {
        return 'nodered';
    }

    protected function scanCandidates(): array
    {
        $hosts = $this->candidateHosts();

        if ($hosts === []) {
            return [];
        }

        $candidates = [];

        foreach (array_chunk($hosts, max(1, (int) config('app.discovery.nodered.chunk_size', 8))) as $chunk) {
            $responses = Http::pool(function (Pool $pool) use ($chunk) {
                return array_map(function (string $host) use ($pool) {
                    return $pool
                        ->as($host)
                        ->timeout((float) config('app.discovery.nodered.timeout_seconds', 0.8))
                        ->connectTimeout((float) config('app.discovery.nodered.connect_timeout_seconds', 0.25))
                        ->acceptJson()
                        ->get("http://{$host}:1880/settings");
                }, $chunk);
            });

            foreach ($chunk as $host) {
                $response = $responses[$host] ?? null;

                if (! $response instanceof Response || ! $response->successful()) {
                    continue;
                }

                $payload = $response->json();

                if (! is_array($payload) || ! isset($payload['version'], $payload['flowFilePretty'])) {
                    continue;
                }

                $url = "http://{$host}:1880";

                $candidates[] = [
                    'id' => sha1('nodered:'.$url),
                    'source' => 'nodered',
                    'sourceLabel' => $this->label(),
                    'title' => "NodeRED {$host}:1880",
                    'subtitle' => 'Node-RED '.trim((string) $payload['version']),
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
