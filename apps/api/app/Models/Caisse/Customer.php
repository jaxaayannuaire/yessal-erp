<?php

namespace App\Models\Caisse;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    protected $fillable = ['shop_id','name','phone','email','address','credit_enabled','status'];
    protected $casts = ['credit_enabled'=>'boolean'];

    public function shop(): BelongsTo { return $this->belongsTo(Shop::class); }
    public function sales(): HasMany { return $this->hasMany(Sale::class); }
    public function credits(): HasMany { return $this->hasMany(CustomerCredit::class); }
}
