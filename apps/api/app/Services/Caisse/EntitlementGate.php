<?php

namespace App\Services\Caisse;

use App\Models\Organization;
use App\Services\Entitlements\EntitlementService;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class EntitlementGate
{
    public function __construct(
        private EntitlementService $entitlements
    ) {}

    public function check(Organization $organization, string $slug): void
    {
        if (! $this->entitlements->has($organization, $slug)) {
            throw new AccessDeniedHttpException(
                'Fonctionnalité non incluse dans votre abonnement.'
            );
        }
    }
}
