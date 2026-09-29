<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class RequirePassword
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() || ! Hash::check($request->password, $request->user()->password)) {
            return back()->withErrors(['password' => 'Password is incorrect.']);
        }

        return $next($request);
    }
}
