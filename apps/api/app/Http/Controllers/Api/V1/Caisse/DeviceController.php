<?php

namespace App\Http\Controllers\Api\V1\Caisse;

use App\Http\Controllers\Controller;
use App\Models\Caisse\Device;
use App\Models\Caisse\DeviceActivityLog;
use App\Models\Caisse\Shop;
use App\Models\Caisse\Terminal;
use App\Services\Caisse\DeviceQuotaExceededException;
use App\Services\Caisse\DeviceQuotaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DeviceController extends Controller
{
    public function __construct(
        private readonly DeviceQuotaService $deviceQuota
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $organization = $request->attributes->get('currentOrganization');

        $devices = Device::query()
            ->where('organization_id', $organization->id)
            ->with(['shop', 'terminal'])
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'devices' => $devices,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $organization = $request->attributes->get('currentOrganization');

        $validated = $request->validate([
            'shop_id' => ['nullable', 'integer', 'exists:shops,id'],
            'terminal_id' => ['nullable', 'integer', 'exists:terminals,id'],
            'device_uuid' => [
                'required',
                'uuid',
                Rule::unique('devices', 'device_uuid')
                    ->where(fn ($query) =>
                        $query->where('organization_id', $organization->id)
                    ),
            ],
            'name' => ['nullable', 'string', 'max:150'],
            'platform' => ['nullable', 'string', 'max:30'],
            'app_version' => ['nullable', 'string', 'max:50'],
            'status' => ['sometimes', 'in:active,inactive'],
        ]);

        $requestedStatus = $validated['status'] ?? 'active';

        $shop = null;

        if (! empty($validated['shop_id'])) {
            $shop = Shop::query()
                ->whereKey($validated['shop_id'])
                ->where('organization_id', $organization->id)
                ->first();

            if (! $shop) {
                abort(403, 'Boutique inaccessible.');
            }
        }

        $terminal = null;

        if (! empty($validated['terminal_id'])) {
            $terminal = Terminal::query()
                ->whereKey($validated['terminal_id'])
                ->whereHas('shop', function ($query) use ($organization) {
                    $query->where('organization_id', $organization->id);
                })
                ->first();

            if (! $terminal) {
                abort(403, 'Terminal inaccessible.');
            }

            if ($shop && (int) $terminal->shop_id !== (int) $shop->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Le terminal n’appartient pas à la boutique indiquée.',
                    'code' => 'terminal_shop_mismatch',
                ], 422);
            }

            $shop ??= $terminal->shop;
        }

        try {
            if ($requestedStatus === 'active') {
                $device = $this->deviceQuota->createActiveDevice(
                    $organization,
                    [
                        'shop_id' => $shop?->id,
                        'terminal_id' => $terminal?->id,
                        'device_uuid' => $validated['device_uuid'],
                        'name' => $validated['name'] ?? null,
                        'platform' => $validated['platform'] ?? null,
                        'app_version' => $validated['app_version'] ?? null,
                        'paired_at' => now(),
                    ]
                );
            } else {
                $device = Device::create([
                    'organization_id' => $organization->id,
                    'shop_id' => $shop?->id,
                    'terminal_id' => $terminal?->id,
                    'device_uuid' => $validated['device_uuid'],
                    'name' => $validated['name'] ?? null,
                    'platform' => $validated['platform'] ?? null,
                    'app_version' => $validated['app_version'] ?? null,
                    'status' => 'inactive',
                    'paired_at' => now(),
                ]);
            }
        } catch (DeviceQuotaExceededException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
                'code' => 'device_quota_exceeded',
                'quota' => $exception->quota,
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Appareil créé avec succès.',
            'device' => $device->load(['shop', 'terminal']),
        ], 201);
    }

    public function show(Request $request, Device $device): JsonResponse
    {
        $organization = $request->attributes->get('currentOrganization');

        $this->ensureDeviceBelongsToOrganization(
            $device,
            $organization->id
        );

        return response()->json([
            'success' => true,
            'device' => $device->load(['shop', 'terminal']),
        ]);
    }

    public function update(Request $request, Device $device): JsonResponse
    {
        $organization = $request->attributes->get('currentOrganization');

        $this->ensureDeviceBelongsToOrganization(
            $device,
            $organization->id
        );

        $validated = $request->validate([
            'shop_id' => ['nullable', 'integer', 'exists:shops,id'],
            'terminal_id' => ['nullable', 'integer', 'exists:terminals,id'],
            'device_uuid' => [
                'sometimes',
                'uuid',
                Rule::unique('devices', 'device_uuid')
                    ->where(fn ($query) =>
                        $query->where('organization_id', $organization->id)
                    )
                    ->ignore($device->id),
            ],
            'name' => ['sometimes', 'nullable', 'string', 'max:150'],
            'platform' => ['sometimes', 'nullable', 'string', 'max:30'],
            'app_version' => ['sometimes', 'nullable', 'string', 'max:50'],
            'status' => ['sometimes', 'in:active,inactive'],
        ]);

        if (
            isset($validated['status']) &&
            $validated['status'] === 'active' &&
            $device->status !== 'active'
        ) {
            try {
                $device = $this->deviceQuota->activateDevice(
                    $organization,
                    $device
                );
            } catch (DeviceQuotaExceededException $exception) {
                return response()->json([
                    'success' => false,
                    'message' => $exception->getMessage(),
                    'code' => 'device_quota_exceeded',
                    'quota' => $exception->quota,
                ], 422);
            }

            unset($validated['status']);
        }

        $shop = null;

        if (
            array_key_exists('shop_id', $validated) &&
            $validated['shop_id'] !== null
        ) {
            $shop = Shop::query()
                ->whereKey($validated['shop_id'])
                ->where('organization_id', $organization->id)
                ->first();

            if (! $shop) {
                abort(403, 'Boutique inaccessible.');
            }
        } elseif ($device->shop_id) {
            $shop = $device->shop;
        }

        if (
            array_key_exists('terminal_id', $validated) &&
            $validated['terminal_id'] !== null
        ) {
            $terminal = Terminal::query()
                ->whereKey($validated['terminal_id'])
                ->whereHas('shop', function ($query) use ($organization) {
                    $query->where(
                        'organization_id',
                        $organization->id
                    );
                })
                ->first();

            if (! $terminal) {
                abort(403, 'Terminal inaccessible.');
            }

            $targetShopId = $shop?->id ?? $device->shop_id;

            if (
                $targetShopId !== null &&
                (int) $terminal->shop_id !== (int) $targetShopId
            ) {
                return response()->json([
                    'success' => false,
                    'message' => 'Le terminal n’appartient pas à la boutique indiquée.',
                    'code' => 'terminal_shop_mismatch',
                ], 422);
            }
        }

        if ($validated !== []) {
            $device->update($validated);
        }

        return response()->json([
            'success' => true,
            'message' => 'Appareil mis à jour avec succès.',
            'device' => $device->fresh()->load(['shop', 'terminal']),
        ]);
    }

    public function activity(
        Request $request,
        Device $device
    ): JsonResponse {
        $organization = $request->attributes->get('currentOrganization');

        $this->ensureDeviceBelongsToOrganization(
            $device,
            $organization->id
        );

        $logs = $device->activityLogs()
            ->where('organization_id', $organization->id)
            ->orderByDesc('created_at')
            ->paginate(50);

        return response()->json([
            'success' => true,
            'device' => $device,
            'activity' => $logs,
        ]);
    }

    public function revoke(
        Request $request,
        Device $device
    ): JsonResponse {
        $organization = $request->attributes->get('currentOrganization');

        $this->ensureDeviceBelongsToOrganization(
            $device,
            $organization->id
        );

        $device->update([
            'status' => 'inactive',
            'revoked_at' => now(),
        ]);

        DeviceActivityLog::create([
            'organization_id' => $organization->id,
            'device_id' => $device->id,
            'event_type' => 'revoked',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'app_version' => $device->app_version,
            'metadata' => [
                'reason' => $request->input('reason'),
            ],
            'created_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Appareil révoqué avec succès.',
            'device' => $device->fresh(),
        ]);
    }

    public function activate(
        Request $request,
        Device $device
    ): JsonResponse {
        $organization = $request->attributes->get('currentOrganization');

        $this->ensureDeviceBelongsToOrganization(
            $device,
            $organization->id
        );

        if ($device->status === 'active') {
            return response()->json([
                'success' => true,
                'message' => 'L’appareil est déjà actif.',
                'device' => $device->fresh(),
            ]);
        }

        try {
            $device = $this->deviceQuota->activateDevice(
                $organization,
                $device
            );
        } catch (DeviceQuotaExceededException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
                'code' => 'device_quota_exceeded',
                'quota' => $exception->quota,
            ], 422);
        }

        DeviceActivityLog::create([
            'organization_id' => $organization->id,
            'device_id' => $device->id,
            'event_type' => 'activated',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'app_version' => $device->app_version,
            'created_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Appareil réactivé avec succès.',
            'device' => $device->fresh(),
        ]);
    }

    private function ensureDeviceBelongsToOrganization(
        Device $device,
        int $organizationId
    ): void {
        if ((int) $device->organization_id !== $organizationId) {
            abort(403, 'Appareil inaccessible.');
        }
    }
}