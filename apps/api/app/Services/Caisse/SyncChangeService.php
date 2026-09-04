<?php

namespace App\Services\Caisse;

use App\Models\Caisse\SyncChange;
use Illuminate\Database\Eloquent\Model;

class SyncChangeService
{
    public function record(
        int $organizationId,
        string $entityType,
        Model $entity,
        ?int $sourceDeviceId = null
    ): SyncChange {
        return SyncChange::create([
            'organization_id' => $organizationId,
            'entity_type' => $entityType,
            'entity_id' => (string) $entity->getKey(),
            'operation' => 'upsert',
            'payload' => $entity->fresh()->toArray(),
            'occurred_at' => now(),
            'source_device_id' => $sourceDeviceId,
        ]);
    }

    public function pull(int $organizationId, int $cursor, int $limit): array
    {
        $changes = SyncChange::query()
            ->where('organization_id', $organizationId)
            ->where('id', '>', $cursor)
            ->orderBy('id')
            ->limit($limit)
            ->get();
        $nextCursor = $changes->isEmpty()
            ? $cursor
            : (int) $changes->last()->id;

        return [
            'changes' => $changes->map(fn (SyncChange $change) => [
                'cursor' => $change->id,
                'entity_type' => $change->entity_type,
                'entity_id' => $change->entity_id,
                'operation' => $change->operation,
                'payload' => $change->payload,
                'occurred_at' => $change->occurred_at?->toISOString(),
                'source_device_id' => $change->source_device_id,
            ])->all(),
            'next_cursor' => $nextCursor,
            'has_more' => SyncChange::query()
                ->where('organization_id', $organizationId)
                ->where('id', '>', $nextCursor)
                ->exists(),
        ];
    }
}
