<?php

namespace Tests\Feature\Caisse;

use App\Models\Caisse\Shop;
use App\Models\Caisse\Terminal;
use App\Models\User;
use App\Models\Organization;
use App\Services\Caisse\CashSessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CashSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_terminal_cannot_have_two_open_sessions(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();

        $shop = Shop::factory()->create([
            'organization_id' => $organization->id,
        ]);

        $terminal = Terminal::factory()->create([
            'shop_id' => $shop->id,
        ]);

        $service = app(CashSessionService::class);

        $first = $service->open($terminal, [
            'organization_id' => $organization->id,
            'opened_by' => $user->id,
            'opening_amount' => 10000,
        ]);

        $this->assertDatabaseHas('cash_sessions', [
            'id' => $first->id,
            'terminal_id' => $terminal->id,
            'status' => 'open',
        ]);

        $this->expectException(ValidationException::class);

        $service->open($terminal, [
            'organization_id' => $organization->id,
            'opened_by' => $user->id,
            'opening_amount' => 5000,
        ]);
    }

    public function test_closing_with_variance_requires_reason(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();

        $shop = Shop::factory()->create([
            'organization_id' => $organization->id,
        ]);

        $terminal = Terminal::factory()->create([
            'shop_id' => $shop->id,
        ]);

        $service = app(CashSessionService::class);

        $session = $service->open($terminal, [
            'organization_id' => $organization->id,
            'opened_by' => $user->id,
            'opening_amount' => 10000,
        ]);

        try {
            $service->close($session, 9500);
            $this->fail('La clôture avec écart sans justification aurait dû être refusée.');
        } catch (ValidationException $e) {
            $this->assertTrue(
                $e->errors()['variance_reason'] !== []
            );
        }

        $this->assertDatabaseHas('cash_sessions', [
            'id' => $session->id,
            'status' => 'open',
        ]);
    }
}