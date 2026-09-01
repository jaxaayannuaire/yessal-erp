<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveOrganizationContext
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
                'code' => 'authentication_required',
            ], 401);
        }

        $organizationId = $request->header('X-Organization-Id');

        $organizations = $user->organizations();

        if ($organizationId !== null) {
            $organization = $organizations
                ->whereKey($organizationId)
                ->first();

            if (! $organization) {
                return response()->json([
                    'success' => false,
                    'message' => 'Organisation introuvable ou inaccessible.',
                    'code' => 'organization_required',
                ], 403);
            }
        } else {
            $organization = $organizations->first();
        }

        if (! $organization) {
            return response()->json([
                'success' => false,
                'message' => 'Organisation introuvable ou inaccessible.',
                'code' => 'organization_required',
            ], 403);
        }

        /*
         * Ce middleware résout uniquement le contexte de l'organisation.
         *
         * Il ne doit PAS exiger un abonnement actif.
         * La vérification de l'abonnement est assurée séparément
         * par EnsureSubscriptionActive sur les routes concernées.
         */

        $subscription = $organization->subscriptions()
            ->with('plan')
            ->latest('ends_at')
            ->first();

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
            $subscription?->plan
        );

        return $next($request);
    }
}