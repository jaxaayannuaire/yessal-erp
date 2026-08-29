<?php

namespace App\Models\Caisse;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'shop_id','category_id','name','sku','barcode','unit',
        'purchase_price','sale_price','tax_rate','status'
    ];

    protected $casts = [
        'purchase_price' => 'integer',
        'sale_price' => 'integer',
        'tax_rate' => 'decimal:4',
    ];

    public function shop(): BelongsTo { return $this->belongsTo(Shop::class); }
    public function category(): BelongsTo { return $this->belongsTo(Category::class); }
    public function variants(): HasMany { return $this->hasMany(ProductVariant::class); }
    public function stockLevels(): HasMany { return $this->hasMany(StockLevel::class); }
    public function stockMovements(): HasMany { return $this->hasMany(StockMovement::class); }
}
