<?php

namespace Tests\Feature;

use App\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WledItemConfigTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_displays_the_wled_network_aliases_on_the_item_edit_page(): void
    {
        $item = Item::factory()->create([
            'title' => 'WLED Buero',
            'url' => 'http://wled-buero.local',
            'user_id' => 0,
            'appid' => 'ac894a3a9399f135f6eb87f27fb742c71189cc86',
            'description' => json_encode([
                'wled_identity' => [
                    'mac' => 'a8032aa13dd8',
                    'mdns' => 'wled-buero',
                    'aliases' => [
                        'wled-buero.local',
                        '192.168.3.57',
                        '192.168.3.167',
                    ],
                ],
                'wled_preferred_url' => 'http://wled-buero.local',
            ]),
        ]);

        $response = $this->get('/items/'.$item->id.'/edit');

        $response->assertOk();
        $response->assertSee('WLED Netzwerk');
        $response->assertSee('wled-buero.local');
        $response->assertSee('192.168.3.57');
        $response->assertSee('192.168.3.167');
    }

    public function test_updates_the_item_url_to_the_selected_wled_alias(): void
    {
        $item = Item::factory()->create([
            'title' => 'WLED Buero',
            'url' => 'http://wled-buero.local',
            'colour' => '#161b1f',
            'icon' => 'icons/wled.png',
            'user_id' => 0,
            'appid' => 'ac894a3a9399f135f6eb87f27fb742c71189cc86',
            'description' => json_encode([
                'wled_identity' => [
                    'mac' => 'a8032aa13dd8',
                    'mdns' => 'wled-buero',
                    'aliases' => [
                        'wled-buero.local',
                        '192.168.3.57',
                        '192.168.3.167',
                    ],
                ],
                'wled_preferred_url' => 'http://wled-buero.local',
            ]),
        ]);

        $response = $this->patch('/items/'.$item->id, [
            'pinned' => 1,
            'appid' => 'ac894a3a9399f135f6eb87f27fb742c71189cc86',
            'website' => null,
            'title' => 'WLED Buero',
            'colour' => '#161b1f',
            'url' => 'http://wled-buero.local',
            'icon' => 'icons/wled.png',
            'tags' => [0],
            'config' => [
                'wled_identity' => [
                    'mac' => 'a8032aa13dd8',
                    'mdns' => 'wled-buero',
                    'aliases' => [
                        'wled-buero.local',
                        '192.168.3.57',
                        '192.168.3.167',
                    ],
                ],
                'wled_preferred_url' => 'http://192.168.3.167',
            ],
            'wled_selected_url' => 'http://192.168.3.167',
        ]);

        $response->assertStatus(302);

        $item->refresh();

        $this->assertSame('http://192.168.3.167', $item->url);
        $this->assertSame('http://192.168.3.167', data_get(json_decode($item->description, true), 'wled_preferred_url'));
    }
}
