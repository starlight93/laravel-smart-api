<?php

namespace Starlight93\LaravelSmartApi\Http\Middleware;
use Laravel\Socialite\Facades\Socialite;
use Starlight93\LaravelSmartApi\Models\User;
use Illuminate\Support\Str;
use Closure;

class SocialiteMiddleware
{
    public function handle($request, Closure $next)
    {
        $token = $request->header('authorization');//'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOi8vbG9jYWxob3N0OjgxL2xvZ2luIiwiaWF0IjoxNjk1OTc2OTA1LCJleHAiOjE2OTY1NzY5MDUsIm5iZiI6MTY5NTk3NjkwNSwianRpIjoidkVueDRTWkJkOW9oa3NoaiIsInN1YiI6IjEiLCJwcnYiOiI1MWMzOTQzMzNiMDI1M2FhZDEyYzg0ZWQzMWExNzIzYmYwMThkZDYwIn0.PDX6_id5WGC_0_Hw0bBHbahewCpuXPg_ojmE65Ii-ac';
        if( !$token ) abort( 401 );
        
        try{
            if( !Str::startsWith( Str::lower($token), 'bearer' ) ){
                $userObj = Socialite::driver('google')
                    ->stateless()
                    ->userFromToken( $token );
                $user = new User;
                foreach($userObj->user as $key => $value){
                    $user->$key = $value;
                }
                if(!$user) abort(401);
                $user->provider = 'google';
            }else{
                $user = auth()->setToken( Str::replace(['bearer ','Bearer '],['',''],$token) )->user();
                if(!$user) abort(401);
                $user->provider = 'local';
            }
            auth()->login($user);
        }catch(\Exception $err){
            abort(401, json_encode(['message'=>'Unauthenticated']));
        }
        $response = $next($request);
        return $response;
    }
}
