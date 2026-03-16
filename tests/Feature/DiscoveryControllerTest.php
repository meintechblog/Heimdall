<?php

namespace Tests\Feature;

use App\Support\Discovery\WledDiscoveryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class DiscoveryControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_discovery_endpoints_require_admin_role_when_auth_roles_are_enabled(): void
    {
        config([
            'app.auth_roles_enable' => true,
            'app.auth_roles_http_header' => 'HTTP_REMOTE_GROUPS',
            'app.auth_roles_admin' => 'admin',
            'app.auth_roles_delimiter' => ',',
        ]);

        $this->withServerVariables([
            'HTTP_REMOTE_GROUPS' => 'viewer,users',
        ])->getJson('/discoveries/summary')->assertForbidden();

        $this->withServerVariables([
            'HTTP_REMOTE_GROUPS' => 'viewer,users',
        ])->getJson('/discoveries/candidates')->assertForbidden();

        $this->withServerVariables([
            'HTTP_REMOTE_GROUPS' => 'viewer,users',
        ])->getJson('/discoveries/progress')->assertForbidden();

        $this->withServerVariables([
            'HTTP_REMOTE_GROUPS' => 'viewer,users',
        ])->postJson('/discoveries/items', [
            'source' => 'wled',
            'candidateId' => 'test-candidate',
        ])->assertForbidden();
    }

    public function test_discovery_endpoints_allow_admin_role_when_auth_roles_are_enabled(): void
    {
        config([
            'app.auth_roles_enable' => true,
            'app.auth_roles_http_header' => 'HTTP_REMOTE_GROUPS',
            'app.auth_roles_admin' => 'admin',
            'app.auth_roles_delimiter' => ',',
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
                '203.0.113.2',
            ],
            'app.discovery.awtrix.hosts' => [
                '203.0.113.4',
            ],
            'app.discovery.mobotix.hosts' => [
                '203.0.113.5',
            ],
            'app.discovery.nodered.hosts' => [
                '203.0.113.6',
            ],
            'app.discovery.go2rtc.hosts' => [
                '203.0.113.7',
            ],
            'app.discovery.openwb.hosts' => [
                '203.0.113.8',
            ],
            'app.discovery.opendtu.hosts' => [
                '203.0.113.9',
            ],
            'app.discovery.homebridge.hosts' => [
                '203.0.113.16',
            ],
            'app.discovery.homeassistant.hosts' => [
                '203.0.113.17',
            ],
        ]);

        $this->withServerVariables([
            'HTTP_REMOTE_GROUPS' => 'viewer,admin',
        ])->getJson('/discoveries/summary')
            ->assertOk()
            ->assertJsonPath('totalCount', 0);
    }

    public function test_discovery_progress_endpoint_returns_partial_scan_payload(): void
    {
        $response = $this->getJson('/discoveries/progress?fresh=1');

        $response->assertOk();
        $response->assertJsonStructure([
            'totalCount',
            'candidates',
            'completedSources',
            'totalSources',
            'isComplete',
        ]);
    }

    public function test_store_forgets_source_candidates_cache_after_adding_item(): void
    {
        Cache::put('discovery:wled:candidates', [
            ['id' => 'candidate-1'],
        ], now()->addMinutes(5));

        $service = $this->mock(WledDiscoveryService::class);
        $service->shouldReceive('createItemFromCandidate')
            ->once()
            ->with('candidate-1')
            ->andReturn([
                'created' => true,
                'item' => [
                    'id' => 99,
                    'title' => 'wled-buero2',
                    'url' => 'http://192.168.3.64',
                ],
            ]);

        $this->postJson('/discoveries/items', [
            'source' => 'wled',
            'candidateId' => 'candidate-1',
        ])->assertCreated();

        $this->assertFalse(Cache::has('discovery:wled:candidates'));
    }
}
