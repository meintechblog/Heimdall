<?php

namespace Tests\Fixtures;

class Proxmox extends \App\SupportedApps\Proxmox\Proxmox
{
    public array $responses = [];

    public function apiCall($endpoint)
    {
        return $this->responses[$endpoint] ?? null;
    }
}
