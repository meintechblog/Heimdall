<?php

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Facade;

return [

    'version' => '2.7.7',

    'appsource' => env('APP_SOURCE', 'https://appslist.heimdall.site/'),

    'allow_internal_requests' => env('ALLOW_INTERNAL_REQUESTS', false),

    'allow_insecure_remote_icon_tls' => env('ALLOW_INSECURE_REMOTE_ICON_TLS', false),

    'discovery' => [
        'summary_refresh_seconds' => (int) env('DISCOVERY_SUMMARY_REFRESH_SECONDS', 300),
        'wled' => [
            'hosts' => array_values(array_filter(array_map(
                static fn ($host) => trim($host),
                explode(',', (string) env('DISCOVERY_WLED_HOSTS', ''))
            ))),
            'cache_ttl_seconds' => (int) env('DISCOVERY_WLED_CACHE_TTL_SECONDS', 900),
            'timeout_seconds' => (float) env('DISCOVERY_WLED_TIMEOUT_SECONDS', 0.8),
            'connect_timeout_seconds' => (float) env('DISCOVERY_WLED_CONNECT_TIMEOUT_SECONDS', 0.4),
            'chunk_size' => (int) env('DISCOVERY_WLED_CHUNK_SIZE', 4),
        ],
        'espresense' => [
            'hosts' => array_values(array_filter(array_map(
                static fn ($host) => trim($host),
                explode(',', (string) env('DISCOVERY_ESPRESENSE_HOSTS', ''))
            ))),
            'cache_ttl_seconds' => (int) env('DISCOVERY_ESPRESENSE_CACHE_TTL_SECONDS', 900),
            'timeout_seconds' => (float) env('DISCOVERY_ESPRESENSE_TIMEOUT_SECONDS', 0.8),
            'connect_timeout_seconds' => (float) env('DISCOVERY_ESPRESENSE_CONNECT_TIMEOUT_SECONDS', 0.4),
            'chunk_size' => (int) env('DISCOVERY_ESPRESENSE_CHUNK_SIZE', 4),
        ],
        'venusos' => [
            'hosts' => array_values(array_filter(array_map(
                static fn ($host) => trim($host),
                explode(',', (string) env('DISCOVERY_VENUSOS_HOSTS', ''))
            ))),
            'cache_ttl_seconds' => (int) env('DISCOVERY_VENUSOS_CACHE_TTL_SECONDS', 900),
            'timeout_seconds' => (float) env('DISCOVERY_VENUSOS_TIMEOUT_SECONDS', 0.8),
            'connect_timeout_seconds' => (float) env('DISCOVERY_VENUSOS_CONNECT_TIMEOUT_SECONDS', 0.4),
            'chunk_size' => (int) env('DISCOVERY_VENUSOS_CHUNK_SIZE', 4),
            'mqtt_port' => (int) env('DISCOVERY_VENUSOS_MQTT_PORT', 1883),
        ],
        'shelly' => [
            'hosts' => array_values(array_filter(array_map(
                static fn ($host) => trim($host),
                explode(',', (string) env('DISCOVERY_SHELLY_HOSTS', ''))
            ))),
            'cache_ttl_seconds' => (int) env('DISCOVERY_SHELLY_CACHE_TTL_SECONDS', 900),
            'timeout_seconds' => (float) env('DISCOVERY_SHELLY_TIMEOUT_SECONDS', 0.8),
            'connect_timeout_seconds' => (float) env('DISCOVERY_SHELLY_CONNECT_TIMEOUT_SECONDS', 0.4),
            'chunk_size' => (int) env('DISCOVERY_SHELLY_CHUNK_SIZE', 4),
        ],
        'awtrix' => [
            'hosts' => array_values(array_filter(array_map(
                static fn ($host) => trim($host),
                explode(',', (string) env('DISCOVERY_AWTRIX_HOSTS', ''))
            ))),
            'cache_ttl_seconds' => (int) env('DISCOVERY_AWTRIX_CACHE_TTL_SECONDS', 900),
            'timeout_seconds' => (float) env('DISCOVERY_AWTRIX_TIMEOUT_SECONDS', 0.8),
            'connect_timeout_seconds' => (float) env('DISCOVERY_AWTRIX_CONNECT_TIMEOUT_SECONDS', 0.4),
            'chunk_size' => (int) env('DISCOVERY_AWTRIX_CHUNK_SIZE', 4),
        ],
    ],

    'aliases' => Facade::defaultAliases()->merge([
        'EnhancedApps' => App\EnhancedApps::class,
        'Form' => App\Facades\Form::class,
        'Redis' => Illuminate\Support\Facades\Redis::class,
        'SupportedApps' => App\SupportedApps::class,
        'Yaml' => Symfony\Component\Yaml\Yaml::class,
    ])->toArray(),

    'auth_roles_enable' =>  (bool) env('AUTH_ROLES_ENABLE', false),

    'auth_roles_header' =>  env('AUTH_ROLES_HEADER', 'remote-groups'),

    'auth_roles_http_header' =>  env('AUTH_ROLES_HTTP_HEADER', 'HTTP_REMOTE_GROUPS'),

    'auth_roles_admin' =>  env('AUTH_ROLES_ADMIN', 'admin'),

    'auth_roles_delimiter' =>  env('AUTH_ROLES_DELIMITER', ','),

];
