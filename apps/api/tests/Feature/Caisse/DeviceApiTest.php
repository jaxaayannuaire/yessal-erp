<?php

namespace Tests\Feature\Caisse;

use App\Models\Caisse\Device;
use App\Models\Caisse\Shop;
use App\Models\Caisse\Terminal;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class DeviceApiTest extends TestCase
{
    use RefreshDatabase;

    private function createOrganizationWithSubscription(): array
    {
        $user = User::factory()->create();

        $organization = Organization::factory()->create();

        $organization->users()->attach($user->id, [
            'role' => 'owner',
        ]);

        $plan = Plan::factory()->create([
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
            'X-Organization-Id' => $organizationId,
        ];
    }

    public function test_user_can_list_devices_of_current_organization(): void
    {
        [$user, $organization] = $this->createOrganizationWithSubscription();

        Device::factory()->create([
            'organization_id' => $organization->id,
        ]);

        Device::factory()->create([
            'organization_id' => $organization->id,
        ]);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization->id))
            ->getJson('/api/v1/caisse/devices');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'devices');
    }

    public function test_user_can_create_device_without_shop_or_terminal(): void
    {
        [$user, $organization] = $this->createOrganizationWithSubscription();

        $uuid = (string) Str::uuid();

        $response = $this
            ->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization->id))
            ->postJson('/api/v1/caisse/devices', [
                'device_uuid' => $uuid,
                'name' => 'Tablette principale',
                'platform' => 'android',
                'app_version' => '1.0.0',
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('device.organization_id', $organization->id)
            ->assertJsonPath('device.shop_id', null)
            ->assertJsonPath('device.terminal_id', null);

        $this->assertDatabaseHas('devices', [
            'organization_id' => $organization->id,
            'device_uuid' => $uuid,
        ]);
    }

    public function test_user_can_create_device_with_current_organization_shop(): void
    {
        [$user, $organization] = $this->createOrganizationWithSubscription();

        $shop = Shop::factory()->create([
            'organization_id' => $organization->id,
        ]);

        $uuid = (string) Str::uuid();

        $response = $this
            ->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization->id))
            ->postJson('/api/v1/caisse/devices', [
                'shop_id' => $shop->id,
                'device_uuid' => $uuid,
                'name' => 'Device boutique',
                'platform' => 'android',
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('device.organization_id', $organization->id)
            ->assertJsonPath('device.shop_id', $shop->id);
    }

    public function test_user_can_create_device_with_current_organization_terminal(): void
    {
        [$user, $organization] = $this->createOrganizationWithSubscription();

        $shop = Shop::factory()->create([
            'organization_id' => $organization->id,
        ]);

        $terminal = Terminal::factory()->create([
            'shop_id' => $shop->id,
        ]);

        $uuid = (string) Str::uuid();

        $response = $this
            ->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization->id))
            ->postJson('/api/v1/caisse/devices', [
                'terminal_id' => $terminal->id,
                'device_uuid' => $uuid,
                'name' => 'Device caisse',
                'platform' => 'android',
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('device.organization_id', $organization->id)
            ->assertJsonPath('device.shop_id', $shop->id)
            ->assertJsonPath('device.terminal_id', $terminal->id);
    }

    public function test_device_creation_is_rejected_for_shop_of_another_organization(): void
    {
        [$user, $organization] = $this->createOrganizationWithSubscription();

        $otherOrganization = Organization::factory()->create();

        $otherShop = Shop::factory()->create([
            'organization_id' => $otherOrganization->id,
        ]);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization->id))
            ->postJson('/api/v1/caisse/devices', [
                'shop_id' => $otherShop->id,
                'device_uuid' => (string) Str::uuid(),
                'name' => 'Device interdit',
            ]);

        $response->assertForbidden();
    }

    public function test_device_creation_is_rejected_for_terminal_of_another_organization(): void
    {
        [$user, $organization] = $this->createOrganizationWithSubscription();

        $otherOrganization = Organization::factory()->create();

        $otherShop = Shop::factory()->create([
            'organization_id' => $otherOrganization->id,
        ]);

        $terminal = Terminal::factory()->create([
            'shop_id' => $otherShop->id,
        ]);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization->id))
            ->postJson('/api/v1/caisse/devices', [
                'terminal_id' => $terminal->id,
                'device_uuid' => (string) Str::uuid(),
                'name' => 'Device interdit',
            ]);

        $response->assertForbidden();
    }

    public function test_terminal_and_shop_must_belong_together(): void
    {
        [$user, $organization] = $this->createOrganizationWithSubscription();

        $shopA = Shop::factory()->create([
            'organization_id' => $organization->id,
        ]);

        $shopB = Shop::factory()->create([
            'organization_id' => $organization->id,
        ]);

        $terminal = Terminal::factory()->create([
            'shop_id' => $shopB->id,
        ]);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization->id))
            ->postJson('/api/v1/caisse/devices', [
                'shop_id' => $shopA->id,
                'terminal_id' => $terminal->id,
                'device_uuid' => (string) Str::uuid(),
            ]);

        $response
            ->assertUnprocessable()
            ->assertJsonPath('code', 'terminal_shop_mismatch');
    }

    public function test_device_uuid_must_be_unique_within_organization(): void
    {
        [$user, $organization] = $this->createOrganizationWithSubscription();

        $uuid = (string) Str::uuid();

        Device::factory()->create([
            'organization_id' => $organization->id,
            'device_uuid' => $uuid,
        ]);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization->id))
            ->postJson('/api/v1/caisse/devices', [
                'device_uuid' => $uuid,
                'name' => 'Duplicate device',
            ]);

        $response->assertUnprocessable();
    }

    public function test_same_device_uuid_is_allowed_in_another_organization(): void
    {
        [$user, $organization] = $this->createOrganizationWithSubscription();

        $otherOrganization = Organization::factory()->create();

        $uuid = (string) Str::uuid();

        Device::factory()->create([
            'organization_id' => $otherOrganization->id,
            'device_uuid' => $uuid,
        ]);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization->id))
            ->postJson('/api/v1/caisse/devices', [
                'device_uuid' => $uuid,
                'name' => 'Device organisation courante',
            ]);

        $response->assertCreated();
    }

    public function test_device_from_another_organization_is_rejected(): void
    {
        [$user, $organization] = $this->createOrganizationWithSubscription();

        $otherOrganization = Organization::factory()->create();

        $device = Device::factory()->create([
            'organization_id' => $otherOrganization->id,
        ]);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization->id))
            ->getJson("/api/v1/caisse/devices/{$device->id}");

        $response->assertForbidden();
    }

    public function test_user_can_update_device_in_current_organization(): void
    {
        [$user, $organization] = $this->createOrganizationWithSubscription();

        $shop = Shop::factory()->create([
            'organization_id' => $organization->id,
        ]);

        $device = Device::factory()->create([
            'organization_id' => $organization->id,
            'shop_id' => $shop->id,
            'name' => 'Ancien device',
        ]);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization->id))
            ->patchJson("/api/v1/caisse/devices/{$device->id}", [
                'name' => 'Nouveau device',
                'status' => 'inactive',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('device.name', 'Nouveau device')
            ->assertJsonPath('device.status', 'inactive');
    }

    public function test_device_update_from_another_organization_is_rejected(): void
    {
        [$user, $organization] = $this->createOrganizationWithSubscription();

        $otherOrganization = Organization::factory()->create();

        $device = Device::factory()->create([
            'organization_id' => $otherOrganization->id,
            'name' => 'Device externe',
        ]);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization->id))
            ->patchJson("/api/v1/caisse/devices/{$device->id}", [
                'name' => 'Modification interdite',
            ]);

        $response->assertForbidden();

        $this->assertDatabaseHas('devices', [
            'id' => $device->id,
            'name' => 'Device externe',
        ]);
    }

    public function test_device_can_be_moved_between_shops_of_same_organization(): void
    {
        [$user, $organization] = $this->createOrganizationWithSubscription();

        $shopA = Shop::factory()->create([
            'organization_id' => $organization->id,
        ]);

        $shopB = Shop::factory()->create([
            'organization_id' => $organization->id,
        ]);

        $device = Device::factory()->create([
            'organization_id' => $organization->id,
            'shop_id' => $shopA->id,
            'terminal_id' => null,
        ]);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization->id))
            ->patchJson("/api/v1/caisse/devices/{$device->id}", [
                'shop_id' => $shopB->id,
                'terminal_id' => null,
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('device.shop_id', $shopB->id);
    }

    public function test_device_cannot_be_assigned_to_terminal_from_another_shop(): void
    {
        [$user, $organization] = $this->createOrganizationWithSubscription();

        $shopA = Shop::factory()->create([
            'organization_id' => $organization->id,
        ]);

        $shopB = Shop::factory()->create([
            'organization_id' => $organization->id,
        ]);

        $terminalB = Terminal::factory()->create([
            'shop_id' => $shopB->id,
        ]);

        $device = Device::factory()->create([
            'organization_id' => $organization->id,
            'shop_id' => $shopA->id,
            'terminal_id' => null,
        ]);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization->id))
            ->patchJson("/api/v1/caisse/devices/{$device->id}", [
                'terminal_id' => $terminalB->id,
            ]);

        $response
            ->assertUnprocessable()
            ->assertJsonPath('code', 'terminal_shop_mismatch');
    }
}