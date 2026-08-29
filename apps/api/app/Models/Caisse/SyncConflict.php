<?php

namespace App\Models\Caisse;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SyncConflict extends Model
{
    protected $fillable = [
        'organization_id','device_id','entity_type','entity_id','local_version',
        'server_version','conflict_type','local_payload','server_payload',
        'resolution','resolved_by','resolved_at'
    ];
    protected $casts = ['local_payload'=>'array','server_payload'=>'array','resolved_at'=>'datetime'];

    public function device(): BelongsTo { return $this->belongsTo(Device::class); }
    public function resolver(): BelongsTo { return $this->belongsTo(\App\Models\User::class, 'resolved_by'); }
}
