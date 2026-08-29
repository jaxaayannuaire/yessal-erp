<?php

namespace Tests\Feature\Caisse;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SaleIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_same_local_uuid_does_not_create_duplicate_sale(): void
    {
        $this->markTestIncomplete(
            'Créer les factories nécessaires puis appeler SaleService::create() deux fois.'
        );
    }
}
