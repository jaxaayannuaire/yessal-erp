<?php

namespace App\Services\Caisse;

use RuntimeException;

class DeviceQuotaExceededException extends RuntimeException
{
    public function __construct(
        public readonly array $quota
    ) {
        parent::__construct(
            'La limite d’appareils de votre abonnement est atteinte.'
        );
    }
}