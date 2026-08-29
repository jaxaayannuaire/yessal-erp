<?php

namespace Database\Factories\Caisse;

use App\Models\Caisse\SyncConflict;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

class SyncConflictFactory extends Factory
{
    protected $model = SyncConflict::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::query()->value('id') ?? 1,
            'sync_event_id' => null,
            'entity_type' => 'sale',
            'entity_id' => null,
            'local_payload' => [],
            'server_payload' => [],
            'conflict_type' => 'version_conflict',
            'resolution' => null,
            'resolved_by' => null,
            'resolved_at' => null,
        ];
    }
}
