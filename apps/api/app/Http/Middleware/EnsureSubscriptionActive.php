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
		
		\Log::info('EnsureSubscriptionActive EXECUTED', [
			'user_id' => $user?->id,
		]);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non authentifié par Sanctum.',
                'code' => 'authentication_failed',
            ], 401);
        }

        $organization = $user->organizations()
            ->with([
                'subscriptions' => function ($query) {
                    $query->latest('ends_at');
                },
            ])
            ->first();

        if (!$organization) {
            return response()->json([
                'success' => false,
                'message' => 'Aucune organisation associée à cet utilisateur.',
                'code' => 'organization_required',
            ], 403);
        }

        $subscription = $organization->subscriptions->first();

        if (!$subscription) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun abonnement trouvé.',
                'code' => 'subscription_required',
            ], 403);
        }

        if ($subscription->status !== 'active') {
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
            'currentSubscription',
            $subscription
        );

        return $next($request);
    }
}