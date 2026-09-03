<?php

namespace App\Http\Controllers\Api\V1\Caisse;

use App\Http\Controllers\Controller;
use App\Models\Caisse\Product;
use App\Models\Caisse\ProductVariant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductVariantController extends Controller
{
    public function index(Request $request, Product $product): JsonResponse
    {
        $this->ensureProductOrganization($request, $product);

        return response()->json([
            'success' => true,
            'variants' => $product->variants()->orderBy('name')->paginate(
                min($request->integer('per_page', 25), 100)
            ),
        ]);
    }

    public function store(Request $request, Product $product): JsonResponse
    {
        $this->ensureProductOrganization($request, $product);

        $variant = $product->variants()->create(
            $request->validate($this->rules($product))
        );

        return response()->json([
            'success' => true,
            'message' => 'Variante créée avec succès.',
            'variant' => $variant,
        ], 201);
    }

    public function show(
        Request $request,
        Product $product,
        ProductVariant $variant
    ): JsonResponse {
        $this->ensureProductOrganization($request, $product);
        $this->ensureVariantBelongsToProduct($product, $variant);

        return response()->json([
            'success' => true,
            'variant' => $variant,
        ]);
    }

    public function update(
        Request $request,
        Product $product,
        ProductVariant $variant
    ): JsonResponse {
        $this->ensureProductOrganization($request, $product);
        $this->ensureVariantBelongsToProduct($product, $variant);

        $variant->update($request->validate($this->rules($product, $variant)));

        return response()->json([
            'success' => true,
            'message' => 'Variante mise à jour avec succès.',
            'variant' => $variant->fresh(),
        ]);
    }

    private function rules(
        Product $product,
        ?ProductVariant $variant = null
    ): array {
        return [
            'name' => [$variant ? 'sometimes' : 'required', 'string', 'max:200'],
            'sku' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('product_variants', 'sku')
                    ->where(fn ($query) => $query->where('product_id', $product->id))
                    ->ignore($variant?->id),
            ],
            'barcode' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('product_variants', 'barcode')
                    ->where(fn ($query) => $query->where('product_id', $product->id))
                    ->ignore($variant?->id),
            ],
            'purchase_price' => ['nullable', 'integer', 'min:0'],
            'sale_price' => [$variant ? 'sometimes' : 'required', 'integer', 'min:0'],
            'attributes' => ['nullable', 'array'],
        ];
    }

    private function ensureProductOrganization(Request $request, Product $product): void
    {
        $organization = $request->attributes->get('currentOrganization');

        if (! $product->shop()->where('organization_id', $organization->id)->exists()) {
            abort(403, 'Produit inaccessible.');
        }
    }

    private function ensureVariantBelongsToProduct(
        Product $product,
        ProductVariant $variant
    ): void {
        if ($variant->product_id !== $product->id) {
            abort(403, 'Variante inaccessible pour ce produit.');
        }
    }
}
