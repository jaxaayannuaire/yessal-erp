<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Module extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function plans(): BelongsToMany
    {
        return $this->belongsToMany(
            Plan::class,
            'module_plan'
        )->withTimestamps();
    }

    public function entitlements(): BelongsToMany
    {
        return $this->belongsToMany(
            Entitlement::class,
            'module_entitlement'
        )->withTimestamps();
    }
}