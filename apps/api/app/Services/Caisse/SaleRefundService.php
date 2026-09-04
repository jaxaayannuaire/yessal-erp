<?php

namespace App\Services\Caisse;

use App\Enums\Caisse\CashSessionStatus;
use App\Models\Caisse\CashMovement;
use App\Models\Caisse\CashSession;
use App\Models\Caisse\Sale;
use App\Models\Caisse\SalePayment;
use App\Models\Caisse\SaleReturn;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SaleRefundService
{
    public function refund(
        Sale $sale,
        int $salePaymentId,
        int $amount,
        string $reason,
        int $userId
    ): SaleReturn {
        return DB::transaction(function () use (
            $sale,
            $salePaymentId,
            $amount,
            $reason,
            $userId
        ) {
            $sale = Sale::query()
                ->lockForUpdate()
                ->findOrFail($sale->id);

            if (! in_array($sale->status, ['finalized', 'cancelled'], true)) {
                throw ValidationException::withMessages([
                    'sale' => [
                        'Seule une vente finalisée ou annulée peut être remboursée.',
                    ],
                ]);
            }

            $payment = SalePayment::query()
                ->whereKey($salePaymentId)
                ->where('sale_id', $sale->id)
                ->lockForUpdate()
                ->first();

            if (! $payment) {
                throw ValidationException::withMessages([
                    'sale_payment_id' => ['Paiement introuvable pour cette vente.'],
                ]);
            }

            if (
                $payment->status !== 'confirmed'
                || $payment->payment_method !== 'cash'
                || $payment->provider !== 'cash'
            ) {
                throw ValidationException::withMessages([
                    'sale_payment_id' => [
                        'Seul un paiement cash confirmé peut être remboursé localement.',
                    ],
                ]);
            }

            $session = CashSession::query()
                ->whereKey($sale->cash_session_id)
                ->where('organization_id', $sale->organization_id)
                ->lockForUpdate()
                ->first();

            if (! $session || $session->status !== CashSessionStatus::Open->value) {
                throw ValidationException::withMessages([
                    'cash_session' => [
                        'La session de caisse de la vente doit être ouverte.',
                    ],
                ]);
            }

            $refunds = SaleReturn::query()
                ->where('sale_id', $sale->id)
                ->where('status', 'completed')
                ->lockForUpdate()
                ->get();
            $totalRefunded = (int) $refunds->sum('amount');
            $paymentRefunded = (int) $refunds
                ->where('sale_payment_id', $payment->id)
                ->sum('amount');
            $totalPaid = (int) SalePayment::query()
                ->where('sale_id', $sale->id)
                ->where('status', 'confirmed')
                ->sum('amount');
            $remainingForSale = $totalPaid - $totalRefunded;
            $remainingForPayment = (int) $payment->amount - $paymentRefunded;

            if ($amount <= 0 || $amount > $remainingForSale || $amount > $remainingForPayment) {
                throw ValidationException::withMessages([
                    'amount' => ['Le montant dépasse le solde remboursable.'],
                ]);
            }

            $refund = SaleReturn::create([
                'organization_id' => $sale->organization_id,
                'sale_id' => $sale->id,
                'sale_payment_id' => $payment->id,
                'reference_number' => 'REF-'.Str::upper((string) Str::uuid()),
                'reason' => $reason,
                'amount' => $amount,
                'refund_method' => 'cash',
                'status' => 'completed',
                'created_by' => $userId,
            ]);

            CashMovement::create([
                'organization_id' => $sale->organization_id,
                'cash_session_id' => $session->id,
                'type' => 'refund',
                'amount' => $amount,
                'reason' => 'Remboursement vente '.($sale->receipt_number ?? $sale->id),
                'reference' => 'sale_return:'.$refund->id,
                'created_by' => $userId,
                'created_at' => now(),
            ]);

            if ($sale->status === 'finalized' && $totalRefunded + $amount === $totalPaid) {
                $sale->update(['status' => 'refunded']);
            }

            return $refund->fresh(['payment', 'creator']);
        });
    }
}
