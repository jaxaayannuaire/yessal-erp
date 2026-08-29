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
    public function index()
    {
        return response()->json([
            'subscriptions' => Subscription::with([
                'organization',
                'plan',
            ])->latest()->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'organization_id' => ['required', 'exists:organizations,id'],
            'plan_id' => ['required', 'exists:plans,id'],
            'billing_cycle' => ['required', 'in:monthly,yearly'],
        ]);

        $organization = Organization::findOrFail(
            $validated['organization_id']
        );

        $plan = Plan::findOrFail(
            $validated['plan_id']
        );

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

    public function show(Subscription $subscription)
    {
        return response()->json([
            'subscription' => $subscription->load([
                'organization',
                'plan',
            ]),
        ]);
    }

    public function update(Request $request, Subscription $subscription)
    {
        $validated = $request->validate([
            'status' => [
                'sometimes',
                'in:pending,active,cancelled,expired',
            ],
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

    public function destroy(Subscription $subscription)
    {
        $subscription->update([
            'status' => 'cancelled',
        ]);

        return response()->json([
            'message' => 'Souscription annulée avec succès.',
        ]);
    }
	
	public function activate(Subscription $subscription)
	{
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

	public function cancel(Subscription $subscription)
	{
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