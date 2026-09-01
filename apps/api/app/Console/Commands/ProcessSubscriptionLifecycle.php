<?php

namespace App\Console\Commands;

use App\Services\Subscriptions\SubscriptionLifecycleService;
use Illuminate\Console\Command;

class ProcessSubscriptionLifecycle extends Command
{
    protected $signature = 'subscriptions:process-lifecycle';

    protected $description = 'Traite les expirations et prépare les renouvellements des abonnements';

    public function handle(
        SubscriptionLifecycleService $service
    ): int {
        $expired = $service->processExpiredSubscriptions();

        $payments = $service->createPendingRenewalPayments();

        $this->info("Abonnements expirés : {$expired}");
        $this->info('Paiements de renouvellement créés : ' . count($payments));

        return self::SUCCESS;
    }
}