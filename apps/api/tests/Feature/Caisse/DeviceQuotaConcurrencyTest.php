<?php

namespace Tests\Feature\Caisse;

use App\Models\Caisse\Device;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\Caisse\DeviceQuotaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeviceQuotaConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_locked_organization_prevents_quota_bypass(): void
    {
        $organization = Organization::factory()->create();

        $plan = Plan::factory()->withCaisseEntitlement()->create([
            'max_devices' => 1,
            'is_active' => true,
        ]);

        Subscription::factory()->create([
            'organization_id' => $organization->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now()->subMinute(),
            'ends_at' => now()->addMonth(),
        ]);

        $service = app(DeviceQuotaService::class);

        $service->createActiveDevice($organization, [
            'device_uuid' => fake()->uuid(),
            'name' => 'Device 1',
            'platform' => 'android',
            'app_version' => '1.0.0',
            'paired_at' => now(),
        ]);

        $this->expectException(
            \App\Services\Caisse\DeviceQuotaExceededException::class
        );

        $service->createActiveDevice($organization, [
            'device_uuid' => fake()->uuid(),
            'name' => 'Device 2',
            'platform' => 'android',
            'app_version' => '1.0.0',
            'paired_at' => now(),
        ]);
    }
}
