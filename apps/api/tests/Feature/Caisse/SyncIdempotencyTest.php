<?php

namespace Tests\Feature\Caisse;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SyncIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_same_event_uuid_is_accepted_only_once(): void
    {
        $this->markTestIncomplete(
            'Créer Organization/Device et tester SyncService::push() avec deux événements identiques.'
        );
    }
}
