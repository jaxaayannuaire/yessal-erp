<?php

namespace App\Models\Caisse;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalePayment extends Model
{
    protected $fillable = [
        'sale_id','payment_method','provider','amount','change_amount','status',
        'external_reference','declared_at','confirmed_at'
    ];
    protected $casts = ['amount'=>'integer','change_amount'=>'integer','declared_at'=>'datetime','confirmed_at'=>'datetime'];

    public function sale(): BelongsTo { return $this->belongsTo(Sale::class); }
    public function refunds(): HasMany { return $this->hasMany(SaleReturn::class, 'sale_payment_id'); }
}
