<?php

namespace Tests\Feature;

use App\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
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
            'app.discovery.venusos.hosts' => [
                '203.0.113.3',
            ],
            'app.discovery.shelly.hosts' => [
                '203.0.113.2',
            ],
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

    public function test_discovery_store_creates_espresense_item_from_candidate(): void
    {
        Cache::put('discovery:espresense:candidates', [
            [
                'id' => sha1('http://192.168.3.239'),
                'source' => 'espresense',
                'sourceLabel' => 'ESPresense',
                'title' => 'Kueche',
                'subtitle' => 'ESPresense Room',
                'url' => 'http://192.168.3.239',
                'host' => '192.168.3.239',
                'icon' => null,
                'iconUrl' => 'http://192.168.3.88/img/heimdall-icon-small.png',
                'tagId' => 0,
                'colour' => '#161b1f',
            ],
        ], now()->addMinutes(5));

        $response = $this->postJson('/discoveries/items', [
            'source' => 'espresense',
            'candidateId' => sha1('http://192.168.3.239'),
        ]);

        $response->assertCreated();
        $response->assertJsonPath('created', true);
        $response->assertJsonPath('item.title', 'Kueche');
        $response->assertJsonPath('item.url', 'http://192.168.3.239');

        $this->assertDatabaseHas('items', [
            'title' => 'Kueche',
            'url' => 'http://192.168.3.239',
            'type' => 0,
        ]);

        $this->assertDatabaseHas('items', [
            'title' => 'ESPresense',
            'url' => 'espresense',
            'type' => 1,
            'pinned' => 1,
        ]);

        $item = Item::query()
            ->where('type', 0)
            ->where('url', 'http://192.168.3.239')
            ->firstOrFail();
        $tag = Item::query()
            ->where('type', 1)
            ->where('url', 'espresense')
            ->firstOrFail();

        $this->assertTrue($item->parents()->where('tag_id', $tag->id)->exists());
    }
}
