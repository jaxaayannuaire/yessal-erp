<?php

namespace App\Models\Caisse;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleReturn extends Model
{
    protected $fillable = ['organization_id','sale_id','reference_number','reason','amount','refund_method','status','created_by'];
    protected $casts = ['amount'=>'integer'];

    public function sale(): BelongsTo { return $this->belongsTo(Sale::class); }
    public function creator(): BelongsTo { return $this->belongsTo(\App\Models\User::class, 'created_by'); }
}
