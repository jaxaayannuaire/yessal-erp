<?php

namespace App\Services\Subscriptions;

use App\Models\Subscription;
use App\Services\Payments\PaymentService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class SubscriptionLifecycleService
{
    public function __construct(
        private readonly PaymentService $paymentService,
    ) {
    }

    /**
     * Fait passer les abonnements actifs arrivés à échéance
     * en past_due et démarre la période de grâce.
     */
    public function markPastDueSubscriptions(): int
    {
        $now = now();

        $graceDays = max(
            0,
            (int) env('SUBSCRIPTION_GRACE_PERIOD_DAYS', 3)
        );

        return Subscription::query()
            ->where('status', 'active')
            ->whereNotNull('ends_at')
            ->where('ends_at', '<', $now)
            ->update([
                'status' => 'past_due',
                'grace_period_ends_at' => $now->copy()->addDays($graceDays),
                'updated_at' => $now,
            ]);
    }

    /**
     * Fait passer les abonnements past_due dont la période
     * de grâce est terminée en expired.
     */
    public function expirePastDueSubscriptions(): int
    {
        $now = now();

        return Subscription::query()
            ->where('status', 'past_due')
            ->whereNotNull('grace_period_ends_at')
            ->where('grace_period_ends_at', '<', $now)
            ->update([
                'status' => 'expired',
                'updated_at' => $now,
            ]);
    }

    /**
     * Exécute l'ensemble du traitement d'expiration.
     *
     * ACTIVE -> PAST_DUE -> EXPIRED
     */
    public function processExpiredSubscriptions(): array
    {
        return [
            'past_due' => $this->markPastDueSubscriptions(),
            'expired' => $this->expirePastDueSubscriptions(),
        ];
    }

    /**
     * Retourne les abonnements actifs dont l'échéance
     * intervient dans les prochains jours.
     *
     * Par défaut : fenêtre de renouvellement de 30 jours.
     *
     * @return Collection<int, Subscription>
     */
    public function findSubscriptionsNeedingRenewal(
        int $renewalDays = 30
    ): Collection {
        $now = now();
        $limit = $now->copy()->addDays($renewalDays);

        return Subscription::query()
            ->where('status', 'active')
            ->whereNotNull('ends_at')
            ->whereBetween('ends_at', [
                $now,
                $limit,
            ])
            ->with('payments')
            ->get()
            ->filter(function (Subscription $subscription): bool {
                return ! $subscription->payments()
                    ->where('status', 'pending')
                    ->where('type', 'renewal')
                    ->exists();
            })
            ->values();
    }

    /**
     * Crée un paiement de renouvellement.
     *
     * PaymentService assure l'idempotence lorsqu'un paiement
     * pending de même type existe déjà.
     */
    public function createRenewalPayment(
        Subscription $subscription,
        string $paymentMethod = 'wave'
    ) {
        return DB::transaction(function () use (
            $subscription,
            $paymentMethod
        ) {
            return $this->paymentService->createPayment(
                $subscription,
                $paymentMethod,
                'renewal'
            );
        });
    }

    /**
     * Crée les paiements de renouvellement nécessaires.
     *
     * @return array<int, mixed>
     */
    public function createPendingRenewalPayments(
        int $renewalDays = 30,
        string $paymentMethod = 'wave'
    ): array {
        $subscriptions = $this->findSubscriptionsNeedingRenewal(
            $renewalDays
        );

        $payments = [];

        foreach ($subscriptions as $subscription) {
            $payments[] = $this->createRenewalPayment(
                $subscription,
                $paymentMethod
            );
        }

        return $payments;
    }

    /**
     * Exécute le cycle complet :
     *
     * 1. ACTIVE -> PAST_DUE
     * 2. PAST_DUE -> EXPIRED
     * 3. Prépare les renouvellements J-30
     */
    public function processLifecycle(
        int $renewalDays = 30,
        string $paymentMethod = 'wave'
    ): array {
        $expiration = $this->processExpiredSubscriptions();

        $payments = $this->createPendingRenewalPayments(
            $renewalDays,
            $paymentMethod
        );

        return [
            'past_due' => $expiration['past_due'],
            'expired' => $expiration['expired'],
            'renewal_payments' => count($payments),
        ];
    }
}