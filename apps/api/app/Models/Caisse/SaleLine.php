<?php

namespace App\Models\Caisse;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleLine extends Model
{
    protected $fillable = [
        'sale_id','product_id','product_variant_id','product_name_snapshot','sku_snapshot',
        'barcode_snapshot','quantity','unit_price','discount_amount','tax_amount','total_amount'
    ];
    protected $casts = ['quantity'=>'decimal:3','unit_price'=>'integer','discount_amount'=>'integer','tax_amount'=>'integer','total_amount'=>'integer'];

    public function sale(): BelongsTo { return $this->belongsTo(Sale::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function variant(): BelongsTo { return $this->belongsTo(ProductVariant::class, 'product_variant_id'); }
}
