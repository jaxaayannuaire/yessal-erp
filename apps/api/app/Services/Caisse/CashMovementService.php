<?php

namespace App\Services\Caisse;

use App\Enums\Caisse\CashSessionStatus;
use App\Models\Caisse\CashMovement;
use App\Models\Caisse\CashSession;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CashMovementService
{
    public function createManualMovement(
        CashSession $cashSession,
        int $userId,
        array $data
    ): CashMovement {
        return DB::transaction(function () use ($cashSession, $userId, $data) {
            $session = CashSession::query()
                ->lockForUpdate()
                ->findOrFail($cashSession->id);

            if ($session->status !== CashSessionStatus::Open->value) {
                throw ValidationException::withMessages([
                    'cash_session' => [
                        'La session de caisse doit être ouverte.',
                    ],
                ]);
            }

            return CashMovement::create([
                'organization_id' => $session->organization_id,
                'cash_session_id' => $session->id,
                'type' => $data['type'],
                'amount' => $data['amount'],
                'reason' => $data['reason'],
                'created_by' => $userId,
                'created_at' => now(),
            ]);
        });
    }
}
