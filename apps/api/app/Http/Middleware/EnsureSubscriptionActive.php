<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSubscriptionActive
{
    public function handle(
        Request $request,
        Closure $next
    ): Response {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non authentifié.',
                'code' => 'authentication_failed',
            ], 401);
        }

        $organization = $request->attributes
            ->get('currentOrganization');

        if (! $organization) {
            $organizationId = $request->header(
                'X-Organization-Id'
            );

            $organizations = $user->organizations();

            $organization = $organizationId !== null
                ? $organizations->whereKey($organizationId)->first()
                : $organizations->first();
        }

        if (! $organization) {
            return response()->json([
                'success' => false,
                'message' => 'Aucune organisation associée à cet utilisateur.',
                'code' => 'organization_required',
            ], 403);
        }

        $subscription = $request->attributes
            ->get('currentSubscription');

        if (! $subscription) {
            $subscription = $organization->subscriptions()
                ->with('plan')
                ->latest('ends_at')
                ->first();
        }

        if (! $subscription) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun abonnement trouvé.',
                'code' => 'subscription_required',
            ], 403);
        }

        $isActive = $subscription->status === 'active';

        $isGracePeriod = $subscription->status === 'past_due'
            && $subscription->grace_period_ends_at !== null
            && $subscription->grace_period_ends_at->isFuture();

        if (! $isActive && ! $isGracePeriod) {
            return response()->json([
                'success' => false,
                'message' => 'Abonnement inactif.',
                'code' => 'subscription_inactive',
                'subscription_status' => $subscription->status,
            ], 403);
        }

        $request->attributes->set(
            'currentOrganization',
            $organization
        );

        $request->attributes->set(
            'organization_id',
            $organization->id
        );

        $request->attributes->set(
            'currentSubscription',
            $subscription
        );

        $request->attributes->set(
            'currentPlan',
            $subscription->plan
        );

        return $next($request);
    }
}