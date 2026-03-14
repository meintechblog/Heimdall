<?php

namespace Tests\Feature;

use App\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AwtrixDiscoveryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        config([
            'app.url' => 'http://192.168.3.88',
            'app.discovery.wled.hosts' => [
                '203.0.113.10',
            ],
            'app.discovery.espresense.hosts' => [
                '203.0.113.11',
            ],
            'app.discovery.venusos.hosts' => [
                '203.0.113.12',
            ],
            'app.discovery.shelly.hosts' => [
                '203.0.113.13',
            ],
            'app.discovery.awtrix.hosts' => [
                '192.168.3.141',
            ],
            'app.discovery.awtrix.cache_ttl_seconds' => 900,
            'app.discovery.awtrix.chunk_size' => 4,
            'app.discovery.awtrix.timeout_seconds' => 1,
            'app.discovery.awtrix.connect_timeout_seconds' => 1,
        ]);
    }

    public function test_discovery_candidates_return_prepared_awtrix_tiles(): void
    {
        Http::fake([
            'http://192.168.3.141/api/stats' => Http::response([
                'version' => '0.98',
                'app' => 'ping_ms',
                'uid' => 'awtrix_46d970',
                'ip_address' => '192.168.3.141',
            ], 200),
            '*' => Http::response([], 404),
        ]);

        $response = $this->getJson('/discoveries/candidates');

        $response->assertOk();
        $response->assertJsonPath('totalCount', 1);
        $response->assertJsonPath('candidates.0.source', 'awtrix');
        $response->assertJsonPath('candidates.0.sourceLabel', 'AWTRIX');
        $response->assertJsonPath('candidates.0.title', 'AWTRIX 192.168.3.141');
        $response->assertJsonPath('candidates.0.subtitle', 'AWTRIX 0.98 · ping_ms');
        $response->assertJsonPath('candidates.0.url', 'http://192.168.3.141');
    }

    public function test_creates_a_normal_item_from_a_cached_awtrix_candidate(): void
    {
        Cache::put('discovery:awtrix:candidates', [
            [
                'id' => sha1('awtrix:http://192.168.3.141'),
                'source' => 'awtrix',
                'title' => 'AWTRIX 192.168.3.141',
                'subtitle' => 'AWTRIX 0.98 · ping_ms',
                'url' => 'http://192.168.3.141',
                'host' => '192.168.3.141',
                'colour' => '#161b1f',
                'icon' => null,
                'tagId' => 0,
            ],
        ], 900);

        $response = $this->postJson('/discoveries/items', [
            'source' => 'awtrix',
            'candidateId' => sha1('awtrix:http://192.168.3.141'),
        ]);

        $response->assertCreated();
        $response->assertJsonPath('item.title', 'AWTRIX 192.168.3.141');
        $response->assertJsonPath('item.url', 'http://192.168.3.141');

        $item = Item::where('url', 'http://192.168.3.141')->firstOrFail();

        $this->assertSame('AWTRIX 192.168.3.141', $item->title);
        $this->assertNull($item->appid);
        $this->assertNull($item->class);
    }

    public function test_discovery_hides_awtrix_candidates_for_existing_hosts(): void
    {
        Item::factory()->create([
            'title' => 'Existing AWTRIX',
            'url' => 'http://192.168.3.141',
            'user_id' => 0,
        ]);

        Http::fake([
            'http://192.168.3.141/api/stats' => Http::response([
                'version' => '0.98',
                'app' => 'ping_ms',
                'uid' => 'awtrix_46d970',
                'ip_address' => '192.168.3.141',
            ], 200),
            '*' => Http::response([], 404),
        ]);

        $response = $this->getJson('/discoveries/candidates');

        $response->assertOk();
        $response->assertJsonPath('totalCount', 0);
        $response->assertJsonCount(0, 'candidates');
    }
}
