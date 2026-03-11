<?php

namespace Tests\Unit;

use App\Providers\AppServiceProvider;
use ReflectionMethod;
use Tests\TestCase;

class AppServiceProviderTest extends TestCase
{
    public function test_automatic_database_setup_is_disabled_in_testing_environment(): void
    {
        config(['app.env' => 'testing']);

        $provider = new AppServiceProvider($this->app);
        $method = new ReflectionMethod(AppServiceProvider::class, 'shouldRunAutomaticDatabaseSetup');

        $this->assertFalse($method->invoke($provider));
    }
}
