<?php

namespace App\Models\Caisse;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RegisterProfile extends Model
{
    protected $fillable = ['shop_id', 'name', 'default_customer_id', 'settings', 'status'];
    protected $casts = ['settings' => 'array'];

    public function shop(): BelongsTo { return $this->belongsTo(Shop::class); }
    public function defaultCustomer(): BelongsTo { return $this->belongsTo(Customer::class, 'default_customer_id'); }
    public function terminals(): HasMany { return $this->hasMany(Terminal::class); }
}
