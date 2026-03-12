<?php

namespace Tests\Fixtures;

class VenusOS extends \App\SupportedApps\VenusOS\VenusOS
{
    public array $metrics = [];

    protected function fetchVenusMetrics(): array
    {
        return $this->metrics;
    }
}
