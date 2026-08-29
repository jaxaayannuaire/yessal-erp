<?php

namespace App\Models\Caisse;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = ['shop_id', 'name', 'slug', 'status'];
    public function shop(): BelongsTo { return $this->belongsTo(Shop::class); }
    public function products(): HasMany { return $this->hasMany(Product::class); }
}
