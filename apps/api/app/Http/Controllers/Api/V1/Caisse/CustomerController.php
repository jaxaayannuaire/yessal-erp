<?php

namespace App\Http\Controllers\Api\V1\Caisse;

use App\Http\Controllers\Controller;
use App\Models\Caisse\Customer;
use App\Models\Caisse\Shop;
use App\Services\Caisse\SyncChangeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{
    public function __construct(private readonly SyncChangeService $syncChanges)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $organization = $request->attributes->get('currentOrganization');

        $query = Customer::query()
            ->with('shop')
            ->whereHas('shop', fn ($query) => $query->where(
                'organization_id',
                $organization->id
            ));

        if ($request->filled('shop_id')) {
            $shop = $this->shopForOrganization(
                $organization->id,
                $request->integer('shop_id')
            );

            $query->where('shop_id', $shop->id);
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();

            $query->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return response()->json([
            'success' => true,
            'customers' => $query->orderBy('name')->paginate(
                min($request->integer('per_page', 25), 100)
            ),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $organization = $request->attributes->get('currentOrganization');

        $request->validate([
            'shop_id' => ['required', 'integer'],
        ]);

        $shop = $this->shopForOrganization(
            $organization->id,
            $request->integer('shop_id')
        );
        $validated = $request->validate($this->rules());

        $customer = DB::transaction(function () use ($validated, $shop, $organization) {
            $customer = Customer::create([
                ...$validated,
                'shop_id' => $shop->id,
                'status' => $validated['status'] ?? 'active',
            ]);

            $this->syncChanges->record($organization->id, 'customer', $customer);

            return $customer;
        });

        return response()->json([
            'success' => true,
            'message' => 'Client créé avec succès.',
            'customer' => $customer->load('shop'),
        ], 201);
    }

    public function show(Request $request, Customer $customer): JsonResponse
    {
        $this->ensureCustomerOrganization($request, $customer);

        return response()->json([
            'success' => true,
            'customer' => $customer->load('shop'),
        ]);
    }

    public function update(Request $request, Customer $customer): JsonResponse
    {
        $this->ensureCustomerOrganization($request, $customer);
        $validated = $request->validate($this->rules($customer));

        $organization = $request->attributes->get('currentOrganization');

        DB::transaction(function () use ($customer, $validated, $organization) {
            $customer->update($validated);

            $this->syncChanges->record($organization->id, 'customer', $customer);
        });

        return response()->json([
            'success' => true,
            'message' => 'Client mis à jour avec succès.',
            'customer' => $customer->fresh()->load('shop'),
        ]);
    }

    public function sales(Request $request, Customer $customer): JsonResponse
    {
        $organization = $request->attributes->get('currentOrganization');
        $this->ensureCustomerOrganization($request, $customer);

        return response()->json([
            'success' => true,
            'sales' => $customer->sales()
                ->with('shop')
                ->where('organization_id', $organization->id)
                ->latest('id')
                ->paginate(min($request->integer('per_page', 25), 100)),
        ]);
    }

    private function rules(?Customer $customer = null): array
    {
        return [
            'name' => [$customer ? 'sometimes' : 'required', 'string', 'max:200'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:150'],
            'address' => ['nullable', 'string'],
            'credit_enabled' => ['sometimes', 'boolean'],
            'status' => ['sometimes', Rule::in(['active', 'inactive'])],
        ];
    }

    private function shopForOrganization(int $organizationId, int $shopId): Shop
    {
        $shop = Shop::query()
            ->whereKey($shopId)
            ->where('organization_id', $organizationId)
            ->first();

        if (! $shop) {
            abort(403, 'Boutique inaccessible.');
        }

        return $shop;
    }

    private function ensureCustomerOrganization(
        Request $request,
        Customer $customer
    ): void {
        $organization = $request->attributes->get('currentOrganization');

        if (! $customer->shop()->where('organization_id', $organization->id)->exists()) {
            abort(403, 'Client inaccessible.');
        }
    }
}
