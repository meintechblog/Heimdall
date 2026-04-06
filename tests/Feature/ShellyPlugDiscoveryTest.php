<?php

namespace Tests\Feature;

use App\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ShellyPlugDiscoveryTest extends TestCase
{
    use RefreshDatabase;

    protected const APP_ID = 'a3f7b2c1e8d94056b1c2e3f4a5b6c7d8e9f0a1b2';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        config([
            'app.url' => 'http://192.168.3.88',
            'app.discovery.shelly.hosts' => [
                '192.168.3.60',
                '192.168.3.61',
            ],
            'app.discovery.shelly.cache_ttl_seconds' => 900,
            'app.discovery.shelly.chunk_size' => 4,
            'app.discovery.shelly.timeout_seconds' => 1,
            'app.discovery.shelly.connect_timeout_seconds' => 1,
        ]);
    }

    public function test_discovers_gen1_shelly_plug_s(): void
    {
        Http::fake([
            'http://192.168.3.60/shelly' => Http::response([
                'type' => 'SHPLG-S',
                'mac' => 'AABBCCDDEEFF',
                'auth' => false,
            ], 200),
            'http://192.168.3.60/settings' => Http::response([
                'name' => 'Waschmaschine',
                'device' => [
                    'type' => 'SHPLG-S',
                    'mac' => 'AABBCCDDEEFF',
                ],
                'fw' => '20211109-130412/v1.11.7-g682a0db',
            ], 200),
            '*' => Http::response([], 404),
        ]);

        $response = $this->getJson('/discoveries/candidates');

        $response->assertOk();

        $candidates = collect($response->json('candidates'));
        $plugCandidate = $candidates->firstWhere('source', 'shellyplug');

        $this->assertNotNull($plugCandidate);
        $this->assertSame('Shelly Plug', $plugCandidate['sourceLabel']);
        $this->assertSame('Waschmaschine', $plugCandidate['title']);
        $this->assertSame('http://192.168.3.60', $plugCandidate['url']);
        $this->assertSame(self::APP_ID, $plugCandidate['appId']);

        // Should NOT appear in generic shelly source
        $shellyCandidates = $candidates->where('source', 'shelly');
        $this->assertTrue($shellyCandidates->isEmpty());
    }

    public function test_discovers_gen2_plus_plug_s(): void
    {
        Http::fake([
            'http://192.168.3.61/shelly' => Http::response([
                'name' => 'Trockner',
                'id' => 'shellyplusplugs-112233445566',
                'mac' => '112233445566',
                'model' => 'SNPL-00116EU',
                'gen' => 2,
                'fw_id' => '20231107-164913/1.0.8-g8c77da5',
                'app' => 'PlusPlugS',
            ], 200),
            'http://192.168.3.61/settings' => Http::response([], 404),
            '*' => Http::response([], 404),
        ]);

        $response = $this->getJson('/discoveries/candidates');

        $response->assertOk();

        $candidates = collect($response->json('candidates'));
        $plugCandidate = $candidates->firstWhere('source', 'shellyplug');

        $this->assertNotNull($plugCandidate);
        $this->assertSame('http://192.168.3.61', $plugCandidate['url']);
        $this->assertSame(self::APP_ID, $plugCandidate['appId']);
    }

    public function test_creates_item_from_shellyplug_candidate(): void
    {
        $tag = Item::factory()->create([
            'title' => 'Shelly',
            'url' => 'shelly',
            'type' => 1,
            'user_id' => 0,
            'pinned' => 1,
        ]);

        Cache::put('discovery:shellyplug:candidates', [
            [
                'id' => sha1('shellyplug:http://192.168.3.60'),
                'source' => 'shellyplug',
                'sourceLabel' => 'Shelly Plug',
                'title' => 'Waschmaschine',
                'url' => 'http://192.168.3.60',
                'host' => '192.168.3.60',
                'appId' => self::APP_ID,
                'icon' => 'icons/shelly.png',
                'iconUrl' => 'http://192.168.3.88/storage/icons/shelly.png',
                'tagId' => $tag->id,
                'colour' => '#161b1f',
            ],
        ], 900);

        $response = $this->postJson('/discoveries/items', [
            'source' => 'shellyplug',
            'candidateId' => sha1('shellyplug:http://192.168.3.60'),
        ]);

        $response->assertCreated();
        $response->assertJsonPath('item.title', 'Waschmaschine');

        $item = Item::where('url', 'http://192.168.3.60')->firstOrFail();

        $this->assertSame(self::APP_ID, $item->appid);
        $this->assertSame('App\\SupportedApps\\ShellyPlug\\ShellyPlug', $item->class);
        $this->assertTrue($item->parents->contains('id', $tag->id));
    }

    public function test_discovers_gen3_plug_s(): void
    {
        Http::fake([
            'http://192.168.3.60/shelly' => Http::response([
                'name' => null,
                'id' => 'shellyplugsg3-d885ac15b828',
                'mac' => 'D885AC15B828',
                'slot' => 0,
                'model' => 'S3PL-00112EU',
                'gen' => 3,
                'fw_id' => '20260311-095902/1.7.5-g9979d16',
                'ver' => '1.7.5',
                'app' => 'PlugSG3',
                'auth_en' => false,
            ], 200),
            'http://192.168.3.60/settings' => Http::response('Not Found', 404),
            '*' => Http::response([], 404),
        ]);

        $response = $this->getJson('/discoveries/candidates');

        $response->assertOk();

        $candidates = collect($response->json('candidates'));
        $plugCandidate = $candidates->firstWhere('source', 'shellyplug');

        $this->assertNotNull($plugCandidate);
        $this->assertSame('Shelly Plug', $plugCandidate['sourceLabel']);
        $this->assertSame('http://192.168.3.60', $plugCandidate['url']);
        $this->assertSame(self::APP_ID, $plugCandidate['appId']);
        $this->assertStringContainsString('S3PL', $plugCandidate['subtitle']);
    }

    public function test_non_plug_shelly_is_excluded_from_shellyplug_source(): void
    {
        Http::fake([
            'http://192.168.3.60/shelly' => Http::response([
                'type' => 'SHSW-25',
                'mac' => '34945469EA08',
                'auth' => false,
            ], 200),
            'http://192.168.3.60/settings' => Http::response([
                'name' => 'Pool',
                'fw' => '20210908-101005/v1.11.4-g4ccc0b2',
            ], 200),
            '*' => Http::response([], 404),
        ]);

        $response = $this->getJson('/discoveries/candidates');

        $response->assertOk();

        $candidates = collect($response->json('candidates'));
        $plugCandidates = $candidates->where('source', 'shellyplug');

        $this->assertTrue($plugCandidates->isEmpty());
    }
}
