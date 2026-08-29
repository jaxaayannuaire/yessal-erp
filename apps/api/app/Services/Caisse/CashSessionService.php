<?php

namespace App\Services\Caisse;

use App\Enums\Caisse\CashSessionStatus;
use App\Models\Caisse\CashSession;
use App\Models\Caisse\Terminal;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CashSessionService
{
    public function open(Terminal $terminal, array $data): CashSession
    {
        return DB::transaction(function () use ($terminal, $data) {
            $existing = CashSession::where('terminal_id', $terminal->id)
                ->where('status', CashSessionStatus::Open->value)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                throw ValidationException::withMessages([
                    'terminal_id' => ['Une session de caisse est déjà ouverte.'],
                ]);
            }

            return CashSession::create([
                'organization_id' => $data['organization_id'],
                'shop_id' => $terminal->shop_id,
                'terminal_id' => $terminal->id,
                'device_id' => $data['device_id'] ?? null,
                'opened_by' => $data['opened_by'],
                'opening_amount' => $data['opening_amount'],
                'status' => CashSessionStatus::Open->value,
                'opened_at' => now(),
            ]);
        });
    }

    public function close(
        CashSession $session,
        int $countedAmount,
        ?string $reason = null
    ): CashSession {
        if ($session->status !== CashSessionStatus::Open->value) {
            throw ValidationException::withMessages([
                'session' => ['La session n’est pas ouverte.'],
            ]);
        }

        $expected = $this->calculateExpected($session);
        $variance = $countedAmount - $expected;

        if ($variance !== 0 && blank($reason)) {
            throw ValidationException::withMessages([
                'variance_reason' => [
                    'Une justification est obligatoire en cas d’écart.',
                ],
            ]);
        }

        $session->update([
            'status' => CashSessionStatus::Closed->value,
            'expected_amount' => $expected,
            'counted_amount' => $countedAmount,
            'variance_amount' => $variance,
            'variance_reason' => $reason,
            'closed_at' => now(),
        ]);

        return $session->refresh();
    }

    /**
     * Calcule le montant théorique présent dans la caisse.
     *
     * Le journal CashMovement est la source unique de vérité
     * pour les mouvements d'espèces. Les SalePayment ne sont
     * donc pas additionnés séparément ici afin d'éviter un
     * double comptage des paiements cash.
     */
    public function calculateExpected(CashSession $session): int
    {
        $movements = $session->movements()->get()->sum(
            fn ($movement) => match ($movement->type) {
                'cash_in', 'sale' => (int) $movement->amount,
                'cash_out', 'refund' => -(int) $movement->amount,
                default => 0,
            }
        );

        return (int) $session->opening_amount + (int) $movements;
    }
}
