<?php

namespace App\Services\Caisse;

use App\Models\Caisse\Product;
use App\Models\Caisse\ProductVariant;
use App\Models\Caisse\Sale;
use App\Models\Caisse\SaleLine;
use App\Models\Caisse\StockLevel;
use App\Models\Caisse\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaleService
{
    public function create(array $data): Sale
    {
        return DB::transaction(function () use ($data) {
            $existing = Sale::where('organization_id', $data['organization_id'])
                ->where('local_uuid', $data['local_uuid'])
                ->first();

            if ($existing) {
                return $existing;
            }

            $sale = Sale::create([
                'organization_id' => $data['organization_id'],
                'shop_id' => $data['shop_id'],
                'terminal_id' => $data['terminal_id'],
                'cash_session_id' => $data['cash_session_id'],
                'device_id' => $data['device_id'] ?? null,
                'cashier_user_id' => $data['cashier_user_id'],
                'seller_user_id' => $data['seller_user_id'] ?? null,
                'customer_id' => $data['customer_id'] ?? null,
                'local_uuid' => $data['local_uuid'],
                'receipt_number' => $data['receipt_number'],
                'currency' => $data['currency'],
                'status' => 'draft',
            ]);

            $subtotal = 0;

            foreach ($data['lines'] as $line) {
                $quantity = (float) $line['quantity'];
                $unitPrice = (int) $line['unit_price'];
                $discount = (int) ($line['discount_amount'] ?? 0);
                $tax = (int) ($line['tax_amount'] ?? 0);
                $total = (int) round($quantity * $unitPrice) - $discount;

                if ($quantity <= 0) {
                    throw ValidationException::withMessages([
                        'lines' => ['La quantité doit être supérieure à zéro.'],
                    ]);
                }

                if ($discount < 0 || $tax < 0 || $total < 0) {
                    throw ValidationException::withMessages([
                        'lines' => ['Montant de ligne invalide.'],
                    ]);
                }

                $hasProduct = ! empty($line['product_id']);
                $hasVariant = ! empty($line['product_variant_id']);

                if ($hasProduct && $hasVariant) {
                    throw ValidationException::withMessages([
                        'lines' => [
                            'Une ligne de vente ne peut référencer qu’un produit ou une variante.',
                        ],
                    ]);
                }

                $product = null;
                $variant = null;

                if ($hasProduct) {
                    $product = Product::query()
                        ->whereKey($line['product_id'])
                        ->where('shop_id', $data['shop_id'])
                        ->first();

                    if (!$product) {
                        throw ValidationException::withMessages([
                            'lines' => [
                                'Produit introuvable ou inaccessible pour cette boutique.',
                            ],
                        ]);
                    }
                }

                if ($hasVariant) {
                    $variant = ProductVariant::query()
                        ->with('product')
                        ->whereKey($line['product_variant_id'])
                        ->whereHas('product', fn ($query) => $query->where(
                            'shop_id',
                            $data['shop_id']
                        ))
                        ->first();

                    if (! $variant || ! $variant->product) {
                        throw ValidationException::withMessages([
                            'lines' => [
                                'Variante introuvable ou inaccessible pour cette boutique.',
                            ],
                        ]);
                    }

                    $product = $variant->product;
                }

                $productName = $product?->name
                    ?? ($line['product_name_snapshot'] ?? 'Produit');
                $productSku = $product?->sku
                    ?? ($line['sku_snapshot'] ?? null);
                $productBarcode = $product?->barcode
                    ?? ($line['barcode_snapshot'] ?? null);

                $sale->lines()->create([
                    'product_id' => $variant ? null : $product?->id,
                    'product_variant_id' => $variant?->id,
                    'product_name_snapshot' => $productName,
                    'sku_snapshot' => $productSku,
                    'barcode_snapshot' => $productBarcode,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'discount_amount' => $discount,
                    'tax_amount' => $tax,
                    'total_amount' => $total,
                ]);

                $subtotal += $total;
            }

            $sale->update([
                'subtotal' => $subtotal,
                'total_amount' => $subtotal,
                'due_amount' => $subtotal,
            ]);

            return $sale->load('lines');
        });
    }

    /**
     * Finalise une vente entièrement payée.
     */
    public function finalize(Sale $sale): Sale
    {
        return DB::transaction(function () use ($sale) {
            $sale = Sale::query()
                ->with('lines')
                ->lockForUpdate()
                ->findOrFail($sale->id);

            if ($sale->finalized_at !== null || $sale->status === 'finalized') {
                throw ValidationException::withMessages([
                    'sale' => ['Cette vente est déjà finalisée.'],
                ]);
            }

            if (
                $sale->status !== 'paid'
                || (int) $sale->due_amount !== 0
                || (int) $sale->paid_amount !== (int) $sale->total_amount
            ) {
                throw ValidationException::withMessages([
                    'sale' => [
                        'Le paiement de la vente est incomplet.',
                    ],
                ]);
            }

            foreach ($sale->lines as $line) {
                $this->decrementStockForLine($sale, $line);
            }

            $sale->update([
                'status' => 'finalized',
                'finalized_at' => now(),
            ]);

            return $sale->refresh();
        });
    }

    /**
     * Annule une vente finalisée et restaure les sorties de stock associées.
     */
    public function cancel(Sale $sale, ?int $cancelledBy = null): Sale
    {
        return DB::transaction(function () use ($sale, $cancelledBy) {
            $sale = Sale::query()
                ->lockForUpdate()
                ->findOrFail($sale->id);

            if ($sale->status === 'cancelled') {
                throw ValidationException::withMessages([
                    'sale' => ['Cette vente est déjà annulée.'],
                ]);
            }

            if ($sale->status !== 'finalized') {
                throw ValidationException::withMessages([
                    'sale' => ['Seule une vente finalisée peut être annulée.'],
                ]);
            }

            $movements = StockMovement::query()
                ->where('organization_id', $sale->organization_id)
                ->where('reference_type', 'sale')
                ->where('reference_id', (string) $sale->id)
                ->where('type', 'sale_out')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            foreach ($movements as $movement) {
                $this->restoreStockMovement($sale, $movement, $cancelledBy);
            }

            $sale->update([
                'status' => 'cancelled',
            ]);

            return $sale->refresh();
        });
    }

    private function decrementStockForLine(Sale $sale, SaleLine $line): void
    {
        $hasProduct = $line->product_id !== null;
        $hasVariant = $line->product_variant_id !== null;

        if (! $hasProduct && ! $hasVariant) {
            return;
        }

        if ($hasProduct === $hasVariant) {
            throw ValidationException::withMessages([
                'sale' => ['La ligne de vente doit référencer un seul article de stock.'],
            ]);
        }

        $product = null;
        $variant = null;

        if ($hasProduct) {
            $product = Product::query()
                ->whereKey($line->product_id)
                ->where('shop_id', $sale->shop_id)
                ->whereHas('shop', fn ($query) => $query->where(
                    'organization_id',
                    $sale->organization_id
                ))
                ->first();
        } else {
            $variant = ProductVariant::query()
                ->with('product')
                ->whereKey($line->product_variant_id)
                ->whereHas('product', fn ($query) => $query->where(
                    'shop_id',
                    $sale->shop_id
                ))
                ->first();

            $product = $variant?->product;
        }

        if (! $product) {
            throw ValidationException::withMessages([
                'sale' => ['Produit ou variante introuvable pour cette vente.'],
            ]);
        }

        $levelQuery = StockLevel::query()
            ->whereHas('location', fn ($query) => $query
                ->where('organization_id', $sale->organization_id)
                ->where('shop_id', $sale->shop_id)
                ->where('status', 'active'))
            ->orderBy('id')
            ->lockForUpdate();

        if ($hasProduct) {
            $levelQuery->where('product_id', $product->id);
        } else {
            $levelQuery->where('product_variant_id', $variant->id);
        }

        $level = $levelQuery->first();

        if (! $level || (float) $level->quantity < (float) $line->quantity) {
            throw ValidationException::withMessages([
                'sale' => ['Le stock disponible est insuffisant pour finaliser cette vente.'],
            ]);
        }

        $level->update([
            'quantity' => (float) $level->quantity - (float) $line->quantity,
        ]);

        StockMovement::create([
            'organization_id' => $sale->organization_id,
            'stock_location_id' => $level->stock_location_id,
            'product_id' => $hasProduct ? $product->id : null,
            'product_variant_id' => $hasVariant ? $variant->id : null,
            'type' => 'sale_out',
            'quantity' => $line->quantity,
            'unit_cost' => $hasVariant
                ? $variant->purchase_price
                : $product->purchase_price,
            'reference_type' => 'sale',
            'reference_id' => (string) $sale->id,
            'reason' => 'Finalisation de vente.',
            'created_by' => $sale->cashier_user_id,
            'created_at' => now(),
        ]);
    }

    private function restoreStockMovement(
        Sale $sale,
        StockMovement $movement,
        ?int $cancelledBy
    ): void {
        $hasProduct = $movement->product_id !== null;
        $hasVariant = $movement->product_variant_id !== null;

        if ($hasProduct === $hasVariant) {
            throw ValidationException::withMessages([
                'sale' => ['Le mouvement de stock de la vente est invalide.'],
            ]);
        }

        $levelQuery = StockLevel::query()
            ->where('stock_location_id', $movement->stock_location_id)
            ->whereHas('location', fn ($query) => $query
                ->where('organization_id', $sale->organization_id)
                ->where('shop_id', $sale->shop_id))
            ->lockForUpdate();

        if ($hasProduct) {
            $levelQuery->where('product_id', $movement->product_id);
        } else {
            $levelQuery->where('product_variant_id', $movement->product_variant_id);
        }

        $level = $levelQuery->first();

        if (! $level) {
            throw ValidationException::withMessages([
                'sale' => ['Niveau de stock introuvable pour annuler cette vente.'],
            ]);
        }

        $level->update([
            'quantity' => (float) $level->quantity + (float) $movement->quantity,
        ]);

        StockMovement::create([
            'organization_id' => $sale->organization_id,
            'stock_location_id' => $movement->stock_location_id,
            'product_id' => $movement->product_id,
            'product_variant_id' => $movement->product_variant_id,
            'type' => 'sale_cancel_in',
            'quantity' => $movement->quantity,
            'unit_cost' => $movement->unit_cost,
            'reference_type' => 'sale_cancel',
            'reference_id' => (string) $sale->id,
            'reason' => 'Annulation de vente.',
            'created_by' => $cancelledBy ?? $sale->cashier_user_id,
            'created_at' => now(),
        ]);
    }

    /**
     * Vérifie qu'une vente peut encore être modifiée.
     */
    public function ensureEditable(Sale $sale): void
    {
        if ($sale->finalized_at !== null || $sale->status === 'finalized') {
            throw ValidationException::withMessages([
                'sale' => [
                    'Une vente finalisée ne peut plus être modifiée.',
                ],
            ]);
        }
    }
}
