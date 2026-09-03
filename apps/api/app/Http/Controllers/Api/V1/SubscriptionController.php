<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class SubscriptionController extends Controller
{
    private function organization(Request $request): Organization
    {
        return $request->attributes->get('currentOrganization');
    }

    private function ensureOwnership(
        Request $request,
        Subscription $subscription
    ): void {
        if (
            (int) $subscription->organization_id !==
            (int) $this->organization($request)->id
        ) {
            abort(403, 'Souscription inaccessible.');
        }
    }

    public function index(Request $request)
    {
        $organization = $this->organization($request);

        return response()->json([
            'subscriptions' => Subscription::where(
                'organization_id',
                $organization->id
            )
                ->with(['organization', 'plan'])
                ->latest()
                ->get(),
        ]);
    }

    public function store(Request $request)
    {
        $organization = $this->organization($request);

        $validated = $request->validate([
            'plan_id' => ['required', 'exists:plans,id'],
            'billing_cycle' => ['required', 'in:monthly,yearly'],
        ]);

        $plan = Plan::findOrFail($validated['plan_id']);

        $existingSubscription = Subscription::where(
            'organization_id',
            $organization->id
        )
            ->whereIn('status', ['pending', 'active'])
            ->first();

        if ($existingSubscription) {
            return response()->json([
                'message' => 'Une souscription active ou en attente existe déjà.',
                'subscription' => $existingSubscription,
            ], 409);
        }

        $startsAt = now();

        $endsAt = $validated['billing_cycle'] === 'monthly'
            ? Carbon::now()->addMonth()
            : Carbon::now()->addYear();

        $subscription = Subscription::create([
            'organization_id' => $organization->id,
            'plan_id' => $plan->id,
            'billing_cycle' => $validated['billing_cycle'],
            'status' => 'pending',
            'price' => $validated['billing_cycle'] === 'monthly'
                ? $plan->price_monthly
                : $plan->price_yearly,
            'currency' => $plan->currency ?? 'XOF',
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
        ]);

        return response()->json([
            'message' => 'Souscription créée avec succès.',
            'subscription' => $subscription->load([
                'organization',
                'plan',
            ]),
        ], 201);
    }

    public function show(
        Request $request,
        Subscription $subscription
    ) {
        $this->ensureOwnership($request, $subscription);

        return response()->json([
            'subscription' => $subscription->load([
                'organization',
                'plan',
            ]),
        ]);
    }

    public function update(
        Request $request,
        Subscription $subscription
    ) {
        $this->ensureOwnership($request, $subscription);

        abort_if(
            $request->has('status'),
            403,
            'Le statut d’une souscription est géré par la plateforme.'
        );

        $validated = $request->validate([
            'billing_cycle' => [
                'sometimes',
                'in:monthly,yearly',
            ],
        ]);

        $subscription->update($validated);

        return response()->json([
            'message' => 'Souscription mise à jour avec succès.',
            'subscription' => $subscription->fresh()->load([
                'organization',
                'plan',
            ]),
        ]);
    }

    public function destroy(
        Request $request,
        Subscription $subscription
    ) {
        $this->ensureOwnership($request, $subscription);

        $subscription->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);

        return response()->json([
            'message' => 'Souscription annulée avec succès.',
        ]);
    }

    public function activate(
        Request $request,
        Subscription $subscription
    ) {
        $this->ensureOwnership($request, $subscription);

        abort(403, 'L’activation d’une souscription est réservée à la plateforme.');

        if ($subscription->status === 'cancelled') {
            return response()->json([
                'message' => 'Une souscription annulée ne peut pas être activée.',
            ], 422);
        }

        if ($subscription->status === 'expired') {
            return response()->json([
                'message' => 'Une souscription expirée doit être renouvelée.',
            ], 422);
        }

        $subscription->update([
            'status' => 'active',
            'cancelled_at' => null,
        ]);

        return response()->json([
            'message' => 'Souscription activée avec succès.',
            'subscription' => $subscription->fresh()->load([
                'organization',
                'plan',
            ]),
        ]);
    }

    public function cancel(
        Request $request,
        Subscription $subscription
    ) {
        $this->ensureOwnership($request, $subscription);

        if ($subscription->status === 'cancelled') {
            return response()->json([
                'message' => 'La souscription est déjà annulée.',
                'subscription' => $subscription,
            ]);
        }

        $subscription->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);

        return response()->json([
            'message' => 'Souscription annulée avec succès.',
            'subscription' => $subscription->fresh()->load([
                'organization',
                'plan',
            ]),
        ]);
    }
}
