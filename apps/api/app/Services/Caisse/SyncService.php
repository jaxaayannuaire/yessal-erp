<?php
namespace App\Services\Caisse;

use App\Models\Caisse\SyncEvent;
use Illuminate\Support\Facades\DB;

class SyncService
{
    public function push(int $organizationId, int $deviceId, array $events): array
    {
        $accepted = [];
        $rejected = [];
        $conflicts = [];

        foreach ($events as $event) {
            $result = DB::transaction(function () use ($organizationId, $deviceId, $event) {
                $existing = SyncEvent::where('organization_id', $organizationId)
                    ->where('event_uuid', $event['event_uuid'])
                    ->first();

                if ($existing) {
                    return ['type' => 'accepted', 'id' => $existing->id, 'duplicate' => true];
                }

                $created = SyncEvent::create([
                    'organization_id' => $organizationId,
                    'shop_id' => $event['shop_id'] ?? null,
                    'device_id' => $deviceId,
                    'event_uuid' => $event['event_uuid'],
                    'entity_type' => $event['entity_type'],
                    'entity_id' => $event['entity_id'],
                    'action' => $event['action'],
                    'payload' => $event['payload'],
                    'status' => 'pending',
                    'created_at' => $event['occurred_at'] ?? now(),
                ]);

                return ['type' => 'accepted', 'id' => $created->id, 'duplicate' => false];
            });

            $accepted[] = $result;
        }

        return compact('accepted', 'rejected', 'conflicts');
    }
}
