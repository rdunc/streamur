<?php

namespace App\Http\Middleware;

use Closure;

class RoleMiddleware
{
    /**
     * Run the request filter.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $role
     * @return mixed
     */
    public function handle($request, Closure $next, ...$role)
    {
        if ($request->user()->hasRole('developer'))
        {
            return $next($request);
        }
        if (!$request->user()->hasRole($role, session('site_id')))
        {
            abort(403);
        }

        return $next($request);
    }

}