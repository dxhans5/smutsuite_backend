<?php

// app/Http/Middleware/RequireActiveIdentity.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RequireActiveIdentity
{
    public function handle(Request $request, Closure $next)
    {
        abort_unless($request->currentIdentity(), 403, 'No active identity selected.');
        return $next($request);
    }
}
