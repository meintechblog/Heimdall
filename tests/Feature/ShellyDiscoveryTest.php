<?php

namespace Tests\Feature;

use App\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ShellyDiscoveryTest extends TestCase
{
    use RefreshDatabase;

    protected const APP_ID = 'd65462dfcc2066849a1aeac8712497f95315ecd9';

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
                '203.0.113.3',
            ],
            'app.discovery.shelly.hosts' => [
                '192.168.3.56',
            ],
            'app.discovery.shelly.cache_ttl_seconds' => 900,
            'app.discovery.shelly.chunk_size' => 4,
            'app.discovery.shelly.timeout_seconds' => 1,
            'app.discovery.shelly.connect_timeout_seconds' => 1,
        ]);
    }

    public function test_discovery_candidates_return_prepared_shelly_tiles_with_device_name(): void
    {
        Http::fake([
            'http://192.168.3.56/shelly' => Http::response([
                'type' => 'SHSW-25',
                'mac' => '34945469EA08',
                'auth' => false,
            ], 200),
            'http://192.168.3.56/settings' => Http::response([
                'name' => 'Pool',
                'device' => [
                    'type' => 'SHSW-25',
                    'mac' => '34945469EA08',
                ],
                'fw' => '20210908-101005/v1.11.4-g4ccc0b2',
            ], 200),
            '*' => Http::response([], 404),
        ]);

        $response = $this->getJson('/discoveries/candidates');

        $response->assertOk();
        $response->assertJsonPath('totalCount', 1);
        $response->assertJsonPath('candidates.0.source', 'shelly');
        $response->assertJsonPath('candidates.0.sourceLabel', 'Shelly');
        $response->assertJsonPath('candidates.0.title', 'Pool');
        $response->assertJsonPath('candidates.0.url', 'http://192.168.3.56');
        $response->assertJsonPath('candidates.0.appId', self::APP_ID);
    }

    public function test_creates_a_new_item_from_a_cached_shelly_candidate(): void
    {
        $tag = Item::factory()->create([
            'title' => 'Shelly',
            'url' => 'shelly',
            'type' => 1,
            'user_id' => 0,
            'pinned' => 1,
        ]);

        Cache::put('discovery:shelly:candidates', [
            [
                'id' => sha1('shelly:http://192.168.3.56'),
                'source' => 'shelly',
                'title' => 'Pool',
                'url' => 'http://192.168.3.56',
                'host' => '192.168.3.56',
                'appId' => self::APP_ID,
                'icon' => 'icons/shelly.png',
                'tagId' => $tag->id,
                'colour' => '#161b1f',
            ],
        ], 900);

        $response = $this->postJson('/discoveries/items', [
            'source' => 'shelly',
            'candidateId' => sha1('shelly:http://192.168.3.56'),
        ]);

        $response->assertCreated();
        $response->assertJsonPath('item.title', 'Pool');
        $response->assertJsonPath('item.url', 'http://192.168.3.56');

        $item = Item::where('url', 'http://192.168.3.56')->firstOrFail();

        $this->assertSame(self::APP_ID, $item->appid);
        $this->assertSame('App\\SupportedApps\\Shelly\\Shelly', $item->class);
        $this->assertTrue($item->parents->contains('id', $tag->id));
    }
}
