<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Services\Payments\PaymentService;
use Illuminate\Console\Command;

class RenewSubscriptions extends Command
{
    protected $signature = 'subscriptions:renew';

    protected $description =
        'Génère les paiements de renouvellement des abonnements proches de leur échéance';

    public function handle(
        PaymentService $paymentService
    ): int {
        $renewalDays = 30;

        $subscriptions = Subscription::where(
            'status',
            'active'
        )
            ->whereNotNull('ends_at')
            ->whereBetween(
                'ends_at',
                [
                    now(),
                    now()->addDays($renewalDays),
                ]
            )
            ->get();

        $this->info(
            "Abonnements à renouveler : {$subscriptions->count()}"
        );

        foreach ($subscriptions as $subscription) {

            $existingPayment =
                $subscription
                    ->payments()
                    ->where('status', 'pending')
					->where('type', 'renewal')
					->exists();

            if ($existingPayment) {
                $this->line(
                    "Abonnement #{$subscription->id} : paiement déjà en attente."
                );

                continue;
            }

            $payment = $paymentService->createPayment(
				$subscription,
				'wave',
				'renewal'
			);

            $this->info(
                "Paiement #{$payment->id} créé pour l'abonnement #{$subscription->id}."
            );
        }

        return self::SUCCESS;
    }
}