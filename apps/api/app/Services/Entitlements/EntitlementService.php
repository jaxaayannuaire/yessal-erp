<?php

namespace App\Services\Entitlements;

use App\Models\Organization;
use App\Models\Subscription;

class EntitlementService
{
    public function has(
        Organization $organization,
        string $entitlement
    ): bool {
        $subscription = $this->getValidSubscription($organization);

        if (! $subscription || ! $subscription->plan) {
            return false;
        }

        return $subscription->plan
            ->modules()
            ->where('modules.is_active', true)
            ->whereHas('entitlements', function ($query) use ($entitlement) {
                $query->where('slug', $entitlement)
                    ->where('is_active', true);
            })
            ->exists();
    }

    public function getValidSubscription(
        Organization $organization
    ): ?Subscription {
        return $organization->subscriptions()
            ->with('plan')
            ->where(function ($query) {
                $query->where('status', 'active')
                    ->orWhere(function ($query) {
                        $query->where('status', 'past_due')
                            ->whereNotNull('grace_period_ends_at')
                            ->where(
                                'grace_period_ends_at',
                                '>',
                                now()
                            );
                    });
            })
            ->latest('ends_at')
            ->first();
    }

    public function hasModule(
        Organization $organization,
        string $module
    ): bool {
        $subscription = $this->getValidSubscription($organization);

        if (! $subscription || ! $subscription->plan) {
            return false;
        }

        return $subscription->plan
            ->modules()
            ->where('slug', $module)
            ->where('modules.is_active', true)
            ->exists();
    }
}