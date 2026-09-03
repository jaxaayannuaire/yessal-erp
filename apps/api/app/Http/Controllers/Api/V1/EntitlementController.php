<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Entitlements\EntitlementService;
use App\Services\Entitlements\PlanLimitService;
use App\Services\Entitlements\QuotaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EntitlementController extends Controller
{
    public function index(
        Request $request,
        EntitlementService $entitlementService,
        PlanLimitService $planLimitService,
        QuotaService $quotaService
    ): JsonResponse {
        $organization = $request->attributes->get('currentOrganization');

        if (! $organization) {
            return response()->json([
                'success' => false,
                'message' => 'Aucune organisation associée.',
                'code' => 'organization_required',
            ], 403);
        }

        $subscription = $entitlementService
            ->getValidSubscription($organization);

        if (! $subscription || ! $subscription->plan) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun abonnement actif.',
                'code' => 'subscription_required',
            ], 403);
        }

        $plan = $subscription->plan;

        $modules = $plan->modules()
            ->where('modules.is_active', true)
            ->with([
                'entitlements' => function ($query) {
                    $query->where('is_active', true)
                        ->orderBy('sort_order');
                },
            ])
            ->orderBy('sort_order')
            ->get();

        $entitlements = $modules
            ->flatMap(
                fn ($module) => $module->entitlements
            )
            ->unique('id')
            ->values()
            ->map(fn ($entitlement) => [
                'name' => $entitlement->name,
                'slug' => $entitlement->slug,
            ]);

        return response()->json([
            'success' => true,

            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
            ],

            'subscription' => [
                'id' => $subscription->id,
                'status' => $subscription->status,
                'billing_cycle' => $subscription->billing_cycle,
                'starts_at' => $subscription->starts_at,
                'ends_at' => $subscription->ends_at,
                'grace_period_ends_at' =>
                    $subscription->grace_period_ends_at,
            ],

            'plan' => [
                'id' => $plan->id,
                'name' => $plan->name,
                'slug' => $plan->slug,
            ],

            'modules' => $modules->map(fn ($module) => [
                'name' => $module->name,
                'slug' => $module->slug,
                'entitlements' => $module->entitlements->map(
                    fn ($entitlement) => [
                        'name' => $entitlement->name,
                        'slug' => $entitlement->slug,
                    ]
                )->values(),
            ])->values(),

            'entitlements' => $entitlements,

            'limits' => collect(['users', 'products', 'devices', 'shops'])->mapWithKeys(
                fn ($resource) => ['max_' . $resource => $planLimitService->getLimit($organization, $resource)]
            ),
            'usage' => collect(['users', 'products', 'devices', 'shops'])->mapWithKeys(
                fn ($resource) => [$resource => $quotaService->getUsage($organization, $resource)]
            ),
        ]);
    }
}
