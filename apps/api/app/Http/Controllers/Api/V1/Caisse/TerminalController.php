<?php

namespace App\Http\Controllers\Api\V1\Caisse;

use App\Http\Controllers\Controller;
use App\Models\Caisse\Shop;
use App\Models\Caisse\Terminal;
use App\Services\Caisse\OrganizationAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TerminalController extends Controller
{
    public function __construct(
        private OrganizationAccessService $access
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $organization = $request->attributes->get('currentOrganization');

        $terminals = Terminal::query()
            ->whereHas('shop', function ($query) use ($organization) {
                $query->where('organization_id', $organization->id);
            })
            ->with('shop')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'terminals' => $terminals,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $organization = $request->attributes->get('currentOrganization');

        $validated = $request->validate([
            'shop_id' => ['required', 'integer', 'exists:shops,id'],
            'register_profile_id' => [
                'nullable',
                'integer',
                'exists:register_profiles,id',
            ],
            'name' => ['required', 'string', 'max:150'],
            'code' => ['required', 'string', 'max:50'],
            'status' => ['sometimes', 'in:active,inactive'],
        ]);

        $shop = Shop::query()
            ->whereKey($validated['shop_id'])
            ->where('organization_id', $organization->id)
            ->first();

        if (! $shop) {
            abort(403, 'Boutique inaccessible.');
        }

        $exists = Terminal::query()
            ->where('shop_id', $shop->id)
            ->where('code', $validated['code'])
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Ce code terminal existe déjà dans cette boutique.',
                'code' => 'terminal_code_taken',
            ], 422);
        }

        $terminal = Terminal::create([
            'shop_id' => $shop->id,
            'register_profile_id' => $validated['register_profile_id'] ?? null,
            'name' => $validated['name'],
            'code' => $validated['code'],
            'status' => $validated['status'] ?? 'active',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Terminal créé avec succès.',
            'terminal' => $terminal->load('shop'),
        ], 201);
    }

    public function show(Request $request, Terminal $terminal): JsonResponse
    {
        $organization = $request->attributes->get('currentOrganization');

        $shop = Shop::query()
            ->whereKey($terminal->shop_id)
            ->where('organization_id', $organization->id)
            ->first();

        if (! $shop) {
            abort(403, 'Terminal inaccessible.');
        }

        $this->access->ensureTerminal($shop, $terminal);

        return response()->json([
            'success' => true,
            'terminal' => $terminal->load('shop'),
        ]);
    }

    public function update(Request $request, Terminal $terminal): JsonResponse
    {
        $organization = $request->attributes->get('currentOrganization');

        $currentShop = Shop::query()
            ->whereKey($terminal->shop_id)
            ->where('organization_id', $organization->id)
            ->first();

        if (! $currentShop) {
            abort(403, 'Terminal inaccessible.');
        }

        $this->access->ensureTerminal($currentShop, $terminal);

        $validated = $request->validate([
            'shop_id' => ['sometimes', 'integer', 'exists:shops,id'],
            'register_profile_id' => [
                'nullable',
                'integer',
                'exists:register_profiles,id',
            ],
            'name' => ['sometimes', 'string', 'max:150'],
            'code' => ['sometimes', 'string', 'max:50'],
            'status' => ['sometimes', 'in:active,inactive'],
        ]);

        $targetShop = $currentShop;

        if (isset($validated['shop_id'])) {
            $targetShop = Shop::query()
                ->whereKey($validated['shop_id'])
                ->where('organization_id', $organization->id)
                ->first();

            if (! $targetShop) {
                abort(403, 'Boutique inaccessible.');
            }
        }

        if (
            isset($validated['code']) &&
            Terminal::query()
                ->where('shop_id', $targetShop->id)
                ->where('code', $validated['code'])
                ->whereKeyNot($terminal->id)
                ->exists()
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Ce code terminal existe déjà dans cette boutique.',
                'code' => 'terminal_code_taken',
            ], 422);
        }

        $terminal->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Terminal mis à jour avec succès.',
            'terminal' => $terminal->fresh()->load('shop'),
        ]);
    }
}