<?php

namespace Tests\Feature\Caisse;

use App\Models\Caisse\Sale;
use App\Services\Caisse\SaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class FinalizedSaleImmutabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_paid_sale_can_be_finalized(): void
    {
        $sale = Sale::factory()->create([
            'status' => 'paid',
            'subtotal' => 10000,
            'total_amount' => 10000,
            'paid_amount' => 10000,
            'due_amount' => 0,
            'finalized_at' => null,
        ]);

        $finalized = app(SaleService::class)->finalize($sale);

        $this->assertSame('finalized', $finalized->status);
        $this->assertNotNull($finalized->finalized_at);
        $this->assertSame(0, (int) $finalized->due_amount);
    }

    public function test_finalized_sale_cannot_be_modified_as_a_normal_sale(): void
    {
        $sale = Sale::factory()->create([
            'status' => 'paid',
            'subtotal' => 10000,
            'total_amount' => 10000,
            'paid_amount' => 10000,
            'due_amount' => 0,
            'finalized_at' => null,
        ]);

        $sale = app(SaleService::class)->finalize($sale);

        $this->expectException(ValidationException::class);

        app(SaleService::class)->ensureEditable($sale);
    }

    public function test_finalized_sale_cannot_be_finalized_twice(): void
    {
        $sale = Sale::factory()->create([
            'status' => 'paid',
            'subtotal' => 10000,
            'total_amount' => 10000,
            'paid_amount' => 10000,
            'due_amount' => 0,
            'finalized_at' => null,
        ]);

        $sale = app(SaleService::class)->finalize($sale);

        $this->expectException(ValidationException::class);

        app(SaleService::class)->finalize($sale);
    }

    public function test_unpaid_sale_cannot_be_finalized(): void
    {
        $sale = Sale::factory()->create([
            'status' => 'partially_paid',
            'subtotal' => 10000,
            'total_amount' => 10000,
            'paid_amount' => 5000,
            'due_amount' => 5000,
            'finalized_at' => null,
        ]);

        $this->expectException(ValidationException::class);

        app(SaleService::class)->finalize($sale);
    }
}