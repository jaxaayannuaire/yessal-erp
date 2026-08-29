<?php

namespace App\Http\Middleware;

use App\Services\Entitlements\EntitlementService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEntitlement
{
    public function __construct(
        private EntitlementService $entitlementService
    ) {
    }

    public function handle(
        Request $request,
        Closure $next,
        string $entitlement
    ): Response {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non authentifié.',
                'code' => 'authentication_required',
            ], 401);
        }

        $organization = $user->organizations()->first();

        if (! $organization) {
            return response()->json([
                'success' => false,
                'message' => 'Aucune organisation associée.',
                'code' => 'organization_required',
            ], 403);
        }

        if (! $this->entitlementService->has(
            $organization,
            $entitlement
        )) {
            return response()->json([
                'success' => false,
                'message' => 'Cette fonctionnalité n’est pas disponible avec votre abonnement.',
                'code' => 'entitlement_required',
                'entitlement' => $entitlement,
            ], 403);
        }

        $request->attributes->set(
            'currentOrganization',
            $organization
        );

        return $next($request);
    }
}