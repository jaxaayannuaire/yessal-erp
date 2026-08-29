<?php

namespace App\Http\Middleware;

use App\Services\Entitlements\EntitlementService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveOrganizationContext
{
    public function __construct(
        private EntitlementService $entitlementService
    ) {
    }

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

        $subscription = $this->entitlementService
            ->getValidSubscription($organization);

        if (! $subscription) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun abonnement actif.',
                'code' => 'subscription_required',
            ], 403);
        }

        $request->attributes->set(
            'currentOrganization',
            $organization
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