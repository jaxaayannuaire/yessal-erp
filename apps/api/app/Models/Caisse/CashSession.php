<?php

namespace App\Models\Caisse;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashSession extends Model
{
    protected $fillable = [
        'organization_id','shop_id','terminal_id','device_id','opened_by','closed_by',
        'opening_amount','expected_amount','counted_amount','variance_amount',
        'variance_reason','status','opened_at','closed_at'
    ];

    protected $casts = [
        'opening_amount'=>'integer','expected_amount'=>'integer','counted_amount'=>'integer',
        'variance_amount'=>'integer','opened_at'=>'datetime','closed_at'=>'datetime'
    ];

    public function terminal(): BelongsTo { return $this->belongsTo(Terminal::class); }
    public function shop(): BelongsTo { return $this->belongsTo(Shop::class); }
    public function device(): BelongsTo { return $this->belongsTo(Device::class); }
    public function opener(): BelongsTo { return $this->belongsTo(\App\Models\User::class, 'opened_by'); }
    public function closer(): BelongsTo { return $this->belongsTo(\App\Models\User::class, 'closed_by'); }
    public function movements(): HasMany { return $this->hasMany(CashMovement::class); }
    public function sales(): HasMany { return $this->hasMany(Sale::class); }
}
