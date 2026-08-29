<?php

namespace Tests\Feature\Caisse;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ApiContextTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_organization_context_is_resolved(): void
    {
        $this->markTestIncomplete(
            'Réutiliser le test context-test déjà validé manuellement dans le Core.'
        );
    }

    public function test_invalid_organization_context_returns_403(): void
    {
        $this->markTestIncomplete(
            'Réutiliser le cas X-Organization-Id=999 déjà validé manuellement.'
        );
    }
}
