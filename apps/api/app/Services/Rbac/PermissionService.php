<?php

namespace App\Services\Rbac;

use App\Models\Organization;
use App\Models\User;

class PermissionService
{
    public function hasPermission(
        User $user,
        Organization $organization,
        string $permission
    ): bool {
        // Le propriétaire de l'organisation dispose de toutes les permissions.
        if ($user->hasOrganizationRole($organization, 'owner')) {
            return true;
        }

        // Une permission individuelle explicite est prioritaire.
        $individual = $user->permissionsForOrganization($organization)
            ->where('slug', $permission)
            ->first();

        if ($individual) {
            return (bool) $individual->pivot->granted;
        }

        // Sinon, vérifier les permissions héritées des rôles RBAC.
        return $user->rolesForOrganization($organization)
            ->whereHas(
                'permissions',
                fn ($query) => $query->where('slug', $permission)
            )
            ->exists();
    }

    public function effectivePermissions(
        User $user,
        Organization $organization
    ): array {
        // Owner : toutes les permissions actives.
        if ($user->hasOrganizationRole($organization, 'owner')) {
            return \App\Models\Permission::query()
                ->where('is_active', true)
                ->pluck('slug')
                ->values()
                ->all();
        }

        $rolePermissions = $user->rolesForOrganization($organization)
            ->with('permissions')
            ->get()
            ->flatMap(fn ($role) => $role->permissions->pluck('slug'))
            ->unique()
            ->values()
            ->all();

        $individualPermissions = $user->permissionsForOrganization($organization)
            ->get()
            ->keyBy('slug');

        $permissions = collect($rolePermissions);

        foreach ($individualPermissions as $permission) {
            if ($permission->pivot->granted) {
                $permissions->push($permission->slug);
            } else {
                $permissions = $permissions->reject(
                    fn ($slug) => $slug === $permission->slug
                );
            }
        }

        return $permissions
            ->unique()
            ->values()
            ->all();
    }
}