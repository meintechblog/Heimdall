<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Fixtures\Proxmox;
use Tests\TestCase;

class ProxmoxLiveStatsTest extends TestCase
{
    use RefreshDatabase;

    public function test_proxmox_livestats_aggregate_lxc_cpu_and_ram(): void
    {
        $this->seed();

        $app = new Proxmox();
        $app->responses = [
            'nodes/pve-a/status' => json_decode('{"cpu":0.10,"memory":{"used":2147483648,"total":4294967296}}'),
            'nodes/pve-b/status' => json_decode('{"cpu":0.20,"memory":{"used":3221225472,"total":4294967296}}'),
            'nodes/pve-a/lxc' => json_decode('[{"status":"running"},{"status":"stopped"}]'),
            'nodes/pve-b/lxc' => json_decode('[{"status":"running"}]'),
        ];
        $app->config = (object) [
            'url' => 'https://proxmox.local:8006',
            'nodes' => 'pve-a,pve-b',
        ];

        $payload = json_decode($app->livestats(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame('active', $payload['status']);
        $this->assertStringContainsString('LXC', $payload['html']);
        $this->assertStringContainsString('2/3', $payload['html']);
        $this->assertStringContainsString('15', $payload['html']);
        $this->assertStringContainsString('62.5', $payload['html']);
        $this->assertStringNotContainsString('VM', $payload['html']);
    }
}
