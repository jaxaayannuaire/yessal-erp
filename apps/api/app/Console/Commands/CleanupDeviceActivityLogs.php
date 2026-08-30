<?php

namespace App\Console\Commands;

use App\Models\Caisse\DeviceActivityLog;
use Illuminate\Console\Command;

class CleanupDeviceActivityLogs extends Command
{
    protected $signature = 'device-activity:cleanup';

    protected $description = 'Supprime les logs d’activité des appareils datant de plus de 3 mois';

    public function handle(): int
    {
        $cutoff = now()->subMonths(3);

        $deleted = DeviceActivityLog::query()
            ->where('created_at', '<', $cutoff)
            ->delete();

        $this->info(
            "{$deleted} log(s) supprimé(s) avant " .
            $cutoff->toDateTimeString()
        );

        return self::SUCCESS;
    }
}