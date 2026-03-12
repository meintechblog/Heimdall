<?php

namespace Tests\Feature;

use App\Item;
use App\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ItemCreateTest extends TestCase
{
    use RefreshDatabase;

    public function test_displays_the_item_create_page(): void
    {
        $this->seed();

        $response = $this->get('/items/create');

        $response->assertStatus(200);
    }

    public function test_display_the_home_dashboard_tag(): void
    {
        $this->seed();

        $response = $this->get('/items/create');

        $response->assertSee('Home dashboard');
    }

    public function test_creates_a_new_item(): void
    {
        $this->seed();
        $item = [
            'pinned' => 1,
            'appid' => 'null',
            'website' => null,
            'title' => 'Item A',
            'colour' => '#00f',
            'url' => 'http://10.0.1.1',
            'tags' => [0],
        ];

        $response = $this->post('/items', $item);

        $response->assertStatus(302);
        $response->assertSee('Redirecting to');
    }

    public function test_redirects_to_dash_when_adding_a_new_item(): void
    {
        $this->seed();
        $item = [
            'pinned' => 1,
            'appid' => 'null',
            'website' => null,
            'title' => 'Item A',
            'colour' => '#00f',
            'url' => 'http://10.0.1.1',
            'tags' => [0],
        ];

        $response = $this->post('/items', $item);

        $response->assertStatus(302);
        $response->assertSee('Redirecting to http://localhost');
    }

    public function test_displays_home_dashboard_items_when_categories_mode_is_enabled(): void
    {
        $this->seed();

        Setting::where('key', 'treat_tags_as')->update([
            'value' => 'categories',
        ]);

        $item = [
            'pinned' => 1,
            'appid' => 'null',
            'website' => null,
            'title' => 'Item A',
            'colour' => '#00f',
            'url' => 'http://10.0.1.1',
            'tags' => [0],
        ];

        $this->post('/items', $item);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Item A');
    }

    public function test_treats_a_proxmox_item_without_saved_config_as_an_enhanced_app(): void
    {
        $item = Item::factory()->make([
            'title' => 'Proxmox Test',
            'url' => 'https://proxmox.local:8006',
            'user_id' => 0,
            'class' => 'App\\SupportedApps\\Proxmox\\Proxmox',
            'appid' => '391f2b7f3fe853e1ea09723eeafc354fa291ab48',
            'description' => null,
        ]);

        $this->assertTrue($item->enhanced());
    }

    public function test_treats_a_venusos_item_without_saved_config_as_an_enhanced_app(): void
    {
        $item = Item::factory()->make([
            'title' => 'VenusOS Test',
            'url' => 'http://192.168.3.11',
            'user_id' => 0,
            'class' => 'App\\SupportedApps\\VenusOS\\VenusOS',
            'appid' => 'venusos-private-app',
            'description' => null,
        ]);

        $this->assertTrue($item->enhanced());
    }

    public function test_displays_the_venusos_config_on_the_item_edit_page(): void
    {
        $this->seed();

        $item = Item::factory()->create([
            'title' => 'VenusOS Test',
            'url' => 'http://192.168.3.11',
            'user_id' => 0,
            'class' => 'App\\SupportedApps\\VenusOS\\VenusOS',
            'appid' => 'venusos-private-app',
            'description' => json_encode([
                'enabled' => true,
                'mqtt_port' => 1883,
                'portal_id' => 'dca6327406c5',
            ]),
        ]);

        $response = $this->get('/items/'.$item->id.'/edit');

        $response->assertOk();
        $response->assertSee('MQTT Port');
        $response->assertSee('Portal ID');
    }
}
