<?php

namespace App\SupportedApps\ShellyPlug;

class ShellyPlug extends \App\SupportedApps implements \App\EnhancedApps
{
    public $config;

    protected const TIMEOUT_SECONDS = 3;

    protected const CONNECT_TIMEOUT_SECONDS = 2;

    public function url($endpoint)
    {
        $baseUrl = parent::normaliseurl($this->config->url, false);

        if ($endpoint === null || $endpoint === '') {
            return $baseUrl;
        }

        return rtrim($baseUrl, '/').'/'.ltrim((string) $endpoint, '/');
    }

    public function test()
    {
        try {
            $metrics = $this->fetchMetrics();

            if ($metrics === null) {
                echo 'Failed: could not read Shelly Plug status';

                return;
            }

            echo 'Successfully communicated with Shelly Plug';
        } catch (\Throwable $exception) {
            report($exception);
            echo 'Failed: '.$exception->getMessage();
        }
    }

    public function livestats()
    {
        try {
            $metrics = $this->fetchMetrics();
        } catch (\Throwable $exception) {
            report($exception);

            return parent::getLiveStats('inactive', $this->fallbackData());
        }

        if ($metrics === null) {
            return parent::getLiveStats('inactive', $this->fallbackData());
        }

        $data = [
            'power_value' => $this->formatPower($metrics['power']),
            'voltage_value' => $this->formatVoltage($metrics['voltage']),
            'energy_value' => $this->formatEnergy($metrics['energy']),
            'relay_on' => $metrics['relay_on'],
        ];

        $status = $metrics['relay_on'] ? 'active' : 'inactive';

        return parent::getLiveStats($status, $data);
    }

    protected function fallbackData(): array
    {
        return [
            'power_value' => '– W',
            'voltage_value' => '– V',
            'energy_value' => '– kWh',
            'relay_on' => false,
        ];
    }

    protected function fetchMetrics(): ?array
    {
        $gen = $this->detectGeneration();

        if ($gen >= 2) {
            return $this->fetchGen2Metrics();
        }

        return $this->fetchGen1Metrics();
    }

    protected function detectGeneration(): int
    {
        try {
            $client = new \GuzzleHttp\Client([
                'http_errors' => false,
                'timeout' => self::TIMEOUT_SECONDS,
                'connect_timeout' => self::CONNECT_TIMEOUT_SECONDS,
            ]);

            $response = $client->get($this->url('/shelly'));

            if ($response->getStatusCode() !== 200) {
                return 1;
            }

            $payload = json_decode((string) $response->getBody(), true);

            $gen = (int) ($payload['gen'] ?? 0);
            if (is_array($payload) && $gen >= 2) {
                return $gen;
            }
        } catch (\Throwable) {
        }

        return 1;
    }

    protected function fetchGen1Metrics(): ?array
    {
        try {
            $client = new \GuzzleHttp\Client([
                'http_errors' => false,
                'timeout' => self::TIMEOUT_SECONDS,
                'connect_timeout' => self::CONNECT_TIMEOUT_SECONDS,
            ]);

            $response = $client->get($this->url('/status'));

            if ($response->getStatusCode() !== 200) {
                return null;
            }

            $payload = json_decode((string) $response->getBody(), true);

            if (! is_array($payload)) {
                return null;
            }

            $meter = $payload['meters'][0] ?? [];
            $relay = $payload['relays'][0] ?? [];

            return [
                'power' => (float) ($meter['power'] ?? 0),
                'voltage' => (float) ($payload['voltage'] ?? 0),
                'energy' => $this->wattMinutesToKwh((float) ($meter['total'] ?? 0)),
                'relay_on' => (bool) ($relay['ison'] ?? false),
            ];
        } catch (\Throwable) {
            return null;
        }
    }

    protected function fetchGen2Metrics(): ?array
    {
        try {
            $client = new \GuzzleHttp\Client([
                'http_errors' => false,
                'timeout' => self::TIMEOUT_SECONDS,
                'connect_timeout' => self::CONNECT_TIMEOUT_SECONDS,
            ]);

            $response = $client->get($this->url('/rpc/Switch.GetStatus?id=0'));

            if ($response->getStatusCode() !== 200) {
                return null;
            }

            $payload = json_decode((string) $response->getBody(), true);

            if (! is_array($payload)) {
                return null;
            }

            $totalWh = (float) ($payload['aenergy']['total'] ?? 0);

            return [
                'power' => (float) ($payload['apower'] ?? 0),
                'voltage' => (float) ($payload['voltage'] ?? 0),
                'energy' => round($totalWh / 1000, 2),
                'relay_on' => (bool) ($payload['output'] ?? false),
            ];
        } catch (\Throwable) {
            return null;
        }
    }

    protected function wattMinutesToKwh(float $wattMinutes): float
    {
        return round($wattMinutes / 60000, 2);
    }

    protected function formatPower(float $watts): string
    {
        if ($watts >= 1000) {
            return round($watts / 1000, 2).' kW';
        }

        return round($watts, 1).' W';
    }

    protected function formatVoltage(float $volts): string
    {
        return round($volts, 1).' V';
    }

    protected function formatEnergy(float $kwh): string
    {
        return number_format($kwh, 2, '.', '').' kWh';
    }
}
