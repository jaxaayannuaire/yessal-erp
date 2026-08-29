<?php

namespace App\Services\Caisse;

use App\Enums\Caisse\CashSessionStatus;
use App\Models\Caisse\CashMovement;
use App\Models\Caisse\CashSession;
use App\Models\Caisse\Sale;
use App\Models\Caisse\SalePayment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    /**
     * Enregistre un paiement d'une vente.
     *
     * v0.1 : le paiement cash est confirmé immédiatement.
     */
    public function payCash(
        Sale $sale,
        int $amount,
        ?string $reference = null
    ): SalePayment {
        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => ['Le montant du paiement doit être supérieur à zéro.'],
            ]);
        }

        return DB::transaction(function () use ($sale, $amount, $reference) {
            $sale = Sale::query()
                ->with('payments')
                ->lockForUpdate()
                ->findOrFail($sale->id);

            if (!in_array($sale->status, ['draft', 'partially_paid'], true)) {
                throw ValidationException::withMessages([
                    'sale' => ['Cette vente ne peut plus recevoir de paiement.'],
                ]);
            }

            $session = CashSession::query()
                ->lockForUpdate()
                ->find($sale->cash_session_id);

            if (!$session || $session->status !== CashSessionStatus::Open->value) {
                throw ValidationException::withMessages([
                    'cash_session_id' => ['La session de caisse est inexistante ou fermée.'],
                ]);
            }

            $remaining = max(
                0,
                (int) $sale->total_amount - (int) $sale->paid_amount
            );

            if ($remaining <= 0) {
                throw ValidationException::withMessages([
                    'sale' => ['Cette vente est déjà entièrement payée.'],
                ]);
            }

            if ($amount > $remaining) {
                throw ValidationException::withMessages([
                    'amount' => ['Le paiement dépasse le montant restant dû.'],
                ]);
            }

            $payment = $sale->payments()->create([
                'payment_method' => 'cash',
                'provider' => 'cash',
                'amount' => $amount,
                'change_amount' => 0,
                'status' => 'confirmed',
                'external_reference' => $reference,
                'declared_at' => now(),
                'confirmed_at' => now(),
            ]);

            $newPaidAmount = (int) $sale->paid_amount + $amount;
            $newDueAmount = max(0, (int) $sale->total_amount - $newPaidAmount);

            $sale->update([
                'paid_amount' => $newPaidAmount,
                'due_amount' => $newDueAmount,
                'status' => $newDueAmount === 0
                    ? 'paid'
                    : 'partially_paid',
            ]);

            CashMovement::create([
                'organization_id' => $sale->organization_id,
                'cash_session_id' => $session->id,
                'type' => 'sale',
                'amount' => $amount,
                'reason' => 'Paiement vente ' . ($sale->receipt_number ?? $sale->id),
                'reference_type' => SalePayment::class,
                'reference_id' => $payment->id,
                'created_by' => $sale->cashier_user_id,
            ]);

            return $payment->fresh();
        });
    }
}
