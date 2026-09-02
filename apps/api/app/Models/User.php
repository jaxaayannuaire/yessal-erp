<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * Vérifie si l'utilisateur possède un rôle précis
     * dans une organisation.
     */
    public function hasOrganizationRole(
        Organization $organization,
        string $role
    ): bool {
        return $this->organizations()
            ->whereKey($organization->id)
            ->wherePivot('role', $role)
            ->exists();
    }

    /**
     * Vérifie si l'utilisateur possède au moins
     * un des rôles indiqués dans une organisation.
     */
    public function hasAnyOrganizationRole(
        Organization $organization,
        array $roles
    ): bool {
        return $this->organizations()
            ->whereKey($organization->id)
            ->wherePivotIn('role', $roles)
            ->exists();
    }

    /**
     * Retourne le rôle de l'utilisateur dans
     * une organisation, ou null s'il n'en est pas membre.
     */
    public function organizationRole(
        Organization $organization
    ): ?string {
        $membership = $this->organizations()
            ->whereKey($organization->id)
            ->first();

        return $membership?->pivot?->role;
    }

    /**
     * Vérifie si l'utilisateur est membre
     * d'une organisation.
     */
    public function belongsToOrganization(
        Organization $organization
    ): bool {
        return $this->organizations()
            ->whereKey($organization->id)
            ->exists();
    }
}