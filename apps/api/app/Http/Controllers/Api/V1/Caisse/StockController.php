<?php

namespace App\Http\Controllers\Api\V1\Caisse;

use App\Http\Controllers\Controller;
use App\Models\Caisse\Product;
use App\Models\Caisse\ProductVariant;
use App\Models\Caisse\StockLevel;
use App\Models\Caisse\StockLocation;
use App\Models\Caisse\StockMovement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $organizationId = (int) $request->attributes->get('organization_id');

        $query = StockLevel::query()
            ->with(['location.shop', 'product', 'variant'])
            ->whereHas('location.shop', function ($query) use ($organizationId) {
                $query->where('organization_id', $organizationId);
            });

        if ($request->filled('stock_location_id')) {
            $query->where(
                'stock_location_id',
                $request->integer('stock_location_id')
            );
        }

        return response()->json([
            'success' => true,
            'stock' => $query
                ->orderBy('id')
                ->paginate(min($request->integer('per_page', 50), 100)),
        ]);
    }

    public function adjust(Request $request): JsonResponse
    {
        $organizationId = (int) $request->attributes->get('organization_id');

        $data = $request->validate([
            'stock_location_id' => [
                'required',
                'integer',
                'exists:stock_locations,id',
            ],
            'product_id' => [
                'nullable',
                'integer',
                'exists:products,id',
            ],
            'product_variant_id' => [
                'nullable',
                'integer',
                'exists:product_variants,id',
            ],
            'quantity' => [
                'required',
                'numeric',
                'not_in:0',
            ],
            'unit_cost' => [
                'nullable',
                'integer',
                'min:0',
            ],
            'reason' => [
                'nullable',
                'string',
                'max:5000',
            ],
            'reference_type' => [
                'nullable',
                'string',
                'max:100',
            ],
            'reference_id' => [
                'nullable',
                'string',
                'max:100',
            ],
        ]);

        $hasProduct = !empty($data['product_id']);
        $hasVariant = !empty($data['product_variant_id']);

        if ($hasProduct === $hasVariant) {
            throw ValidationException::withMessages([
                'product_id' => [
                    'Un produit ou une variante doit être fourni, mais pas les deux.',
                ],
            ]);
        }

        $location = StockLocation::query()
            ->with('shop')
            ->whereKey($data['stock_location_id'])
            ->whereHas('shop', function ($query) use ($organizationId) {
                $query->where('organization_id', $organizationId);
            })
            ->first();

        if (!$location) {
            abort(403, 'Emplacement de stock inaccessible.');
        }

        $product = null;
        $variant = null;

        if ($hasProduct) {
            $product = Product::query()
                ->whereKey($data['product_id'])
                ->where('shop_id', $location->shop_id)
                ->first();

            if (!$product) {
                throw ValidationException::withMessages([
                    'product_id' => [
                        'Produit introuvable ou inaccessible pour cette boutique.',
                    ],
                ]);
            }
        }

        if ($hasVariant) {
            $variant = ProductVariant::query()
                ->with('product')
                ->whereKey($data['product_variant_id'])
                ->first();

            if (!$variant || !$variant->product) {
                throw ValidationException::withMessages([
                    'product_variant_id' => [
                        'Variante introuvable ou inaccessible.',
                    ],
                ]);
            }

            if (
                (int) $variant->product->shop_id !==
                (int) $location->shop_id
            ) {
                throw ValidationException::withMessages([
                    'product_variant_id' => [
                        "La variante n'appartient pas à cette boutique.",
                    ],
                ]);
            }
        }

        $quantity = (float) $data['quantity'];

        $level = DB::transaction(function () use (
            $data,
            $organizationId,
            $location,
            $product,
            $variant,
            $quantity,
            $request
        ) {
            $query = StockLevel::query()
                ->where('stock_location_id', $location->id);

            if ($product) {
                $query->where('product_id', $product->id);
            } else {
                $query->where('product_variant_id', $variant->id);
            }

            $level = $query->lockForUpdate()->first();

            if (!$level) {
                if ($quantity < 0) {
                    throw ValidationException::withMessages([
                        'quantity' => [
                            'Un stock inexistant ne peut pas être diminué.',
                        ],
                    ]);
                }

                $level = StockLevel::create([
                    'stock_location_id' => $location->id,
                    'product_id' => $product?->id,
                    'product_variant_id' => $variant?->id,
                    'quantity' => 0,
                    'reserved_quantity' => 0,
                ]);
            }

            $newQuantity = (float) $level->quantity + $quantity;

            if ($newQuantity < 0) {
                throw ValidationException::withMessages([
                    'quantity' => [
                        'Le stock disponible ne peut pas devenir négatif.',
                    ],
                ]);
            }

            $level->update([
                'quantity' => $newQuantity,
            ]);

            StockMovement::create([
                'organization_id' => $organizationId,
                'stock_location_id' => $location->id,
                'product_id' => $product?->id,
                'product_variant_id' => $variant?->id,
                'type' => $quantity > 0
                    ? 'adjustment_in'
                    : 'adjustment_out',
                'quantity' => abs($quantity),
                'unit_cost' => $data['unit_cost'] ?? null,
                'reference_type' => $data['reference_type'] ?? null,
                'reference_id' => $data['reference_id'] ?? null,
                'reason' => $data['reason'] ?? null,
                'created_by' => $request->user()?->id,
                'created_at' => now(),
            ]);

            return $level->fresh([
                'location',
                'product',
                'variant',
            ]);
        });

        return response()->json([
            'success' => true,
            'stock' => $level,
        ], 201);
    }
}