<?php

namespace App\Http\Middleware;

use Closure;
use Tymon\JWTAuth\Http\Middleware\Authenticate;

class AuthenticateActiveUser extends Authenticate
{
    public function handle($request, Closure $next)
    {
        return parent::handle($request, function ($request) use ($next) {
            if (!$request->user() || (int) $request->user()->active !== 1) {
                return response()->json(['error' => 'unauthenticated'], 401);
            }
            return $next($request);
        });
    }
}
