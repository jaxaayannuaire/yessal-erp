<?php

namespace Tests\Feature\Caisse;

use App\Models\Caisse\CashSession;
use App\Models\Caisse\Category;
use App\Models\Caisse\Product;
use App\Models\Caisse\ProductVariant;
use App\Models\Caisse\Shop;
use App\Models\Caisse\StockLevel;
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

class CategoryVariantApiTest extends TestCase
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

    private function categoryPayload(Shop $shop, array $overrides = []): array
    {
        return array_replace([
            'shop_id' => $shop->id,
            'name' => 'Boissons',
            'slug' => 'boissons-' . fake()->unique()->numerify('#####'),
            'status' => 'active',
        ], $overrides);
    }

    private function variantPayload(array $overrides = []): array
    {
        return array_replace([
            'name' => 'Format 50 cl',
            'sku' => 'VAR-' . fake()->unique()->numerify('#####'),
            'barcode' => fake()->unique()->numerify('700000000001'),
            'purchase_price' => 500,
            'sale_price' => 1000,
            'attributes' => ['volume' => '50 cl'],
        ], $overrides);
    }

    public function test_category_routes_require_the_existing_product_permissions(): void
    {
        $organization = $this->organizationWithAccess();
        $cashier = $this->userWithRole($organization, 'cashier');
        $member = User::factory()->create();
        $organization->users()->attach($member->id, ['role' => 'member']);
        $shop = Shop::factory()->create(['organization_id' => $organization->id]);

        $this->getJson('/api/v1/caisse/categories')->assertUnauthorized();

        $this->actingAs($member, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->getJson('/api/v1/caisse/categories')
            ->assertForbidden();

        $this->actingAs($cashier, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->getJson('/api/v1/caisse/categories')
            ->assertOk();

        $this->actingAs($cashier, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->postJson('/api/v1/caisse/categories', $this->categoryPayload($shop))
            ->assertForbidden();
    }

    public function test_categories_can_be_created_listed_shown_and_updated_per_organization(): void
    {
        $organization = $this->organizationWithAccess();
        $owner = $this->owner($organization);
        $shop = Shop::factory()->create(['organization_id' => $organization->id]);

        $category = $this->actingAs($owner, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->postJson('/api/v1/caisse/categories', $this->categoryPayload($shop))
            ->assertCreated()
            ->assertJsonPath('category.shop_id', $shop->id)
            ->json('category');

        $this->actingAs($owner, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->getJson('/api/v1/caisse/categories?shop_id=' . $shop->id)
            ->assertOk()
            ->assertJsonCount(1, 'categories.data')
            ->assertJsonPath('categories.data.0.id', $category['id']);

        $this->actingAs($owner, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->getJson("/api/v1/caisse/categories/{$category['id']}")
            ->assertOk();

        $this->actingAs($owner, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->patchJson("/api/v1/caisse/categories/{$category['id']}", [
                'name' => 'Boissons fraîches',
                'status' => 'inactive',
            ])
            ->assertOk()
            ->assertJsonPath('category.status', 'inactive');
    }

    public function test_categories_are_tenant_scoped_and_reject_a_foreign_shop(): void
    {
        $organization = $this->organizationWithAccess();
        $otherOrganization = $this->organizationWithAccess();
        $owner = $this->owner($organization);
        $shop = Shop::factory()->create(['organization_id' => $organization->id]);
        $otherShop = Shop::factory()->create([
            'organization_id' => $otherOrganization->id,
        ]);
        $otherCategory = Category::factory()->create(['shop_id' => $otherShop->id]);

        $this->actingAs($owner, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->postJson('/api/v1/caisse/categories', $this->categoryPayload($otherShop))
            ->assertForbidden();

        $this->actingAs($owner, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->getJson("/api/v1/caisse/categories/{$otherCategory->id}")
            ->assertForbidden();

        $this->actingAs($owner, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->patchJson("/api/v1/caisse/categories/{$otherCategory->id}", [
                'name' => 'Interdite',
            ])
            ->assertForbidden();

        $this->assertSame($shop->organization_id, $organization->id);
    }

    public function test_variants_can_be_created_listed_shown_and_updated(): void
    {
        $organization = $this->organizationWithAccess();
        $owner = $this->owner($organization);
        $shop = Shop::factory()->create(['organization_id' => $organization->id]);
        $product = Product::factory()->create(['shop_id' => $shop->id]);

        $variant = $this->actingAs($owner, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->postJson(
                "/api/v1/caisse/products/{$product->id}/variants",
                $this->variantPayload()
            )
            ->assertCreated()
            ->assertJsonPath('variant.product_id', $product->id)
            ->json('variant');

        $this->actingAs($owner, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->getJson("/api/v1/caisse/products/{$product->id}/variants")
            ->assertOk()
            ->assertJsonCount(1, 'variants.data')
            ->assertJsonPath('variants.data.0.id', $variant['id']);

        $this->actingAs($owner, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->getJson("/api/v1/caisse/products/{$product->id}/variants/{$variant['id']}")
            ->assertOk();

        $this->actingAs($owner, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->patchJson("/api/v1/caisse/products/{$product->id}/variants/{$variant['id']}", [
                'sale_price' => 1250,
                'attributes' => ['volume' => '75 cl'],
            ])
            ->assertOk()
            ->assertJsonPath('variant.sale_price', 1250)
            ->assertJsonPath('variant.attributes.volume', '75 cl');
    }

    public function test_variants_are_scoped_to_the_parent_product_and_organization(): void
    {
        $organization = $this->organizationWithAccess();
        $otherOrganization = $this->organizationWithAccess();
        $owner = $this->owner($organization);
        $shop = Shop::factory()->create(['organization_id' => $organization->id]);
        $otherShop = Shop::factory()->create([
            'organization_id' => $otherOrganization->id,
        ]);
        $product = Product::factory()->create(['shop_id' => $shop->id]);
        $anotherProduct = Product::factory()->create(['shop_id' => $shop->id]);
        $variant = ProductVariant::factory()->create(['product_id' => $anotherProduct->id]);
        $otherProduct = Product::factory()->create(['shop_id' => $otherShop->id]);

        $this->actingAs($owner, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->getJson("/api/v1/caisse/products/{$product->id}/variants/{$variant->id}")
            ->assertForbidden();

        $this->actingAs($owner, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->postJson(
                "/api/v1/caisse/products/{$otherProduct->id}/variants",
                $this->variantPayload()
            )
            ->assertForbidden();
    }

    public function test_variant_sku_and_barcode_are_validated_per_product(): void
    {
        $organization = $this->organizationWithAccess();
        $owner = $this->owner($organization);
        $shop = Shop::factory()->create(['organization_id' => $organization->id]);
        $product = Product::factory()->create(['shop_id' => $shop->id]);
        $otherProduct = Product::factory()->create(['shop_id' => $shop->id]);
        $payload = $this->variantPayload([
            'sku' => 'FORMAT-UNIQUE',
            'barcode' => '711111111111',
        ]);

        $this->actingAs($owner, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->postJson("/api/v1/caisse/products/{$product->id}/variants", $payload)
            ->assertCreated();

        $this->actingAs($owner, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->postJson("/api/v1/caisse/products/{$product->id}/variants", $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['sku', 'barcode']);

        $this->actingAs($owner, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->postJson("/api/v1/caisse/products/{$otherProduct->id}/variants", $payload)
            ->assertCreated();
    }

    public function test_variant_created_by_api_supports_stock_sale_finalization_and_cancellation(): void
    {
        $organization = $this->organizationWithAccess();
        $owner = $this->owner($organization);
        $shop = Shop::factory()->create(['organization_id' => $organization->id]);
        $product = Product::factory()->create(['shop_id' => $shop->id]);
        $location = StockLocation::factory()->create([
            'organization_id' => $organization->id,
            'shop_id' => $shop->id,
            'status' => 'active',
        ]);
        $terminal = Terminal::factory()->create(['shop_id' => $shop->id]);
        $session = CashSession::factory()->create([
            'organization_id' => $organization->id,
            'shop_id' => $shop->id,
            'terminal_id' => $terminal->id,
            'opened_by' => $owner->id,
            'status' => 'open',
        ]);

        $variant = $this->actingAs($owner, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->postJson(
                "/api/v1/caisse/products/{$product->id}/variants",
                $this->variantPayload(['sale_price' => 2000])
            )
            ->assertCreated()
            ->json('variant');

        $this->actingAs($owner, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->postJson('/api/v1/caisse/stock/adjustments', [
                'stock_location_id' => $location->id,
                'product_variant_id' => $variant['id'],
                'quantity' => 3,
            ])
            ->assertCreated();

        $sale = $this->actingAs($owner, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->postJson('/api/v1/caisse/sales', [
                'shop_id' => $shop->id,
                'terminal_id' => $terminal->id,
                'cash_session_id' => $session->id,
                'local_uuid' => (string) Str::uuid(),
                'receipt_number' => 'VAR-' . fake()->unique()->numerify('#####'),
                'currency' => 'XOF',
                'lines' => [[
                    'product_variant_id' => $variant['id'],
                    'quantity' => 2,
                    'unit_price' => 2000,
                ]],
            ])
            ->assertCreated()
            ->json('sale');

        $this->actingAs($owner, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->postJson("/api/v1/caisse/sales/{$sale['id']}/payments/cash", [
                'amount' => 4000,
            ])
            ->assertCreated();

        $this->actingAs($owner, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->postJson("/api/v1/caisse/sales/{$sale['id']}/finalize")
            ->assertOk()
            ->assertJsonPath('sale.status', 'finalized');

        $this->assertDatabaseHas('stock_levels', [
            'stock_location_id' => $location->id,
            'product_variant_id' => $variant['id'],
            'quantity' => 1,
        ]);

        $this->actingAs($owner, 'sanctum')
            ->withHeaders($this->headers($organization))
            ->postJson("/api/v1/caisse/sales/{$sale['id']}/cancel")
            ->assertOk()
            ->assertJsonPath('sale.status', 'cancelled');

        $this->assertDatabaseHas('stock_levels', [
            'stock_location_id' => $location->id,
            'product_variant_id' => $variant['id'],
            'quantity' => 3,
        ]);
        $this->assertDatabaseHas('stock_movements', [
            'product_variant_id' => $variant['id'],
            'type' => 'sale_cancel_in',
        ]);
    }
}
