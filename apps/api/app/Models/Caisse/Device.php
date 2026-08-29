<?php

namespace App\Models\Caisse;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Device extends Model
{
    protected $fillable = [
        'organization_id','shop_id','terminal_id','device_uuid','name','platform',
        'app_version','status','last_seen_at','last_sync_at','paired_at','revoked_at'
    ];

    protected $casts = [
        'device_uuid' => 'string',
        'last_seen_at' => 'datetime',
        'last_sync_at' => 'datetime',
        'paired_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    public function organization(): BelongsTo { return $this->belongsTo(\App\Models\Organization::class); }
    public function shop(): BelongsTo { return $this->belongsTo(Shop::class); }
    public function terminal(): BelongsTo { return $this->belongsTo(Terminal::class); }
    public function syncEvents(): HasMany { return $this->hasMany(SyncEvent::class); }
    public function syncConflicts(): HasMany { return $this->hasMany(SyncConflict::class); }
}
