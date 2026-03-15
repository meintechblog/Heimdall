<?php

namespace Tests\Feature;

use App\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ServiceDiscoverySourcesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        config([
            'app.url' => 'http://192.168.3.88',
            'app.discovery.wled.hosts' => ['203.0.113.10'],
            'app.discovery.espresense.hosts' => ['203.0.113.11'],
            'app.discovery.venusos.hosts' => ['203.0.113.12'],
            'app.discovery.shelly.hosts' => ['203.0.113.13'],
            'app.discovery.awtrix.hosts' => ['203.0.113.14'],
            'app.discovery.mobotix.hosts' => ['203.0.113.15'],
            'app.discovery.nodered.hosts' => ['192.168.3.8'],
            'app.discovery.go2rtc.hosts' => ['192.168.3.219'],
            'app.discovery.openwb.hosts' => ['192.168.3.156'],
            'app.discovery.opendtu.hosts' => ['192.168.3.98'],
            'app.discovery.homebridge.hosts' => ['192.168.3.7'],
            'app.discovery.homeassistant.hosts' => ['192.168.3.175'],
        ]);
    }

    public function test_discovery_candidates_return_prepared_service_tiles(): void
    {
        Http::fake($this->discoveryFakes());

        $response = $this->getJson('/discoveries/candidates');

        $response->assertOk();
        $response->assertJsonPath('totalCount', 6);
        $response->assertJsonFragment([
            'source' => 'nodered',
            'title' => 'NodeRED 192.168.3.8:1880',
            'subtitle' => 'Node-RED 3.1.1',
            'url' => 'http://192.168.3.8:1880',
        ]);
        $response->assertJsonFragment([
            'source' => 'go2rtc',
            'title' => 'go2rtc 192.168.3.219:1984',
            'subtitle' => 'go2rtc 1.9.10',
            'url' => 'http://192.168.3.219:1984',
        ]);
        $response->assertJsonFragment([
            'source' => 'openwb',
            'title' => 'openWB 192.168.3.156',
            'subtitle' => 'openWB Pro',
            'url' => 'http://192.168.3.156',
        ]);
        $response->assertJsonFragment([
            'source' => 'opendtu',
            'title' => 'openDTU 192.168.3.98',
            'subtitle' => 'openDTU',
            'url' => 'http://192.168.3.98',
        ]);
        $response->assertJsonFragment([
            'source' => 'homebridge',
            'title' => 'Homebridge 192.168.3.7:8581',
            'subtitle' => 'Homebridge UI',
            'url' => 'http://192.168.3.7:8581',
        ]);
        $response->assertJsonFragment([
            'source' => 'homeassistant',
            'title' => 'Home Assistant 192.168.3.175:8123',
            'subtitle' => 'Home Assistant',
            'url' => 'http://192.168.3.175:8123',
        ]);
    }

    /**
     * @dataProvider cachedServiceCandidateProvider
     */
    public function test_creates_normal_items_from_cached_service_candidates(
        string $source,
        string $candidateId,
        string $title,
        string $url
    ): void {
        Cache::put("discovery:{$source}:candidates", [[
            'id' => $candidateId,
            'source' => $source,
            'title' => $title,
            'subtitle' => 'Test',
            'url' => $url,
            'host' => parse_url($url, PHP_URL_HOST),
            'colour' => '#161b1f',
            'icon' => null,
            'tagId' => 0,
        ]], 900);

        $response = $this->postJson('/discoveries/items', [
            'source' => $source,
            'candidateId' => $candidateId,
        ]);

        $response->assertCreated();
        $response->assertJsonPath('item.title', $title);
        $response->assertJsonPath('item.url', $url);

        $item = Item::where('url', $url)->firstOrFail();

        $this->assertSame($title, $item->title);
        $this->assertNull($item->appid);
        $this->assertNull($item->class);
    }

    public static function cachedServiceCandidateProvider(): array
    {
        return [
            'nodered' => ['nodered', sha1('nodered:http://192.168.3.8:1880'), 'NodeRED 192.168.3.8:1880', 'http://192.168.3.8:1880'],
            'go2rtc' => ['go2rtc', sha1('go2rtc:http://192.168.3.219:1984'), 'go2rtc 192.168.3.219:1984', 'http://192.168.3.219:1984'],
            'openwb' => ['openwb', sha1('openwb:http://192.168.3.156'), 'openWB 192.168.3.156', 'http://192.168.3.156'],
            'opendtu' => ['opendtu', sha1('opendtu:http://192.168.3.98'), 'openDTU 192.168.3.98', 'http://192.168.3.98'],
            'homebridge' => ['homebridge', sha1('homebridge:http://192.168.3.7:8581'), 'Homebridge 192.168.3.7:8581', 'http://192.168.3.7:8581'],
            'homeassistant' => ['homeassistant', sha1('homeassistant:http://192.168.3.175:8123'), 'Home Assistant 192.168.3.175:8123', 'http://192.168.3.175:8123'],
        ];
    }

    public function test_discovery_keeps_candidate_visible_for_same_host_on_different_port(): void
    {
        Item::factory()->create([
            'title' => 'Frigate Existing',
            'url' => 'http://192.168.3.219:5000',
            'user_id' => 0,
        ]);

        Http::fake($this->discoveryFakes());

        $response = $this->getJson('/discoveries/candidates');

        $response->assertOk();
        $response->assertJsonFragment([
            'source' => 'go2rtc',
            'title' => 'go2rtc 192.168.3.219:1984',
            'url' => 'http://192.168.3.219:1984',
        ]);
    }

    protected function discoveryFakes(): array
    {
        return [
            'http://192.168.3.8:1880/settings' => Http::response([
                'version' => '3.1.1',
                'flowFilePretty' => true,
            ], 200),
            'http://192.168.3.219:1984/api' => Http::response([
                'config_path' => '/etc/go2rtc.yaml',
                'version' => '1.9.10',
            ], 200),
            'http://192.168.3.156/' => Http::response(
                '<!doctype html><html><body><img src="openWB_logo.svg"><h1>openWB Pro</h1></body></html>',
                200
            ),
            'http://192.168.3.98/api/livedata/status' => Http::response([
                'inverters' => [
                    [
                        'serial' => '112183818450',
                    ],
                ],
            ], 200),
            'http://192.168.3.7:8581/' => Http::response('<!doctype html><html><head><title>Homebridge</title></head></html>', 200),
            'http://192.168.3.175:8123/api/' => Http::response('401: Unauthorized', 401),
            '*' => Http::response([], 404),
        ];
    }
}
