<?php

namespace Database\Factories\Caisse;

use App\Models\Caisse\Product;
use App\Models\Caisse\Sale;
use App\Models\Caisse\SaleLine;
use Illuminate\Database\Eloquent\Factories\Factory;

class SaleLineFactory extends Factory
{
    protected $model = SaleLine::class;

    public function definition(): array
    {
        $product = Product::factory()->create();
        $unitPrice = (int) $product->sale_price;
        $quantity = 1;
        $total = $unitPrice * $quantity;

        return [
            'sale_id' => Sale::factory(),
            'product_id' => $product->id,
            'product_variant_id' => null,
            'product_name_snapshot' => $product->name,
            'sku_snapshot' => $product->sku,
            'barcode_snapshot' => $product->barcode,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'total_amount' => $total,
        ];
    }
}
