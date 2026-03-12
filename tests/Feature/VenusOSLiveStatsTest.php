<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Fixtures\VenusOS;
use Tests\TestCase;

class VenusOSLiveStatsTest extends TestCase
{
    use RefreshDatabase;

    public function test_venusos_livestats_show_pv_battery_and_grid_import(): void
    {
        $this->seed();

        $app = new VenusOS();
        $app->config = (object) [
            'url' => 'http://192.168.3.11',
            'mqtt_port' => 1883,
            'portal_id' => 'dca6327406c5',
        ];
        $app->metrics = [
            'pv_power' => 1280.0,
            'battery_soc' => 76.4,
            'grid_l1_power' => 600.0,
            'grid_l2_power' => 725.0,
            'grid_l3_power' => 500.0,
        ];

        $payload = json_decode($app->livestats(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame('active', $payload['status']);
        $this->assertStringContainsString('PV', $payload['html']);
        $this->assertStringContainsString('1.3 kW', $payload['html']);
        $this->assertStringContainsString('Bat', $payload['html']);
        $this->assertStringContainsString('76.4%', $payload['html']);
        $this->assertStringContainsString('Grid', $payload['html']);
        $this->assertStringContainsString('1.8 kW', $payload['html']);
        $this->assertStringContainsString('venus-grid-import', $payload['html']);
        $this->assertStringContainsString('↓', $payload['html']);
    }

    public function test_venusos_livestats_show_grid_export_and_treat_missing_pv_as_zero(): void
    {
        $this->seed();

        $app = new VenusOS();
        $app->config = (object) [
            'url' => 'http://192.168.3.11',
            'mqtt_port' => 1883,
        ];
        $app->metrics = [
            'pv_power' => null,
            'battery_soc' => 21.5,
            'grid_l1_power' => -10.0,
            'grid_l2_power' => 227.9,
            'grid_l3_power' => -260.0,
        ];

        $payload = json_decode($app->livestats(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame('active', $payload['status']);
        $this->assertStringContainsString('0 W', $payload['html']);
        $this->assertStringContainsString('21.5%', $payload['html']);
        $this->assertStringContainsString('venus-grid-export', $payload['html']);
        $this->assertStringContainsString('↑', $payload['html']);
        $this->assertStringContainsString('42 W', $payload['html']);
    }
}
