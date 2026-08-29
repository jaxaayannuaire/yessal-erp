<?php

namespace Tests\Feature\Caisse;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_unknown_organization_header_is_rejected(): void
    {
        $this->markTestIncomplete(
            'Brancher ce test sur le User/Organization factory du Core avant exécution.'
        );
    }
}
