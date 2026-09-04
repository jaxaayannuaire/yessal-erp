<?php

namespace Tests\Feature\Caisse;

use App\Models\Caisse\CashSession;
use App\Models\Caisse\Device;
use App\Models\Caisse\Product;
use App\Models\Caisse\Sale;
use App\Models\Caisse\Shop;
use App\Models\Caisse\StockLevel;
use App\Models\Caisse\StockLocation;
use App\Models\Caisse\Terminal;
use App\Models\Caisse\SyncEvent;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SyncSaleReplayApiTest extends TestCase
{
    use RefreshDatabase;

    private function context(): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->users()->attach($user->id, ['role' => 'owner']);
        $plan = Plan::factory()->withCaisseEntitlement()->create(['is_active' => true]);
        Subscription::factory()->create(['organization_id' => $organization->id, 'plan_id' => $plan->id, 'status' => 'active', 'starts_at' => now()->subMinute(), 'ends_at' => now()->addMonth()]);
        $shop = Shop::factory()->create(['organization_id' => $organization->id]);
        $terminal = Terminal::factory()->create(['shop_id' => $shop->id]);
        $device = Device::factory()->create(['organization_id' => $organization->id, 'shop_id' => $shop->id, 'terminal_id' => $terminal->id, 'status' => 'active']);
        $session = CashSession::factory()->create(['organization_id' => $organization->id, 'shop_id' => $shop->id, 'terminal_id' => $terminal->id, 'device_id' => $device->id, 'status' => 'open']);
        $location = StockLocation::factory()->create(['organization_id' => $organization->id, 'shop_id' => $shop->id, 'status' => 'active']);
        return compact('user', 'organization', 'shop', 'terminal', 'device', 'session', 'location');
    }

    private function headers(Organization $organization): array
    {
        return ['X-Organization-Id' => (string) $organization->id, 'Accept' => 'application/json'];
    }

    private function event(array $context, Product $product, int $quantity = 1, bool $finalize = true, ?string $uuid = null): array
    {
        return ['event_uuid' => $uuid ?? (string) Str::uuid(), 'shop_id' => $context['shop']->id, 'entity_type' => 'sale', 'entity_id' => (string) Str::uuid(), 'action' => 'create', 'payload' => [
            'terminal_id' => $context['terminal']->id, 'cash_session_id' => $context['session']->id,
            'local_uuid' => (string) Str::uuid(), 'receipt_number' => 'OFF-' . fake()->unique()->numerify('#####'), 'currency' => 'XOF',
            'lines' => [['product_id' => $product->id, 'quantity' => $quantity, 'unit_price' => 1000]],
            'payment' => ['method' => 'cash', 'amount' => $quantity * 1000], 'finalize' => $finalize,
        ]];
    }

    public function test_sale_create_is_applied_once_with_cash_stock_and_a_pull_change(): void
    {
        $context = $this->context();
        $product = Product::factory()->create(['shop_id' => $context['shop']->id, 'sale_price' => 1000]);
        StockLevel::create(['stock_location_id' => $context['location']->id, 'product_id' => $product->id, 'quantity' => 4, 'reserved_quantity' => 0]);
        $event = $this->event($context, $product, 2);
        $payload = ['device_id' => $context['device']->id, 'events' => [$event]];

        $first = $this->actingAs($context['user'], 'sanctum')->withHeaders($this->headers($context['organization']))->postJson('/api/v1/caisse/sync/push', $payload);
        $first->assertOk()->assertJsonPath('accepted.0.status', 'applied')->assertJsonPath('accepted.0.duplicate', false);
        $second = $this->actingAs($context['user'], 'sanctum')->withHeaders($this->headers($context['organization']))->postJson('/api/v1/caisse/sync/push', $payload);
        $second->assertOk()->assertJsonPath('accepted.0.status', 'applied')->assertJsonPath('accepted.0.duplicate', true);

        $this->assertDatabaseCount('sales', 1);
        $this->assertDatabaseHas('sync_events', ['event_uuid' => $event['event_uuid'], 'status' => 'applied']);
        $this->assertDatabaseHas('stock_levels', ['product_id' => $product->id, 'quantity' => 2]);
        $this->assertDatabaseCount('stock_movements', 1);
        $this->assertDatabaseCount('sale_payments', 1);
        $this->assertDatabaseCount('sync_changes', 1);
        $this->actingAs($context['user'], 'sanctum')->withHeaders($this->headers($context['organization']))->getJson('/api/v1/caisse/sync/pull')->assertOk()->assertJsonPath('changes.0.entity_type', 'sale')->assertJsonPath('changes.0.source_device_id', $context['device']->id);
    }

    public function test_insufficient_stock_is_a_conflict_without_partial_state(): void
    {
        $context = $this->context();
        $product = Product::factory()->create(['shop_id' => $context['shop']->id]);
        StockLevel::create(['stock_location_id' => $context['location']->id, 'product_id' => $product->id, 'quantity' => 1, 'reserved_quantity' => 0]);
        $event = $this->event($context, $product, 2);
        $this->actingAs($context['user'], 'sanctum')->withHeaders($this->headers($context['organization']))->postJson('/api/v1/caisse/sync/push', ['device_id' => $context['device']->id, 'events' => [$event]])->assertOk()->assertJsonPath('conflicts.0.status', 'conflict');
        $this->assertDatabaseCount('sales', 0);
        $this->assertDatabaseCount('sale_payments', 0);
        $this->assertDatabaseCount('stock_movements', 0);
        $this->assertDatabaseCount('sync_changes', 0);
        $this->assertDatabaseHas('sync_events', ['event_uuid' => $event['event_uuid'], 'status' => 'conflict']);
    }

    public function test_non_cash_payment_and_closed_session_are_rejected(): void
    {
        $context = $this->context();
        $product = Product::factory()->create(['shop_id' => $context['shop']->id]);
        $event = $this->event($context, $product, 1);
        $event['payload']['payment']['method'] = 'wave';
        $this->actingAs($context['user'], 'sanctum')->withHeaders($this->headers($context['organization']))->postJson('/api/v1/caisse/sync/push', ['device_id' => $context['device']->id, 'events' => [$event]])->assertOk()->assertJsonPath('rejected.0.status', 'rejected');
        $this->assertDatabaseCount('sales', 0);

        $context['session']->update(['status' => 'closed']);
        $closed = $this->event($context, $product, 1);
        $this->actingAs($context['user'], 'sanctum')->withHeaders($this->headers($context['organization']))->postJson('/api/v1/caisse/sync/push', ['device_id' => $context['device']->id, 'events' => [$closed]])->assertOk()->assertJsonPath('rejected.0.status', 'rejected');
    }

    public function test_other_tenant_device_and_product_cannot_be_replayed(): void
    {
        $context = $this->context();
        $other = $this->context();
        $otherProduct = Product::factory()->create(['shop_id' => $other['shop']->id]);
        $event = $this->event($context, $otherProduct);
        $this->actingAs($context['user'], 'sanctum')->withHeaders($this->headers($context['organization']))->postJson('/api/v1/caisse/sync/push', ['device_id' => $context['device']->id, 'events' => [$event]])->assertOk()->assertJsonPath('rejected.0.status', 'rejected');
        $this->assertDatabaseCount('sales', 0);
        $this->actingAs($context['user'], 'sanctum')->withHeaders($this->headers($context['organization']))->postJson('/api/v1/caisse/sync/push', ['device_id' => $other['device']->id, 'events' => [$event]])->assertUnprocessable();
    }
}
