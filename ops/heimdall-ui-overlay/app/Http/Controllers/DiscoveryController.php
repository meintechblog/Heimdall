<?php

namespace App\Http\Controllers;

use App\Support\Discovery\WledDiscoveryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DiscoveryController extends Controller
{
    protected const SOURCE_SERVICES = [
        'wled' => WledDiscoveryService::class,
    ];

    public function __construct()
    {
        parent::__construct();
        $this->middleware('allowed');
    }

    public function summary(): JsonResponse
    {
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
        $validated = $request->validate([
            'source' => 'required|string',
            'candidateId' => 'required|string',
        ]);

        $serviceClass = self::SOURCE_SERVICES[$validated['source']] ?? null;

        abort_if($serviceClass === null, Response::HTTP_NOT_FOUND, 'Discovery source not found.');

        $result = app($serviceClass)->createItemFromCandidate($validated['candidateId']);

        return response()->json($result, $result['created'] ? Response::HTTP_CREATED : Response::HTTP_OK);
    }
}
