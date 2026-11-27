<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureNewsroomStaff
{
    /**
     * Allow only active newsroom staff / administrators to access /admin routes.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->canAccessAdmin()) {
            abort(403, 'Access denied. The newsroom dashboard is restricted to authorized Kisii TV staff.');
        }

        return $next($request);
    }
}
