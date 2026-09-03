<?php

namespace Tests\Feature\Caisse;

use App\Models\Caisse\CashSession;
use App\Models\Caisse\Category;
use App\Models\Caisse\Product;
use App\Models\Caisse\Shop;
use App\Models\Caisse\StockLocation;
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

class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);
    }

    private function organizationWithAccess(?int $maxProducts = null): Organization
    {
        $organization = Organization::factory()->create();
        $plan = Plan::factory()->withCaisseEntitlement()->create([
            'max_products' => $maxProducts,
        ]);

        Subscription::factory()->create([
            'organization_id' => $organization->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now()->subMinute(),
            'ends_at' => now()->addMonth(),
        ]);

        return $organization;
    }

    private function userWithRole(Organization $organization, string $roleSlug): User
    {
        $user = User::factory()->create();
        $organization->users()->attach($user->id, ['role' => 'member']);

        $role = Role::query()->where('slug', $roleSlug)->firstOrFail();
        $user->organizationRoleAssignments()->create([
            'organization_id' => $organization->id,
            'role_id' => $role->id,
        ]);

        return $user;
    }

    private function owner(Organization $organization): User
    {
        $user = User::factory()->create();
        $organization->users()->attach($user->id, ['role' => 'owner']);

        return $user;
    }

    private function headers(Organization $organization): array
    {
        return ['X-Organization-Id' => (string) $organization->id];
    }

    private function productPayload(Shop $shop, array $overrides = []): array
    {
        return array_replace([
            'shop_id' => $shop->id,
            'name' => 'Café Touba',
            'sku' => 'CT-' . fake()->unique()->numerify('#####'),
            'barcode' => fake()->unique()->numerify('###########'),
            'unit' => 'unit',
            'purchase_price' => 500,
            'sale_price' => 1000,
            'tax_rate' => 0,
        ], $overrides);
    }

    public function test_guest_is_rejected(): void
    {
        $this->getJson('/api/v1/caisse/products')->assertUnauthorized();
    }

    public function test_user_without_organization_context_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/caisse/products')
            ->assertForbidden();
    }

    public function test_subscription_and_entitlement_are_required(): void
    {
        $organization = Organization::factory()->create();
        $owner = $this->owner($organization);

        $this->actingAs($owner, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->getJson('/api/v1/caisse/products')
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
            ->getJson('/api/v1/caisse/products')
            ->assertForbidden();
    }

    public function test_products_permissions_distinguish_view_and_manage(): void
    {
        $organization = $this->organizationWithAccess();
        $shop = Shop::factory()->create(['organization_id' => $organization->id]);
        $cashier = $this->userWithRole($organization, 'cashier');
        $member = User::factory()->create();
        $organization->users()->attach($member->id, ['role' => 'member']);

        $this->actingAs($member, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->getJson('/api/v1/caisse/products')
            ->assertForbidden();

        $this->actingAs($cashier, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->getJson('/api/v1/caisse/products')
            ->assertOk();

        $this->actingAs($cashier, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->postJson('/api/v1/caisse/products', $this->productPayload($shop))
            ->assertForbidden();
    }

    public function test_legacy_owner_can_manage_products(): void
    {
        $organization = $this->organizationWithAccess();
        $owner = $this->owner($organization);
        $shop = Shop::factory()->create(['organization_id' => $organization->id]);

        $this->actingAs($owner, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->postJson('/api/v1/caisse/products', $this->productPayload($shop))
            ->assertCreated();
    }

    public function test_admin_can_create_show_update_and_deactivate_a_product(): void
    {
        $organization = $this->organizationWithAccess();
        $shop = Shop::factory()->create(['organization_id' => $organization->id]);
        $admin = $this->userWithRole($organization, 'admin');

        $create = $this->actingAs($admin, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->postJson('/api/v1/caisse/products', $this->productPayload($shop));

        $create->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('product.shop_id', $shop->id);

        $productId = $create->json('product.id');

        $this->actingAs($admin, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->getJson("/api/v1/caisse/products/{$productId}")
            ->assertOk()
            ->assertJsonPath('product.id', $productId);

        $this->actingAs($admin, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->patchJson("/api/v1/caisse/products/{$productId}", [
                'name' => 'Café Touba Premium',
                'status' => 'inactive',
            ])
            ->assertOk()
            ->assertJsonPath('product.status', 'inactive');

        $this->assertDatabaseHas('products', [
            'id' => $productId,
            'name' => 'Café Touba Premium',
            'status' => 'inactive',
        ]);
    }

    public function test_list_search_and_tenant_isolation_are_scoped_to_the_current_organization(): void
    {
        $organization = $this->organizationWithAccess();
        $otherOrganization = $this->organizationWithAccess();
        $admin = $this->userWithRole($organization, 'admin');
        $shop = Shop::factory()->create(['organization_id' => $organization->id]);
        $otherShop = Shop::factory()->create(['organization_id' => $otherOrganization->id]);

        Product::factory()->create([
            'shop_id' => $shop->id,
            'name' => 'Savon Yessal',
            'sku' => 'SAVON-001',
            'barcode' => '10000000001',
        ]);
        $otherProduct = Product::factory()->create([
            'shop_id' => $otherShop->id,
            'name' => 'Savon Autre',
            'sku' => 'SAVON-002',
            'barcode' => '10000000002',
        ]);

        $this->actingAs($admin, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->getJson('/api/v1/caisse/products?search=Yessal')
            ->assertOk()
            ->assertJsonCount(1, 'products.data')
            ->assertJsonPath('products.data.0.name', 'Savon Yessal');

        $this->actingAs($admin, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->getJson('/api/v1/caisse/products?search=SAVON-001')
            ->assertOk()
            ->assertJsonCount(1, 'products.data')
            ->assertJsonPath('products.data.0.sku', 'SAVON-001');

        $this->actingAs($admin, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->getJson('/api/v1/caisse/products?search=10000000001')
            ->assertOk()
            ->assertJsonCount(1, 'products.data')
            ->assertJsonPath('products.data.0.barcode', '10000000001');

        $this->actingAs($admin, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->getJson("/api/v1/caisse/products/{$otherProduct->id}")
            ->assertForbidden();

        $this->actingAs($admin, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->getJson("/api/v1/caisse/products?shop_id={$otherShop->id}")
            ->assertForbidden();
    }

    public function test_products_cannot_use_or_move_to_a_shop_from_another_organization(): void
    {
        $organization = $this->organizationWithAccess();
        $otherOrganization = $this->organizationWithAccess();
        $admin = $this->userWithRole($organization, 'admin');
        $shop = Shop::factory()->create(['organization_id' => $organization->id]);
        $otherShop = Shop::factory()->create(['organization_id' => $otherOrganization->id]);
        $product = Product::factory()->create(['shop_id' => $shop->id]);
        $otherProduct = Product::factory()->create(['shop_id' => $otherShop->id]);

        $this->actingAs($admin, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->postJson('/api/v1/caisse/products', $this->productPayload($otherShop))
            ->assertForbidden();

        $this->actingAs($admin, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->patchJson("/api/v1/caisse/products/{$product->id}", [
                'shop_id' => $otherShop->id,
            ])
            ->assertForbidden();

        $this->actingAs($admin, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->patchJson("/api/v1/caisse/products/{$otherProduct->id}", [
                'status' => 'inactive',
            ])
            ->assertForbidden();

        $this->assertSame($shop->id, $product->fresh()->shop_id);
    }

    public function test_product_category_must_belong_to_the_selected_shop(): void
    {
        $organization = $this->organizationWithAccess();
        $admin = $this->userWithRole($organization, 'admin');
        $shop = Shop::factory()->create(['organization_id' => $organization->id]);
        $otherShop = Shop::factory()->create(['organization_id' => $organization->id]);
        $category = Category::query()->create([
            'shop_id' => $otherShop->id,
            'name' => 'Épicerie',
            'slug' => 'epicerie',
            'status' => 'active',
        ]);

        $this->actingAs($admin, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->postJson('/api/v1/caisse/products', $this->productPayload($shop, [
                'category_id' => $category->id,
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('category_id');
    }

    public function test_sku_and_barcode_are_unique_per_shop(): void
    {
        $organization = $this->organizationWithAccess();
        $admin = $this->userWithRole($organization, 'admin');
        $shop = Shop::factory()->create(['organization_id' => $organization->id]);
        $otherShop = Shop::factory()->create(['organization_id' => $organization->id]);
        $payload = $this->productPayload($shop, [
            'sku' => 'UNIQUE-001',
            'barcode' => '12345678901',
        ]);

        $this->actingAs($admin, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->postJson('/api/v1/caisse/products', $payload)
            ->assertCreated();

        $this->actingAs($admin, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->postJson('/api/v1/caisse/products', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['sku', 'barcode']);

        $this->actingAs($admin, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->postJson('/api/v1/caisse/products', $this->productPayload($otherShop, [
                'sku' => 'UNIQUE-001',
                'barcode' => '12345678901',
            ]))
            ->assertCreated();
    }

    public function test_product_quota_is_enforced_and_unlimited_plans_allow_creation(): void
    {
        $organization = $this->organizationWithAccess(1);
        $admin = $this->userWithRole($organization, 'admin');
        $shop = Shop::factory()->create(['organization_id' => $organization->id]);
        Product::factory()->create(['shop_id' => $shop->id]);

        $this->actingAs($admin, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->postJson('/api/v1/caisse/products', $this->productPayload($shop))
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Quota de produits atteint.');

        $unlimitedOrganization = $this->organizationWithAccess();
        $unlimitedAdmin = $this->userWithRole($unlimitedOrganization, 'admin');
        $unlimitedShop = Shop::factory()->create([
            'organization_id' => $unlimitedOrganization->id,
        ]);

        $this->actingAs($unlimitedAdmin, 'sanctum')
            ->withHeaders($this->headers($unlimitedOrganization))
            ->postJson('/api/v1/caisse/products', $this->productPayload($unlimitedShop))
            ->assertCreated();

        $this->actingAs($unlimitedAdmin, 'sanctum')
            ->withHeaders($this->headers($unlimitedOrganization))
            ->postJson('/api/v1/caisse/products', $this->productPayload($unlimitedShop))
            ->assertCreated();
    }

    public function test_product_quota_is_isolated_by_organization_and_required_fields_are_validated(): void
    {
        $organization = $this->organizationWithAccess(1);
        $admin = $this->userWithRole($organization, 'admin');
        $shop = Shop::factory()->create(['organization_id' => $organization->id]);
        $otherOrganization = $this->organizationWithAccess(1);
        $otherShop = Shop::factory()->create([
            'organization_id' => $otherOrganization->id,
        ]);
        Product::factory()->create(['shop_id' => $otherShop->id]);

        $this->actingAs($admin, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->postJson('/api/v1/caisse/products', ['shop_id' => $shop->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'sale_price']);

        $this->actingAs($admin, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->postJson('/api/v1/caisse/products', $this->productPayload($shop))
            ->assertCreated();
    }

    public function test_product_created_by_api_can_be_adjusted_in_stock_and_used_in_a_sale(): void
    {
        $organization = $this->organizationWithAccess();
        $admin = $this->userWithRole($organization, 'admin');
        $shop = Shop::factory()->create(['organization_id' => $organization->id]);
        $location = StockLocation::factory()->create(['shop_id' => $shop->id]);
        $terminal = Terminal::factory()->create(['shop_id' => $shop->id]);
        $session = CashSession::factory()->create([
            'organization_id' => $organization->id,
            'shop_id' => $shop->id,
            'terminal_id' => $terminal->id,
            'opened_by' => $admin->id,
            'status' => 'open',
        ]);

        $product = $this->actingAs($admin, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->postJson('/api/v1/caisse/products', $this->productPayload($shop, [
                'name' => 'Produit API',
                'sku' => 'API-001',
                'barcode' => '99999999999',
                'sale_price' => 2500,
            ]))
            ->assertCreated()
            ->json('product');

        $this->actingAs($admin, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->postJson('/api/v1/caisse/stock/adjustments', [
                'stock_location_id' => $location->id,
                'product_id' => $product['id'],
                'quantity' => 5,
            ])
            ->assertCreated();

        $this->actingAs($admin, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->postJson('/api/v1/caisse/sales', [
                'shop_id' => $shop->id,
                'terminal_id' => $terminal->id,
                'cash_session_id' => $session->id,
                'local_uuid' => (string) Str::uuid(),
                'receipt_number' => 'PRD-' . fake()->unique()->numerify('#####'),
                'currency' => 'XOF',
                'lines' => [[
                    'product_id' => $product['id'],
                    'quantity' => 1,
                    'unit_price' => 2500,
                ]],
            ])
            ->assertCreated()
            ->assertJsonPath('sale.lines.0.product_name_snapshot', 'Produit API');
    }
}
