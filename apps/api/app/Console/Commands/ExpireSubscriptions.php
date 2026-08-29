<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use Illuminate\Console\Command;

class ExpireSubscriptions extends Command
{
    protected $signature = 'subscriptions:expire';

    protected $description = 'Gère les abonnements arrivés à échéance';

    public function handle(): int
    {
        $graceDays = (int) env(
            'SUBSCRIPTION_GRACE_PERIOD_DAYS',
            3
        );

        // 1. Les abonnements actifs arrivés à échéance
        $pastDueCount = Subscription::query()
            ->where('status', 'active')
            ->whereNotNull('ends_at')
            ->where('ends_at', '<', now())
            ->update([
                'status' => 'past_due',
                'grace_period_ends_at' => now()->addDays($graceDays),
                'updated_at' => now(),
            ]);

        // 2. Les abonnements dont le délai de grâce est terminé
        $expiredCount = Subscription::query()
            ->where('status', 'past_due')
            ->whereNotNull('grace_period_ends_at')
            ->where('grace_period_ends_at', '<', now())
            ->update([
                'status' => 'expired',
                'updated_at' => now(),
            ]);

        $this->info(
            "Passés en past_due : {$pastDueCount}"
        );

        $this->info(
            "Abonnements expirés : {$expiredCount}"
        );

        return self::SUCCESS;
    }
}