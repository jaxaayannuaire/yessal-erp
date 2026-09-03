<?php

namespace App\Http\Middleware;

use App\Services\Rbac\PermissionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function handle(
        Request $request,
        Closure $next,
        string $permission
    ): Response {
        $user = $request->user();

        abort_unless($user, 401);

        $organization = $request->attributes->get('currentOrganization');

        abort_unless($organization, 403);

        $permissionService = app(PermissionService::class);

        abort_unless(
            $permissionService->hasPermission(
                $user,
                $organization,
                $permission
            ),
            403
        );

        return $next($request);
    }
}