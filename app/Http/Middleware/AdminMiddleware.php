<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @spec-link [[rule_admin_access_restriction]] */
        if ($request->user() && $request->user()->isAdmin()) {
            return $next($request);
        }

        abort(403, 'Unauthorized Access - Administrative Privileges Required.');
    }
}
