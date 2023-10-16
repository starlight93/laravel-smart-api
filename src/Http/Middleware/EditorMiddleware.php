<?php

namespace Starlight93\LaravelSmartApi\Http\Middleware;
use Illuminate\Support\Str;

use Closure;

class EditorMiddleware
{
    public function handle($request, Closure $next)
    {
        if(Str::startsWith($request->path(), ['laradev/connect','laradev/assets', 'laradev/schema','laradev/activities','laradev/activities'])){
            return $next($request);
        }

        $ori = ($request->header('sec-fetch-site')??'same-site')=='same-site';
        if ( !($devToken=$request->header('developer-token')) && !$ori && !$request->header('laradev') || $request->header('laradev')!=config('editor.password') ) {
            return response()->json(['status'=>'unauthorized'], 401);
        }
        $frontenders = explode(",", config('editor.frontend_devs'));
        $backenders = explode(",", config('editor.backend_devs'));
        $owners = explode(",", config('editor.owners'));
        if( !$ori && !in_array($devToken, $frontenders) && !in_array($devToken, $backenders) && !in_array($devToken, $owners) ){
            return response()->json(['status'=>'unauthorized'], 401);
        }

        config(['developer'=> $devToken]);
        if( in_array($devToken, $frontenders) ){
            config(['devrole'=> 'frontend']);
        }elseif( in_array($devToken, $backenders) ){
            config(['devrole'=> 'backend']);
        }elseif( $ori || in_array($devToken, $owners) ){
            config(['devrole'=> 'owner']);
        }
        
        return $next($request);
    }
}
