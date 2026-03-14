<?php

namespace App\Http\Controllers;

use App\Support\Discovery\EspresenseDiscoveryService;
use App\Support\Discovery\AwtrixDiscoveryService;
use App\Support\Discovery\ShellyDiscoveryService;
use App\Support\Discovery\VenusOSDiscoveryService;
use App\Support\Discovery\WledDiscoveryService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DiscoveryController extends Controller
{
    protected const SOURCE_SERVICES = [
        'wled' => WledDiscoveryService::class,
        'espresense' => EspresenseDiscoveryService::class,
        'venusos' => VenusOSDiscoveryService::class,
        'shelly' => ShellyDiscoveryService::class,
        'awtrix' => AwtrixDiscoveryService::class,
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
}
