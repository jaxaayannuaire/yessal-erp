<?php

namespace App\Http\Controllers\Api\V1\Caisse;

use App\Http\Controllers\Controller;
use App\Models\Caisse\Shop;
use App\Services\Caisse\OrganizationAccessService;
use App\Services\Entitlements\QuotaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ShopController extends Controller
{
    public function __construct(
        private OrganizationAccessService $access,
        private QuotaService $quotaService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $organization = $request->attributes->get('currentOrganization');

        $shops = Shop::query()
            ->where('organization_id', $organization->id)
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'shops' => $shops,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $organization = $request->attributes->get('currentOrganization');

        if (! $this->quotaService->canAdd($organization, 'shops')) {
            return response()->json(['message' => 'Quota de boutiques atteint.'], 422);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('shops', 'code')
                    ->where(fn ($query) =>
                        $query->where('organization_id', $organization->id)
                    ),
            ],
            'address' => ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:50'],
            'status' => ['sometimes', 'in:active,inactive'],
        ]);

        $shop = Shop::create([
            'organization_id' => $organization->id,
            'name' => $validated['name'],
            'code' => $validated['code'],
            'address' => $validated['address'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'status' => $validated['status'] ?? 'active',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Boutique créée avec succès.',
            'shop' => $shop,
        ], 201);
    }

    public function show(Request $request, Shop $shop): JsonResponse
    {
        $organization = $request->attributes->get('currentOrganization');

        if ((int) $shop->organization_id !== (int) $organization->id) {
            abort(403, 'Boutique inaccessible.');
        }

        return response()->json([
            'success' => true,
            'shop' => $shop,
        ]);
    }

    public function update(Request $request, Shop $shop): JsonResponse
    {
        $organization = $request->attributes->get('currentOrganization');

        if ((int) $shop->organization_id !== (int) $organization->id) {
            abort(403, 'Boutique inaccessible.');
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:150'],
            'code' => [
                'sometimes',
                'string',
                'max:50',
                Rule::unique('shops', 'code')
                    ->where(fn ($query) =>
                        $query->where('organization_id', $organization->id)
                    )
                    ->ignore($shop->id),
            ],
            'address' => ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:50'],
            'status' => ['sometimes', 'in:active,inactive'],
        ]);

        $shop->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Boutique mise à jour avec succès.',
            'shop' => $shop->fresh(),
        ]);
    }
}
