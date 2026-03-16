<?php

namespace App\Http\Controllers;

use App\Support\Discovery\EspresenseDiscoveryService;
use App\Support\Discovery\AwtrixDiscoveryService;
use App\Support\Discovery\Go2rtcDiscoveryService;
use App\Support\Discovery\HomeAssistantDiscoveryService;
use App\Support\Discovery\HomebridgeDiscoveryService;
use App\Support\Discovery\MobotixDiscoveryService;
use App\Support\Discovery\NodeRedDiscoveryService;
use App\Support\Discovery\OpenDTUDiscoveryService;
use App\Support\Discovery\OpenWBDiscoveryService;
use App\Support\Discovery\ShellyDiscoveryService;
use App\Support\Discovery\VenusOSDiscoveryService;
use App\Support\Discovery\WledDiscoveryService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class DiscoveryController extends Controller
{
    protected const SOURCE_SERVICES = [
        'wled' => WledDiscoveryService::class,
        'espresense' => EspresenseDiscoveryService::class,
        'venusos' => VenusOSDiscoveryService::class,
        'shelly' => ShellyDiscoveryService::class,
        'awtrix' => AwtrixDiscoveryService::class,
        'mobotix' => MobotixDiscoveryService::class,
        'nodered' => NodeRedDiscoveryService::class,
        'go2rtc' => Go2rtcDiscoveryService::class,
        'openwb' => OpenWBDiscoveryService::class,
        'opendtu' => OpenDTUDiscoveryService::class,
        'homebridge' => HomebridgeDiscoveryService::class,
        'homeassistant' => HomeAssistantDiscoveryService::class,
    ];

    public function __construct()
    {
        parent::__construct();
        $this->middleware('allowed');
    }

    public function summary(): JsonResponse
    {
        $this->authorizeDiscoveryAccess(request());

        $sources = [];
        $totalCount = 0;

        foreach (self::SOURCE_SERVICES as $key => $serviceClass) {
            $service = app($serviceClass);
            $count = count($service->candidates());

            if ($count < 1) {
                continue;
            }

            $sources[] = [
                'key' => $key,
                'label' => $service->label(),
                'count' => $count,
            ];
            $totalCount += $count;
        }

        return response()->json([
            'totalCount' => $totalCount,
            'sources' => $sources,
        ]);
    }

    public function candidates(): JsonResponse
    {
        $this->authorizeDiscoveryAccess(request());

        $candidates = [];

        foreach (self::SOURCE_SERVICES as $serviceClass) {
            $service = app($serviceClass);
            $candidates = array_merge($candidates, $service->candidates());
        }

        usort($candidates, static function (array $left, array $right): int {
            return [$left['source'], $left['title'], $left['host']] <=> [$right['source'], $right['title'], $right['host']];
        });

        return response()->json([
            'totalCount' => count($candidates),
            'candidates' => $candidates,
        ]);
    }

    public function progress(Request $request): JsonResponse
    {
        $this->authorizeDiscoveryAccess($request);

        $state = $request->boolean('fresh')
            ? $this->resetProgressState()
            : $this->loadProgressState();

        $state = $this->advanceProgressState($state);
        $this->storeProgressState($state);

        return response()->json([
            'totalCount' => count($this->flattenProgressCandidates($state)),
            'candidates' => $this->flattenProgressCandidates($state),
            'completedSources' => (int) ($state['nextIndex'] ?? 0),
            'totalSources' => count(self::SOURCE_SERVICES),
            'isComplete' => (bool) ($state['isComplete'] ?? false),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizeDiscoveryAccess($request);

        $validated = $request->validate([
            'source' => 'required|string',
            'candidateId' => 'required|string',
        ]);

        $serviceClass = self::SOURCE_SERVICES[$validated['source']] ?? null;

        abort_if($serviceClass === null, Response::HTTP_NOT_FOUND, 'Discovery source not found.');

        $result = app($serviceClass)->createItemFromCandidate($validated['candidateId']);
        Cache::forget("discovery:{$validated['source']}:candidates");

        return response()->json($result, $result['created'] ? Response::HTTP_CREATED : Response::HTTP_OK);
    }

    protected function authorizeDiscoveryAccess(Request $request): void
    {
        if (! config('app.auth_roles_enable')) {
            return;
        }

        $headerName = (string) config('app.auth_roles_http_header', 'HTTP_REMOTE_GROUPS');
        $adminRole = (string) config('app.auth_roles_admin', 'admin');
        $delimiter = (string) config('app.auth_roles_delimiter', ',');
        $rawRoles = trim((string) $request->server($headerName, ''));
        $roles = array_filter(array_map('trim', explode($delimiter, $rawRoles)));

        if (! in_array($adminRole, $roles, true)) {
            throw new AuthorizationException('Discovery requires admin privileges.');
        }
    }

    protected function resetProgressState(): array
    {
        foreach (array_keys(self::SOURCE_SERVICES) as $source) {
            Cache::forget("discovery:{$source}:candidates");
        }

        return [
            'nextIndex' => 0,
            'isComplete' => false,
            'candidates' => [],
        ];
    }

    protected function loadProgressState(): array
    {
        $state = Cache::get($this->progressCacheKey());

        if (! is_array($state)) {
            return $this->resetProgressState();
        }

        return array_merge([
            'nextIndex' => 0,
            'isComplete' => false,
            'candidates' => [],
        ], $state);
    }

    protected function storeProgressState(array $state): void
    {
        Cache::put(
            $this->progressCacheKey(),
            $state,
            now()->addSeconds((int) config('app.discovery.summary_refresh_seconds', 300))
        );
    }

    protected function advanceProgressState(array $state): array
    {
        $sourceKeys = array_keys(self::SOURCE_SERVICES);
        $nextIndex = (int) ($state['nextIndex'] ?? 0);

        if ($nextIndex >= count($sourceKeys)) {
            $state['isComplete'] = true;

            return $state;
        }

        $sourceKey = $sourceKeys[$nextIndex];
        $serviceClass = self::SOURCE_SERVICES[$sourceKey];
        $service = app($serviceClass);

        $state['candidates'][$sourceKey] = $service->candidates();
        $state['nextIndex'] = $nextIndex + 1;
        $state['isComplete'] = $state['nextIndex'] >= count($sourceKeys);

        return $state;
    }

    protected function flattenProgressCandidates(array $state): array
    {
        $candidates = array_values(array_merge(...array_values($state['candidates'] ?? [[]])));

        usort($candidates, static function (array $left, array $right): int {
            return [$left['source'], $left['title'], $left['host']] <=> [$right['source'], $right['title'], $right['host']];
        });

        return $candidates;
    }

    protected function progressCacheKey(): string
    {
        return 'discovery:progress:state';
    }
}
