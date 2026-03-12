<?php

namespace Tests\Feature;

use App\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FileFlowsProcessingToggleTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_stats_returns_fileflows_processing_state(): void
    {
        $this->seed();

        Http::fake([
            'http://fileflows.local/api/status' => Http::response([
                'queue' => 4,
                'processing' => 1,
                'processed' => 20,
                'time' => '00:42',
            ], 200),
            'http://fileflows.local/api/settings' => Http::response([
                'IsPaused' => false,
                'PausedUntil' => '0001-01-01T00:00:00',
            ], 200),
        ]);

        $item = $this->createFileFlowsItem();

        $response = $this->get('/get_stats/'.$item->id);

        $response->assertOk();
        $response->assertJsonPath('processingState', 'running');
        $response->assertJsonPath('toggleAction', 'pause');
        $response->assertJsonPath('queue', 4);
        $response->assertJsonPath('isBusy', true);
        $response->assertJsonPath('html', fn ($html) => is_string($html) && str_contains($html, 'Queue'));
    }

    public function test_get_stats_returns_unavailable_state_when_fileflows_is_unreachable(): void
    {
        $this->seed();

        Http::fake([
            'http://fileflows.local/api/status' => Http::failedConnection(),
            'http://fileflows.local/api/settings' => Http::failedConnection(),
        ]);

        $item = $this->createFileFlowsItem();

        $response = $this->get('/get_stats/'.$item->id);

        $response->assertOk();
        $response->assertJsonPath('status', 'inactive');
        $response->assertJsonPath('processingState', 'unavailable');
        $response->assertJsonPath('toggleAction', null);
        $response->assertJsonPath('queue', 0);
        $response->assertJsonPath('isBusy', false);
        $response->assertJsonPath('html', fn ($html) => is_string($html) && str_contains($html, 'Unavailable'));
    }

    public function test_toggle_endpoint_resumes_a_paused_fileflows_instance(): void
    {
        $this->seed();

        Http::fake([
            'http://fileflows.local/api/settings' => Http::sequence()
                ->push([
                    'IsPaused' => true,
                    'PausedUntil' => '6109-04-04T09:56:12.3369452Z',
                ], 200)
                ->push([
                    'IsPaused' => false,
                    'PausedUntil' => '0001-01-01T00:00:00',
                ], 200),
            'http://fileflows.local/api/status' => Http::response([
                'queue' => 0,
                'processing' => 0,
                'processed' => 25,
                'time' => '',
            ], 200),
            'http://fileflows.local/api/system/pause?duration=0' => Http::response(
                '0001-01-01T00:00:00',
                200
            ),
        ]);

        $item = $this->createFileFlowsItem();

        $response = $this->post('/items/'.$item->id.'/fileflows/toggle');

        $response->assertOk();
        $response->assertJsonPath('processingState', 'running');
        $response->assertJsonPath('toggleAction', 'pause');
        $response->assertJsonPath('pausedUntil', '0001-01-01T00:00:00');
        $response->assertJsonPath('isBusy', false);

        Http::assertSent(fn ($request) => $request->url() === 'http://fileflows.local/api/system/pause?duration=0');
    }

    public function test_toggle_endpoint_pauses_a_running_fileflows_instance_until_far_future(): void
    {
        $this->seed();

        Http::fake([
            'http://fileflows.local/api/settings' => Http::sequence()
                ->push([
                    'IsPaused' => false,
                    'PausedUntil' => '0001-01-01T00:00:00',
                ], 200)
                ->push([
                    'IsPaused' => true,
                    'PausedUntil' => '2099-12-31T23:59:59Z',
                ], 200),
            'http://fileflows.local/api/settings/ui-settings' => Http::sequence()
                ->push([
                    'Uid' => '1d75462a-f912-41ea-bb1b-ec17dc9b11b8',
                    'Name' => 'Settings',
                    'PausedUntil' => '0001-01-01T00:00:00',
                    'IsPaused' => false,
                ], 200)
                ->push('', 200),
            'http://fileflows.local/api/status' => Http::response([
                'queue' => 0,
                'processing' => 0,
                'processed' => 25,
                'time' => '',
            ], 200),
            'http://fileflows.local/api/system/pause?duration=52560000' => Http::response(
                '2126-02-16T09:47:57.576397Z',
                200
            ),
        ]);

        $item = $this->createFileFlowsItem();

        $response = $this->post('/items/'.$item->id.'/fileflows/toggle');

        $response->assertOk();
        $response->assertJsonPath('processingState', 'paused');
        $response->assertJsonPath('toggleAction', 'resume');
        $response->assertJsonPath('pausedUntil', '2099-12-31T23:59:59Z');
        $response->assertJsonPath('isBusy', false);

        Http::assertSent(fn ($request) => $request->url() === 'http://fileflows.local/api/system/pause?duration=52560000');
    }

    private function createFileFlowsItem(): Item
    {
        return Item::factory()->create([
            'title' => 'FileFlows Test',
            'url' => 'http://fileflows.local',
            'user_id' => 0,
            'class' => 'App\\SupportedApps\\FileFlows\\FileFlows',
            'description' => json_encode([
                'enabled' => '1',
                'override_url' => null,
            ]),
        ]);
    }
}
