<?php

namespace App\Support\Discovery;

use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class OpenWBDiscoveryService extends AbstractUrlDiscoveryService
{
    public function label(): string
    {
        return 'openWB';
    }

    protected function sourceKey(): string
    {
        return 'openwb';
    }

    protected function scanCandidates(): array
    {
        $hosts = $this->candidateHosts();

        if ($hosts === []) {
            return [];
        }

        $candidates = [];

        foreach (array_chunk($hosts, max(1, (int) config('app.discovery.openwb.chunk_size', 8))) as $chunk) {
            $responses = Http::pool(function (Pool $pool) use ($chunk) {
                return array_map(function (string $host) use ($pool) {
                    return $pool
                        ->as($host)
                        ->timeout((float) config('app.discovery.openwb.timeout_seconds', 0.8))
                        ->connectTimeout((float) config('app.discovery.openwb.connect_timeout_seconds', 0.25))
                        ->get("http://{$host}/");
                }, $chunk);
            });

            foreach ($chunk as $host) {
                $response = $responses[$host] ?? null;

                if (! $response instanceof Response || ! $response->successful()) {
                    continue;
                }

                $body = $response->body();

                if (! is_string($body) || ! $this->looksLikeOpenWB($body)) {
                    continue;
                }

                $url = "http://{$host}";

                $candidates[] = [
                    'id' => sha1('openwb:'.$url),
                    'source' => 'openwb',
                    'sourceLabel' => $this->label(),
                    'title' => "openWB {$host}",
                    'subtitle' => 'openWB Pro',
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

    protected function looksLikeOpenWB(string $body): bool
    {
        $normalized = strtolower($body);

        return str_contains($normalized, 'openwb_logo.svg')
            && str_contains($normalized, '<h1>openwb pro</h1>');
    }
}
