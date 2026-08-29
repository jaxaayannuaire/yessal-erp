<?php

namespace Tests\Feature\Caisse;

use App\Models\Caisse\Product;
use App\Models\Caisse\Shop;
use App\Models\Caisse\Terminal;
use App\Models\Organization;
use App\Models\User;
use App\Services\Caisse\CashSessionService;
use App\Services\Caisse\SaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SaleIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_same_local_uuid_does_not_create_duplicate_sale(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();

        $shop = Shop::factory()->create([
            'organization_id' => $organization->id,
        ]);

        $terminal = Terminal::factory()->create([
            'shop_id' => $shop->id,
        ]);

        $session = app(CashSessionService::class)->open($terminal, [
            'organization_id' => $organization->id,
            'opened_by' => $user->id,
            'opening_amount' => 10000,
        ]);

        $product = Product::factory()->create([
            'shop_id' => $shop->id,
            'sale_price' => 1000,
        ]);

        $localUuid = (string) Str::uuid();

        $data = [
            'organization_id' => $organization->id,
            'shop_id' => $shop->id,
            'terminal_id' => $terminal->id,
            'cash_session_id' => $session->id,
            'cashier_user_id' => $user->id,
            'local_uuid' => $localUuid,
            'receipt_number' => 'TEST-IDEMPOTENT-001',
            'currency' => 'XOF',
            'lines' => [[
                'product_id' => $product->id,
                'quantity' => 2,
                'unit_price' => 1000,
            ]],
        ];

        $service = app(SaleService::class);

        $firstSale = $service->create($data);
        $secondSale = $service->create($data);

        $this->assertSame($firstSale->id, $secondSale->id);
        $this->assertDatabaseCount('sales', 1);
        $this->assertDatabaseCount('sale_lines', 1);

        $this->assertDatabaseHas('sales', [
            'id' => $firstSale->id,
            'organization_id' => $organization->id,
            'local_uuid' => $localUuid,
            'receipt_number' => 'TEST-IDEMPOTENT-001',
        ]);
    }
}