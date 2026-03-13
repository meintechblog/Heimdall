<?php

namespace Tests\Feature;

use App\Item;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WledDiscoveryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        config([
            'app.url' => 'http://192.168.3.88',
            'app.discovery.wled.hosts' => [
                '192.168.3.50',
                '192.168.3.60',
            ],
            'app.discovery.wled.cache_ttl_seconds' => 900,
            'app.discovery.wled.chunk_size' => 10,
            'app.discovery.wled.timeout_seconds' => 1,
            'app.discovery.wled.connect_timeout_seconds' => 1,
        ]);
    }

    public function test_discovery_summary_only_counts_new_wled_devices(): void
    {
        Item::factory()->create([
            'title' => 'Existing WLED',
            'url' => 'http://192.168.3.50',
            'user_id' => 0,
        ]);

        Http::fake([
            'http://192.168.3.60/json/info' => Http::response([
                'name' => 'Hall Strip',
                'ver' => '0.14.4',
            ], 200),
        ]);

        $response = $this->getJson('/discoveries/summary');

        $response->assertOk();
        $response->assertJsonPath('totalCount', 1);
        $response->assertJsonPath('sources.0.key', 'wled');
        $response->assertJsonPath('sources.0.count', 1);

        Http::assertSentCount(1);
        Http::assertSent(function ($request) {
            return (string) $request->url() === 'http://192.168.3.60/json/info';
        });
    }

    public function test_discovery_candidates_return_prepared_wled_tiles(): void
    {
        config([
            'app.discovery.wled.hosts' => [
                '192.168.3.77',
            ],
        ]);

        Http::fake([
            'http://192.168.3.77/json/info' => Http::response([
                'name' => 'Terrasse',
                'ver' => '0.15.0',
            ], 200),
        ]);

        $response = $this->getJson('/discoveries/candidates');

        $response->assertOk();
        $response->assertJsonPath('totalCount', 1);
        $response->assertJsonPath('candidates.0.source', 'wled');
        $response->assertJsonPath('candidates.0.title', 'Terrasse');
        $response->assertJsonPath('candidates.0.url', 'http://192.168.3.77');
        $response->assertJsonPath('candidates.0.host', '192.168.3.77');
    }

    public function test_discovery_uses_the_request_host_when_app_url_is_localhost(): void
    {
        config([
            'app.url' => 'http://localhost',
            'app.discovery.wled.hosts' => [],
        ]);

        app()->instance('request', HttpRequest::create(
            'http://192.168.3.88/discoveries/candidates',
            'GET',
            [],
            [],
            [],
            [
                'HTTP_HOST' => '192.168.3.88',
                'SERVER_NAME' => '192.168.3.88',
            ]
        ));

        $service = app(\App\Support\Discovery\WledDiscoveryService::class);
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('discoverBaseHost');
        $method->setAccessible(true);

        $this->assertSame('192.168.3.88', $method->invoke($service));
    }

    public function test_discovery_uses_the_request_host_for_icon_urls_when_app_url_is_localhost(): void
    {
        config([
            'app.url' => 'http://localhost',
            'app.discovery.wled.hosts' => [
                '192.168.3.77',
            ],
        ]);

        app()->instance('request', HttpRequest::create(
            'http://192.168.3.88/discoveries/candidates',
            'GET',
            [],
            [],
            [],
            [
                'HTTP_HOST' => '192.168.3.88',
                'SERVER_NAME' => '192.168.3.88',
                'REQUEST_SCHEME' => 'http',
            ]
        ));

        Http::fake([
            'http://192.168.3.77/json/info' => Http::response([
                'name' => 'Terrasse',
                'ver' => '0.15.0',
            ], 200),
        ]);

        $response = $this->getJson('/discoveries/candidates');

        $response->assertOk();
        $response->assertJsonPath('candidates.0.iconUrl', 'http://192.168.3.88/storage/icons/wled.png');
    }

    public function test_creates_a_new_item_from_a_cached_wled_candidate(): void
    {
        Storage::disk('public')->put('icons/wled.png', 'wled');

        $wledTag = Item::factory()->create([
            'title' => 'WLED',
            'url' => 'wled',
            'type' => 1,
            'user_id' => 0,
            'pinned' => 1,
        ]);

        Cache::put('discovery:wled:candidates', [
            [
                'id' => sha1('http://192.168.3.60'),
                'source' => 'wled',
                'title' => 'Hall Strip',
                'url' => 'http://192.168.3.60',
                'host' => '192.168.3.60',
                'appId' => 'ac894a3a9399f135f6eb87f27fb742c71189cc86',
                'icon' => 'icons/wled.png',
                'tagId' => $wledTag->id,
                'colour' => '#161b1f',
            ],
        ], 900);

        $response = $this->postJson('/discoveries/items', [
            'source' => 'wled',
            'candidateId' => sha1('http://192.168.3.60'),
        ]);

        $response->assertCreated();
        $response->assertJsonPath('item.title', 'Hall Strip');
        $response->assertJsonPath('item.url', 'http://192.168.3.60');

        $item = Item::where('url', 'http://192.168.3.60')->firstOrFail();

        $this->assertSame('Hall Strip', $item->title);
        $this->assertSame('ac894a3a9399f135f6eb87f27fb742c71189cc86', $item->appid);
        $this->assertSame('icons/wled.png', $item->icon);
        $this->assertSame(1, (int) $item->pinned);
        $this->assertTrue($item->parents->contains('id', $wledTag->id));
    }
}
