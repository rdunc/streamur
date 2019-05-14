<?php

namespace App\Http\Middleware;

use Closure;

class AdminMiddleware
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
        if (!$request->user()->hasRole($role, null))
        {
            abort(403);
        }

        return $next($request);
    }

}