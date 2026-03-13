<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
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
            'app.discovery.wled.hosts' => [],
            'app.discovery.espresense.hosts' => [],
        ]);

        $this->withServerVariables([
            'HTTP_REMOTE_GROUPS' => 'viewer,admin',
        ])->getJson('/discoveries/summary')
            ->assertOk()
            ->assertJsonPath('totalCount', 0);
    }
}
