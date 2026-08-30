<?php

namespace Tests\Feature\Caisse;

use App\Models\Caisse\Shop;
use App\Models\Caisse\Terminal;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TerminalApiTest extends TestCase
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

    public function test_user_can_list_terminals_of_current_organization(): void
    {
        [$user, $organization] = $this->createOrganizationWithSubscription();

        $shop = Shop::factory()->create([
            'organization_id' => $organization->id,
        ]);

        Terminal::factory()->create([
            'shop_id' => $shop->id,
            'name' => 'Caisse principale',
            'code' => 'POS-001',
        ]);

        Terminal::factory()->create([
            'shop_id' => $shop->id,
            'name' => 'Caisse secondaire',
            'code' => 'POS-002',
        ]);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->withHeader('X-Organization-Id', $organization->id)
            ->getJson('/api/v1/caisse/terminals');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'terminals');
    }

    public function test_user_can_create_terminal_in_current_organization_shop(): void
    {
        [$user, $organization] = $this->createOrganizationWithSubscription();

        $shop = Shop::factory()->create([
            'organization_id' => $organization->id,
        ]);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->withHeader('X-Organization-Id', $organization->id)
            ->postJson('/api/v1/caisse/terminals', [
                'shop_id' => $shop->id,
                'name' => 'Caisse principale',
                'code' => 'POS-001',
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('terminal.shop_id', $shop->id);

        $this->assertDatabaseHas('terminals', [
            'shop_id' => $shop->id,
            'code' => 'POS-001',
        ]);
    }

    public function test_terminal_creation_is_rejected_for_shop_of_another_organization(): void
    {
        [$user, $organization] = $this->createOrganizationWithSubscription();

        $otherOrganization = Organization::factory()->create();

        $otherShop = Shop::factory()->create([
            'organization_id' => $otherOrganization->id,
        ]);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->withHeader('X-Organization-Id', $organization->id)
            ->postJson('/api/v1/caisse/terminals', [
                'shop_id' => $otherShop->id,
                'name' => 'Terminal interdit',
                'code' => 'POS-999',
            ]);

        $response->assertForbidden();
    }

    public function test_terminal_from_another_organization_is_rejected(): void
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
            ->withHeader('X-Organization-Id', $organization->id)
            ->getJson("/api/v1/caisse/terminals/{$terminal->id}");

        $response->assertForbidden();
    }

    public function test_terminal_code_must_be_unique_within_same_shop(): void
    {
        [$user, $organization] = $this->createOrganizationWithSubscription();

        $shop = Shop::factory()->create([
            'organization_id' => $organization->id,
        ]);

        Terminal::factory()->create([
            'shop_id' => $shop->id,
            'code' => 'POS-001',
        ]);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->withHeader('X-Organization-Id', $organization->id)
            ->postJson('/api/v1/caisse/terminals', [
                'shop_id' => $shop->id,
                'name' => 'Deuxième terminal',
                'code' => 'POS-001',
            ]);

        $response
            ->assertUnprocessable()
            ->assertJsonPath('code', 'terminal_code_taken');
    }

    public function test_same_terminal_code_is_allowed_in_another_shop(): void
    {
        [$user, $organization] = $this->createOrganizationWithSubscription();

        $shopA = Shop::factory()->create([
            'organization_id' => $organization->id,
            'code' => 'SHOP-A',
        ]);

        $shopB = Shop::factory()->create([
            'organization_id' => $organization->id,
            'code' => 'SHOP-B',
        ]);

        Terminal::factory()->create([
            'shop_id' => $shopA->id,
            'code' => 'POS-001',
        ]);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->withHeader('X-Organization-Id', $organization->id)
            ->postJson('/api/v1/caisse/terminals', [
                'shop_id' => $shopB->id,
                'name' => 'Terminal boutique B',
                'code' => 'POS-001',
            ]);

        $response->assertCreated();
    }

    public function test_user_can_update_terminal_in_current_organization(): void
    {
        [$user, $organization] = $this->createOrganizationWithSubscription();

        $shop = Shop::factory()->create([
            'organization_id' => $organization->id,
        ]);

        $terminal = Terminal::factory()->create([
            'shop_id' => $shop->id,
            'name' => 'Ancien nom',
            'code' => 'POS-001',
        ]);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->withHeader('X-Organization-Id', $organization->id)
            ->patchJson("/api/v1/caisse/terminals/{$terminal->id}", [
                'name' => 'Nouveau nom',
                'status' => 'inactive',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('terminal.name', 'Nouveau nom')
            ->assertJsonPath('terminal.status', 'inactive');
    }

    public function test_terminal_cannot_be_moved_to_shop_of_another_organization(): void
    {
        [$user, $organization] = $this->createOrganizationWithSubscription();

        $shop = Shop::factory()->create([
            'organization_id' => $organization->id,
        ]);

        $otherOrganization = Organization::factory()->create();

        $otherShop = Shop::factory()->create([
            'organization_id' => $otherOrganization->id,
        ]);

        $terminal = Terminal::factory()->create([
            'shop_id' => $shop->id,
        ]);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->withHeader('X-Organization-Id', $organization->id)
            ->patchJson("/api/v1/caisse/terminals/{$terminal->id}", [
                'shop_id' => $otherShop->id,
            ]);

        $response->assertForbidden();

        $this->assertDatabaseHas('terminals', [
            'id' => $terminal->id,
            'shop_id' => $shop->id,
        ]);
    }

    public function test_terminal_cannot_be_updated_from_another_organization(): void
    {
        [$user, $organization] = $this->createOrganizationWithSubscription();

        $otherOrganization = Organization::factory()->create();

        $otherShop = Shop::factory()->create([
            'organization_id' => $otherOrganization->id,
        ]);

        $terminal = Terminal::factory()->create([
            'shop_id' => $otherShop->id,
            'name' => 'Terminal externe',
        ]);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->withHeader('X-Organization-Id', $organization->id)
            ->patchJson("/api/v1/caisse/terminals/{$terminal->id}", [
                'name' => 'Modification interdite',
            ]);

        $response->assertForbidden();

        $this->assertDatabaseHas('terminals', [
            'id' => $terminal->id,
            'name' => 'Terminal externe',
        ]);
    }
}