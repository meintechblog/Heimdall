<?php

namespace App\SupportedApps\VenusOS;

class VenusOS extends \App\SupportedApps implements \App\EnhancedApps
{
    public $config;

    protected const DEFAULT_MQTT_PORT = 1883;

    protected const SOCKET_TIMEOUT_SECONDS = 3;

    protected const DISCOVERY_TOPIC = 'N/#';

    protected const SOCKET_READ_BYTES = 8192;

    public function url($endpoint)
    {
        $baseUrl = $this->getConfigValue('override_url', $this->config->url);
        $baseUrl = parent::normaliseurl((string) $baseUrl, false);

        if ($endpoint === null || $endpoint === '') {
            return $baseUrl;
        }

        return rtrim($baseUrl, '/').'/'.ltrim((string) $endpoint, '/');
    }

    public function test()
    {
        try {
            $metrics = $this->fetchVenusMetrics();

            if ($metrics === []) {
                echo 'Failed: no VenusOS metrics available';

                return;
            }

            echo 'Successfully communicated with the local MQTT service';
        } catch (\Throwable $exception) {
            report($exception);
            echo 'Failed: '.$exception->getMessage();
        }
    }

    public function livestats()
    {
        try {
            $metrics = $this->fetchVenusMetrics();
        } catch (\Throwable $exception) {
            report($exception);

            return parent::getLiveStats('inactive', $this->fallbackData());
        }

        if ($metrics === []) {
            return parent::getLiveStats('inactive', $this->fallbackData());
        }

        $pvPower = $this->normalizeMetricValue($metrics['pv_power'] ?? 0);
        $batterySoc = $this->normalizeMetricValue($metrics['battery_soc'] ?? null);
        $gridPower = $this->sumGridPower($metrics);
        $gridDirection = $this->resolveGridDirection($gridPower);

        $data = [
            'pv_value' => $this->formatPowerValue($pvPower),
            'battery_value' => $batterySoc === null ? 'Unavailable' : $this->formatPercent($batterySoc),
            'grid_value' => $this->formatPowerValue(abs($gridPower)),
            'grid_css_class' => 'venus-grid-'.$gridDirection,
            'grid_arrow' => $this->gridArrow($gridDirection),
        ];

        $status = ($batterySoc !== null || abs($gridPower) > 0.05 || $pvPower > 0.0) ? 'active' : 'inactive';

        return parent::getLiveStats($status, $data);
    }

    protected function fallbackData(): array
    {
        return [
            'pv_value' => '0 W',
            'battery_value' => 'Unavailable',
            'grid_value' => '0 W',
            'grid_css_class' => 'venus-grid-neutral',
            'grid_arrow' => '',
        ];
    }

    protected function fetchVenusMetrics(): array
    {
        $url = $this->getConfigValue('override_url', $this->config->url);
        $host = parse_url($url, PHP_URL_HOST);
        $port = (int) $this->getConfigValue('mqtt_port', self::DEFAULT_MQTT_PORT);

        if (! $host) {
            return [];
        }

        $portalId = trim((string) $this->getConfigValue('portal_id', ''));
        if ($portalId === '') {
            $portalId = $this->discoverPortalId($host, $port);
        }

        if ($portalId === '') {
            return [];
        }

        $topics = [
            'pv_power' => 'N/'.$portalId.'/system/0/Dc/Pv/Power',
            'battery_soc' => 'N/'.$portalId.'/system/0/Dc/Battery/Soc',
            'grid_l1_power' => 'N/'.$portalId.'/system/0/Ac/Grid/L1/Power',
            'grid_l2_power' => 'N/'.$portalId.'/system/0/Ac/Grid/L2/Power',
            'grid_l3_power' => 'N/'.$portalId.'/system/0/Ac/Grid/L3/Power',
        ];

        return $this->readMetricTopics($host, $port, $portalId, $topics);
    }

    protected function discoverPortalId(string $host, int $port): string
    {
        $messages = $this->readMqttMessages($host, $port, [self::DISCOVERY_TOPIC], null, 1);

        foreach ($messages as $topic => $value) {
            if (preg_match('#^N/([^/]+)/#', $topic, $matches) === 1) {
                return trim((string) $matches[1]);
            }
        }

        return '';
    }

    protected function readMetricTopics(string $host, int $port, string $portalId, array $topics): array
    {
        $messages = $this->readMqttMessages(
            $host,
            $port,
            array_values($topics),
            'R/'.$portalId.'/keepalive',
            2
        );

        $metrics = [];
        foreach ($topics as $key => $topic) {
            $metrics[$key] = $messages[$topic] ?? null;
        }

        return $metrics;
    }

    protected function readMqttMessages(
        string $host,
        int $port,
        array $topics,
        ?string $publishTopic,
        int $timeoutSeconds
    ): array {
        $socket = @stream_socket_client(
            'tcp://'.$host.':'.$port,
            $errorNumber,
            $errorMessage,
            self::SOCKET_TIMEOUT_SECONDS
        );

        if (! is_resource($socket)) {
            throw new \RuntimeException('MQTT connection failed: '.$errorMessage.' ('.$errorNumber.')');
        }

        stream_set_timeout($socket, $timeoutSeconds);
        stream_set_blocking($socket, true);

        try {
            fwrite($socket, $this->mqttConnectPacket('heimdall-venusos'));
            $this->expectPacketType($socket, 2);

            fwrite($socket, $this->mqttSubscribePacket(1, $topics));
            $this->expectPacketType($socket, 9);

            if ($publishTopic) {
                fwrite($socket, $this->mqttPublishPacket($publishTopic));
            }

            $deadline = microtime(true) + $timeoutSeconds;
            $messages = [];
            $expectedTopics = array_fill_keys($topics, true);

            while (microtime(true) < $deadline) {
                $packet = $this->readPacket($socket);
                if ($packet === null) {
                    break;
                }

                if ($packet['type'] !== 3) {
                    continue;
                }

                $decoded = $this->decodePublishPacket($packet['payload']);
                if ($decoded === null) {
                    continue;
                }

                $messages[$decoded['topic']] = $decoded['value'];

                if ($publishTopic !== null && $this->receivedAllTopics($messages, $expectedTopics)) {
                    break;
                }

                $metadata = stream_get_meta_data($socket);
                if (! empty($metadata['timed_out'])) {
                    break;
                }
            }

            return $messages;
        } finally {
            fclose($socket);
        }
    }

    protected function receivedAllTopics(array $messages, array $expectedTopics): bool
    {
        foreach ($expectedTopics as $topic => $_expected) {
            if (! array_key_exists($topic, $messages)) {
                return false;
            }
        }

        return true;
    }

    protected function expectPacketType($socket, int $expectedType): void
    {
        $packet = $this->readPacket($socket);

        if ($packet === null || $packet['type'] !== $expectedType) {
            throw new \RuntimeException('Unexpected MQTT response packet');
        }
    }

    protected function readPacket($socket): ?array
    {
        $header = fread($socket, 1);

        if ($header === false || $header === '') {
            return null;
        }

        $type = ord($header) >> 4;
        $remainingLength = $this->readRemainingLength($socket);
        $payload = '';

        while (strlen($payload) < $remainingLength) {
            $chunk = fread($socket, $remainingLength - strlen($payload));

            if ($chunk === false || $chunk === '') {
                break;
            }

            $payload .= $chunk;
        }

        if (strlen($payload) !== $remainingLength) {
            return null;
        }

        return [
            'type' => $type,
            'payload' => $payload,
        ];
    }

    protected function readRemainingLength($socket): int
    {
        $multiplier = 1;
        $value = 0;

        do {
            $encodedByte = fread($socket, 1);

            if ($encodedByte === false || $encodedByte === '') {
                throw new \RuntimeException('Failed reading MQTT remaining length');
            }

            $byteValue = ord($encodedByte);
            $value += ($byteValue & 127) * $multiplier;
            $multiplier *= 128;
        } while (($byteValue & 128) !== 0);

        return $value;
    }

    protected function decodePublishPacket(string $payload): ?array
    {
        if (strlen($payload) < 2) {
            return null;
        }

        $topicLength = unpack('n', substr($payload, 0, 2))[1];
        $topic = substr($payload, 2, $topicLength);
        $message = substr($payload, 2 + $topicLength);
        $decoded = json_decode($message, true);

        return [
            'topic' => $topic,
            'value' => is_array($decoded) ? ($decoded['value'] ?? null) : null,
        ];
    }

    protected function mqttConnectPacket(string $clientId): string
    {
        $payload = $this->encodeMqttString('MQTT')
            . chr(4)
            . chr(2)
            . pack('n', 20)
            . $this->encodeMqttString($clientId);

        return chr(0x10).$this->encodeRemainingLength(strlen($payload)).$payload;
    }

    protected function mqttSubscribePacket(int $packetId, array $topics): string
    {
        $payload = pack('n', $packetId);

        foreach ($topics as $topic) {
            $payload .= $this->encodeMqttString($topic).chr(0);
        }

        return chr(0x82).$this->encodeRemainingLength(strlen($payload)).$payload;
    }

    protected function mqttPublishPacket(string $topic, string $payload = ''): string
    {
        $body = $this->encodeMqttString($topic).$payload;

        return chr(0x30).$this->encodeRemainingLength(strlen($body)).$body;
    }

    protected function encodeMqttString(string $value): string
    {
        return pack('n', strlen($value)).$value;
    }

    protected function encodeRemainingLength(int $length): string
    {
        $encoded = '';

        do {
            $digit = $length % 128;
            $length = intdiv($length, 128);

            if ($length > 0) {
                $digit |= 0x80;
            }

            $encoded .= chr($digit);
        } while ($length > 0);

        return $encoded;
    }

    protected function normalizeMetricValue($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (float) $value;
    }

    protected function sumGridPower(array $metrics): float
    {
        $total = 0.0;

        foreach (['grid_l1_power', 'grid_l2_power', 'grid_l3_power'] as $key) {
            $value = $this->normalizeMetricValue($metrics[$key] ?? null);
            if ($value !== null) {
                $total += $value;
            }
        }

        return $total;
    }

    protected function formatPowerValue(?float $value): string
    {
        $absoluteValue = abs((float) ($value ?? 0.0));

        if ($absoluteValue >= 1000) {
            return number_format($absoluteValue / 1000, 1, '.', '').' kW';
        }

        return (string) ((int) round($absoluteValue)).' W';
    }

    protected function formatPercent(float $value): string
    {
        return number_format($value, 1, '.', '').'%';
    }

    protected function resolveGridDirection(float $gridPower): string
    {
        if ($gridPower > 0.05) {
            return 'import';
        }

        if ($gridPower < -0.05) {
            return 'export';
        }

        return 'neutral';
    }

    protected function gridArrow(string $direction): string
    {
        if ($direction === 'import') {
            return '↓';
        }

        if ($direction === 'export') {
            return '↑';
        }

        return '';
    }

    protected function getConfigValue(string $key, $default = null)
    {
        return isset($this->config) && isset($this->config->{$key})
            ? $this->config->{$key}
            : $default;
    }
}
