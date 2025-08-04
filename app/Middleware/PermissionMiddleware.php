<?php

namespace App\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PermissionMiddleware
{
    public function handle(Request $request, Closure $next, ...$permissions): Response
    {
        $user = $request->user();

        if (! $user || ! $user->permissions()->whereIn('name', $permissions)->exists()) {
            abort(403, __('auth.unauthorized'));
        }

        return $next($request);
    }
}
