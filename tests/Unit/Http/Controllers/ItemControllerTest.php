<?php

namespace Tests\Unit\Http\Controllers;

use App\Http\Controllers\ItemController;
use ReflectionMethod;
use Tests\TestCase;

class ItemControllerTest extends TestCase
{
    public function test_remote_icon_download_verifies_tls_by_default(): void
    {
        config(['app.allow_insecure_remote_icon_tls' => false]);

        $options = $this->remoteIconStreamContextOptions();

        $this->assertArrayNotHasKey('ssl', $options);
    }

    public function test_remote_icon_download_can_disable_tls_verification_when_explicitly_enabled(): void
    {
        config(['app.allow_insecure_remote_icon_tls' => true]);

        $options = $this->remoteIconStreamContextOptions();

        $this->assertSame([
            'verify_peer' => false,
            'verify_peer_name' => false,
        ], $options['ssl']);
    }

    private function remoteIconStreamContextOptions(): array
    {
        $method = new ReflectionMethod(ItemController::class, 'remoteIconStreamContextOptions');
        $method->setAccessible(true);

        return $method->invoke(null);
    }
}
