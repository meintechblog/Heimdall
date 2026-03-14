<?php

namespace Tests\Feature;

use App\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MobotixDiscoveryTest extends TestCase
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
                '203.0.113.14',
            ],
            'app.discovery.mobotix.hosts' => [
                '192.168.3.21',
            ],
            'app.discovery.mobotix.cache_ttl_seconds' => 900,
            'app.discovery.mobotix.chunk_size' => 4,
            'app.discovery.mobotix.timeout_seconds' => 1,
            'app.discovery.mobotix.connect_timeout_seconds' => 1,
        ]);
    }

    public function test_discovery_candidates_return_prepared_mobotix_tiles(): void
    {
        Http::fake([
            'http://192.168.3.21/' => Http::response('', 302, [
                'Location' => '/control/userimage.html',
            ]),
            '*' => Http::response([], 404),
        ]);

        $response = $this->getJson('/discoveries/candidates');

        $response->assertOk();
        $response->assertJsonPath('totalCount', 1);
        $response->assertJsonPath('candidates.0.source', 'mobotix');
        $response->assertJsonPath('candidates.0.sourceLabel', 'Mobotix');
        $response->assertJsonPath('candidates.0.title', 'Mobotix 192.168.3.21');
        $response->assertJsonPath('candidates.0.subtitle', 'Mobotix Camera');
        $response->assertJsonPath('candidates.0.url', 'http://192.168.3.21');
    }

    public function test_creates_a_normal_item_from_a_cached_mobotix_candidate(): void
    {
        Cache::put('discovery:mobotix:candidates', [
            [
                'id' => sha1('mobotix:http://192.168.3.21'),
                'source' => 'mobotix',
                'title' => 'Mobotix 192.168.3.21',
                'subtitle' => 'Mobotix Camera',
                'url' => 'http://192.168.3.21',
                'host' => '192.168.3.21',
                'colour' => '#161b1f',
                'icon' => null,
                'tagId' => 0,
            ],
        ], 900);

        $response = $this->postJson('/discoveries/items', [
            'source' => 'mobotix',
            'candidateId' => sha1('mobotix:http://192.168.3.21'),
        ]);

        $response->assertCreated();
        $response->assertJsonPath('item.title', 'Mobotix 192.168.3.21');
        $response->assertJsonPath('item.url', 'http://192.168.3.21');

        $item = Item::where('url', 'http://192.168.3.21')->firstOrFail();

        $this->assertSame('Mobotix 192.168.3.21', $item->title);
        $this->assertNull($item->appid);
        $this->assertNull($item->class);
    }

    public function test_discovery_hides_mobotix_candidates_for_existing_hosts(): void
    {
        Item::factory()->create([
            'title' => 'Existing Mobotix',
            'url' => 'http://192.168.3.21',
            'user_id' => 0,
        ]);

        Http::fake([
            'http://192.168.3.21/' => Http::response('', 302, [
                'Location' => '/control/userimage.html',
            ]),
            '*' => Http::response([], 404),
        ]);

        $response = $this->getJson('/discoveries/candidates');

        $response->assertOk();
        $response->assertJsonPath('totalCount', 0);
        $response->assertJsonCount(0, 'candidates');
    }
}
