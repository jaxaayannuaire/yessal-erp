<?php

namespace App\Services\Entitlements;

use App\Models\Organization;
use App\Models\Subscription;

class PlanLimitService
{
    public function getLimit(
        Organization $organization,
        string $resource
    ): ?int {
        $subscription = app(
            EntitlementService::class
        )->getValidSubscription($organization);

        if (! $subscription || ! $subscription->plan) {
            return null;
        }

        return match ($resource) {
            'users' => $subscription->plan->max_users,
            'products' => $subscription->plan->max_products,
			'devices' => $subscription->plan->max_devices,
            'shops' => $subscription->plan->max_shops,
            default => null,
        };
    }

    public function isUnlimited(
        Organization $organization,
        string $resource
    ): bool {
        return $this->getLimit($organization, $resource) === null;
    }

    public function canAdd(
        Organization $organization,
        string $resource,
        int $currentCount
    ): bool {
        $limit = $this->getLimit(
            $organization,
            $resource
        );

        if ($limit === null) {
            return true;
        }

        return $currentCount < $limit;
    }
}
