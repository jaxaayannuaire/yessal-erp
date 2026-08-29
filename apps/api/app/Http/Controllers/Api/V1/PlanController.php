<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PlanController extends Controller
{
    public function index(): JsonResponse
    {
        $plans = Plan::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return response()->json([
            'plans' => $plans,
        ]);
    }

    public function show(Plan $plan): JsonResponse
    {
        return response()->json([
            'plan' => $plan,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:plans,slug'],
            'description' => ['nullable', 'string'],
            'price_monthly' => ['required', 'numeric', 'min:0'],
            'price_yearly' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'features' => ['nullable', 'array'],
            'max_users' => ['nullable', 'integer', 'min:1'],
            'max_products' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $validated['slug'] = $validated['slug']
            ?? Str::slug($validated['name']);

        $plan = Plan::create($validated);

        return response()->json([
            'message' => 'Plan créé avec succès.',
            'plan' => $plan,
        ], 201);
    }

    public function update(Request $request, Plan $plan): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:255', 'unique:plans,slug,' . $plan->id],
            'description' => ['nullable', 'string'],
            'price_monthly' => ['sometimes', 'numeric', 'min:0'],
            'price_yearly' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'features' => ['nullable', 'array'],
            'max_users' => ['nullable', 'integer', 'min:1'],
            'max_products' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $plan->update($validated);

        return response()->json([
            'message' => 'Plan mis à jour avec succès.',
            'plan' => $plan->fresh(),
        ]);
    }

    public function destroy(Plan $plan): JsonResponse
    {
        $plan->delete();

        return response()->json([
            'message' => 'Plan supprimé avec succès.',
        ]);
    }
}