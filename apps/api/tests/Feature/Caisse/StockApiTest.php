<?php

namespace Tests\Feature\Caisse;

use App\Models\Caisse\Product;
use App\Models\Caisse\ProductVariant;
use App\Models\Caisse\StockLevel;
use App\Models\Caisse\StockLocation;
use App\Models\Caisse\StockMovement;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockApiTest extends TestCase
{
    use RefreshDatabase;

    private function context(): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();

        $organization->users()->attach($user->id, ['role' => 'owner']);

        $plan = Plan::factory()->create(['is_active' => true]);

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
        return ['X-Organization-Id' => $organizationId];
    }

    private function location(Organization $organization): StockLocation
	{
		return StockLocation::factory()->create([
			'shop_id' => \App\Models\Caisse\Shop::factory()->create([
				'organization_id' => $organization->id,
			])->id,
		]);
	}

    private function product(
        Organization $organization,
        StockLocation $location
    ): Product {
        return Product::factory()->create([
            'shop_id' => $location->shop_id,
        ]);
    }

    public function test_user_can_list_organization_stock(): void
    {
        [$user, $organization] = $this->context();

        $location = $this->location($organization);
        $product = $this->product($organization, $location);

        StockLevel::create([
            'stock_location_id' => $location->id,
            'product_id' => $product->id,
            'product_variant_id' => null,
            'quantity' => 25,
            'reserved_quantity' => 0,
        ]);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization->id))
            ->getJson('/api/v1/caisse/stock');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'stock.data')
            ->assertJsonPath('stock.data.0.quantity', '25.000');
    }

    public function test_user_can_create_stock_with_positive_adjustment(): void
    {
        [$user, $organization] = $this->context();

        $location = $this->location($organization);
        $product = $this->product($organization, $location);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization->id))
            ->postJson('/api/v1/caisse/stock/adjustments', [
                'stock_location_id' => $location->id,
                'product_id' => $product->id,
                'quantity' => 10,
                'reason' => 'Réception initiale',
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('stock.quantity', '10.000');

        $this->assertDatabaseHas('stock_levels', [
            'stock_location_id' => $location->id,
            'product_id' => $product->id,
            'product_variant_id' => null,
            'quantity' => 10,
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'organization_id' => $organization->id,
            'stock_location_id' => $location->id,
            'product_id' => $product->id,
            'type' => 'adjustment_in',
            'reason' => 'Réception initiale',
            'created_by' => $user->id,
        ]);
    }

    public function test_user_can_decrease_existing_stock(): void
    {
        [$user, $organization] = $this->context();

        $location = $this->location($organization);
        $product = $this->product($organization, $location);

        StockLevel::create([
            'stock_location_id' => $location->id,
            'product_id' => $product->id,
            'quantity' => 20,
            'reserved_quantity' => 0,
        ]);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization->id))
            ->postJson('/api/v1/caisse/stock/adjustments', [
                'stock_location_id' => $location->id,
                'product_id' => $product->id,
                'quantity' => -7,
                'reason' => 'Sortie de stock',
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('stock.quantity', '13.000');

        $this->assertDatabaseHas('stock_movements', [
            'organization_id' => $organization->id,
            'stock_location_id' => $location->id,
            'product_id' => $product->id,
            'type' => 'adjustment_out',
            'quantity' => 7,
        ]);
    }

    public function test_stock_cannot_become_negative(): void
    {
        [$user, $organization] = $this->context();

        $location = $this->location($organization);
        $product = $this->product($organization, $location);

        StockLevel::create([
            'stock_location_id' => $location->id,
            'product_id' => $product->id,
            'quantity' => 5,
            'reserved_quantity' => 0,
        ]);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization->id))
            ->postJson('/api/v1/caisse/stock/adjustments', [
                'stock_location_id' => $location->id,
                'product_id' => $product->id,
                'quantity' => -6,
            ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors('quantity');

        $this->assertDatabaseHas('stock_levels', [
            'stock_location_id' => $location->id,
            'product_id' => $product->id,
            'quantity' => 5,
        ]);
    }

    public function test_stock_level_requires_product_or_variant(): void
    {
        [$user, $organization] = $this->context();

        $location = $this->location($organization);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization->id))
            ->postJson('/api/v1/caisse/stock/adjustments', [
                'stock_location_id' => $location->id,
                'quantity' => 10,
            ]);

        $response->assertUnprocessable();
    }

    public function test_stock_level_rejects_product_and_variant_together(): void
    {
        [$user, $organization] = $this->context();

        $location = $this->location($organization);
        $product = $this->product($organization, $location);

        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
        ]);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization->id))
            ->postJson('/api/v1/caisse/stock/adjustments', [
                'stock_location_id' => $location->id,
                'product_id' => $product->id,
                'product_variant_id' => $variant->id,
                'quantity' => 10,
            ]);

        $response->assertUnprocessable();
    }

    public function test_user_can_manage_variant_stock(): void
    {
        [$user, $organization] = $this->context();

        $location = $this->location($organization);
        $product = $this->product($organization, $location);

        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
        ]);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization->id))
            ->postJson('/api/v1/caisse/stock/adjustments', [
                'stock_location_id' => $location->id,
                'product_variant_id' => $variant->id,
                'quantity' => 15,
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('stock.quantity', '15.000');

        $this->assertDatabaseHas('stock_levels', [
            'stock_location_id' => $location->id,
            'product_id' => null,
            'product_variant_id' => $variant->id,
            'quantity' => 15,
        ]);
    }

	  public function test_location_from_another_organization_is_rejected(): void
	{
		[$user, $organization] = $this->context();

		$otherOrganization = Organization::factory()->create();
		$otherLocation = $this->location($otherOrganization);
		$otherProduct = $this->product(
			$otherOrganization,
			$otherLocation
		);

		$response = $this
			->actingAs($user, 'sanctum')
			->withHeaders($this->headers($organization->id))
			->postJson('/api/v1/caisse/stock/adjustments', [
				'stock_location_id' => $otherLocation->id,
				'quantity' => 10,
				'product_id' => $otherProduct->id,
			]);

		$response->assertForbidden();
	}

    public function test_stock_from_another_organization_is_not_listed(): void
    {
        [$user, $organization] = $this->context();

        $otherOrganization = Organization::factory()->create();

        $otherLocation = $this->location($otherOrganization);
        $otherProduct = $this->product(
            $otherOrganization,
            $otherLocation
        );

        StockLevel::create([
            'stock_location_id' => $otherLocation->id,
            'product_id' => $otherProduct->id,
            'quantity' => 50,
            'reserved_quantity' => 0,
        ]);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization->id))
            ->getJson('/api/v1/caisse/stock');

        $response
            ->assertOk()
            ->assertJsonCount(0, 'stock.data');
    }

    public function test_new_stock_cannot_be_created_with_negative_quantity(): void
    {
        [$user, $organization] = $this->context();

        $location = $this->location($organization);
        $product = $this->product($organization, $location);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization->id))
            ->postJson('/api/v1/caisse/stock/adjustments', [
                'stock_location_id' => $location->id,
                'product_id' => $product->id,
                'quantity' => -5,
            ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors('quantity');

        $this->assertDatabaseMissing('stock_levels', [
            'stock_location_id' => $location->id,
            'product_id' => $product->id,
        ]);
    }

    public function test_adjustment_can_store_reference_information(): void
    {
        [$user, $organization] = $this->context();

        $location = $this->location($organization);
        $product = $this->product($organization, $location);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->withHeaders($this->headers($organization->id))
            ->postJson('/api/v1/caisse/stock/adjustments', [
                'stock_location_id' => $location->id,
                'product_id' => $product->id,
                'quantity' => 12,
                'reference_type' => 'purchase',
                'reference_id' => 'PO-2026-001',
            ]);

        $response->assertCreated();

        $this->assertDatabaseHas('stock_movements', [
            'reference_type' => 'purchase',
            'reference_id' => 'PO-2026-001',
        ]);
    }
}