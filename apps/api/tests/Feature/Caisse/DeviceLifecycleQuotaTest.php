<?php

namespace Tests\Feature\Caisse;

use App\Models\Caisse\Device;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeviceLifecycleQuotaTest extends TestCase
{
    use RefreshDatabase;

    private function context(int $maxDevices): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();

        $organization->users()->attach($user->id, [
            'role' => 'owner',
        ]);

        $plan = Plan::factory()->create([
            'max_devices' => $maxDevices,
            'is_active' => true,
        ]);

        Subscription::factory()->create([
            'organization_id' => $organization->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now()->subMinute(),
            'ends_at' => now()->addMonth(),
        ]);

        return [$user, $organization];
    }

    private function headers(Organization $organization): array
    {
        return [
            'X-Organization-Id' => (string) $organization->id,
            'Accept' => 'application/json',
        ];
    }

    private function device(
        Organization $organization,
        string $status = 'active'
    ): Device {
        return Device::factory()->create([
            'organization_id' => $organization->id,
            'status' => $status,
        ]);
    }

    public function test_patch_cannot_reactivate_device_when_quota_is_full(): void
    {
        [$user, $organization] = $this->context(1);

        $this->device($organization, 'active');

        $inactive = $this->device($organization, 'inactive');

        $response = $this
            ->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->patchJson(
                "/api/v1/caisse/devices/{$inactive->id}",
                [
                    'status' => 'active',
                ]
            );

        $response
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'device_quota_exceeded')
            ->assertJsonPath('quota.limit', 1)
            ->assertJsonPath('quota.usage', 1);

        $this->assertSame(
            'inactive',
            $inactive->fresh()->status
        );
    }

    public function test_patch_inactive_to_active_is_allowed_after_quota_is_freed(): void
    {
        [$user, $organization] = $this->context(1);

        $active = $this->device($organization, 'active');
        $inactive = $this->device($organization, 'inactive');

        $active->update([
            'status' => 'inactive',
            'revoked_at' => now(),
        ]);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->patchJson(
                "/api/v1/caisse/devices/{$inactive->id}",
                [
                    'status' => 'active',
                ]
            );

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('device.status', 'active');

        $this->assertSame(
            1,
            Device::where('organization_id', $organization->id)
                ->where('status', 'active')
                ->count()
        );
    }

    public function test_active_to_inactive_frees_quota_slot(): void
    {
        [$user, $organization] = $this->context(1);

        $device = $this->device($organization, 'active');

        $response = $this
            ->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->patchJson(
                "/api/v1/caisse/devices/{$device->id}",
                [
                    'status' => 'inactive',
                ]
            );

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('device.status', 'inactive');

        $this->assertSame(
            0,
            Device::where('organization_id', $organization->id)
                ->where('status', 'active')
                ->count()
        );
    }

    public function test_inactive_to_inactive_does_not_consume_quota(): void
    {
        [$user, $organization] = $this->context(1);

        $device = $this->device($organization, 'inactive');

        $response = $this
            ->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->patchJson(
                "/api/v1/caisse/devices/{$device->id}",
                [
                    'status' => 'inactive',
                    'name' => 'Device updated',
                ]
            );

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('device.status', 'inactive')
            ->assertJsonPath('device.name', 'Device updated');

        $this->assertSame(
            0,
            Device::where('organization_id', $organization->id)
                ->where('status', 'active')
                ->count()
        );
    }

    public function test_active_to_active_does_not_consume_another_quota_slot(): void
    {
        [$user, $organization] = $this->context(1);

        $device = $this->device($organization, 'active');

        $response = $this
            ->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->patchJson(
                "/api/v1/caisse/devices/{$device->id}",
                [
                    'status' => 'active',
                    'name' => 'Renamed device',
                ]
            );

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('device.status', 'active')
            ->assertJsonPath('device.name', 'Renamed device');

        $this->assertSame(
            1,
            Device::where('organization_id', $organization->id)
                ->where('status', 'active')
                ->count()
        );
    }

    public function test_patch_cannot_create_second_active_device_by_status_change(): void
    {
        [$user, $organization] = $this->context(2);

        $this->device($organization, 'active');

        $second = $this->device($organization, 'inactive');

        $response = $this
            ->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->patchJson(
                "/api/v1/caisse/devices/{$second->id}",
                [
                    'status' => 'active',
                ]
            );

        $response->assertOk();

        $this->assertSame(
            2,
            Device::where('organization_id', $organization->id)
                ->where('status', 'active')
                ->count()
        );
    }
}