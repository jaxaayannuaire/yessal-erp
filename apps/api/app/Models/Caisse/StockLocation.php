<?php

namespace App\Models\Caisse;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockLocation extends Model
{
    protected $fillable = ['shop_id','name','type','status'];
    public function shop(): BelongsTo { return $this->belongsTo(Shop::class); }
    public function levels(): HasMany { return $this->hasMany(StockLevel::class); }
    public function movements(): HasMany { return $this->hasMany(StockMovement::class); }
}
