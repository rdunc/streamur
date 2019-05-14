<?php

namespace App\Http\Middleware;

use Closure;

class PermissionMiddleware
{
    /**
     * Run the request filter.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $permission
     * @return mixed
     */
    public function handle($request, Closure $next, ...$permission)
    {
        if (!$request->user()->can($permission, session('site_id')))
        {
            abort(403);
        }

        return $next($request);
    }

}