<?php

namespace Tests\Feature\Caisse;

use App\Models\Caisse\Device;
use App\Models\Caisse\SyncEvent;
use App\Models\Caisse\Shop;
use App\Models\Organization;
use App\Models\User;
use App\Services\Caisse\SyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SyncIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_same_event_uuid_is_accepted_only_once(): void
    {
        $organization = Organization::factory()->create();

        $shop = Shop::factory()->create([
            'organization_id' => $organization->id,
        ]);

        $device = Device::factory()->create([
            'organization_id' => $organization->id,
            'shop_id' => $shop->id,
        ]);

        $eventUuid = (string) Str::uuid();

        $event = [
            'shop_id' => $shop->id,
            'event_uuid' => $eventUuid,
            'entity_type' => 'sale',
            'entity_id' => 1,
            'action' => 'created',
            'payload' => [
                'receipt_number' => 'SYNC-001',
            ],
            'occurred_at' => now(),
        ];

        $service = app(SyncService::class);

        $first = $service->push(
            $organization->id,
            $device->id,
            [$event]
        );

        $second = $service->push(
            $organization->id,
            $device->id,
            [$event]
        );

        $this->assertCount(1, $first['accepted']);
        $this->assertFalse($first['accepted'][0]['duplicate']);

        $this->assertCount(1, $second['accepted']);
        $this->assertTrue($second['accepted'][0]['duplicate']);

        $this->assertSame(
            $first['accepted'][0]['id'],
            $second['accepted'][0]['id']
        );

        $this->assertDatabaseCount('sync_events', 1);

        $this->assertDatabaseHas('sync_events', [
            'organization_id' => $organization->id,
            'device_id' => $device->id,
            'event_uuid' => $eventUuid,
            'entity_type' => 'sale',
            'entity_id' => 1,
            'action' => 'created',
            'status' => 'pending',
        ]);
    }
}