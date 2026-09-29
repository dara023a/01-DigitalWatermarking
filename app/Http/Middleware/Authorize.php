<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Authorize
{
    public function handle(Request $request, Closure $next, string $ability, ...$models): Response
    {
        $request->user()->authorize($ability, $models);

        return $next($request);
    }
}
