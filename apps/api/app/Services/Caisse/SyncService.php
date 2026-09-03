<?php

namespace App\Services\Caisse;

use App\Models\Caisse\Device;
use App\Models\Caisse\Shop;
use App\Models\Caisse\SyncEvent;
use App\Models\Caisse\DeviceActivityLog;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SyncService
{
    public function push(
        int $organizationId,
        int $deviceId,
        array $events,
        array $activity = []
    ): array {
        $device = Device::query()
            ->whereKey($deviceId)
            ->where('organization_id', $organizationId)
            ->where('status', 'active')
            ->first();

        if (! $device) {
            throw ValidationException::withMessages([
                'device_id' => [
                    'Appareil inexistant, inactif ou inaccessible.',
                ],
            ]);
        }

        $now = now();

        $device->update([
            'last_seen_at' => $now,
        ]);

        $this->logActivity(
            $organizationId,
            $device,
            'connected',
            $activity
        );

        $accepted = [];
        $rejected = [];
        $conflicts = [];

        foreach ($events as $event) {
            try {
                $result = DB::transaction(function () use (
                    $organizationId,
                    $device,
                    $event
                ) {
                    $shopId = $event['shop_id'] ?? null;

                    if ($shopId !== null) {
                        $shop = Shop::query()
                            ->whereKey($shopId)
                            ->where('organization_id', $organizationId)
                            ->first();

                        if (! $shop) {
                            throw ValidationException::withMessages([
                                'shop_id' => [
                                    'Boutique inexistante ou inaccessible.',
                                ],
                            ]);
                        }

                        if (
                            $device->shop_id !== null &&
                            (int) $device->shop_id !== (int) $shopId
                        ) {
                            throw ValidationException::withMessages([
                                'shop_id' => [
                                    'La boutique ne correspond pas à l’appareil.',
                                ],
                            ]);
                        }
                    }

                    $existing = $this->findSyncEvent(
                        $organizationId,
                        $event['event_uuid']
                    );

                    if ($existing) {
                        return [
                            'type' => 'accepted',
                            'id' => $existing->id,
                            'duplicate' => true,
                        ];
                    }

                    $created = $this->createSyncEvent([
                        'organization_id' => $organizationId,
                        'shop_id' => $shopId,
                        'device_id' => $device->id,
                        'event_uuid' => $event['event_uuid'],
                        'entity_type' => $event['entity_type'],
                        'entity_id' => $event['entity_id'],
                        'action' => $event['action'],
                        'payload' => $event['payload'],
                        'status' => 'pending',
                        'created_at' => $event['occurred_at'] ?? now(),
                    ]);

                    return [
                        'type' => 'accepted',
                        'id' => $created->id,
                        'duplicate' => false,
                    ];
                });

                $accepted[] = $result;
            } catch (QueryException $e) {
                if (! $this->isUniqueConstraintViolation($e)) {
                    throw $e;
                }

                $existing = $this->findSyncEvent(
                    $organizationId,
                    $event['event_uuid']
                );

                if (! $existing) {
                    throw $e;
                }

                $accepted[] = [
                    'type' => 'accepted',
                    'id' => $existing->id,
                    'duplicate' => true,
                ];
            } catch (ValidationException $e) {
                $rejected[] = [
                    'event_uuid' => $event['event_uuid'] ?? null,
                    'errors' => $e->errors(),
                ];
            }
        }

        $device->update([
            'last_seen_at' => $now,
            'last_sync_at' => $now,
        ]);

        $this->logActivity(
            $organizationId,
            $device,
            'sync_push',
            array_merge($activity, [
                'events_count' => count($events),
                'accepted_count' => count($accepted),
                'rejected_count' => count($rejected),
            ])
        );

        foreach ($rejected as $item) {
            $this->logActivity(
                $organizationId,
                $device,
                'sync_rejected',
                [
                    'event_uuid' => $item['event_uuid'],
                    'errors' => $item['errors'],
                ] + $activity
            );
        }

        return compact(
            'accepted',
            'rejected',
            'conflicts'
        );
    }

    protected function createSyncEvent(array $attributes): SyncEvent
    {
        return SyncEvent::create($attributes);
    }

    protected function findSyncEvent(
        int $organizationId,
        string $eventUuid
    ): ?SyncEvent {
        return SyncEvent::query()
            ->where('organization_id', $organizationId)
            ->where('event_uuid', $eventUuid)
            ->first();
    }

    private function isUniqueConstraintViolation(QueryException $exception): bool
    {
        return in_array((string) $exception->getCode(), ['19', '23000', '23505'], true);
    }

    private function logActivity(
        int $organizationId,
        Device $device,
        string $eventType,
        array $activity = []
    ): void {
        DeviceActivityLog::create([
            'organization_id' => $organizationId,
            'device_id' => $device->id,
            'event_type' => $eventType,
            'ip_address' => $activity['ip_address'] ?? null,
            'user_agent' => $activity['user_agent'] ?? null,
            'app_version' => $activity['app_version'] ?? $device->app_version,
            'metadata' => $activity['metadata'] ?? null,
            'created_at' => now(),
        ]);
    }
}
