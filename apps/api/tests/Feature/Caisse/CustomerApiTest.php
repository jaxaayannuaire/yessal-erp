<?php

namespace Tests\Feature\Caisse;

use App\Models\Caisse\CashSession;
use App\Models\Caisse\Customer;
use App\Models\Caisse\Shop;
use App\Models\Caisse\Terminal;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CustomerApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);
    }

    private function organizationWithAccess(): Organization
    {
        $organization = Organization::factory()->create();
        $plan = Plan::factory()->withCaisseEntitlement()->create();

        Subscription::factory()->create([
            'organization_id' => $organization->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now()->subMinute(),
            'ends_at' => now()->addMonth(),
        ]);

        return $organization;
    }

    private function owner(Organization $organization): User
    {
        $user = User::factory()->create();
        $organization->users()->attach($user->id, ['role' => 'owner']);

        return $user;
    }

    private function userWithRole(Organization $organization, string $roleSlug): User
    {
        $user = User::factory()->create();
        $organization->users()->attach($user->id, ['role' => 'member']);

        $user->organizationRoleAssignments()->create([
            'organization_id' => $organization->id,
            'role_id' => Role::query()->where('slug', $roleSlug)->firstOrFail()->id,
        ]);

        return $user;
    }

    private function headers(Organization $organization): array
    {
        return ['X-Organization-Id' => (string) $organization->id];
    }

    private function customerPayload(Shop $shop, array $overrides = []): array
    {
        return array_replace([
            'shop_id' => $shop->id,
            'name' => 'Awa Ndiaye',
            'phone' => '+221770000001',
            'email' => 'awa@example.test',
            'address' => 'Dakar',
            'status' => 'active',
        ], $overrides);
    }

    private function salePayload(
        Shop $shop,
        Terminal $terminal,
        CashSession $session,
        array $overrides = []
    ): array {
        return array_replace([
            'shop_id' => $shop->id,
            'terminal_id' => $terminal->id,
            'cash_session_id' => $session->id,
            'local_uuid' => (string) Str::uuid(),
            'receipt_number' => 'CLI-' . fake()->unique()->numerify('#####'),
            'currency' => 'XOF',
            'lines' => [[
                'product_name_snapshot' => 'Vente comptoir',
                'quantity' => 1,
                'unit_price' => 2000,
            ]],
        ], $overrides);
    }

    public function test_guest_context_subscription_and_entitlement_are_required(): void
    {
        $this->getJson('/api/v1/caisse/customers')->assertUnauthorized();

        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/caisse/customers')
            ->assertForbidden();

        $organization = Organization::factory()->create();
        $owner = $this->owner($organization);

        $this->actingAs($owner, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->getJson('/api/v1/caisse/customers')
            ->assertForbidden();

        $plan = Plan::factory()->create();
        Subscription::factory()->create([
            'organization_id' => $organization->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now()->subMinute(),
            'ends_at' => now()->addMonth(),
        ]);

        $this->actingAs($owner, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->getJson('/api/v1/caisse/customers')
            ->assertForbidden();
    }

    public function test_customers_require_the_existing_view_and_manage_permissions(): void
    {
        $organization = $this->organizationWithAccess();
        $shop = Shop::factory()->create(['organization_id' => $organization->id]);
        $cashier = $this->userWithRole($organization, 'cashier');
        $manager = $this->userWithRole($organization, 'manager');
        $member = User::factory()->create();
        $organization->users()->attach($member->id, ['role' => 'member']);

        $this->actingAs($member, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->getJson('/api/v1/caisse/customers')
            ->assertForbidden();

        $this->actingAs($cashier, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->getJson('/api/v1/caisse/customers')
            ->assertOk();

        $this->actingAs($cashier, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->postJson('/api/v1/caisse/customers', $this->customerPayload($shop))
            ->assertForbidden();

        $this->actingAs($manager, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->postJson('/api/v1/caisse/customers', $this->customerPayload($shop))
            ->assertCreated();
    }

    public function test_owner_can_create_list_show_update_and_search_customers(): void
    {
        $organization = $this->organizationWithAccess();
        $owner = $this->owner($organization);
        $shop = Shop::factory()->create(['organization_id' => $organization->id]);

        $customer = $this->actingAs($owner, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->postJson('/api/v1/caisse/customers', $this->customerPayload($shop))
            ->assertCreated()
            ->assertJsonPath('customer.shop_id', $shop->id)
            ->json('customer');

        foreach (['Awa', '+221770000001', 'awa@example.test'] as $search) {
            $this->actingAs($owner, 'sanctum')
                ->withHeaders($this->headers($organization))
                ->getJson('/api/v1/caisse/customers?search=' . urlencode($search))
                ->assertOk()
                ->assertJsonCount(1, 'customers.data')
                ->assertJsonPath('customers.data.0.id', $customer['id']);
        }

        $this->actingAs($owner, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->getJson("/api/v1/caisse/customers/{$customer['id']}")
            ->assertOk();

        $this->actingAs($owner, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->patchJson("/api/v1/caisse/customers/{$customer['id']}", [
                'phone' => '+221770000002',
                'email' => 'awa.ndiaye@example.test',
            ])
            ->assertOk()
            ->assertJsonPath('customer.phone', '+221770000002');
    }

    public function test_customers_are_tenant_scoped_and_foreign_shops_are_rejected(): void
    {
        $organization = $this->organizationWithAccess();
        $otherOrganization = $this->organizationWithAccess();
        $owner = $this->owner($organization);
        $shop = Shop::factory()->create(['organization_id' => $organization->id]);
        $otherShop = Shop::factory()->create([
            'organization_id' => $otherOrganization->id,
        ]);
        $otherCustomer = Customer::factory()->create(['shop_id' => $otherShop->id]);

        $this->actingAs($owner, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->getJson('/api/v1/caisse/customers')
            ->assertOk()
            ->assertJsonCount(0, 'customers.data');

        $this->actingAs($owner, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->postJson('/api/v1/caisse/customers', $this->customerPayload($otherShop))
            ->assertForbidden();

        $this->actingAs($owner, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->getJson("/api/v1/caisse/customers/{$otherCustomer->id}")
            ->assertForbidden();

        $this->actingAs($owner, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->patchJson("/api/v1/caisse/customers/{$otherCustomer->id}", [
                'name' => 'Inaccessible',
            ])
            ->assertForbidden();

        $this->assertSame($shop->organization_id, $organization->id);
    }

    public function test_sales_accept_an_active_same_tenant_customer_but_keep_counter_sales_optional(): void
    {
        $organization = $this->organizationWithAccess();
        $owner = $this->owner($organization);
        $shop = Shop::factory()->create(['organization_id' => $organization->id]);
        $terminal = Terminal::factory()->create(['shop_id' => $shop->id]);
        $session = CashSession::factory()->create([
            'organization_id' => $organization->id,
            'shop_id' => $shop->id,
            'terminal_id' => $terminal->id,
            'opened_by' => $owner->id,
            'status' => 'open',
        ]);
        $customer = Customer::factory()->create(['shop_id' => $shop->id]);

        $counterSale = $this->actingAs($owner, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->postJson('/api/v1/caisse/sales', $this->salePayload(
                $shop,
                $terminal,
                $session
            ))
            ->assertCreated()
            ->json('sale');

        $this->assertNull($counterSale['customer_id']);

        $customerSale = $this->actingAs($owner, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->postJson('/api/v1/caisse/sales', $this->salePayload(
                $shop,
                $terminal,
                $session,
                ['customer_id' => $customer->id]
            ))
            ->assertCreated()
            ->assertJsonPath('sale.customer_id', $customer->id)
            ->json('sale');

        $this->actingAs($owner, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->postJson("/api/v1/caisse/sales/{$customerSale['id']}/payments/cash", [
                'amount' => 2000,
            ])
            ->assertCreated();

        $this->actingAs($owner, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->postJson("/api/v1/caisse/sales/{$customerSale['id']}/finalize")
            ->assertOk()
            ->assertJsonPath('sale.status', 'finalized');

        $this->actingAs($owner, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->getJson("/api/v1/caisse/customers/{$customer->id}/sales")
            ->assertOk()
            ->assertJsonCount(1, 'sales.data')
            ->assertJsonPath('sales.data.0.id', $customerSale['id'])
            ->assertJsonPath('sales.data.0.status', 'finalized');

        $this->actingAs($owner, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->postJson("/api/v1/caisse/sales/{$customerSale['id']}/cancel")
            ->assertOk();

        $this->actingAs($owner, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->getJson("/api/v1/caisse/customers/{$customer->id}/sales")
            ->assertOk()
            ->assertJsonPath('sales.data.0.status', 'cancelled');
    }

    public function test_sales_reject_inactive_or_foreign_customers(): void
    {
        $organization = $this->organizationWithAccess();
        $otherOrganization = $this->organizationWithAccess();
        $owner = $this->owner($organization);
        $shop = Shop::factory()->create(['organization_id' => $organization->id]);
        $terminal = Terminal::factory()->create(['shop_id' => $shop->id]);
        $session = CashSession::factory()->create([
            'organization_id' => $organization->id,
            'shop_id' => $shop->id,
            'terminal_id' => $terminal->id,
            'opened_by' => $owner->id,
            'status' => 'open',
        ]);
        $inactiveCustomer = Customer::factory()->create([
            'shop_id' => $shop->id,
            'status' => 'inactive',
        ]);
        $otherShop = Shop::factory()->create([
            'organization_id' => $otherOrganization->id,
        ]);
        $otherCustomer = Customer::factory()->create(['shop_id' => $otherShop->id]);

        foreach ([$inactiveCustomer->id, $otherCustomer->id] as $customerId) {
            $this->actingAs($owner, 'sanctum')
                ->withHeaders($this->headers($organization))
                ->postJson('/api/v1/caisse/sales', $this->salePayload(
                    $shop,
                    $terminal,
                    $session,
                    ['customer_id' => $customerId]
                ))
                ->assertUnprocessable()
                ->assertJsonValidationErrors('customer_id');
        }

        $this->actingAs($owner, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->getJson("/api/v1/caisse/customers/{$otherCustomer->id}/sales")
            ->assertForbidden();
    }
}
