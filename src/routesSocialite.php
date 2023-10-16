<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/auth/redirect/{driver}', function (Request $req, string $driver) {
    return Socialite::driver($driver)->stateless()->redirect();
});
 
Route::get('/auth/callback/{driver}', function ( Request $req, string $driver ) {
    $user = Socialite::driver ($driver )->stateless()->user();
    return (array)$user;
});