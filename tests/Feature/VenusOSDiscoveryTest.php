<?php

namespace Tests\Feature;

use App\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class VenusOSDiscoveryTest extends TestCase
{
    use RefreshDatabase;

    protected const APP_ID = '191c67b4933ec1ca9dda6ccb69a4a7e0d40d42e9';

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
            'app.discovery.shelly.hosts' => [
                '203.0.113.2',
            ],
            'app.discovery.venusos.hosts' => [
                '192.168.3.14',
            ],
            'app.discovery.venusos.cache_ttl_seconds' => 900,
            'app.discovery.venusos.chunk_size' => 4,
            'app.discovery.venusos.timeout_seconds' => 1,
            'app.discovery.venusos.connect_timeout_seconds' => 1,
            'app.discovery.venusos.mqtt_port' => 1883,
        ]);
    }

    public function test_discovery_candidates_return_prepared_venusos_tiles(): void
    {
        Http::fake([
            'http://192.168.3.14/' => Http::response('', 302, [
                'Location' => '/gui-v1',
            ]),
            'http://192.168.3.14/websocket-mqtt' => Http::response(
                'HTTP request is not a websocket upgrade request.',
                400
            ),
            '*' => Http::response([], 404),
        ]);

        $response = $this->getJson('/discoveries/candidates');

        $response->assertOk();
        $response->assertJsonPath('totalCount', 1);
        $response->assertJsonPath('candidates.0.source', 'venusos');
        $response->assertJsonPath('candidates.0.sourceLabel', 'VenusOS');
        $response->assertJsonPath('candidates.0.title', 'VenusOS 192.168.3.14');
        $response->assertJsonPath('candidates.0.url', 'http://192.168.3.14');
        $response->assertJsonPath('candidates.0.appId', self::APP_ID);
    }

    public function test_creates_a_new_item_from_a_cached_venusos_candidate(): void
    {
        $tag = Item::factory()->create([
            'title' => 'VenusOS',
            'url' => 'venusos',
            'type' => 1,
            'user_id' => 0,
            'pinned' => 1,
        ]);

        Cache::put('discovery:venusos:candidates', [
            [
                'id' => sha1('venusos:http://192.168.3.14'),
                'source' => 'venusos',
                'title' => 'VenusOS 192.168.3.14',
                'url' => 'http://192.168.3.14',
                'host' => '192.168.3.14',
                'appId' => self::APP_ID,
                'icon' => 'icons/venusos.png',
                'tagId' => $tag->id,
                'colour' => '#161b1f',
                'config' => [
                    'enabled' => true,
                    'override_url' => null,
                    'mqtt_port' => 1883,
                    'portal_id' => null,
                ],
            ],
        ], 900);

        $response = $this->postJson('/discoveries/items', [
            'source' => 'venusos',
            'candidateId' => sha1('venusos:http://192.168.3.14'),
        ]);

        $response->assertCreated();
        $response->assertJsonPath('item.title', 'VenusOS 192.168.3.14');
        $response->assertJsonPath('item.url', 'http://192.168.3.14');

        $item = Item::where('url', 'http://192.168.3.14')->firstOrFail();

        $this->assertSame(self::APP_ID, $item->appid);
        $this->assertSame('App\\SupportedApps\\VenusOS\\VenusOS', $item->class);
        $this->assertTrue($item->parents->contains('id', $tag->id));
        $this->assertSame(1883, data_get(json_decode($item->description, true), 'mqtt_port'));
    }
}
