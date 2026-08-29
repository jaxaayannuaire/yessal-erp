<?php

namespace Tests\Feature\Caisse;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CashSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_terminal_cannot_have_two_open_sessions(): void
    {
        $this->markTestIncomplete(
            'Créer les factories Shop/Terminal/User puis tester la contrainte unique PostgreSQL.'
        );
    }

    public function test_closing_with_variance_requires_reason(): void
    {
        $this->markTestIncomplete(
            'Créer une session ouverte et appeler CashSessionService::close().'
        );
    }
}
