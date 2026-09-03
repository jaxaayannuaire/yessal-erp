<?php

namespace Tests\Feature\Caisse;

use App\Models\Caisse\Device;
use App\Models\Caisse\DeviceActivityLog;
use App\Models\Caisse\Shop;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeviceManagementApiTest extends TestCase
{
    use RefreshDatabase;

    private function context(): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();

        $organization->users()->attach($user->id, [
            'role' => 'owner',
        ]);

        $plan = Plan::factory()->withCaisseEntitlement()->create([
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

    private function headers(int $organizationId): array
    {
        return [
            'X-Organization-Id' => (string) $organizationId,
            'Accept' => 'application/json',
        ];
    }

    private function device(Organization $organization): Device
    {
        $shop = Shop::factory()->create([
            'organization_id' => $organization->id,
        ]);

        return Device::factory()->create([
            'organization_id' => $organization->id,
            'shop_id' => $shop->id,
            'status' => 'active',
        ]);
    }

    public function test_user_can_view_device_activity(): void
    {
        [$user, $organization] = $this->context();

        $device = $this->device($organization);

        DeviceActivityLog::factory()->count(3)->create([
            'organization_id' => $organization->id,
            'device_id' => $device->id,
        ]);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization->id))
            ->getJson("/api/v1/caisse/devices/{$device->id}/activity");

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('device.id', $device->id)
            ->assertJsonCount(3, 'activity.data');
    }

    public function test_device_activity_is_paginated(): void
    {
        [$user, $organization] = $this->context();

        $device = $this->device($organization);

        DeviceActivityLog::factory()->count(55)->create([
            'organization_id' => $organization->id,
            'device_id' => $device->id,
        ]);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization->id))
            ->getJson("/api/v1/caisse/devices/{$device->id}/activity");

        $response
            ->assertOk()
            ->assertJsonPath('activity.per_page', 50)
            ->assertJsonPath('activity.total', 55)
            ->assertJsonCount(50, 'activity.data');
    }

    public function test_user_can_revoke_device(): void
    {
        [$user, $organization] = $this->context();

        $device = $this->device($organization);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization->id))
            ->postJson("/api/v1/caisse/devices/{$device->id}/revoke", [
                'reason' => 'Appareil perdu',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('device.id', $device->id)
            ->assertJsonPath('device.status', 'inactive');

        $device->refresh();

        $this->assertSame('inactive', $device->status);
        $this->assertNotNull($device->revoked_at);

        $this->assertDatabaseHas('device_activity_logs', [
            'organization_id' => $organization->id,
            'device_id' => $device->id,
            'event_type' => 'revoked',
        ]);
    }

    public function test_revoke_preserves_activity_history(): void
    {
        [$user, $organization] = $this->context();

        $device = $this->device($organization);

        DeviceActivityLog::create([
            'organization_id' => $organization->id,
            'device_id' => $device->id,
            'event_type' => 'connected',
            'ip_address' => '127.0.0.1',
            'app_version' => '0.1.0',
            'metadata' => ['platform' => 'android'],
            'created_at' => now()->subHour(),
        ]);

        $this
            ->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization->id))
            ->postJson("/api/v1/caisse/devices/{$device->id}/revoke");

        $this->assertDatabaseHas('device_activity_logs', [
            'organization_id' => $organization->id,
            'device_id' => $device->id,
            'event_type' => 'connected',
        ]);

        $this->assertDatabaseHas('device_activity_logs', [
            'organization_id' => $organization->id,
            'device_id' => $device->id,
            'event_type' => 'revoked',
        ]);
    }

    public function test_user_can_activate_revoked_device(): void
    {
        [$user, $organization] = $this->context();

        $device = $this->device($organization);

        $device->update([
            'status' => 'inactive',
            'revoked_at' => now()->subHour(),
        ]);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization->id))
            ->postJson("/api/v1/caisse/devices/{$device->id}/activate");

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('device.id', $device->id)
            ->assertJsonPath('device.status', 'active');

        $device->refresh();

        $this->assertSame('active', $device->status);
        $this->assertNull($device->revoked_at);

        $this->assertDatabaseHas('device_activity_logs', [
            'organization_id' => $organization->id,
            'device_id' => $device->id,
            'event_type' => 'activated',
        ]);
    }

    public function test_other_organization_cannot_view_device_activity(): void
    {
        [$user, $organization] = $this->context();

        $otherOrganization = Organization::factory()->create();

        $device = $this->device($otherOrganization);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization->id))
            ->getJson("/api/v1/caisse/devices/{$device->id}/activity");

        $response->assertForbidden();
    }

    public function test_other_organization_cannot_revoke_device(): void
    {
        [$user, $organization] = $this->context();

        $otherOrganization = Organization::factory()->create();

        $device = $this->device($otherOrganization);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization->id))
            ->postJson("/api/v1/caisse/devices/{$device->id}/revoke");

        $response->assertForbidden();

        $this->assertSame(
            'active',
            $device->fresh()->status
        );
    }

    public function test_other_organization_cannot_activate_device(): void
    {
        [$user, $organization] = $this->context();

        $otherOrganization = Organization::factory()->create();

        $device = $this->device($otherOrganization);

        $device->update([
            'status' => 'inactive',
            'revoked_at' => now(),
        ]);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization->id))
            ->postJson("/api/v1/caisse/devices/{$device->id}/activate");

        $response->assertForbidden();

        $this->assertSame(
            'inactive',
            $device->fresh()->status
        );
    }

    public function test_other_organization_activity_is_not_exposed(): void
    {
        [$user, $organization] = $this->context();

        $otherOrganization = Organization::factory()->create();

        $device = $this->device($otherOrganization);

        DeviceActivityLog::factory()->count(5)->create([
            'organization_id' => $otherOrganization->id,
            'device_id' => $device->id,
        ]);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization->id))
            ->getJson("/api/v1/caisse/devices/{$device->id}/activity");

        $response->assertForbidden();
    }
}
