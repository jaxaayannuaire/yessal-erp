<?php

namespace App\Http\Controllers\Api\V1\Caisse;

use App\Http\Controllers\Controller;
use App\Models\Caisse\Category;
use App\Models\Caisse\Product;
use App\Models\Caisse\Shop;
use App\Services\Entitlements\QuotaService;
use App\Services\Caisse\SyncChangeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function __construct(
        private readonly QuotaService $quotaService,
        private readonly SyncChangeService $syncChanges
    )
    {
    }

    public function index(Request $request): JsonResponse
    {
        $organization = $request->attributes->get('currentOrganization');

        $query = Product::query()
            ->with(['shop', 'category'])
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
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        return response()->json([
            'success' => true,
            'products' => $query
                ->orderBy('name')
                ->paginate(min($request->integer('per_page', 25), 100)),
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

        if (! $this->quotaService->canAdd($organization, 'products')) {
            return response()->json([
                'message' => 'Quota de produits atteint.',
            ], 422);
        }

        $validated = $request->validate($this->rules($shop));
        $category = $this->categoryForShop(
            $validated['category_id'] ?? null,
            $shop
        );

        $product = DB::transaction(function () use ($validated, $shop, $category, $organization) {
            $product = Product::create([
                ...$validated,
                'shop_id' => $shop->id,
                'category_id' => $category?->id,
                'status' => $validated['status'] ?? 'active',
            ]);

            $this->syncChanges->record($organization->id, 'product', $product);

            return $product;
        });

        return response()->json([
            'success' => true,
            'message' => 'Produit créé avec succès.',
            'product' => $product->load(['shop', 'category']),
        ], 201);
    }

    public function show(Request $request, Product $product): JsonResponse
    {
        $this->ensureProductOrganization($request, $product);

        return response()->json([
            'success' => true,
            'product' => $product->load(['shop', 'category', 'variants']),
        ]);
    }

    public function update(Request $request, Product $product): JsonResponse
    {
        $organization = $request->attributes->get('currentOrganization');
        $this->ensureProductOrganization($request, $product);

        $shop = $this->shopForOrganization(
            $organization->id,
            $request->integer('shop_id', $product->shop_id)
        );

        $validated = $request->validate($this->rules($shop, $product));
        $categoryId = array_key_exists('category_id', $validated)
            ? $validated['category_id']
            : $product->category_id;

        $category = $this->categoryForShop($categoryId, $shop);

        DB::transaction(function () use ($product, $validated, $shop, $category, $organization) {
            $product->update([
                ...$validated,
                'shop_id' => $shop->id,
                'category_id' => $category?->id,
            ]);

            $this->syncChanges->record($organization->id, 'product', $product);
        });

        return response()->json([
            'success' => true,
            'message' => 'Produit mis à jour avec succès.',
            'product' => $product->fresh()->load(['shop', 'category']),
        ]);
    }

    private function rules(Shop $shop, ?Product $product = null): array
    {
        return [
            'shop_id' => ['sometimes', 'integer'],
            'category_id' => ['nullable', 'integer'],
            'name' => [$product ? 'sometimes' : 'required', 'string', 'max:200'],
            'sku' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('products', 'sku')
                    ->where(fn ($query) => $query->where('shop_id', $shop->id))
                    ->ignore($product?->id),
            ],
            'barcode' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('products', 'barcode')
                    ->where(fn ($query) => $query->where('shop_id', $shop->id))
                    ->ignore($product?->id),
            ],
            'unit' => [$product ? 'sometimes' : 'nullable', 'string', 'max:30'],
            'purchase_price' => ['nullable', 'integer', 'min:0'],
            'sale_price' => [$product ? 'sometimes' : 'required', 'integer', 'min:0'],
            'tax_rate' => ['nullable', 'numeric', 'min:0'],
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

    private function categoryForShop(?int $categoryId, Shop $shop): ?Category
    {
        if ($categoryId === null) {
            return null;
        }

        $category = Category::query()
            ->whereKey($categoryId)
            ->where('shop_id', $shop->id)
            ->first();

        if (! $category) {
            throw ValidationException::withMessages([
                'category_id' => ['Catégorie introuvable ou inaccessible.'],
            ]);
        }

        return $category;
    }

    private function ensureProductOrganization(Request $request, Product $product): void
    {
        $organization = $request->attributes->get('currentOrganization');

        if (! $product->shop()->where('organization_id', $organization->id)->exists()) {
            abort(403, 'Produit inaccessible.');
        }
    }
}
