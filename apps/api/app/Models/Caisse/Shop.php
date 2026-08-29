<?php

namespace App\Models\Caisse;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shop extends Model
{
    protected $fillable = ['organization_id', 'name', 'code', 'address', 'phone', 'status'];

    public function organization(): BelongsTo { return $this->belongsTo(\App\Models\Organization::class); }
    public function registerProfiles(): HasMany { return $this->hasMany(RegisterProfile::class); }
    public function terminals(): HasMany { return $this->hasMany(Terminal::class); }
    public function devices(): HasMany { return $this->hasMany(Device::class); }
    public function categories(): HasMany { return $this->hasMany(Category::class); }
    public function products(): HasMany { return $this->hasMany(Product::class); }
    public function customers(): HasMany { return $this->hasMany(Customer::class); }
    public function stockLocations(): HasMany { return $this->hasMany(StockLocation::class); }
}
