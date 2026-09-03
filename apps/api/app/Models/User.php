<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'is_platform_admin',
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
            'is_platform_admin' => 'boolean',
        ];
    }

    public function isPlatformAdmin(): bool
    {
        return $this->is_platform_admin;
    }

    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    public function hasOrganizationRole(
        Organization $organization,
        string $role
    ): bool {
        return $this->organizations()
            ->whereKey($organization->id)
            ->wherePivot('role', $role)
            ->exists();
    }

    public function hasAnyOrganizationRole(
        Organization $organization,
        array $roles
    ): bool {
        return $this->organizations()
            ->whereKey($organization->id)
            ->wherePivotIn('role', $roles)
            ->exists();
    }

    public function organizationRole(
        Organization $organization
    ): ?string {
        $membership = $this->organizations()
            ->whereKey($organization->id)
            ->first();

        return $membership?->pivot?->role;
    }

    public function belongsToOrganization(
        Organization $organization
    ): bool {
        return $this->organizations()
            ->whereKey($organization->id)
            ->exists();
    }

    public function organizationRoleAssignments(): HasMany
    {
        return $this->hasMany(
            OrganizationUserRole::class,
            'user_id'
        );
    }

    public function rolesForOrganization(
        Organization $organization
    ): BelongsToMany {
        return $this->belongsToMany(
            Role::class,
            'organization_user_roles',
            'user_id',
            'role_id'
        )
            ->wherePivot('organization_id', $organization->id)
            ->withPivot('organization_id')
            ->withTimestamps();
    }

    /**
     * Permissions individuelles de l'utilisateur
     * dans une organisation.
     *
     * Le champ pivot "granted" permet :
     * true  = autoriser explicitement
     * false = refuser explicitement
     */
    public function permissionsForOrganization(
        Organization $organization
    ): BelongsToMany {
        return $this->belongsToMany(
            Permission::class,
            'user_permissions',
            'user_id',
            'permission_id'
        )
            ->wherePivot('organization_id', $organization->id)
            ->withPivot([
                'organization_id',
                'granted',
            ])
            ->withTimestamps();
    }
}
