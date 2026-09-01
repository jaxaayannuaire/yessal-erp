<?php

namespace App\Services\Payments;

use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class WaveReconciliationService
{
    public function __construct(
        private readonly WaveBalanceService $balanceService,
    ) {
    }

    /**
     * Réconcilie une transaction Wave avec un paiement Yessal.
     *
     * Aucun paiement n'est créé automatiquement.
     * Aucun paiement n'est confirmé automatiquement.
     */
    public function reconcileTransaction(array $transaction): array
    {
        $transactionId = $this->transactionId($transaction);
        $reference = $this->clientReference($transaction);

        if ($transactionId === null && $reference === null) {
            return [
                'status' => 'unmatched',
                'matched' => false,
                'payment' => null,
                'transaction' => $transaction,
                'reason' => 'Transaction Wave sans identifiant ni référence Yessal.',
            ];
        }

        $payment = $this->findPayment(
            $transactionId,
            $reference
        );

        if (!$payment) {
            return [
                'status' => 'unmatched',
                'matched' => false,
                'payment' => null,
                'transaction' => $transaction,
                'reason' => 'Aucun paiement Yessal correspondant.',
            ];
        }

        if ($payment->provider !== null
            && strtolower((string) $payment->provider) !== 'wave') {
            return [
                'status' => 'provider_mismatch',
                'matched' => true,
                'payment' => $payment,
                'transaction' => $transaction,
                'reason' => 'Le paiement ne provient pas du fournisseur Wave.',
            ];
        }

        $amountCheck = $this->compareAmount(
            $payment,
            $transaction
        );

        if (!$amountCheck['matches']) {
            return [
                'status' => 'amount_mismatch',
                'matched' => true,
                'payment' => $payment,
                'transaction' => $transaction,
                'reason' => 'Le montant Wave ne correspond pas au montant Yessal.',
                'expected_amount' => $amountCheck['expected'],
                'actual_amount' => $amountCheck['actual'],
            ];
        }

        $currencyCheck = $this->compareCurrency(
            $payment,
            $transaction
        );

        if (!$currencyCheck['matches']) {
            return [
                'status' => 'currency_mismatch',
                'matched' => true,
                'payment' => $payment,
                'transaction' => $transaction,
                'reason' => 'La devise Wave ne correspond pas à la devise Yessal.',
                'expected_currency' => $currencyCheck['expected'],
                'actual_currency' => $currencyCheck['actual'],
            ];
        }

        return [
            'status' => 'reconciled',
            'matched' => true,
            'payment' => $payment,
            'transaction' => $transaction,
            'reason' => 'Transaction Wave et paiement Yessal concordants.',
        ];
    }

    /**
     * Réconcilie toutes les transactions retournées par Wave Balance.
     */
    public function reconcileTransactions(
        array $response
    ): array {
        $transactions = $response['items'] ?? [];

        $results = [
            'total' => count($transactions),
            'reconciled' => 0,
            'unmatched' => 0,
            'amount_mismatch' => 0,
            'currency_mismatch' => 0,
            'provider_mismatch' => 0,
            'results' => [],
            'page_info' => $response['page_info'] ?? null,
        ];

        foreach ($transactions as $transaction) {
            $result = $this->reconcileTransaction($transaction);

            $status = $result['status'];

            if (isset($results[$status])) {
                $results[$status]++;
            }

            $results['results'][] = $result;
        }

        return $results;
    }

    /**
     * Récupère et réconcilie les transactions Wave d'une date.
     */
    public function reconcileDate(
        ?string $date = null,
        bool $includeSubaccounts = false
    ): array {
        $response = $this->balanceService->getTransactions(
            $date,
            null,
            $includeSubaccounts
        );

        return $this->reconcileTransactions($response);
    }

    /**
     * Récupère et réconcilie une page de transactions Wave.
     */
    public function reconcilePage(
        ?string $date = null,
        ?string $after = null,
        bool $includeSubaccounts = false
    ): array {
        $response = $this->balanceService->getTransactions(
            $date,
            $after,
            $includeSubaccounts
        );

        return $this->reconcileTransactions($response);
    }

    /**
     * Vérifie les paiements Wave Yessal qui ne possèdent pas encore
     * de transaction fournisseur.
     *
     * Aucun paiement n'est modifié.
     */
    public function findPaymentsMissingTransaction(
        int $limit = 100
    ): array {
        return Payment::query()
            ->where('provider', 'wave')
            ->whereNull('provider_transaction_id')
            ->whereIn('status', [
                'pending',
                'paid',
            ])
            ->latest('id')
            ->limit($limit)
            ->get()
            ->all();
    }

    /**
     * Recherche un paiement par identifiant Wave ou référence Yessal.
     */
    private function findPayment(
        ?string $transactionId,
        ?string $reference
    ): ?Payment {
        if ($transactionId !== null) {
            $payment = Payment::query()
                ->where('provider_transaction_id', $transactionId)
                ->first();

            if ($payment) {
                return $payment;
            }
        }

        if ($reference !== null) {
            return Payment::query()
                ->where('reference', $reference)
                ->where('provider', 'wave')
                ->first();
        }

        return null;
    }

    /**
     * Extrait l'identifiant de transaction Wave.
     */
    private function transactionId(array $transaction): ?string
    {
        $value = $transaction['id']
            ?? $transaction['transaction_id']
            ?? $transaction['transactionId']
            ?? null;

        return $this->normalizeString($value);
    }

    /**
     * Extrait la référence client/Yessal.
     *
     * Plusieurs noms sont acceptés afin de rester compatible
     * avec les différentes représentations possibles de la transaction.
     */
    private function clientReference(array $transaction): ?string
    {
        $value = $transaction['client_reference']
            ?? $transaction['clientReference']
            ?? $transaction['reference']
            ?? $transaction['client_ref']
            ?? null;

        return $this->normalizeString($value);
    }

    private function normalizeString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function compareAmount(
        Payment $payment,
        array $transaction
    ): array {
        if (!array_key_exists('amount', $transaction)) {
            return [
                'matches' => false,
                'expected' => (string) $payment->amount,
                'actual' => null,
            ];
        }

        $expected = number_format(
            (float) $payment->amount,
            2,
            '.',
            ''
        );

        $actual = number_format(
            (float) $transaction['amount'],
            2,
            '.',
            ''
        );

        return [
            'matches' => bccomp($expected, $actual, 2) === 0,
            'expected' => $expected,
            'actual' => $actual,
        ];
    }

    private function compareCurrency(
        Payment $payment,
        array $transaction
    ): array {
        $expected = strtoupper((string) $payment->currency);
        $actual = strtoupper(
            (string) ($transaction['currency'] ?? '')
        );

        return [
            'matches' => $actual !== '' && $expected === $actual,
            'expected' => $expected,
            'actual' => $actual !== '' ? $actual : null,
        ];
    }
}