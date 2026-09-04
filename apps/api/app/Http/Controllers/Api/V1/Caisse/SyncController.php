<?php

namespace App\Http\Controllers\Api\V1\Caisse;

use App\Http\Controllers\Controller;
use App\Services\Caisse\SyncService;
use App\Services\Caisse\SyncChangeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SyncController extends Controller
{
    public function __construct(
        private readonly SyncService $syncService,
        private readonly SyncChangeService $syncChanges
    ) {
    }

    public function push(Request $request): JsonResponse
    {
        $organizationId = (int) $request
            ->attributes
            ->get('organization_id');

        $data = $request->validate([
            'device_id' => [
                'required',
                'integer',
                'exists:devices,id',
            ],
            'events' => [
                'required',
                'array',
                'min:1',
                'max:500',
            ],
            'events.*.event_uuid' => [
                'required',
                'uuid',
            ],
            'events.*.shop_id' => [
                'nullable',
                'integer',
                'exists:shops,id',
            ],
            'events.*.entity_type' => [
                'required',
                'string',
                'max:100',
            ],
            'events.*.entity_id' => [
                'required',
                'string',
                'max:100',
            ],
            'events.*.action' => [
                'required',
                'string',
                'max:50',
            ],
            'events.*.payload' => [
                'required',
                'array',
            ],
            'events.*.occurred_at' => [
                'nullable',
                'date',
            ],



        ]);
		$activity = [
			'ip_address' => $request->ip(),
			'user_agent' => $request->userAgent(),
			'app_version' => $request->header('X-App-Version'),
			'metadata' => [
				'platform' => $request->header('X-Platform'),
			],
		];

        $result = $this->syncService->push(
            $organizationId,
            (int) $data['device_id'],
            $data['events'],
			$activity
        );

        return response()->json([
            'success' => true,
            'accepted' => $result['accepted'],
            'rejected' => $result['rejected'],
            'conflicts' => $result['conflicts'],
        ]);
    }

    public function pull(Request $request): JsonResponse
    {
        $organizationId = (int) $request
            ->attributes
            ->get('organization_id');
        $validated = $request->validate([
            'cursor' => ['nullable', 'integer', 'min:0'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:500'],
        ]);

        return response()->json([
            'success' => true,
            ...$this->syncChanges->pull(
                $organizationId,
                (int) ($validated['cursor'] ?? 0),
                (int) ($validated['limit'] ?? 100)
            ),
        ]);
    }
}
