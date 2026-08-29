<?php

namespace App\Models\Caisse;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SyncEvent extends Model
{
    public $timestamps = false;
    protected $fillable = [
        'organization_id','shop_id','device_id','event_uuid','entity_type','entity_id',
        'action','payload','status','error_code','created_at','processed_at'
    ];
    protected $casts = ['payload'=>'array','event_uuid'=>'string','created_at'=>'datetime','processed_at'=>'datetime'];

    public function device(): BelongsTo { return $this->belongsTo(Device::class); }
    public function shop(): BelongsTo { return $this->belongsTo(Shop::class); }
}
