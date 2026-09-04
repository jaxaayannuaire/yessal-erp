<?php

namespace App\Models\Caisse;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleReturn extends Model
{
    protected $fillable = ['organization_id','sale_id','sale_payment_id','reference_number','reason','amount','refund_method','status','created_by'];
    protected $casts = ['amount'=>'integer'];

    public function sale(): BelongsTo { return $this->belongsTo(Sale::class); }
    public function payment(): BelongsTo { return $this->belongsTo(SalePayment::class, 'sale_payment_id'); }
    public function creator(): BelongsTo { return $this->belongsTo(\App\Models\User::class, 'created_by'); }
}
