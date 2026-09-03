<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsurePlatformAdmin
{
    public function handle(Request $request, Closure $next): mixed
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Non authentifié.'], 401);
        }

        if (! $user->isPlatformAdmin()) {
            return response()->json(['message' => 'Accès plateforme refusé.'], 403);
        }

        return $next($request);
    }
}
