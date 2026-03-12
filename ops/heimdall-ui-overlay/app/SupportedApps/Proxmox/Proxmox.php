<?php

namespace App\SupportedApps\Proxmox;

class Proxmox extends \App\SupportedApps implements \App\EnhancedApps
{
    public $config;

    public function getRequestAttrs(): array
    {
        $tokenId = $this->getConfigValue('token_id');
        $tokenValue = $this->getConfigValue('token_value');
        $ignoreTls = (bool) $this->getConfigValue('ignore_tls', false);

        $attrs['headers'] = [
            'Accept' => 'application/json',
            'Authorization' => 'PVEAPIToken='.$tokenId.'='.$tokenValue,
        ];

        if ($ignoreTls) {
            $attrs['verify'] = false;
        }

        return $attrs;
    }

    public function test()
    {
        $test = parent::appTest($this->url('version'), $this->getRequestAttrs());

        echo $test->status;
    }

    public function livestats()
    {
        $inactiveData = [
            'container_running' => 0,
            'container_total' => 0,
            'cpu_percent' => 0,
            'memory_percent' => 0,
        ];

        $nodes = $this->resolvedNodes();

        if (empty($nodes)) {
            return parent::getLiveStats('inactive', $inactiveData);
        }

        $containerRunning = 0;
        $containerTotal = 0;
        $cpuPercentSum = 0.0;
        $memoryUsed = 0.0;
        $memoryTotal = 0.0;
        $validNodes = 0;

        foreach ($nodes as $node) {
            $nodeStatus = $this->apiCall('nodes/'.$node.'/status');
            if ($nodeStatus !== null) {
                $validNodes++;
                $cpuPercentSum += (float) ($nodeStatus->cpu ?? 0);
                $memoryUsed += (float) ($nodeStatus->memory->used ?? 0);
                $memoryTotal += (float) ($nodeStatus->memory->total ?? 0);
            }

            $containerStats = $this->apiCall('nodes/'.$node.'/lxc');
            if (is_array($containerStats)) {
                $containerTotal += count($containerStats);
                $containerRunning += count(array_filter($containerStats, function ($container) {
                    return isset($container->status) && $container->status === 'running';
                }));
            }
        }

        if ($validNodes === 0) {
            return parent::getLiveStats('inactive', $inactiveData);
        }

        return parent::getLiveStats('active', [
            'container_running' => $containerRunning,
            'container_total' => $containerTotal,
            'cpu_percent' => ($cpuPercentSum / $validNodes) * 100,
            'memory_percent' => $memoryTotal > 0 ? ($memoryUsed / $memoryTotal) * 100 : 0,
        ]);
    }

    public function url($endpoint)
    {
        return parent::normaliseurl(
            $this->getConfigValue('override_url', $this->config->url)
        ).'api2/json/'.$endpoint;
    }

    public function apiCall($endpoint)
    {
        $res = parent::execute($this->url($endpoint), $this->getRequestAttrs());

        if ($res === null) {
            return null;
        }

        $object = json_decode($res->getBody());

        if (! $object instanceof \stdClass) {
            return null;
        }

        return $object->data;
    }

    protected function resolvedNodes(): array
    {
        $configuredNodes = array_values(array_filter(array_map('trim', explode(',', (string) $this->getConfigValue('nodes', '')))));

        if (! empty($configuredNodes)) {
            return $configuredNodes;
        }

        $nodeData = $this->apiCall('nodes');
        if (! is_array($nodeData)) {
            return [];
        }

        return array_values(array_filter(array_map(function ($node) {
            return isset($node->node) ? trim((string) $node->node) : '';
        }, $nodeData)));
    }

    protected function getConfigValue($key, $default = null)
    {
        return isset($this->config) && isset($this->config->$key)
            ? $this->config->$key
            : $default;
    }
}
