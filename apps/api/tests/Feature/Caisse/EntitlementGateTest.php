<?php

namespace Tests\Feature\Caisse;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class EntitlementGateTest extends TestCase
{
    use RefreshDatabase;

    public function test_restricted_feature_is_denied_without_entitlement(): void
    {
        $this->markTestIncomplete(
            'Utiliser les entitlements déjà présents dans le Core Yessal.'
        );
    }

    public function test_allowed_feature_passes_with_entitlement(): void
    {
        $this->markTestIncomplete(
            'Utiliser pos.sell ou une entitlement active du Pack Tambali.'
        );
    }
}
