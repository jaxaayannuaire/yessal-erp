<?php

namespace App\Services\Entitlements;

use App\Models\Organization;

class QuotaService
{
    public function getLimit(
        Organization $organization,
        string $resource
    ): ?int {
        return app(PlanLimitService::class)
            ->getLimit($organization, $resource);
    }

    public function getUsage(
        Organization $organization,
        string $resource
    ): int {
        return match ($resource) {
            'users' => $organization->users()->count(),
            default => 0,
        };
    }

    public function canAdd(
        Organization $organization,
        string $resource,
        int $quantity = 1
    ): bool {
        $limit = $this->getLimit($organization, $resource);

        if ($limit === null) {
            return true;
        }

        $usage = $this->getUsage(
            $organization,
            $resource
        );

        return ($usage + $quantity) <= $limit;
    }

    public function check(
        Organization $organization,
        string $resource,
        int $quantity = 1
    ): array {
        $limit = $this->getLimit($organization, $resource);
        $usage = $this->getUsage($organization, $resource);

        return [
            'resource' => $resource,
            'usage' => $usage,
            'limit' => $limit,
            'unlimited' => $limit === null,
            'requested' => $quantity,
            'allowed' => $limit === null
                || ($usage + $quantity) <= $limit,
        ];
    }
}