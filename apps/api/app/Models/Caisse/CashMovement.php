<?php

namespace App\Models\Caisse;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashMovement extends Model
{
    public $timestamps = false;
    protected $fillable = ['organization_id','cash_session_id','type','amount','reason','reference','created_by','created_at'];
    protected $casts = ['amount'=>'integer','created_at'=>'datetime'];

    public function session(): BelongsTo { return $this->belongsTo(CashSession::class, 'cash_session_id'); }
    public function creator(): BelongsTo { return $this->belongsTo(\App\Models\User::class, 'created_by'); }
}
