<?php

namespace App\Models\Caisse;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    public $timestamps = false;
    protected $fillable = [
        'organization_id','shop_id','user_id','device_id','action','entity_type',
        'entity_id','before_payload','after_payload','reason','ip_address','created_at'
    ];
    protected $casts = ['before_payload'=>'array','after_payload'=>'array','created_at'=>'datetime'];

    public function shop(): BelongsTo { return $this->belongsTo(Shop::class); }
    public function user(): BelongsTo { return $this->belongsTo(\App\Models\User::class); }
    public function device(): BelongsTo { return $this->belongsTo(Device::class); }
}
