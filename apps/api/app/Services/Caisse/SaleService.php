<?php

namespace App\Services\Caisse;

use App\Models\Caisse\Product;
use App\Models\Caisse\Sale;
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

                $product = null;

                if (!empty($line['product_id'])) {
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

                $productName = $line['product_name_snapshot']
                    ?? $product?->name
                    ?? 'Produit';

                $productSku = $line['sku_snapshot']
                    ?? $product?->sku;

                $productBarcode = $line['barcode_snapshot']
                    ?? $product?->barcode;

                $sale->lines()->create([
                    'product_id' => $product?->id ?? $line['product_id'] ?? null,
                    'product_variant_id' => $line['product_variant_id'] ?? null,
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
                ->lockForUpdate()
                ->findOrFail($sale->id);

            if ($sale->finalized_at !== null || $sale->status === 'finalized') {
                throw ValidationException::withMessages([
                    'sale' => ['Cette vente est déjà finalisée.'],
                ]);
            }

            if ($sale->status !== 'paid' || (int) $sale->due_amount !== 0) {
                throw ValidationException::withMessages([
                    'sale' => [
                        'Seule une vente entièrement payée peut être finalisée.',
                    ],
                ]);
            }

            $sale->update([
                'status' => 'finalized',
                'finalized_at' => now(),
            ]);

            return $sale->refresh();
        });
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