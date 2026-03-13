<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class EspresenseDiscoveryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        config([
            'app.url' => 'http://192.168.3.88',
            'app.discovery.wled.hosts' => [
                '192.168.3.250',
            ],
            'app.discovery.espresense.hosts' => [
                '192.168.3.239',
            ],
            'app.discovery.espresense.cache_ttl_seconds' => 900,
            'app.discovery.espresense.chunk_size' => 4,
            'app.discovery.espresense.timeout_seconds' => 1,
            'app.discovery.espresense.connect_timeout_seconds' => 1,
        ]);
    }

    public function test_discovery_candidates_return_espresense_room_name(): void
    {
        Http::fake([
            'http://192.168.3.239/json/info' => Http::response([
                'room' => 'Kueche',
            ], 200),
            'http://192.168.3.239/' => Http::response(
                '<!DOCTYPE html><title>espresense-kueche</title>',
                200,
                ['Content-Type' => 'text/html']
            ),
        ]);

        $response = $this->getJson('/discoveries/candidates');

        $response->assertOk();
        $response->assertJsonPath('totalCount', 1);
        $response->assertJsonPath('candidates.0.source', 'espresense');
        $response->assertJsonPath('candidates.0.sourceLabel', 'ESPresense');
        $response->assertJsonPath('candidates.0.title', 'Kueche');
        $response->assertJsonPath('candidates.0.url', 'http://192.168.3.239');
        $response->assertJsonPath('candidates.0.host', '192.168.3.239');
    }
}
