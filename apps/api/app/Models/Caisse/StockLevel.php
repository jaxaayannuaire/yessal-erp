<?php

namespace App\Models\Caisse;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockLevel extends Model
{
    protected $fillable = ['stock_location_id','product_id','product_variant_id','quantity','reserved_quantity'];
    protected $casts = ['quantity'=>'decimal:3','reserved_quantity'=>'decimal:3'];

    public function location(): BelongsTo { return $this->belongsTo(StockLocation::class, 'stock_location_id'); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function variant(): BelongsTo { return $this->belongsTo(ProductVariant::class, 'product_variant_id'); }
}
