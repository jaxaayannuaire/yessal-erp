<?php

namespace App\Http\Controllers\Api\V1\Caisse;

use App\Http\Controllers\Controller;
use App\Models\Caisse\Category;
use App\Models\Caisse\Shop;
use App\Services\Caisse\SyncChangeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;

class CategoryController extends Controller
{
    public function __construct(private readonly SyncChangeService $syncChanges)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $organization = $request->attributes->get('currentOrganization');

        $query = Category::query()
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

        return response()->json([
            'success' => true,
            'categories' => $query->orderBy('name')->paginate(
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
        $category = DB::transaction(function () use ($request, $shop, $organization) {
            $category = Category::create([
                ...$request->validate($this->rules($shop)),
                'shop_id' => $shop->id,
                'status' => $request->input('status', 'active'),
            ]);

            $this->syncChanges->record($organization->id, 'category', $category);

            return $category;
        });

        return response()->json([
            'success' => true,
            'message' => 'Catégorie créée avec succès.',
            'category' => $category->load('shop'),
        ], 201);
    }

    public function show(Request $request, Category $category): JsonResponse
    {
        $this->ensureCategoryOrganization($request, $category);

        return response()->json([
            'success' => true,
            'category' => $category->load('shop'),
        ]);
    }

    public function update(Request $request, Category $category): JsonResponse
    {
        $organization = $request->attributes->get('currentOrganization');
        $this->ensureCategoryOrganization($request, $category);

        $shop = $this->shopForOrganization(
            $organization->id,
            $request->integer('shop_id', $category->shop_id)
        );

        if ($shop->id !== $category->shop_id) {
            throw ValidationException::withMessages([
                'shop_id' => [
                    'Une catégorie ne peut pas être déplacée vers une autre boutique.',
                ],
            ]);
        }

        $validated = $request->validate($this->rules($shop, $category));

        DB::transaction(function () use ($category, $validated, $shop, $organization) {
            $category->update([
                ...$validated,
                'shop_id' => $shop->id,
            ]);

            $this->syncChanges->record($organization->id, 'category', $category);
        });

        return response()->json([
            'success' => true,
            'message' => 'Catégorie mise à jour avec succès.',
            'category' => $category->fresh()->load('shop'),
        ]);
    }

    private function rules(Shop $shop, ?Category $category = null): array
    {
        return [
            'shop_id' => ['sometimes', 'integer'],
            'name' => [$category ? 'sometimes' : 'required', 'string', 'max:150'],
            'slug' => [
                $category ? 'sometimes' : 'required',
                'string',
                'max:180',
                Rule::unique('categories', 'slug')
                    ->where(fn ($query) => $query->where('shop_id', $shop->id))
                    ->ignore($category?->id),
            ],
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

    private function ensureCategoryOrganization(Request $request, Category $category): void
    {
        $organization = $request->attributes->get('currentOrganization');

        if (! $category->shop()->where('organization_id', $organization->id)->exists()) {
            abort(403, 'Catégorie inaccessible.');
        }
    }
}
