<?php

namespace Starlight93\LaravelSmartApi\Http\Middleware;

use Starlight93\LaravelSmartApi\Helpers\ApiFunc as Api;
use Closure;

/**
 * Autentikasi request memakai guard aktif package (jwt | sanctum | passport).
 * Guard resolve bearer token dari header Authorization sendiri.
 */
class AuthenticateApi
{
    public function handle($request, Closure $next)
    {
        try{
            $user = Api::guard()->user();
        }catch(\Exception $err){
            $user = null;
        }

        if( !$user ) abort(401, json_encode(['message'=>'Unauthenticated']));

        // Jadikan guard package default request ini supaya Auth::user() di
        // ApiController/Logger/Cryptor menunjuk user yang sama.
        auth()->shouldUse( Api::authGuard() );

        return $next($request);
    }
}
