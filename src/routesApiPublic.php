<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/{modelname}/{function}', function(Request $req,$modelname,$function){
    $modelCandidate = "\App\Models\CustomModels\\$modelname";
    if( !class_exists( $modelCandidate ) ){
        return response()->json("Resource [$modelname] does not exist",404);
    }
    $function = "public_".$function;
    $model = new $modelCandidate;
    if( !method_exists( $model, $function ) ){
        return response()->json([
            'message' => "function [$function] in Resource [$modelname] does not exist"
        ],404);
    }
    $result = $model->$function($req);
    return $result;
});

Route::post('/{modelname}/{function}', function(Request $req,$modelname,$function){
    $modelCandidate = "\App\Models\CustomModels\\$modelname";
    if( !class_exists( $modelCandidate ) ){
        return response()->json([
            'message' => "Resource [$modelname] does not exist"
        ],400);
    }
    $function = "public_".$function;
    $model = new $modelCandidate;
    if( !method_exists( $model, $function ) ){
        return response()->json([
            'message' => "function [$function] in Resource [$modelname] does not exist"
        ],400);
    }
    $result = $model->$function($req);
    return $result;
});