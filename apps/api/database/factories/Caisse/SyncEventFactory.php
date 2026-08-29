<?php

namespace Database\Factories\Caisse;

use App\Models\Caisse\SyncEvent;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class SyncEventFactory extends Factory
{
    protected $model = SyncEvent::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::query()->value('id') ?? 1,
            'shop_id' => null,
            'device_id' => null,
            'event_uuid' => Str::uuid(),
            'entity_type' => 'sale',
            'entity_id' => null,
            'action' => 'created',
            'payload' => [],
            'status' => 'pending',
            'error_code' => null,
            'error_message' => null,
            'occurred_at' => now(),
            'processed_at' => null,
        ];
    }
}
