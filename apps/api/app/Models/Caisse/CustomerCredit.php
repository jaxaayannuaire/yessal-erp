<?php

namespace App\Models\Caisse;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerCredit extends Model
{
    protected $fillable = ['organization_id','customer_id','sale_id','original_amount','paid_amount','remaining_amount','due_date','status'];
    protected $casts = ['original_amount'=>'integer','paid_amount'=>'integer','remaining_amount'=>'integer','due_date'=>'date'];

    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function sale(): BelongsTo { return $this->belongsTo(Sale::class); }
}
