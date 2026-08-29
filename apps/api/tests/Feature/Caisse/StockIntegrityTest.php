<?php

namespace Tests\Feature\Caisse;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class StockIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_stock_level_requires_product_or_variant_but_not_both(): void
    {
        $this->markTestIncomplete(
            'Tester les CHECK constraints PostgreSQL product_id XOR product_variant_id.'
        );
    }
}
