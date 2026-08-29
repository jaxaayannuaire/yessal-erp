<?php

namespace App\Models\Caisse;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sale extends Model
{
    protected $fillable = [
        'organization_id','shop_id','terminal_id','cash_session_id','device_id',
        'cashier_user_id','seller_user_id','customer_id','local_uuid','receipt_number',
        'status','subtotal','discount_amount','tax_amount','total_amount','paid_amount',
        'due_amount','currency','finalized_at'
    ];

    protected $casts = [
        'local_uuid'=>'string','subtotal'=>'integer','discount_amount'=>'integer',
        'tax_amount'=>'integer','total_amount'=>'integer','paid_amount'=>'integer',
        'due_amount'=>'integer','finalized_at'=>'datetime'
    ];

    public function shop(): BelongsTo { return $this->belongsTo(Shop::class); }
    public function terminal(): BelongsTo { return $this->belongsTo(Terminal::class); }
    public function cashSession(): BelongsTo { return $this->belongsTo(CashSession::class); }
    public function device(): BelongsTo { return $this->belongsTo(Device::class); }
    public function cashier(): BelongsTo { return $this->belongsTo(\App\Models\User::class, 'cashier_user_id'); }
    public function seller(): BelongsTo { return $this->belongsTo(\App\Models\User::class, 'seller_user_id'); }
    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function lines(): HasMany { return $this->hasMany(SaleLine::class); }
    public function payments(): HasMany { return $this->hasMany(SalePayment::class); }
    public function returns(): HasMany { return $this->hasMany(SaleReturn::class); }
    public function credit(): \Illuminate\Database\Eloquent\Relations\HasOne { return $this->hasOne(CustomerCredit::class); }
}
