<?php

namespace App\Models\Caisse;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    public $timestamps = false;
    protected $fillable = [
        'organization_id','stock_location_id','product_id','product_variant_id',
        'type','quantity','unit_cost','reference_type','reference_id','reason','created_by','created_at'
    ];
    protected $casts = ['quantity'=>'decimal:3','unit_cost'=>'integer','created_at'=>'datetime'];

    public function location(): BelongsTo { return $this->belongsTo(StockLocation::class, 'stock_location_id'); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function variant(): BelongsTo { return $this->belongsTo(ProductVariant::class, 'product_variant_id'); }
    public function creator(): BelongsTo { return $this->belongsTo(\App\Models\User::class, 'created_by'); }
}
