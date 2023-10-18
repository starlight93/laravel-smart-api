<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;

Route::post('/login', "UserController@login");

Route::group([
        'middleware' => [\Starlight93\LaravelSmartApi\Http\Middleware\SocialiteMiddleware::class],
    ], function () {
        Route::get('/logout', "UserController@logout");
        Route::get('/user', "UserController@user");
        Route::post('/unlock-screen', "UserController@unlockScreen");
        Route::post('/change-password', "UserController@changePassword");
        Route::post('/upload', function(Request $req){
            if( !$req->hasFile('file') ) abort(422, '`file` payload must exist');
            $userId = auth()->check()? auth()->id():0;
            $extensionArr = explode( ".", $req->file->getClientOriginalName() );
            $key = 'temp-'.Carbon::now()->format('his').uniqid().$userId.".".end( $extensionArr );
            $path =  $req->file->getRealPath();
            $blob = base64_encode( File::get( $path ) );
            Cache::put( $key, $blob, 3600);
            return $key;
        });

        Route::group([
            'prefix'=>config('api.route_prefix'),
        ],function(){
            Route::get('/{modelname}',['as'=>'read_list', 'uses'=> 'ApiController@router']);         //LIST PARENTS
            Route::post('/{modelname}', 'ApiController@router');        //CREATE PARENT-ALL-DETAILS
            Route::post('/{modelname}/{id}', 'ApiController@router');        //CREATE PARENT-ALL-DETAILS

            Route::get('/{modelname}/{id}',['as'=>'read_id', 'uses'=>'ApiController@router']);    //GET SINGLE PARENT-ALL-DETAILS
            Route::put('/{modelname}/{id}', 'ApiController@router');    //UPDATE SINGLE PARENT-ALL-DETAILS
            Route::patch('/{modelname}/{id}', 'ApiController@router');  //UPDATE SINGLE PARENT-ALL-DETAILS
            Route::delete('/{modelname}/{id}', 'ApiController@router'); //DELETE SINGLE PARENT-ALL-DETAILS

            Route::get('/{modelname}/{id}/{detailmodelname}',['as'=>'read_list_detail', 'uses'=> 'ApiController@router']);    //LIST PARENT DETAIL TERTENTU
            Route::get('/{modelname}/{id}/{detailmodelname}/{detailid}',['as'=>'read_id_detail', 'uses'=>'ApiController@router']);   //CREATE DETAIL TERTENTU DARI PARENT ID
            Route::get('/{modelname}/{id}/{detailmodelname}/{detailid}/{subdetailmodelname}',['as'=>'read_list_sub_detail', 'uses'=>'ApiController@router']);   //CREATE DETAIL TERTENTU DARI PARENT ID
            Route::get('/{modelname}/{id}/{detailmodelname}/{detailid}/{subdetailmodelname}/{subdetailid}',['as'=>'read_id_sub_detail', 'uses'=>'ApiController@router']);   //CREATE DETAIL TERTENTU DARI PARENT ID
                    
            //Route::put('/{modelname}/{id}/{detailmodelname}', 'ApiController@router');    //UPDATE DETAIL TERTENTU DARI PARENT ID
            //Route::patch('/{modelname}/{id}/{detailmodelname}', 'ApiController@router');  //UPDATE DETAIL TERTENTU DARI PARENT ID
            //Route::delete('/{modelname}/{id}/{detailmodelname}', 'ApiController@router'); //DELETE DETAIL TERTENTU DARI PARENT ID

            //Route::get('/{modelname}/{id}/{detailmodelname}/{iddetailmodelname}', 'ApiController@level3');
            //Route::put('/{modelname}/{id}/{detailmodelname}/{iddetailmodelname}', 'ApiController@level3');
            //Route::patch('/{modelname}/{id}/{detailmodelname}/{iddetailmodelname}', 'ApiController@level3');
            //Route::delete('/{modelname}/{id}/{detailmodelname}/{iddetailmodelname}', 'ApiController@level3');
        });
});