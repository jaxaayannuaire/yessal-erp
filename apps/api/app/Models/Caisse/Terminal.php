<?php

namespace App\Models\Caisse;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Terminal extends Model
{
    protected $fillable = ['shop_id', 'register_profile_id', 'name', 'code', 'status'];

    public function shop(): BelongsTo { return $this->belongsTo(Shop::class); }
    public function registerProfile(): BelongsTo { return $this->belongsTo(RegisterProfile::class); }
    public function devices(): HasMany { return $this->hasMany(Device::class); }
    public function cashSessions(): HasMany { return $this->hasMany(CashSession::class); }
    public function openCashSession(): HasOne
    {
        return $this->hasOne(CashSession::class)->where('status', 'open');
    }
}
