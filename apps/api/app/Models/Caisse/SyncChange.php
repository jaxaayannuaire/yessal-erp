<?php

namespace App\Models\Caisse;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SyncChange extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'organization_id',
        'entity_type',
        'entity_id',
        'operation',
        'payload',
        'occurred_at',
        'source_device_id',
    ];

    protected $casts = [
        'payload' => 'array',
        'occurred_at' => 'datetime',
    ];

    public function sourceDevice(): BelongsTo
    {
        return $this->belongsTo(Device::class, 'source_device_id');
    }
}
