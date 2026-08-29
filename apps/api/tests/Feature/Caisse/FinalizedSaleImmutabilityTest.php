<?php

namespace Tests\Feature\Caisse;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class FinalizedSaleImmutabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_finalized_sale_cannot_be_modified_as_a_normal_sale(): void
    {
        $this->markTestIncomplete(
            'La règle doit être implémentée dans SaleService/Policy avant activation du test.'
        );
    }
}
